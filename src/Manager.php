<?php
/**
 * WP Flyout Manager
 *
 * Manages flyout registration, building, trigger generation, and asset management.
 * AJAX handling has been replaced by the REST API (RestApi.php).
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     4.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use ArrayPress\RegisterFlyouts\Components\FormField;
use ArrayPress\RegisterFlyouts\Core\Flyout;
use ArrayPress\RegisterFlyouts\Parts\ActionBar;

/**
 * Class Manager
 *
 * Orchestrates flyout registration, building, and asset management.
 * All data transport is handled by RestApi via the REST API.
 *
 * @since 1.0.0
 */
class Manager {

	// =========================================================================
	// PROPERTIES
	// =========================================================================

	/**
	 * Unique prefix for this manager instance.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $prefix;

	/**
	 * Registered flyout configurations.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $flyouts = [];

	/**
	 * Admin pages where assets should load.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all flyouts.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $components = [];

	/**
	 * Whether assets have been enqueued.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	// =========================================================================
	// CONSTRUCTOR & INITIALIZATION
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param string $prefix Unique prefix for this manager instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Register this manager in the global registry.
		Registry::register( $this->prefix, $this );

		// Ensure REST routes are registered (safe to call multiple times).
		RestApi::register();

		// Auto-enqueue assets on admin pages.
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	// =========================================================================
	// FLYOUT REGISTRATION
	// =========================================================================

	/**
	 * Register a flyout with declarative configuration.
	 *
	 * @param string $id     Unique flyout identifier.
	 * @param array  $config Flyout configuration array.
	 *
	 * @return self Returns instance for method chaining.
	 * @since 1.0.0
	 */
	public function register_flyout( string $id, array $config ): self {
		$defaults = [
			'title'       => '',
			'subtitle'    => '',
			'size'        => 'medium',
			'tabs'        => [],
			'fields'      => [],
			'actions'     => [],
			'capability'  => 'manage_options',
			'admin_pages' => [],
			'load'        => null,
			'validate'    => null,
			'save'        => null,
			'delete'      => null,
		];

		$config = wp_parse_args( $config, $defaults );

		// Apply filters for extensibility.
		$config = apply_filters( 'wp_flyout_register_config', $config, $id, $this->prefix );
		$config = apply_filters( "wp_flyout_{$this->prefix}_{$id}_config", $config );

		// Auto-detect required components.
		$this->detect_components( $config );

		// Track admin pages for asset loading.
		if ( ! empty( $config['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $config['admin_pages'] )
			);
		}

		// Store flyout configuration.
		$this->flyouts[ $id ] = $config;

		return $this;
	}

	// =========================================================================
	// FLYOUT BUILDING
	// =========================================================================

	/**
	 * Build flyout interface.
	 *
	 * @param array  $config Flyout configuration.
	 * @param mixed  $data   Data for field population.
	 * @param string $id     Record ID if editing.
	 *
	 * @return Flyout Configured flyout instance.
	 * @since 1.0.0
	 */
	public function build_flyout( array $config, $data, $id = null ): Flyout {
		$flyout_instance_id = $config['id'] ?? uniqid() . '_' . ( $id ?: 'new' );
		$flyout             = new Flyout( $flyout_instance_id );

		$flyout->set_title( $config['title'] );
		$flyout->set_subtitle( $config['subtitle'] );
		$flyout->set_size( $config['size'] );

		$flyout = apply_filters( 'wp_flyout_build_flyout', $flyout, $config, $data, $this->prefix );

		if ( ! empty( $config['tabs'] ) ) {
			$this->build_tab_interface( $flyout, $config['tabs'], $config['fields'], $data );
		} else {
			$content = $this->render_fields( $config['fields'], $data );
			$flyout->add_content( '', $content );
		}

		if ( $id ) {
			$tab_key = ! empty( $config['tabs'] ) ? array_key_first( $config['tabs'] ) : '';
			$flyout->add_content( $tab_key, sprintf(
				'<input type="hidden" name="id" value="%s">',
				esc_attr( $id )
			) );
		}

		$actions = ! empty( $config['actions'] )
			? $config['actions']
			: $this->get_default_actions( $config );

		if ( ! empty( $actions ) ) {
			$flyout->set_footer( $this->render_actions( $actions ) );
		}

		return $flyout;
	}

	/**
	 * Build tab interface for flyout.
	 *
	 * @param Flyout $flyout Flyout instance.
	 * @param array  $tabs   Tab configurations.
	 * @param array  $fields All field configurations.
	 * @param mixed  $data   Data for field population.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function build_tab_interface( Flyout $flyout, array $tabs, array $fields, $data ): void {
		$fields_by_tab = [];
		foreach ( $fields as $key => $field ) {
			$tab = $field['tab'] ?? 'default';
			if ( ! isset( $fields_by_tab[ $tab ] ) ) {
				$fields_by_tab[ $tab ] = [];
			}
			$fields_by_tab[ $tab ][ $key ] = $field;
		}

		foreach ( $tabs as $tab_id => $tab_config ) {
			$label    = is_array( $tab_config ) ? $tab_config['label'] : $tab_config;
			$is_first = array_key_first( $tabs ) === $tab_id;

			$flyout->add_tab( $tab_id, $label, $is_first );

			$tab_fields = $fields_by_tab[ $tab_id ] ?? [];
			$content    = $this->render_fields( $tab_fields, $data );
			$flyout->set_tab_content( $tab_id, $content );
		}
	}

	/**
	 * Render action buttons for footer.
	 *
	 * @param array $actions Action button configurations.
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 */
	private function render_actions( array $actions ): string {
		$action_bar = new ActionBar( [ 'actions' => $actions ] );

		return $action_bar->render();
	}

	/**
	 * Get default action buttons based on configuration.
	 *
	 * @param array $config Flyout configuration.
	 *
	 * @return array Default action buttons.
	 * @since 1.0.0
	 */
	private function get_default_actions( array $config ): array {
		$actions = [];

		if ( ! empty( $config['save'] ) ) {
			$actions[] = [
				'text'  => __( 'Save', 'wp-flyout' ),
				'style' => 'primary',
				'class' => 'wp-flyout-save',
			];
		}

		if ( ! empty( $config['delete'] ) ) {
			$actions[] = [
				'text'  => __( 'Delete', 'wp-flyout' ),
				'style' => 'link-delete',
				'class' => 'wp-flyout-delete',
			];
		}

		return $actions;
	}

	// =========================================================================
	// FIELD RENDERING
	// =========================================================================

	/**
	 * Render fields from configuration.
	 *
	 * @param array $fields Field configurations.
	 * @param mixed $data   Data object or array for field population.
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 */
	private function render_fields( array $fields, $data ): string {
		$output = '';

		$fields = apply_filters( 'wp_flyout_before_render_fields', $fields, $data, $this->prefix );

		$normalized_fields = $this->normalize_fields( $fields );

		foreach ( $normalized_fields as $field_key => $field ) {
			// Process conditional dependencies.
			if ( isset( $field['depends'] ) ) {
				$field = $this->process_field_dependencies( $field, $field_key );
			}

			// Apply field-specific filters.
			$field = apply_filters( 'wp_flyout_render_field', $field, $field_key, $data, $this->prefix );
			$field = apply_filters( "wp_flyout_render_field_{$field_key}", $field, $data, $this->prefix );

			// Normalize AJAX fields (search URL, hydration).
			$field = $this->normalize_ajax_fields( $field, $field_key, $data );

			$type = $field['type'] ?? 'text';

			// Use field name for data lookup (field name may differ from field key).
			$data_key = $field['name'] ?? $field_key;

			// Render field based on type.
			if ( Components::is_component( $type ) ) {
				$resolved_data = Components::resolve_data( $type, $data_key, $data );

				foreach ( $resolved_data as $key => $value ) {
					if ( ! isset( $field[ $key ] ) && $value !== null ) {
						$field[ $key ] = $value;
					}
				}

				$component    = Components::create( $type, $field );
				$field_output = $component ? $component->render() : '';
			} else {
				if ( ! isset( $field['value'] ) && $data ) {
					$field['value'] = Components::resolve_value( $data_key, $data );
				}

				$form_field   = new FormField( $field );
				$field_output = $form_field->render();
			}

			$output .= $field_output;
		}

		return apply_filters( 'wp_flyout_after_render_fields', $output, $fields, $data, $this->prefix );
	}

	/**
	 * Normalize field configurations.
	 *
	 * Ensures every field has a 'name' key and uses string keys.
	 *
	 * @param array $fields Field configurations.
	 *
	 * @return array Normalized fields.
	 * @since 1.0.0
	 */
	public function normalize_fields( array $fields ): array {
		$fields = apply_filters( 'wp_flyout_before_normalize_fields', $fields, $this->prefix );

		$normalized = [];

		foreach ( $fields as $field_key => $field ) {
			if ( is_numeric( $field_key ) ) {
				$field_key = $field['name'] ?? 'field_' . $field_key;
			}

			if ( ! isset( $field['name'] ) ) {
				$field['name'] = $field_key;
			}

			$normalized[ $field_key ] = $field;
		}

		return apply_filters( 'wp_flyout_after_normalize_fields', $normalized, $this->prefix );
	}

	/**
	 * Normalize AJAX field configurations for REST API.
	 *
	 * Sets the REST search URL and handles hydration for ajax_select fields.
	 *
	 * @param array  $field     Field configuration.
	 * @param string $field_key Field identifier.
	 * @param mixed  $data      Data source for value resolution.
	 *
	 * @return array Normalized field configuration.
	 * @since 4.0.0
	 */
	private function normalize_ajax_fields( array $field, string $field_key, $data ): array {
		$type = $field['type'] ?? 'text';

		// Convert derivative types to ajax_select with built-in callback.
		switch ( $type ) {
			case 'post':
				$field['type']     = 'ajax_select';
				$field['callback'] = SearchCallbacks::posts(
					$field['post_type'] ?? 'post',
					$field['query_args'] ?? []
				);
				break;

			case 'taxonomy':
				$field['type']     = 'ajax_select';
				$field['callback'] = SearchCallbacks::taxonomy(
					$field['taxonomy'] ?? 'category',
					$field['query_args'] ?? []
				);
				break;

			case 'user':
				$field['type']     = 'ajax_select';
				$field['callback'] = SearchCallbacks::users(
					$field['role'] ?? '',
					$field['query_args'] ?? []
				);
				break;
		}

		if ( $field['type'] !== 'ajax_select' ) {
			return $field;
		}

		$data_key = $field['name'] ?? $field_key;

		// Set the REST search URL for Select2.
		$field['ajax_url']    = rest_url( RestApi::NAMESPACE . '/search' );
		$field['ajax_params'] = [
			'manager'   => $this->prefix,
			'flyout'    => $this->get_flyout_id_for_field( $field_key ),
			'field_key' => $data_key,
		];

		// Resolve value if not already set.
		if ( ! isset( $field['value'] ) && $data ) {
			$field['value'] = Components::resolve_value( $data_key, $data );
		}

		// Hydrate options from callback if we have a value but no options.
		if ( ! empty( $field['value'] ) && empty( $field['options'] ) && ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
			$ids              = is_array( $field['value'] ) ? $field['value'] : [ $field['value'] ];
			$field['options'] = call_user_func( $field['callback'], '', $ids );
		}

		return $field;
	}

	/**
	 * Process field dependencies for conditional display.
	 *
	 * @param array  $field     Field configuration.
	 * @param string $field_key Field identifier.
	 *
	 * @return array Modified field configuration with dependency data.
	 * @since 1.0.0
	 */
	private function process_field_dependencies( array $field, string $field_key ): array {
		if ( ! isset( $field['depends'] ) ) {
			return $field;
		}

		$depends = $field['depends'];

		$dependency_data = null;

		if ( is_string( $depends ) ) {
			$dependency_data = $depends;
		} elseif ( is_array( $depends ) ) {
			if ( isset( $depends['field'] ) ) {
				$dependency_data = [
					'field' => $depends['field'],
				];

				if ( isset( $depends['value'] ) ) {
					$dependency_data['value'] = $depends['value'];
				} elseif ( isset( $depends['contains'] ) ) {
					$dependency_data['contains'] = $depends['contains'];
				}
			}
		}

		if ( $dependency_data ) {
			if ( ! isset( $field['wrapper_attrs'] ) ) {
				$field['wrapper_attrs'] = [];
			}

			if ( is_array( $dependency_data ) ) {
				$field['wrapper_attrs']['data-depends'] = htmlspecialchars( wp_json_encode( $dependency_data ), ENT_QUOTES, 'UTF-8' );
			} else {
				$field['wrapper_attrs']['data-depends'] = $dependency_data;
			}

			if ( empty( $field['wrapper_attrs']['id'] ) ) {
				$field['wrapper_attrs']['id'] = 'field-' . sanitize_key( $field_key );
			}

			$field['wrapper_attrs']['style'] = 'display: none;';

			if ( ! empty( $field['wrapper_attrs']['class'] ) ) {
				$field['wrapper_attrs']['class'] .= ' has-dependency';
			} else {
				$field['wrapper_attrs']['class'] = 'has-dependency';
			}
		}

		return $field;
	}

	// =========================================================================
	// COMPONENT DETECTION
	// =========================================================================

	/**
	 * Detect and register required components from configuration.
	 *
	 * @param array $config Flyout configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function detect_components( array $config ): void {
		foreach ( $config['fields'] as $field ) {
			$type = $field['type'] ?? 'text';

			if ( $asset = Components::get_asset( $type, $field ) ) {
				$this->components[] = $asset;
			}
		}

		$this->components = array_unique( $this->components );
	}

	// =========================================================================
	// ASSET MANAGEMENT
	// =========================================================================

	/**
	 * Maybe enqueue assets based on current admin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		if ( $this->assets_enqueued || empty( $this->flyouts ) ) {
			return;
		}

		if ( $this->should_enqueue( $hook_suffix ) ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Determine if assets should be enqueued.
	 *
	 * Only loads on explicitly declared admin pages. No fallback list.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return bool True if assets should load.
	 * @since 4.0.0
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		if ( empty( $this->admin_pages ) ) {
			return false;
		}

		return in_array( $hook_suffix, $this->admin_pages, true );
	}

	/**
	 * Enqueue required assets.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		Assets::enqueue();

		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		// Localize REST API data for JavaScript.
		wp_localize_script( 'wp-flyout-manager', 'wpFlyout', [
			'restUrl'   => rest_url( RestApi::NAMESPACE ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		] );

		$this->assets_enqueued = true;
	}

	// =========================================================================
	// TRIGGER GENERATION (BUTTONS & LINKS)
	// =========================================================================

	/**
	 * Render a trigger button.
	 *
	 * @param string $flyout_id Flyout identifier.
	 * @param array  $data      Data attributes to pass.
	 * @param array  $args      Button configuration.
	 *
	 * @return void Outputs HTML.
	 * @since 1.0.0
	 */
	public function button( string $flyout_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $flyout_id, $data, $args );
	}

