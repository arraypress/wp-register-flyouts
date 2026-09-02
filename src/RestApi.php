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
				'required'          => false,
				'type'              => [ 'integer', 'string' ],
				'default'           => 0,
				'sanitize_callback' => [ __CLASS__, 'sanitize_item_id' ],
			],
		];
	}

	/**
	 * Clean an item id without deciding what shape it is.
	 *
	 * Ids here are whatever the consumer's `load` and `save` deal in: a post
	 * id is an integer, a licence key or a UUID is a string, and the route
	 * accepts both. An integer is kept as one, so a callback comparing
	 * strictly against what it stored still matches; anything else is
	 * reduced to a line of text, which is the most a string id can be.
	 *
	 * What this does not do is decide whether the current user may touch
	 * the object the id names. It cannot: the library never sees the object.
	 * That is the consumer's check, in the callbacks or through the
	 * `wp_flyout_rest_capability_{prefix}` filter.
	 *
	 * @param mixed $value The submitted id.
	 *
	 * @return int|string
	 */
	public static function sanitize_item_id( $value ) {
		if ( is_int( $value ) ) {
			return $value;
		}

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
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
		$capability = apply_filters( "wp_flyout_rest_capability_{$manager_prefix}", $capability, $flyout_id, $request );

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

		$sanitized = apply_filters( 'wp_flyout_before_save_' . $manager->get_prefix(), $sanitized, $config );

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

		do_action( 'wp_flyout_after_save_' . $manager->get_prefix(), $result, $id, $sanitized, $config );

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

		$item_id = apply_filters( 'wp_flyout_before_delete_' . $manager->get_prefix(), $item_id, $config );

		$result = self::run( $config['delete'], 'delete', $item_id );

		do_action( 'wp_flyout_after_delete_' . $manager->get_prefix(), $result, $item_id, $config );

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

		$action_key = (string) $request->get_param( 'action_key' );
		$item_id    = $request->get_param( 'item_id' );

		// Only an action a field declares, by the key it declared it under.
		$action = self::find_action( $config, $action_key );

		if ( null === $action ) {
			return new WP_Error(
				'flyout_action_not_found',
				/* translators: %s: action key */
				sprintf( __( 'Action "%s" not found.', 'arraypress' ), $action_key ),
				[ 'status' => 404 ]
			);
		}

		// The action's own capability, on top of the flyout's. The
		// permission callback has already asked whether this person may open
		// the panel; a refund button on it may reasonably ask for more.
		if ( ! current_user_can( $action['capability'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'arraypress' ),
				[ 'status' => 403 ]
			);
		}

		// Action callbacks receive all request params.
		$params               = (array) $request->get_json_params();
		$params['id']         = $item_id;
		$params['action_key'] = $action_key;

		$result = self::run( $action['callback'], $action_key, $params );

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
	 * Resolve an action a flyout declares, with the capability it demands.
	 *
	 * @param array<string, mixed> $config     Flyout configuration.
	 * @param string               $action_key Action key to find.
	 *
	 * @return array{callback: callable, capability: string}|null Null when no field declares it.
	 */
	private static function find_action( array $config, string $action_key ): ?array {
		// An empty key matches nothing. `required` on the route argument only
		// rejects a *missing* parameter, so an empty string arrives here.
		if ( '' === $action_key ) {
			return null;
		}

		foreach ( (array) ( $config['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$actions = self::field_actions( $field );

			if ( ! isset( $actions[ $action_key ] ) ) {
				continue;
			}

			return [
				'callback'   => $actions[ $action_key ],

				// The same chain the kit's registration uses, ending at the
				// flyout's own capability rather than at a fixed one, so a
				// field that names nothing asks for what the panel asks for.
				'capability' => (string) (
					$field['action_capability']
					?? $field['capability']
					?? $config['capability']
					?? 'manage_options'
				),
			];
		}

		return null;
	}

	/**
	 * The actions one field declares, keyed as a request names them.
	 *
	 * Declared ones only. This used to accept `{action_key}_callback` on any
	 * field, which made every callable a field carries into an action:
	 * `action_key=search` ran a picker's `search_callback` with the raw
	 * request body as its term, and `sanitize` ran a `sanitize_callback` the
	 * same way. A field says which keys are actions -- a refund form's
	 * `action`, a notes component's `add_action` and `delete_action`, a line
	 * items component's `details_key`, each of an action button's `action`,
	 * and the named `actions` it registers with the kit -- and a key it did
	 * not declare is not one, whatever callable happens to share its name.
	 *
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return array<string, callable>
	 */
	private static function field_actions( array $field ): array {
		$type    = (string) ( $field['type'] ?? '' );
		$actions = [];

		// One action with one handler: the refund form.
		if ( ! empty( $field['action'] ) ) {
			$actions[ (string) $field['action'] ] = $field['callback'] ?? null;
		}

		// Components that name their action keys and carry each handler
		// under `{key}_callback`. Notes has defaults for its two, which the
		// component applies when it renders and so are applied here too.
		$declared = [
			'add_action'    => 'notes' === $type ? 'add' : '',
			'delete_action' => 'notes' === $type ? 'delete' : '',
			'details_key'   => '',
		];

		foreach ( $declared as $option => $default ) {
			$key = (string) ( $field[ $option ] ?? $default );

			if ( '' !== $key ) {
				$actions[ $key ] = $field[ $key . '_callback' ] ?? null;
			}
		}

		// The named handlers a field registers with the kit.
		foreach ( (array) ( $field['actions'] ?? [] ) as $name => $callback ) {
			$actions[ (string) $name ] = $callback;
		}

		if ( isset( $field['action_callback'] ) ) {
			$actions['run'] = $field['action_callback'];
		}

		// Action buttons, each with an action of its own.
		if ( 'action_buttons' === $type ) {
			foreach ( (array) ( $field['buttons'] ?? [] ) as $button ) {
				if ( ! is_array( $button ) || 'separator' === ( $button['type'] ?? '' ) || empty( $button['action'] ) ) {
					continue;
				}

				$actions[ (string) $button['action'] ] = $button['callback'] ?? null;
			}
		}

		return array_filter( $actions, 'is_callable' );
	}
}
