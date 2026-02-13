<?php
/**
 * Flyout REST API
 *
 * Registers and handles REST API routes for flyout operations.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class RestApi
 *
 * Handles all REST API routes for flyout load, save, delete, search, and action operations.
 * Routes are registered once globally. Each request resolves the correct Manager and flyout
 * configuration via the Registry singleton.
 */
class RestApi {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'wp-flyout/v1';

	/**
	 * Whether routes have been registered.
	 *
	 * @var bool
	 */
	private static bool $routes_registered = false;

	/**
	 * Register REST API routes. Safe to call multiple times — only registers once.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$routes_registered ) {
			return;
		}

		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );

		self::$routes_registered = true;
	}

	/**
	 * Register all REST routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {

		// Load flyout HTML.
		register_rest_route( self::NAMESPACE, '/load', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_load' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => self::get_common_args(),
		] );

		// Save flyout form data.
		register_rest_route( self::NAMESPACE, '/save', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_save' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => array_merge( self::get_common_args(), [
				'form_data' => [
					'required' => true,
					'type'     => 'object',
				],
			] ),
		] );

		// Delete record.
		register_rest_route( self::NAMESPACE, '/delete', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_delete' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => self::get_common_args(),
		] );

		// Search for ajax_select fields.
		register_rest_route( self::NAMESPACE, '/search', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'handle_search' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => array_merge( self::get_common_args(), [
				'field_key' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'term'      => [
					'required' => false,
					'type'     => 'string',
					'default'  => '',
				],
				'include'   => [
					'required' => false,
					'type'     => 'string',
					'default'  => '',
				],
			] ),
		] );

		// Action button/menu callbacks.
		register_rest_route( self::NAMESPACE, '/action', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_action' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => array_merge( self::get_common_args(), [
				'action_key' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
			] ),
		] );
	}

	/**
	 * Common args shared by all endpoints.
	 *
	 * @return array
	 */
	private static function get_common_args(): array {
		return [
			'manager' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			],
			'flyout'  => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			],
			'item_id' => [
				'required' => false,
				'type'     => [ 'integer', 'string' ],
				'default'  => 0,
			],
		];
	}

	/**
	 * Permission check for all endpoints.
	 *
	 * Resolves the flyout's declared capability, falling back to manage_options.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return bool|WP_Error
	 */
	public static function check_permission( WP_REST_Request $request ) {
		$manager_prefix = $request->get_param( 'manager' );
		$flyout_id      = $request->get_param( 'flyout' );

		// Try to resolve the flyout's specific capability.
		$capability = 'manage_options';
		$manager    = Registry::instance()->get( $manager_prefix );

		if ( $manager ) {
			$config = $manager->get_flyout( $flyout_id );
			if ( $config && ! empty( $config['capability'] ) ) {
				$capability = $config['capability'];
			}
		}

		/**
		 * Filter the required capability for flyout REST endpoints.
		 *
		 * @param string          $capability     Required capability.
		 * @param string          $manager_prefix The manager prefix.
		 * @param string          $flyout_id      The flyout identifier.
		 * @param WP_REST_Request $request        Full request object.
		 */
		$capability = apply_filters( 'wp_flyout_rest_capability', $capability, $manager_prefix, $flyout_id, $request );

		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'arraypress' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	// =========================================================================
	// RESOLUTION HELPERS
	// =========================================================================

	/**
	 * Resolve a Manager instance from the request.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return Manager|WP_Error
	 */
	private static function resolve_manager( WP_REST_Request $request ) {
		$prefix  = $request->get_param( 'manager' );
		$manager = Registry::instance()->get( $prefix );

		if ( ! $manager ) {
			return new WP_Error(
				'flyout_manager_not_found',
				sprintf( __( 'Flyout manager "%s" not found.', 'arraypress' ), $prefix ),
				[ 'status' => 404 ]
			);
		}

		return $manager;
	}

	/**
	 * Resolve a flyout configuration from the request.
	 *
	 * @param Manager         $manager Manager instance.
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return array|WP_Error Flyout config array or error.
	 */
	private static function resolve_flyout( Manager $manager, WP_REST_Request $request ) {
		$flyout_id = $request->get_param( 'flyout' );
		$config    = $manager->get_flyout( $flyout_id );

		if ( ! $config ) {
			return new WP_Error(
				'flyout_not_found',
				sprintf( __( 'Flyout "%s" not found.', 'arraypress' ), $flyout_id ),
				[ 'status' => 404 ]
			);
		}

		return $config;
	}

	// =========================================================================
	// ENDPOINT HANDLERS
	// =========================================================================

	/**
	 * Handle flyout load request.
	 *
	 * Calls the flyout's load callback, builds the flyout HTML, and returns it.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_load( WP_REST_Request $request ) {
		$manager = self::resolve_manager( $request );
		if ( is_wp_error( $manager ) ) {
			return $manager;
		}

		$config = self::resolve_flyout( $manager, $request );
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$item_id = $request->get_param( 'item_id' );

		// Call the load callback to get the data object.
		$data = null;
		if ( ! empty( $config['load'] ) && is_callable( $config['load'] ) ) {
			$data = call_user_func( $config['load'], $item_id );
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( $data === false ) {
			return new WP_Error(
				'flyout_load_failed',
				__( 'Record not found.', 'arraypress' ),
				[ 'status' => 404 ]
			);
		}

		// Build the flyout HTML via the Manager.
		$flyout = $manager->build_flyout( $config, $data, $item_id );

		return new WP_REST_Response( [
			'success' => true,
			'html'    => $flyout->render(),
		] );
	}

	/**
	 * Handle flyout save request.
	 *
	 * Sanitizes form data and calls the flyout's save callback.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_save( WP_REST_Request $request ) {
		$manager = self::resolve_manager( $request );
		if ( is_wp_error( $manager ) ) {
			return $manager;
		}

		$config = self::resolve_flyout( $manager, $request );
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		if ( empty( $config['save'] ) || ! is_callable( $config['save'] ) ) {
			return new WP_Error(
				'flyout_save_not_configured',
				__( 'Save not configured for this flyout.', 'arraypress' ),
				[ 'status' => 500 ]
			);
		}

		$item_id   = $request->get_param( 'item_id' );
		$form_data = $request->get_param( 'form_data' );

		// Normalize and sanitize the form data using the flyout's field configuration.
		$normalized_fields = $manager->normalize_fields( $config['fields'] );
		$sanitized         = Sanitizer::sanitize_form_data( $form_data, $normalized_fields );

		$sanitized = apply_filters( 'wp_flyout_before_save', $sanitized, $config, $manager->get_prefix() );

		// Run validation callback if provided.
		if ( ! empty( $config['validate'] ) && is_callable( $config['validate'] ) ) {
			$validation = call_user_func( $config['validate'], $sanitized );

			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			if ( $validation === false ) {
				return new WP_Error(
					'flyout_validation_failed',
					__( 'Validation failed.', 'arraypress' ),
					[ 'status' => 422 ]
				);
			}
		}

		// Resolve the ID — may come from form data or request param.
		$id = $sanitized['id'] ?? $item_id;

		$result = call_user_func( $config['save'], $id, $sanitized );

		do_action( 'wp_flyout_after_save', $result, $id, $sanitized, $config, $manager->get_prefix() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $result === false ) {
			return new WP_Error(
				'flyout_save_failed',
				__( 'Save failed.', 'arraypress' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [
			'success' => true,
			'message' => __( 'Saved successfully.', 'arraypress' ),
		] );
	}

	/**
	 * Handle flyout delete request.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_delete( WP_REST_Request $request ) {
		$manager = self::resolve_manager( $request );
		if ( is_wp_error( $manager ) ) {
			return $manager;
		}

		$config = self::resolve_flyout( $manager, $request );
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		if ( empty( $config['delete'] ) || ! is_callable( $config['delete'] ) ) {
			return new WP_Error(
				'flyout_delete_not_configured',
				__( 'Delete not configured for this flyout.', 'arraypress' ),
				[ 'status' => 500 ]
			);
		}

		$item_id = $request->get_param( 'item_id' );

		$item_id = apply_filters( 'wp_flyout_before_delete', $item_id, $config, $manager->get_prefix() );

		$result = call_user_func( $config['delete'], $item_id );

		do_action( 'wp_flyout_after_delete', $result, $item_id, $config, $manager->get_prefix() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $result === false ) {
			return new WP_Error(
				'flyout_delete_failed',
				__( 'Delete failed.', 'arraypress' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [
			'success' => true,
			'message' => __( 'Deleted successfully.', 'arraypress' ),
		] );
	}

	/**
	 * Handle ajax_select search request.
	 *
	 * Supports both search (user typing) and hydration (resolving saved IDs to labels).
	 * Uses the unified callback pattern: callback( string $search, ?array $ids )
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_search( WP_REST_Request $request ) {
		$manager = self::resolve_manager( $request );
		if ( is_wp_error( $manager ) ) {
			return $manager;
		}

		$config = self::resolve_flyout( $manager, $request );
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$field_key = $request->get_param( 'field_key' );
		$term      = sanitize_text_field( $request->get_param( 'term' ) );
		$include   = $request->get_param( 'include' );

		// Find the field in the flat fields array.
		$field = self::find_field( $config['fields'], $field_key );

		if ( ! $field ) {
			return new WP_Error(
				'flyout_field_not_found',
				sprintf( __( 'Field "%s" not found.', 'arraypress' ), $field_key ),
				[ 'status' => 404 ]
			);
		}

		// Unified callback (preferred).
		if ( ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
			$ids = null;
			if ( ! empty( $include ) ) {
				$raw_ids = is_string( $include ) ? explode( ',', $include ) : (array) $include;
				$ids     = array_map( 'absint', array_filter( $raw_ids ) );
				$term    = '';
			}

			$result = call_user_func( $field['callback'], $term, $ids );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Normalize to Select2 format: [ { id, text }, ... ]
			$formatted = [];
			if ( is_array( $result ) ) {
				foreach ( $result as $value => $label ) {
					$formatted[] = [
						'id'   => (string) $value,
						'text' => (string) $label,
					];
				}
			}

			return new WP_REST_Response( [
				'success' => true,
				'results' => $formatted,
			] );
		}

		// Legacy search_callback fallback.
		if ( ! empty( $field['search_callback'] ) && is_callable( $field['search_callback'] ) ) {
			$result = call_user_func( $field['search_callback'], $term );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return new WP_REST_Response( [
				'success' => true,
				'results' => $result,
			] );
		}

		return new WP_Error(
			'flyout_search_no_callback',
			sprintf( __( 'No search callback defined for field "%s".', 'arraypress' ), $field_key ),
			[ 'status' => 500 ]
		);
	}

	/**
	 * Handle action button/menu callback.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_action( WP_REST_Request $request ) {
		$manager = self::resolve_manager( $request );
		if ( is_wp_error( $manager ) ) {
			return $manager;
		}

		$config = self::resolve_flyout( $manager, $request );
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$action_key = $request->get_param( 'action_key' );
		$item_id    = $request->get_param( 'item_id' );

		// Find the action callback within action_buttons or action_menu fields.
		$callback = self::find_action_callback( $config['fields'], $action_key );

		if ( ! $callback ) {
			return new WP_Error(
				'flyout_action_not_found',
				sprintf( __( 'Action "%s" not found.', 'arraypress' ), $action_key ),
				[ 'status' => 404 ]
			);
		}

		// Action callbacks receive all request params.
		$params               = $request->get_json_params();
		$params['id']         = $item_id;
		$params['action_key'] = $action_key;

		$result = call_user_func( $callback, $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Allow callbacks to return custom response data.
		if ( is_array( $result ) ) {
			return new WP_REST_Response( array_merge( [ 'success' => true ], $result ) );
		}

		return new WP_REST_Response( [
			'success' => true,
			'message' => __( 'Action completed successfully.', 'arraypress' ),
		] );
	}

	// =========================================================================
	// FIELD & ACTION RESOLUTION
	// =========================================================================

	/**
	 * Find a field configuration by key within the flat fields array.
	 *
	 * @param array  $fields    Flat fields array from flyout config.
	 * @param string $field_key Field key to find.
	 *
	 * @return array|null Field config or null if not found.
	 */
	private static function find_field( array $fields, string $field_key ): ?array {
		// Direct key match.
		if ( isset( $fields[ $field_key ] ) ) {
			return $fields[ $field_key ];
		}

		// Match by 'name' attribute (field key may differ from name).
		foreach ( $fields as $key => $field ) {
			if ( ( $field['name'] ?? $key ) === $field_key ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Find an action callback by action key within the fields array.
	 *
	 * Searches action_buttons, action_menu, and notes field types for matching action keys.
	 *
	 * @param array  $fields     Flat fields array from flyout config.
	 * @param string $action_key Action key to find.
	 *
	 * @return callable|null Callback or null if not found.
	 */
	private static function find_action_callback( array $fields, string $action_key ): ?callable {
		foreach ( $fields as $field ) {
			$type = $field['type'] ?? '';

			// Notes component: match action key to add/delete callbacks.
			if ( $type === 'notes' ) {
				$add_action    = $field['add_action'] ?? 'add_note';
				$delete_action = $field['delete_action'] ?? 'delete_note';

				if ( $action_key === $add_action && ! empty( $field['add_callback'] ) && is_callable( $field['add_callback'] ) ) {
					return $field['add_callback'];
				}

				if ( $action_key === $delete_action && ! empty( $field['delete_callback'] ) && is_callable( $field['delete_callback'] ) ) {
					return $field['delete_callback'];
				}

				continue;
			}

			// Determine which key holds the action items.
			$items = [];
			if ( $type === 'action_buttons' ) {
				$items = $field['buttons'] ?? [];
			} elseif ( $type === 'action_menu' ) {
				$items = $field['items'] ?? [];
			} else {
				continue;
			}

			foreach ( $items as $item ) {
				// Skip separators.
				if ( isset( $item['type'] ) && $item['type'] === 'separator' ) {
					continue;
				}

				$action = $item['action'] ?? '';
				if ( $action === $action_key && ! empty( $item['callback'] ) && is_callable( $item['callback'] ) ) {
					return $item['callback'];
				}
			}
		}

		return null;
	}

}