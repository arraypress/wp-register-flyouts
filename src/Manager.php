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

use ArrayPress\FieldKit\Context\ObjectContext;
use ArrayPress\FieldKit\Actions\Actions;
use ArrayPress\FieldKit\Actions\CallbackAction;
use ArrayPress\FieldKit\Search\CallbackSource;
use ArrayPress\FieldKit\Search\Sources;
use ArrayPress\FieldKit\Field;
use ArrayPress\FieldKit\FieldSet;
use ArrayPress\FieldKit\Assets as KitAssets;
use ArrayPress\FieldKit\Registry as KitRegistry;
use ArrayPress\RegisterFlyouts\Utils\Runtime;

use ArrayPress\RegisterFlyouts\Components\FormField;
use ArrayPress\RegisterFlyouts\Flyout;
use ArrayPress\RegisterFlyouts\ActionBar;

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

	/**
	 * Types that resolve to ajax_select at render time.
	 *
	 * @var array<string>
	 */
	private static array $ajax_select_types = [ 'post', 'taxonomy', 'user' ];

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

		// One key of this library's own: which tab a field belongs to. The
		// kit warns about configuration nothing reads, and it is right to —
		// a key nobody reads is a documented option that quietly does
		// nothing. This one is read here, so it is declared here.
		Field::allow_config_keys( [ 'tab' ] );

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
		$config = apply_filters( "wp_flyout_register_config_{$this->prefix}", $config, $id );
		$config = apply_filters( "wp_flyout_{$this->prefix}_{$id}_config", $config );

		// Auto-detect required components.
		$this->detect_components( $config );

		// Track admin pages for asset loading.
		if ( ! empty( $config['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $config['admin_pages'] )
			);
		}

		// Every field that searches, named and registered here rather than
		// when the panel renders. The request that searches is not the
		// request that rendered — a combobox asks the endpoint on its own —
		// so a source registered while drawing the panel does not exist by
		// the time anyone types into it.
		$config['fields'] = $this->register_search_sources( $id, (array) $config['fields'] );
		$config['fields'] = $this->register_field_actions( $id, (array) $config['fields'] );

		// Store flyout configuration.
		$this->flyouts[ $id ] = $config;

		return $this;
	}

	/**
	 * Whether a field's `callback` is a search.
	 *
	 * It is not always: this library has spelled several things `callback`,
	 * and an action field's is a handler that runs when a button is pressed.
	 * Registering one as a search source would put it behind a GET endpoint
	 * anyone with `edit_posts` could call with any term — so a bare
	 * `callback` counts only on a field that searches by definition.
	 *
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return bool
	 */
	private static function searches( array $field ): bool {
		// Named for what it is, so any field type can have one.
		if ( isset( $field['search_callback'] ) ) {
			return true;
		}

		// An action's handler, which is not one.
		if ( ! empty( $field['action'] ) ) {
			return false;
		}

		// A component with a search embedded in it — line items and its
		// product picker.
		if ( ! empty( $field['search_key'] ) ) {
			return true;
		}

		// And the field type whose whole purpose is a callback-backed search,
		// under both spellings.
		return in_array( (string) ( $field['type'] ?? '' ), [ 'ajax_select', 'ajax' ], true );
	}

	/**
	 * Register a search source for every field that has one.
	 *
	 * The name is written back onto the field, which is what both halves
	 * read: a component renders it into its own picker, and the field set
	 * hands it to the kit rather than deriving a name of its own.
	 *
	 * @param string                              $flyout_id The flyout.
	 * @param array<string, array<string, mixed>> $fields    Field configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function register_search_sources( string $flyout_id, array $fields ): array {
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name = $this->register_search_source(
				$flyout_id,
				(string) ( $field['search_key'] ?? $field['name'] ?? $key ),
				$field
			);

			if ( '' !== $name ) {
				$fields[ $key ]['search_source'] = $name;
			}
		}

		return $fields;
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

		/*
		 * Defaulted rather than read straight out. title and size are required
		 * in practice but subtitle is not, and a flyout registered without one
		 * passed null into a string-typed setter -- a TypeError inside a REST
		 * handler, which reaches the browser as an HTML error page where the
		 * script expects JSON. The message it produces there says nothing
		 * about a missing subtitle.
		 */
		$flyout->set_title( (string) ( $config['title'] ?? '' ) );
		$flyout->set_subtitle( (string) ( $config['subtitle'] ?? '' ) );
		$flyout->set_size( (string) ( $config['size'] ?? 'medium' ) );

		$flyout = apply_filters( "wp_flyout_build_flyout_{$this->prefix}", $flyout, $config, $data );

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
				// The kit's, which is core's Site Icon treatment: red text, red
				// border, filling red on hover. .button-link-delete is core's
				// red *link* and gives red text with no border — combined with
				// .button it produced a blue label inside a red outline.
				'class' => 'wp-flyout-delete field-kit__button--delete',
			];
		}

		return $actions;
	}

	// =========================================================================
	// FIELD RENDERING
	// =========================================================================

	/**
	 * Register a kit search source for a field that searches.
	 *
	 * The name is the manager's, the flyout's and the field's. The field set
	 * would otherwise derive one from the field key alone — this library
	 * gives it no input prefix, because the prefix is also what the form
	 * submits under — and two flyouts each with a `customer` field would name
	 * the same source, the second registration answering the first's
	 * searches.
	 *
	 * @param string               $flyout_id The flyout.
	 * @param string               $field_key The field.
	 * @param array<string, mixed> $field     Field configuration.
	 *
	 * @return string The source's name, or empty when it has no callback.
	 */
	public function register_search_source( string $flyout_id, string $field_key, array $field ): string {
		if ( ! self::searches( $field ) ) {
			return '';
		}

		$callback = $field['search_callback'] ?? $field['callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return '';
		}

		$name = sprintf( '%s-%s-%s', $this->prefix, $flyout_id, $field_key );

		Sources::shared()->register(
			new CallbackSource(
				$name,
				$callback,
				(string) ( $field['search_capability'] ?? 'edit_posts' )
			)
		);

		return $name;
	}

	/**
	 * Register the action handlers every field declares.
	 *
	 * The same arrangement as the search sources, for the same reason: the
	 * request that presses a button is not the request that drew it, so a
	 * handler registered while rendering does not exist by the time anyone
	 * clicks. An action button in a panel came back "Unknown action." every
	 * time.
	 *
	 * The names are written back onto the field, and are the manager's, the
	 * flyout's and the field's. A field set given no input prefix would
	 * derive them from the field key alone, and two panels each with an
	 * `activate` button would name the same handler.
	 *
	 * @param string                              $flyout_id The flyout.
	 * @param array<string, array<string, mixed>> $fields    Field configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function register_field_actions( string $flyout_id, array $fields ): array {
		$actions = Actions::shared();

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$handlers = (array) ( $field['actions'] ?? [] );

			if ( isset( $field['action_callback'] ) ) {
				$handlers['run'] = $field['action_callback'];
			}

			$names = [];

			foreach ( $handlers as $name => $callback ) {
				if ( ! is_callable( $callback ) ) {
					continue;
				}

				$names[ $name ] = sprintf(
					'%s-%s-%s-%s',
					$this->prefix,
					$flyout_id,
					(string) ( $field['name'] ?? $key ),
					(string) $name
				);

				$actions->register(
					new CallbackAction(
						$names[ $name ],
						$callback,
						(string) ( $field['action_capability'] ?? $field['capability'] ?? 'manage_options' )
					)
				);
			}

			if ( [] !== $names ) {
				$fields[ $key ]['action_names'] = $names;
			}
		}

		return $fields;
	}

	/**
	 * Sanitize a submission against this flyout's own fields.
	 *
	 * Through the field set, which means each value is coerced by its own
	 * type — the same coercion the same field gets on a settings page, a
	 * metabox or a term screen. This library used to carry a Sanitizer of
	 * its own, seven hundred lines deciding for a second time what a number
	 * or a checkbox is, and the two had already drifted.
	 *
	 * A key the flyout does not declare is dropped rather than passed on,
	 * which is what makes a submission from a crafted form no more powerful
	 * than one from the panel.
	 *
	 * @param array<string, mixed> $fields Field configuration.
	 * @param array<string, mixed> $input  Submitted values.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( array $fields, array $input ): array {
		[ $set, $context ] = $this->field_set( $fields, null );

		// Re-slashed because the REST layer has already unslashed and the
		// field set unslashes at its own boundary; without it every
		// backslash someone typed is eaten by the save that stores it.
		$set->save( wp_slash( $input ) );

		return $context->values();
	}

	/**
	 * A field set over whatever the load callback handed back.
	 *
	 * This is the change that makes a flyout the same thing as every other
	 * surface in this codebase: a set of fields over a context. `load`
	 * returning a record and `save` taking an array *is* a context — reading
	 * and writing — so it is expressed as one, and the kit then does the
	 * building, the value reading, the sanitizing and the accessibility that
	 * this library used to do itself in four separate places.
	 *
	 * Components are left out of the set. They display rather than collect,
	 * have no value to read and nothing to sanitize, and each is rendered in
	 * its place by the walk that calls this.
	 *
	 * @param array<string, mixed> $fields Field configuration.
	 * @param mixed                $data   Whatever `load` returned.
	 *
	 * @return array{0: FieldSet, 1: ObjectContext}
	 */
	private function field_set( array $fields, $data ): array {
		$context = new ObjectContext( is_object( $data ) ? $data : null );
		$configs = [];

		foreach ( $this->normalize_fields( $fields ) as $key => $field ) {
			if ( Components::is_component( (string) ( $field['type'] ?? 'text' ) ) ) {
				continue;
			}

			// Keyed by the posted name where one is given: the field set
			// derives input_name from the key, and the name is what the form
			// actually submits.
			$config = FormField::to_kit( $field );

			// Which leaves nothing for `name` to say -- it is the key now --
			// and `tab` was spent grouping the fields before this ran. The
			// kit reads neither, and reports configuration nothing reads
			// under WP_DEBUG once per field per render: a product panel
			// emitted a hundred and fifty-eight notices, which is enough to
			// bury any that meant something.
			unset( $config['name'], $config['tab'] );

			$configs[ (string) ( $field['name'] ?? $key ) ] = $config;
		}

		return [ new FieldSet( $configs, $context, '' ), $context ];
	}

	private function render_fields( array $fields, $data ): string {
		$output = '';

		[ $set ] = $this->field_set( $fields, $data );

		$fields = apply_filters( "wp_flyout_before_render_fields_{$this->prefix}", $fields, $data );

		$normalized_fields = $this->normalize_fields( $fields );

		foreach ( $normalized_fields as $field_key => $field ) {
			// Process conditional dependencies.
			if ( isset( $field['depends'] ) ) {
				$field = $this->process_field_dependencies( $field, $field_key );
			}

			// Apply field-specific filters.
			$field = apply_filters( "wp_flyout_render_field_{$this->prefix}", $field, $field_key, $data );
			$field = apply_filters( "wp_flyout_render_field_{$this->prefix}_{$field_key}", $field, $data );

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

				$component    = Components::create( $type, $field, $this->prefix );
				$field_output = $component ? $component->render() : '';
			} else {
				// Through the set, which reads the value from the context and
				// renders with the kit's labelling, descriptions, required
				// state and conditional logic already correct.
				$rendered = $set->field( $data_key );

				$field_output = null === $rendered
					? ''
					: FormField::wrap( $field, $set->render_field( $rendered ) );
			}

			$output .= $field_output;
		}

		return apply_filters( "wp_flyout_after_render_fields_{$this->prefix}", $output, $fields, $data );
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
		$fields = apply_filters( "wp_flyout_before_normalize_fields_{$this->prefix}", $fields );

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

		return apply_filters( "wp_flyout_after_normalize_fields_{$this->prefix}", $normalized );
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
		// `post`, `taxonomy` and `user` used to be converted here into this
		// library's own ajax_select, with a hand-written search callback, so
		// they never reached the kit's types of the same names at all — which
		// is why a relational field in a flyout came out as a plain <select>
		// reading "— Select —" while the same field on a settings page was a
		// searchable combobox. They go straight through now, and the kit's
		// search endpoint answers for them.
		//
		// What is left here is the context two components cannot work out for
		// themselves: which manager and which flyout they belong to.
		if ( 'line_items' === ( $field['type'] ?? '' ) ) {
			$flyout_id = $this->get_flyout_id_for_field( $field_key );

			$field['manager'] = $this->prefix;
			$field['flyout']  = $flyout_id;
		}

		return $field;
	}

	/**
	 * Process field dependencies for conditional display.
	 *
	 * Normalizes the 'depends' configuration to the same data-conditions
	 * format used by the settings library, ensuring interchangeable syntax.
	 *
	 * Supported formats:
	 * - String: 'depends' => 'field_name' (truthy/not_empty check)
	 * - Simple key => value: 'depends' => ['enable_feature' => 1]
	 * - Single condition: 'depends' => ['field' => 'x', 'value' => 'y', 'operator' => '=']
	 * - Multiple conditions (AND): 'depends' => [['field' => 'a', ...], ['field' => 'b', ...]]
	 *
	 * @param array  $field     Field configuration.
	 * @param string $field_key Field identifier.
	 *
	 * @return array Modified field configuration with data-conditions attribute.
	 * @since 1.0.0
	 */
	private function process_field_dependencies( array $field, string $field_key ): array {
		if ( ! isset( $field['depends'] ) ) {
			return $field;
		}

		$depends    = $field['depends'];
		$conditions = $this->normalize_depends( $depends );

		if ( empty( $conditions ) ) {
			return $field;
		}

		if ( ! isset( $field['wrapper_attrs'] ) ) {
			$field['wrapper_attrs'] = [];
		}

		$field['wrapper_attrs']['data-conditions'] = esc_attr( wp_json_encode( $conditions ) );

		if ( empty( $field['wrapper_attrs']['id'] ) ) {
			$field['wrapper_attrs']['id'] = 'field-' . sanitize_key( $field_key );
		}

		$field['wrapper_attrs']['style'] = 'display: none;';

		if ( ! empty( $field['wrapper_attrs']['class'] ) ) {
			$field['wrapper_attrs']['class'] .= ' has-dependency';
		} else {
			$field['wrapper_attrs']['class'] = 'has-dependency';
		}

		return $field;
	}

	/**
	 * Normalize a depends configuration to a conditions array.
	 *
	 * Produces the same format as the settings library's ConditionalLogic trait:
	 * [['field' => '...', 'value' => '...', 'operator' => '...'], ...]
	 *
	 * @param string|array $depends Raw depends configuration.
	 *
	 * @return array Normalized conditions array.
	 * @since 4.0.0
	 */
	private function normalize_depends( $depends ): array {
		// String format: 'field_name' → not_empty check
		if ( is_string( $depends ) ) {
			return [
				[
					'field'    => $depends,
					'value'    => '',
					'operator' => 'not_empty',
				],
			];
		}

		if ( ! is_array( $depends ) || empty( $depends ) ) {
			return [];
		}

		$first_key = array_key_first( $depends );

		// Simple key => value format: ['enable_feature' => 1, 'mode' => 'advanced']
		if ( is_string( $first_key ) && ! in_array( $first_key, [ 'field', 'value', 'operator' ], true ) ) {
			$conditions = [];
			foreach ( $depends as $field => $value ) {
				$conditions[] = [
					'field'    => $field,
					'value'    => $value,
					'operator' => is_array( $value ) ? 'in' : '=',
				];
			}

			return $conditions;
		}

		// Single condition: ['field' => 'x', 'value' => 'y']
		if ( isset( $depends['field'] ) ) {
			return [ $this->normalize_single_condition( $depends ) ];
		}

		// Array of conditions: [['field' => 'a', ...], ['field' => 'b', ...]]
		if ( is_int( $first_key ) ) {
			return array_map( [ $this, 'normalize_single_condition' ], $depends );
		}

		return [];
	}

	/**
	 * Normalize a single condition array.
	 *
	 * Handles legacy 'contains' key and ensures operator is always set.
	 *
	 * @param array $condition Single condition array.
	 *
	 * @return array Normalized condition.
	 * @since 4.0.0
	 */
	private function normalize_single_condition( array $condition ): array {
		// Handle legacy 'contains' key
		if ( isset( $condition['contains'] ) && ! isset( $condition['operator'] ) ) {
			return [
				'field'    => $condition['field'] ?? '',
				'value'    => $condition['contains'],
				'operator' => 'contains',
			];
		}

		$default_operator = is_array( $condition['value'] ?? '' ) ? 'in' : '=';

		return [
			'field'    => $condition['field'] ?? '',
			'value'    => $condition['value'] ?? '',
			'operator' => $condition['operator'] ?? $default_operator,
		];
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

			// Derivative types resolve to ajax_select at render time.
			if ( in_array( $type, self::$ajax_select_types, true ) ) {
				$type = 'ajax_select';
			}

			$asset = Components::get_asset( $type, $field );

			if ( $asset ) {
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

		if ( in_array( $hook_suffix, $this->admin_pages, true ) ) {
			return true;
		}

		// A page slug, which is what a consumer thinks they are giving. The
		// hook suffix of a submenu page is built by WordPress out of the
		// parent's slug and is not something anyone knows without printing
		// it — 'apfd_demo_page_apfd-table' for a page registered as
		// 'apfd-table' under a post type. Matching against the slug too means
		// the obvious thing works, instead of failing silently: an unmatched
		// page renders no flyout at all, so the trigger button is there and
		// pressing it does nothing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- identifying the screen, not acting.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return '' !== $page && in_array( $page, $this->admin_pages, true );
	}

	/**
	 * What the field types across every flyout on this screen need.
	 *
	 * The media frame, the colour picker, the code editor: each is a
	 * dependency of a particular type rather than of the kit, and each has to
	 * be enqueued before the kit's own script so it is there when the kit
	 * looks for it.
	 *
	 * Asked of the types rather than of a field set, because a flyout has no
	 * field set — it renders one FormField at a time.
	 *
	 * @return array{scripts: string[], styles: string[], code_editors: string[]}
	 */
	private function field_dependencies(): array {
		$registry = new KitRegistry();
		$scripts  = [];
		$styles   = [];

		foreach ( $this->flyouts as $config ) {
			foreach ( (array) ( $config['fields'] ?? [] ) as $field ) {
				$id = (string) ( $field['type'] ?? 'text' );

				if ( ! $registry->has( $id ) ) {
					continue;
				}

				$needs   = $registry->get( $id )->dependencies();
				$scripts = array_merge( $scripts, $needs['scripts'] ?? [] );
				$styles  = array_merge( $styles, $needs['styles'] ?? [] );
			}
		}

		return [
			'scripts'      => array_values( array_unique( $scripts ) ),
			'styles'       => array_values( array_unique( $styles ) ),
			'code_editors' => [],
		];
	}

	/**
	 * Enqueue required assets.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		// The kit's, first. Every field in a flyout is rendered by
		// wp-field-kit, and its stylesheet and script were never enqueued
		// here — so the markup arrived with none of the rules or behaviour
		// that make it work. A button group came out as stacked radios, a
		// toggle as a plain checkbox, a sortable as a numbered list, a
		// tooltip as a bare question mark in a box, and every searchable
		// select stayed a plain <select> because the combobox never ran.
		( new KitAssets() )->enqueue( $this->field_dependencies() );

		Assets::enqueue();

		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		// Published into a registry keyed by this build's core handle rather
		// than to a bare `wpFlyout` global. Two Strauss-prefixed copies each
		// enqueue their own scripts, and a shared global would leave whichever
		// localized last owning the REST URL and nonce for both. Each script
		// resolves its own entry from the id WordPress stamps on its element.
		$handle = Runtime::handle();

		wp_add_inline_script(
			Runtime::handle( 'wp-flyout' ),
			sprintf(
				'window.ArrayPressFlyouts=window.ArrayPressFlyouts||{};window.ArrayPressFlyouts[%s]=%s;',
				wp_json_encode( $handle ),
				wp_json_encode( [
					'restUrl'   => rest_url( RestApi::rest_namespace() ),
					'restNonce' => wp_create_nonce( 'wp_rest' ),
				] )
			),
			'before'
		);

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
		// Returns markup this library assembled and escaped as it built it.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

		$attrs = $this->build_trigger_attributes( $flyout_id, $data, 'button ' . $class );

		$html = '<button type="button"';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

		// No glyph. A trigger always has text -- it defaults to "Open" --
		// and core puts no picture in front of its own button text anywhere
		// in the admin.
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
	private function build_trigger_attributes( string $flyout_id, array $data, string $class_name = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class_name ),
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
