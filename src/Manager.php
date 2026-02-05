<?php
/**
 * WP Flyout Manager - Standardized Implementation
 *
 * Manages flyout registration, AJAX handling, and automatic data mapping.
 * Fixed version with memory optimization, proper sanitization, and error handling.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use ArrayPress\RegisterFlyouts\Components\FormField;
use ArrayPress\RegisterFlyouts\Core\Flyout;
use ArrayPress\RegisterFlyouts\Parts\ActionBar;
use Exception;
use Throwable;

/**
 * Class Manager
 *
 * Orchestrates flyout operations with automatic data resolution.
 * Uses standardized nonce handling where all components use action names as nonce keys.
 *
 * @since 1.0.0
 */
class Manager {

	// =========================================================================
	// PROPERTIES
	// =========================================================================

	/**
	 * Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $prefix;

	/**
	 * Registered flyout configurations
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $flyouts = [];

	/**
	 * Registered AJAX endpoints (minimal data to avoid memory leaks)
	 *
	 * Stores only callback and capability instead of entire config arrays
	 * to prevent memory bloat with many fields.
	 *
	 * @since 2.0.0
	 * @var array<string, array{callback: callable, capability: string, type: string}>
	 */
	private array $ajax_endpoints = [];

	/**
	 * Admin pages where assets should load
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all flyouts
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $components = [];

	/**
	 * Whether assets have been enqueued
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	// =========================================================================
	// CONSTRUCTOR & INITIALIZATION
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Single AJAX handler for all actions
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets on admin pages
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	// =========================================================================
	// FLYOUT REGISTRATION
	// =========================================================================

	/**
	 * Register a flyout with declarative configuration
	 *
	 * @param string $id     Unique flyout identifier
	 * @param array  $config Flyout configuration array
	 *
	 * @return self Returns instance for method chaining
	 * @since 1.0.0
	 */
	public function register_flyout( string $id, array $config ): self {
		$defaults = [
			'title'       => '',
			'subtitle'    => '',
			'size'        => 'medium',
			'tabs'      => [],
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

		// Apply filters for extensibility
		$config = apply_filters( 'wp_flyout_register_config', $config, $id, $this->prefix );
		$config = apply_filters( "wp_flyout_{$this->prefix}_{$id}_config", $config );

		// Auto-detect required components
		$this->detect_components( $config );

		// Track admin pages for asset loading
		if ( ! empty( $config['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $config['admin_pages'] )
			);
		}

		// Store flyout configuration
		$this->flyouts[ $id ] = $config;

		// Register AJAX endpoints for components with callbacks
		$this->register_component_endpoints( $id, $this->flyouts[ $id ] );

		// Register action button endpoints
		$this->register_action_button_endpoints( $id, $config );

		return $this;
	}

	// =========================================================================
	// AJAX ENDPOINT REGISTRATION
	// =========================================================================

	/**
	 * Register AJAX endpoints for components
	 *
	 * Standardized approach: all components use action name as nonce key
	 * and send _wpnonce parameter.
	 *
	 * FIXED: Reduced memory usage by storing minimal endpoint data
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration (passed by reference to update)
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function register_component_endpoints( string $flyout_id, array &$config ): void {
		// Define callback to AJAX field mappings
		$callback_mappings = [
			'search_callback'  => 'ajax_search',
			'details_callback' => 'ajax_details',
			'add_callback'     => 'ajax_add',
			'delete_callback'  => 'ajax_delete',
			'options_callback' => 'ajax_options'
		];

		$capability = $config['capability'];

		foreach ( $config['fields'] as $field_key => &$field ) {
			$field_name = $field['name'] ?? $field_key;

			foreach ( $callback_mappings as $callback_key => $ajax_key ) {
				if ( ! isset( $field[ $callback_key ] ) || ! is_callable( $field[ $callback_key ] ) ) {
					continue;
				}

				// Generate unique action name
				$action_name = $this->generate_action_name( $flyout_id, $field_name, $ajax_key );

				// Store action name in field config for frontend use
				$field[ $ajax_key ] = $action_name;

				// Always use action name as nonce key
				$field[ $ajax_key . '_nonce_key' ] = $action_name;

				// Store minimal endpoint data (fixes memory leak)
				$this->ajax_endpoints[ $action_name ] = [
					'callback'   => $field[ $callback_key ],
					'capability' => $capability,
					'type'       => 'field'
				];

				// Register lightweight handler
				add_action( 'wp_ajax_' . $action_name, [ $this, 'handle_field_ajax' ] );
			}
		}
	}

	/**
	 * Generate AJAX action name
	 *
	 * @param string $flyout_id  Flyout identifier
	 * @param string $field_name Field name
	 * @param string $ajax_key   AJAX key (e.g. 'ajax_search')
	 *
	 * @return string Generated action name
	 * @since 2.0.0
	 */
	private function generate_action_name( string $flyout_id, string $field_name, string $ajax_key ): string {
		$suffix = str_replace( 'ajax_', '', $ajax_key );

		return sprintf( 'wp_flyout_%s_%s_%s_%s',
			$this->prefix,
			$flyout_id,
			sanitize_key( $field_name ),
			$suffix
		);
	}

	/**
	 * Handle field AJAX requests
	 *
	 * FIXED: Added sanitization and error boundaries
	 *
	 * @return void Sends JSON response and exits
	 * @since 2.0.0
	 */
	public function handle_field_ajax(): void {
		try {
			$action_name = $_POST['action'] ?? '';

			if ( ! isset( $this->ajax_endpoints[ $action_name ] ) ) {
				wp_send_json_error( 'Invalid endpoint', 400 );
			}

			$endpoint = $this->ajax_endpoints[ $action_name ];

			// Security checks
			if ( ! check_ajax_referer( $action_name, '_wpnonce', false ) ) {
				wp_send_json_error( 'Security check failed', 403 );
			}

			if ( ! current_user_can( $endpoint['capability'] ) ) {
				wp_send_json_error( 'Insufficient permissions', 403 );
			}

			// Execute callback with error boundary
			$result = call_user_func( $endpoint['callback'], $_POST );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( $result );

		} catch ( Throwable $e ) {
			wp_send_json_error( 'Operation failed: ' . $e->getMessage(), 500 );
		}
	}

	/**
	 * Register AJAX endpoints for action components (buttons and menus)
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function register_action_button_endpoints( string $flyout_id, array $config ): void {
		$capability = $config['capability'];

		foreach ( $config['fields'] as $field ) {
			$type = $field['type'] ?? '';

			// Get items array based on component type
			$items = [];
			if ( $type === 'action_buttons' ) {
				$items = $field['buttons'] ?? [];
			} elseif ( $type === 'action_menu' ) {
				$items = $field['items'] ?? [];
			} else {
				continue;
			}

			foreach ( $items as $item ) {
				// Skip separators (action_menu only)
				if ( isset( $item['type'] ) && $item['type'] === 'separator' ) {
					continue;
				}

				// Check for callback instead of action string
				if ( empty( $item['callback'] ) || ! is_callable( $item['callback'] ) ) {
					continue;
				}

				// Generate action name if not provided
				$action      = $item['action'] ?? uniqid( 'action_' );
				$action_name = 'wp_flyout_action_' . $action;

				// Check if already registered to avoid duplicates
				if ( isset( $this->ajax_endpoints[ $action_name ] ) ) {
					continue;
				}

				// Store minimal data
				$this->ajax_endpoints[ $action_name ] = [
					'callback'   => $item['callback'],
					'capability' => $capability,
					'type'       => 'action'
				];

				// Register the AJAX handler
				add_action( 'wp_ajax_' . $action_name, [ $this, 'handle_action_ajax' ] );
			}
		}
	}

	/**
	 * Handle action button AJAX requests
	 *
	 * @return void Sends JSON response and exits
	 * @since 2.0.0
	 */
	public function handle_action_ajax(): void {
		try {
			$action_name = $_POST['action'] ?? '';

			if ( ! isset( $this->ajax_endpoints[ $action_name ] ) ) {
				wp_send_json_error( 'Invalid action', 400 );
			}

			$endpoint = $this->ajax_endpoints[ $action_name ];

			// Security checks
			if ( ! check_ajax_referer( $action_name, '_wpnonce', false ) ) {
				wp_send_json_error( 'Security check failed', 403 );
			}

			if ( ! current_user_can( $endpoint['capability'] ) ) {
				wp_send_json_error( 'Insufficient permissions', 403 );
			}

			// Execute callback
			$result = call_user_func( $endpoint['callback'], $_POST );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			// If result is array with message, send as success
			if ( is_array( $result ) && isset( $result['message'] ) ) {
				wp_send_json_success( $result );
			}

			// Default success
			wp_send_json_success( [ 'message' => 'Action completed successfully' ] );

		} catch ( Throwable $e ) {
			wp_send_json_error( 'Action failed: ' . $e->getMessage(), 500 );
		}
	}

	// =========================================================================
	// AJAX REQUEST HANDLING
	// =========================================================================

	/**
	 * Central AJAX handler with security checks
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	public function handle_ajax(): void {
		try {
			// Extract and validate request
			$request = $this->validate_request();

			// Get flyout config
			$config = $this->flyouts[ $request['flyout_id'] ];

			// Route to appropriate handler
			switch ( $request['action'] ) {
				case 'load':
					$this->handle_load( $config, $request );
					break;

				case 'save':
					$this->handle_save( $config, $request );
					break;

				case 'delete':
					$this->handle_delete( $config, $request );
					break;

				default:
					wp_send_json_error( __( 'Invalid action', 'wp-flyout' ), 400 );
			}

		} catch ( Throwable $e ) {
			$code = $e->getCode() ?: 500;
			wp_send_json_error( $e->getMessage(), $code );
		}
	}

	/**
	 * Validate AJAX request and check permissions
	 *
	 * @return array Validated request data
	 * @throws Exception If validation fails
	 * @since 1.0.0
	 */
	private function validate_request(): array {
		$flyout_id = sanitize_key( $_POST['flyout'] ?? '' );
		$action    = sanitize_key( $_POST['flyout_action'] ?? 'load' );

		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			throw new Exception( 'Invalid flyout', 400 );
		}

		$config = $this->flyouts[ $flyout_id ];

		if ( ! check_ajax_referer( 'wp_flyout_' . $this->prefix . '_' . $flyout_id, 'nonce', false ) ) {
			throw new Exception( 'Security check failed', 403 );
		}

		if ( ! current_user_can( $config['capability'] ) ) {
			throw new Exception( 'Insufficient permissions', 403 );
		}

		return [
			'flyout_id' => $flyout_id,
			'action'    => $action,
			'id'        => $_POST['id'] ?? null,
			'form_data' => $_POST['form_data'] ?? '',
		];
	}

	/**
	 * Handle load action
	 *
	 * FIXED: Added error boundaries
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_load( array $config, array $request ): void {
		$data = [];

		if ( $config['load'] && is_callable( $config['load'] ) ) {
			try {
				$data = call_user_func( $config['load'], $request['id'] );

				if ( is_wp_error( $data ) ) {
					wp_send_json_error( $data->get_error_message(), 400 );
				}

				if ( $data === false ) {
					wp_send_json_error( __( 'Record not found', 'wp-flyout' ), 404 );
				}
			} catch ( Throwable $e ) {
				wp_send_json_error( 'Failed to load data: ' . $e->getMessage(), 500 );
			}
		}

		$flyout = $this->build_flyout( $config, $data, $request['id'] );
		wp_send_json_success( [ 'html' => $flyout->render() ] );
	}

	/**
	 * Handle save action
	 *
	 * FIXED: Added error boundaries
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_save( array $config, array $request ): void {
		if ( ! $config['save'] || ! is_callable( $config['save'] ) ) {
			wp_send_json_error( __( 'Save not configured', 'wp-flyout' ), 501 );
		}

		parse_str( $request['form_data'], $raw_data );

		$normalized_fields = $this->normalize_fields( $config['fields'] );
		$form_data         = Sanitizer::sanitize_form_data( $raw_data, $normalized_fields );

		// Apply filter after sanitization
		$form_data = apply_filters( 'wp_flyout_before_save', $form_data, $config, $this->prefix );

		if ( ! empty( $config['validate'] ) && is_callable( $config['validate'] ) ) {
			try {
				$validation = call_user_func( $config['validate'], $form_data );

				if ( is_wp_error( $validation ) ) {
					wp_send_json_error(
						$validation->get_error_message(),
						400
					);
				}

				if ( $validation === false ) {
					wp_send_json_error( __( 'Validation failed', 'wp-flyout' ), 400 );
				}
			} catch ( Throwable $e ) {
				wp_send_json_error( 'Validation error: ' . $e->getMessage(), 500 );
			}
		}

		$id = $form_data['id'] ?? $request['id'] ?? null;

		try {
			$result = call_user_func( $config['save'], $id, $form_data );

			// Apply filter after save
			do_action( 'wp_flyout_after_save', $result, $id, $form_data, $config, $this->prefix );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message(), 400 );
			}

			if ( $result === false ) {
				wp_send_json_error( __( 'Save failed', 'wp-flyout' ), 500 );
			}

			wp_send_json_success( [
				'message' => __( 'Saved successfully', 'wp-flyout' )
			] );
		} catch ( Throwable $e ) {
			wp_send_json_error( 'Save operation failed: ' . $e->getMessage(), 500 );
		}
	}

	/**
	 * Handle delete action
	 *
	 * FIXED: Added error boundaries
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_delete( array $config, array $request ): void {
		if ( ! $config['delete'] || ! is_callable( $config['delete'] ) ) {
			wp_send_json_error( __( 'Delete not configured', 'wp-flyout' ), 501 );
		}

		// Apply filter before delete
		$id = apply_filters( 'wp_flyout_before_delete', $request['id'], $config, $this->prefix );

		try {
			$result = call_user_func( $config['delete'], $id );

			// Apply action after delete
			do_action( 'wp_flyout_after_delete', $result, $id, $config, $this->prefix );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message(), 400 );
			}

			if ( $result === false ) {
				wp_send_json_error( __( 'Delete failed', 'wp-flyout' ), 500 );
			}

			wp_send_json_success( [
				'message' => __( 'Deleted successfully', 'wp-flyout' )
			] );
		} catch ( Throwable $e ) {
			wp_send_json_error( 'Delete operation failed: ' . $e->getMessage(), 500 );
		}
	}

	// =========================================================================
	// FLYOUT BUILDING
	// =========================================================================

	/**
	 * Build flyout interface
	 *
	 * @param array  $config Flyout configuration
	 * @param mixed  $data   Data for field population
	 * @param string $id     Record ID if editing
	 *
	 * @return Flyout Configured flyout instance
	 * @since 1.0.0
	 */
	private function build_flyout( array $config, $data, $id = null ): Flyout {
		$flyout_instance_id = $config['id'] ?? uniqid() . '_' . ( $id ?: 'new' );
		$flyout             = new Flyout( $flyout_instance_id );

		// Override title/subtitle if passed via button
		$title    = sanitize_text_field( $_POST['title'] ?? '' ) ?: $config['title'];
		$subtitle = sanitize_text_field( $_POST['subtitle'] ?? '' ) ?: $config['subtitle'];

		$flyout->set_title( $title );
		$flyout->set_subtitle( $subtitle );
		$flyout->set_size( $config['size'] );

		// Apply filter to modify flyout instance
		$flyout = apply_filters( 'wp_flyout_build_flyout', $flyout, $config, $data, $this->prefix );

		if ( ! empty( $config['tabs'] ) ) {
			$this->build_tab_interface( $flyout, $config['tabs'], $config['fields'], $data );
		} else {
			$content = $this->render_fields( $config['fields'], $data );
			$flyout->add_content( '', $content );
		}

		if ( $id ) {
			$flyout->add_content( '', sprintf(
				'<input type="hidden" name="id" value="%s">',
				esc_attr( $id )
			) );
		}

		$actions = ! empty( $config['actions'] )
			? $config['actions']
			: $this->get_default_actions( $config );

		// Only set footer if there are actions
		if ( ! empty( $actions ) ) {
			$flyout->set_footer( $this->render_actions( $actions ) );
		}

		return $flyout;
	}

	/**
	 * Build tab interface for flyout
	 *
	 * @param Flyout $flyout Flyout instance
	 * @param array  $tabs   Tab configurations
	 * @param array  $fields All field configurations
	 * @param mixed  $data   Data for field population
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function build_tab_interface( Flyout $flyout, array $tabs, array $fields, $data ): void {
		// Group fields by tab
		$fields_by_tab = [];
		foreach ( $fields as $key => $field ) {
			$tab = $field['tab'] ?? 'default';
			if ( ! isset( $fields_by_tab[ $tab ] ) ) {
				$fields_by_tab[ $tab ] = [];
			}
			$fields_by_tab[ $tab ][ $key ] = $field;
		}

		// Add tabs with their fields
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
	 * Render action buttons for footer
	 *
	 * @param array $actions Action button configurations
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_actions( array $actions ): string {
		$action_bar = new ActionBar( [ 'actions' => $actions ] );

		return $action_bar->render();
	}

	/**
	 * Get default action buttons based on configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return array Default action buttons
	 * @since 1.0.0
	 */
	private function get_default_actions( array $config ): array {
		$actions = [];

		if ( ! empty( $config['save'] ) ) {
			$actions[] = [
				'text'  => __( 'Save', 'wp-flyout' ),
				'style' => 'primary',
				'class' => 'wp-flyout-save'
			];
		}

		if ( ! empty( $config['delete'] ) ) {
			$actions[] = [
				'text'  => __( 'Delete', 'wp-flyout' ),
				'style' => 'link-delete',
				'class' => 'wp-flyout-delete'
			];
		}

		return $actions;
	}

	// =========================================================================
	// FIELD RENDERING
	// =========================================================================

	/**
	 * Render fields from configuration
	 *
	 * Processes field configurations and renders them with support for:
	 * - Conditional field dependencies
	 * - Component resolution
	 * - AJAX endpoint nonce generation
	 * - Data value resolution from objects/arrays
	 *
	 * @param array $fields Field configurations
	 * @param mixed $data   Data object or array for field population
	 *
	 * @return string Generated HTML
	 * @since 12.1.0 Added conditional field support and simplified AJAX handling
	 * @since 1.0.0
	 */
	private function render_fields( array $fields, $data ): string {
		$output = '';

		// Apply filter before rendering
		$fields = apply_filters( 'wp_flyout_before_render_fields', $fields, $data, $this->prefix );

		$normalized_fields = $this->normalize_fields( $fields );

		foreach ( $normalized_fields as $field_key => $field ) {
			// Process conditional dependencies
			if ( isset( $field['depends'] ) ) {
				$field = $this->process_field_dependencies( $field, $field_key );
			}

			// Apply field-specific filters
			$field = apply_filters( 'wp_flyout_render_field', $field, $field_key, $data, $this->prefix );
			$field = apply_filters( "wp_flyout_render_field_{$field_key}", $field, $data, $this->prefix );

			// Normalize AJAX fields (handles nonces, ajax_select, etc.)
			$field = $this->normalize_ajax_fields( $field, $field_key, $data );

			// Get field type
			$type = $field['type'] ?? 'text';

			// Render field based on type
			if ( Components::is_component( $type ) ) {
				// Component rendering
				$resolved_data = Components::resolve_data( $type, $field_key, $data );

				// Merge resolved data with field config (field config takes precedence)
				foreach ( $resolved_data as $key => $value ) {
					if ( ! isset( $field[ $key ] ) && $value !== null ) {
						$field[ $key ] = $value;
					}
				}

				$component    = Components::create( $type, $field );
				$field_output = $component ? $component->render() : '';
			} else {
				// Standard field rendering
				if ( ! isset( $field['value'] ) && $data ) {
					$field['value'] = Components::resolve_value( $field_key, $data );
				}

				$form_field   = new FormField( $field );
				$field_output = $form_field->render();
			}

			$output .= $field_output;
		}

		// Apply filter after rendering
		return apply_filters( 'wp_flyout_after_render_fields', $output, $fields, $data, $this->prefix );
	}

	/**
	 * Normalize field configurations
	 * Ensures all fields have proper 'name' attributes
	 *
	 * @param array $fields Field configurations
	 *
	 * @return array Normalized fields
	 * @since 1.0.0
	 */
	private function normalize_fields( array $fields ): array {
		// Apply pre-normalization filter
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

		// Apply post-normalization filter
		return apply_filters( 'wp_flyout_after_normalize_fields', $normalized, $this->prefix );
	}

	/**
	 * Normalize AJAX field configurations
	 *
	 * Centralizes AJAX-related field processing including nonce generation
	 * and ajax_select specific handling.
	 *
	 * @param array  $field     Field configuration
	 * @param string $field_key Field identifier
	 * @param mixed  $data      Data source for value resolution
	 *
	 * @return array Normalized field configuration
	 * @since 12.1.0
	 */
	private function normalize_ajax_fields( array $field, string $field_key, $data ): array {
		$type = $field['type'] ?? 'text';

		// Generate nonces for any AJAX actions
		$ajax_actions = [
			'ajax_search'  => 'nonce',
			'ajax_add'     => 'add_nonce',
			'ajax_delete'  => 'delete_nonce',
			'ajax_details' => 'details_nonce'
		];

		foreach ( $ajax_actions as $action => $nonce_field ) {
			$nonce_key = $action . '_nonce_key';
			if ( ! empty( $field[ $nonce_key ] ) ) {
				$field[ $nonce_field ] = wp_create_nonce( $field[ $nonce_key ] );
			}
		}

		// Special handling for ajax_select fields
		if ( $type === 'ajax_select' ) {
			// Map ajax_search to ajax for compatibility
			if ( ! empty( $field['ajax_search'] ) && empty( $field['ajax'] ) ) {
				$field['ajax'] = $field['ajax_search'];
			}

			// Resolve value if not already set
			if ( ! isset( $field['value'] ) && $data ) {
				$field['value'] = Components::resolve_value( $field_key, $data );
			}

			// Load options if we have a value but no options
			if ( ! empty( $field['value'] ) && empty( $field['options'] ) ) {
				if ( ! empty( $field['options_callback'] ) && is_callable( $field['options_callback'] ) ) {
					$field['options'] = call_user_func( $field['options_callback'], $field['value'], $data );
				}
			}
		}

		return $field;
	}

	/**
	 * Process field dependencies for conditional display
	 *
	 * Adds data attributes to fields that have dependencies configured.
	 * Supports three dependency patterns:
	 * - Simple truthy check: 'depends' => 'field_name'
	 * - Value match: 'depends' => ['field' => 'name', 'value' => 'x']
	 * - Contains check: 'depends' => ['field' => 'name', 'contains' => 'x']
	 *
	 * @param array  $field     Field configuration
	 * @param string $field_key Field identifier
	 *
	 * @return array Modified field configuration with dependency data
	 * @since  12.1.0
	 * @access private
	 */
	private function process_field_dependencies( array $field, string $field_key ): array {
		if ( ! isset( $field['depends'] ) ) {
			return $field;
		}

		$depends = $field['depends'];

		// Build dependency data attribute
		$dependency_data = null;

		if ( is_string( $depends ) ) {
			// Simple truthy check
			$dependency_data = $depends;
		} elseif ( is_array( $depends ) ) {
			// Complex dependency
			if ( isset( $depends['field'] ) ) {
				$dependency_data = [
					'field' => $depends['field']
				];

				if ( isset( $depends['value'] ) ) {
					$dependency_data['value'] = $depends['value'];
				} elseif ( isset( $depends['contains'] ) ) {
					$dependency_data['contains'] = $depends['contains'];
				}
			}
		}

		// Add wrapper attributes for the field
		if ( $dependency_data ) {
			if ( ! isset( $field['wrapper_attrs'] ) ) {
				$field['wrapper_attrs'] = [];
			}

			// Add data-depends attribute - properly encode if array
			if ( is_array( $dependency_data ) ) {
				$field['wrapper_attrs']['data-depends'] = htmlspecialchars( wp_json_encode( $dependency_data ), ENT_QUOTES, 'UTF-8' );
			} else {
				$field['wrapper_attrs']['data-depends'] = $dependency_data;
			}

			// Add unique ID if not present
			if ( empty( $field['wrapper_attrs']['id'] ) ) {
				$field['wrapper_attrs']['id'] = 'field-' . sanitize_key( $field_key );
			}

			// Start hidden if dependency exists (JS will show if condition met)
			$field['wrapper_attrs']['style'] = 'display: none;';

			// Add class for styling
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
	 * Detect and register required components from configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function detect_components( array $config ): void {
		foreach ( $config['fields'] as $field ) {
			$type = $field['type'] ?? 'text';

			// Get asset from Components registry
			if ( $asset = Components::get_asset( $type ) ) {
				$this->components[] = $asset;
			}
		}

		$this->components = array_unique( $this->components );
	}

	// =========================================================================
	// ASSET MANAGEMENT
	// =========================================================================

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook
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
	 * Determine if assets should be enqueued
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return bool True if assets should load
	 * @since 1.0.0
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		if ( ! empty( $this->admin_pages ) ) {
			return in_array( $hook_suffix, $this->admin_pages, true );
		}

		$default_pages = [
			'index.php',
			'edit.php',
			'post.php',
			'post-new.php',
			'users.php',
			'user-edit.php',
			'profile.php',
			'options-general.php',
			'tools.php',
		];

		if ( str_starts_with( $hook_suffix, 'toplevel_page_' ) ||
		     str_starts_with( $hook_suffix, 'page_' ) ) {
			return true;
		}

		return in_array( $hook_suffix, $default_pages, true );
	}

	/**
	 * Enqueue required assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		Assets::enqueue();

		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		$this->assets_enqueued = true;
	}

	// =========================================================================
	// TRIGGER GENERATION (BUTTONS & LINKS)
	// =========================================================================

	/**
	 * Render a trigger button
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return void Outputs HTML
	 * @since 1.0.0
	 */
	public function button( string $flyout_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $flyout_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return string Button HTML or empty string if unauthorized
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
	 * Create a trigger link
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param string $text      Link text
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Additional link arguments
	 *
	 * @return string Link HTML or empty string if unauthorized
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
	 * Build trigger element attributes
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes
	 * @param string $class     Additional CSS classes
	 *
	 * @return array Attributes array
	 * @since 1.0.0
	 */
	private function build_trigger_attributes( string $flyout_id, array $data, string $class = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout'         => $flyout_id,
			'data-flyout-nonce'   => wp_create_nonce( 'wp_flyout_' . $this->prefix . '_' . $flyout_id ),
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
	 * Get all registered flyouts
	 *
	 * @return array Flyout configurations
	 * @since 1.0.0
	 */
	public function get_flyouts(): array {
		return $this->flyouts;
	}

	/**
	 * Check if flyout is registered
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if flyout exists
	 * @since 1.0.0
	 */
	public function has_flyout( string $flyout_id ): bool {
		return isset( $this->flyouts[ $flyout_id ] );
	}

	/**
	 * Get manager prefix
	 *
	 * @return string Manager prefix
	 * @since 1.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

	// =========================================================================
	// PRIVATE UTILITY METHODS
	// =========================================================================

	/**
	 * Check if current user can access flyout
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if user has required capability
	 * @since 1.0.0
	 */
	private function can_access( string $flyout_id ): bool {
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			return false;
		}

		$config = $this->flyouts[ $flyout_id ];

		return current_user_can( $config['capability'] );
	}

}