	/**
	 * Get trigger button HTML.
	 *
	 * @param string $flyout_id Flyout identifier.
	 * @param array  $data      Data attributes to pass.
	 * @param array  $args      Button configuration.
	 *
	 * @return string Button HTML or empty string if unauthorized.
	 * @since 1.0.0
	 */
	public function get_button( string $flyout_id, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$text  = $args['text'] ?? __( 'Open', 'wp-flyout' );
		$class = $args['class'] ?? 'button';
		$icon  = $args['icon'] ?? '';

		$attrs = $this->build_trigger_attributes( $flyout_id, $data, 'button ' . $class );

		$html = '<button type="button"';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

		if ( $icon ) {
			$html .= sprintf(
				'<span class="dashicons dashicons-%s"></span> ',
				esc_attr( $icon )
			);
		}

		$html .= esc_html( $text );
		$html .= '</button>';

		return $html;
	}

	/**
	 * Create a trigger link.
	 *
	 * @param string $flyout_id Flyout identifier.
	 * @param string $text      Link text.
	 * @param array  $data      Data attributes to pass.
	 * @param array  $args      Additional link arguments.
	 *
	 * @return string Link HTML or empty string if unauthorized.
	 * @since 1.0.0
	 */
	public function link( string $flyout_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$class         = $args['class'] ?? '';
		$attrs         = $this->build_trigger_attributes( $flyout_id, $data, $class );
		$attrs['href'] = '#';

		$html = '<a';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>' . esc_html( $text ) . '</a>';

		return $html;
	}

