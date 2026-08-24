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

use ArrayPress\RegisterFlyouts\Utils\Runtime;

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
	public static function rest_namespace(): string {
		return Runtime::rest_namespace();
	}

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
		register_rest_route( self::rest_namespace(), '/load', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_load' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => self::get_common_args(),
		] );

		// Save flyout form data.
		register_rest_route( self::rest_namespace(), '/save', [
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
		register_rest_route( self::rest_namespace(), '/delete', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_delete' ],
			'permission_callback' => [ __CLASS__, 'check_permission' ],
			'args'                => self::get_common_args(),
		] );

		// Action button/menu callbacks.
		register_rest_route( self::rest_namespace(), '/action', [
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
		$capability = apply_filters( Runtime::hook( 'rest_capability' ), $capability, $manager_prefix, $flyout_id, $request );

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
				/* translators: %s: flyout manager prefix */
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
				/* translators: %s: flyout id */
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
			$data = self::run( $config['load'], 'load', $item_id );

			if ( is_wp_error( $data ) ) {
				return $data;
			}
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

		// Sanitized by the field set, so every value is coerced by its own
		// type — the same coercion the same field gets on a settings page or
		// in a metabox — and a key the flyout does not declare is dropped.
		$sanitized = $manager->sanitize( $config['fields'], (array) $form_data );

		$sanitized = apply_filters( Runtime::hook( 'before_save' ), $sanitized, $config, $manager->get_prefix() );

		// Run validation callback if provided.
		if ( ! empty( $config['validate'] ) && is_callable( $config['validate'] ) ) {
			$validation = self::run( $config['validate'], 'validate', $sanitized );

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

		$result = self::run( $config['save'], 'save', $id, $sanitized );

		do_action( Runtime::hook( 'after_save' ), $result, $id, $sanitized, $config, $manager->get_prefix() );

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

		$item_id = apply_filters( Runtime::hook( 'before_delete' ), $item_id, $config, $manager->get_prefix() );

		$result = self::run( $config['delete'], 'delete', $item_id );

		do_action( Runtime::hook( 'after_delete' ), $result, $item_id, $config, $manager->get_prefix() );

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

		// Find the action callback within the fields that declare one.
		$callback = self::find_action_callback( $config['fields'], $action_key );

		if ( ! $callback ) {
			return new WP_Error(
				'flyout_action_not_found',
				/* translators: %s: action key */
				sprintf( __( 'Action "%s" not found.', 'arraypress' ), $action_key ),
				[ 'status' => 404 ]
			);
		}

		// Action callbacks receive all request params.
		$params               = $request->get_json_params();
		$params['id']         = $item_id;
		$params['action_key'] = $action_key;

		$result = self::run( $callback, $action_key, $params );

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

	/**
	 * Call a consumer's callback without letting it take the admin down.
	 *
	 * These are somebody else's functions, reached from a REST route, and a
	 * TypeError in one is a white screen on the whole page rather than a
	 * failed request — which is what a handler declared as `save( $data )`
	 * did, since every save callback here is called as `( $id, $data )`.
	 *
	 * Caught and returned as an error instead: the panel shows what went
	 * wrong and stays open. Only under WP_DEBUG is the message passed on,
	 * because a message from an exception is as likely to name a file path as
	 * to be useful to anybody.
	 *
	 * @param callable $callback The consumer's callback.
	 * @param string   $what     What it was for, for the message.
	 * @param mixed    ...$args  Arguments to pass.
	 *
	 * @return mixed|WP_Error
	 */
	private static function run( callable $callback, string $what, ...$args ) {
		try {
			return $callback( ...$args );
		} catch ( \Throwable $error ) {
			return new WP_Error(
				'flyout_callback_failed',
				defined( 'WP_DEBUG' ) && WP_DEBUG
					? sprintf(
						/* translators: 1: callback name, 2: error message */
						__( 'The "%1$s" callback failed: %2$s', 'arraypress' ),
						$what,
						$error->getMessage()
					)
					: sprintf(
						/* translators: %s: callback name */
						__( 'The "%s" callback failed.', 'arraypress' ),
						$what
					),
				[ 'status' => 500 ]
			);
		}
	}

	// =========================================================================
	// FIELD & ACTION RESOLUTION
	// =========================================================================


	/**
	 * Find an action callback by action key within the fields array.
	 *
	 * Searches action_buttons and notes field types for matching action keys.
	 *
	 * @param array  $fields     Flat fields array from flyout config.
	 * @param string $action_key Action key to find.
	 *
	 * @return callable|null Callback or null if not found.
	 */
	private static function find_action_callback( array $fields, string $action_key ): ?callable {
		// An empty key matches nothing. `required` on the route argument only
		// rejects a *missing* parameter, so an empty string arrives here — and
		// used to match the first field declaring no action at all, whose
		// `callback` on a searching field is a search rather than an action.
		if ( '' === $action_key ) {
			return null;
		}

		foreach ( $fields as $field ) {
			$type = $field['type'] ?? '';

			// Direct callback on field (e.g. refund_form with action + callback).
			if ( ! empty( $field['action'] ) && ( $field['action'] ?? '' ) === $action_key
				&& ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
				return $field['callback'];
			}

			// Convention-based: {action_key}_callback (e.g. add_callback, delete_callback).
			$callback_key = $action_key . '_callback';
			if ( ! empty( $field[ $callback_key ] ) && is_callable( $field[ $callback_key ] ) ) {
				return $field[ $callback_key ];
			}

			// Action buttons: search within the buttons array.
			if ( 'action_buttons' !== $type ) {
				continue;
			}

			$items = $field['buttons'] ?? [];

			foreach ( $items as $item ) {
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