	/**
	 * Build trigger element attributes.
	 *
	 * REST nonce is global (via wp_localize_script), so no per-flyout nonce needed.
	 *
	 * @param string $flyout_id Flyout identifier.
	 * @param array  $data      Data attributes.
	 * @param string $class     Additional CSS classes.
	 *
	 * @return array Attributes array.
	 * @since 1.0.0
	 */
	private function build_trigger_attributes( string $flyout_id, array $data, string $class = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout'         => $flyout_id,
		];

		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( (string) $value );
		}

		return $attrs;
	}

	// =========================================================================
	// PUBLIC ACCESSOR METHODS
	// =========================================================================

	/**
	 * Get a specific flyout configuration.
	 *
	 * @param string $flyout_id Flyout identifier.
	 *
	 * @return array|null Flyout config or null if not found.
	 * @since 4.0.0
	 */
	public function get_flyout( string $flyout_id ): ?array {
		return $this->flyouts[ $flyout_id ] ?? null;
	}

	/**
	 * Get all registered flyouts.
	 *
	 * @return array Flyout configurations.
	 * @since 1.0.0
	 */
	public function get_flyouts(): array {
		return $this->flyouts;
	}

	/**
	 * Check if flyout is registered.
	 *
	 * @param string $flyout_id Flyout identifier.
	 *
	 * @return bool True if flyout exists.
	 * @since 1.0.0
	 */
	public function has_flyout( string $flyout_id ): bool {
		return isset( $this->flyouts[ $flyout_id ] );
	}

	/**
	 * Get manager prefix.
	 *
	 * @return string Manager prefix.
	 * @since 1.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

	// =========================================================================
	// PRIVATE UTILITY METHODS
	// =========================================================================

	/**
	 * Check if current user can access flyout.
	 *
	 * @param string $flyout_id Flyout identifier.
	 *
	 * @return bool True if user has required capability.
	 * @since 1.0.0
	 */
	private function can_access( string $flyout_id ): bool {
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			return false;
		}

		$config = $this->flyouts[ $flyout_id ];

		return current_user_can( $config['capability'] );
	}

	/**
	 * Find which flyout a field belongs to.
	 *
	 * Used by normalize_ajax_fields to set the flyout param on search URLs.
	 *
	 * @param string $field_key Field key to look up.
	 *
	 * @return string Flyout ID or empty string.
	 * @since 4.0.0
	 */
	private function get_flyout_id_for_field( string $field_key ): string {
		foreach ( $this->flyouts as $flyout_id => $config ) {
			if ( isset( $config['fields'][ $field_key ] ) ) {
				return $flyout_id;
			}

			// Check by name attribute.
			foreach ( $config['fields'] as $key => $field ) {
				if ( ( $field['name'] ?? $key ) === $field_key ) {
					return $flyout_id;
				}
			}
		}

		return '';
	}

}