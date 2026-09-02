<?php
/**
 * REST action route tests.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Manager;
use ArrayPress\RegisterFlyouts\RestApi;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

/**
 * What the `/action` route will run, and for whom.
 *
 * It used to resolve a handler by suffix: any field carrying
 * `{action_key}_callback` matched, so `action_key=search` ran a picker's
 * `search_callback` with the request body as its search term and
 * `sanitize` ran a `sanitize_callback` the same way. And the only capability
 * it checked was the flyout's, so a button that asked for more with
 * `action_capability` was asking nobody.
 */
final class RestActionTest extends TestCase {

	/**
	 * What the callbacks were handed, if anything.
	 *
	 * @var array<string, mixed>
	 */
	public static array $ran = [];

	/**
	 * Everybody can do everything, and nothing has run.
	 */
	protected function setUp(): void {
		$GLOBALS['wf_caps'] = null;
		self::$ran          = [];
	}

	/**
	 * A search callback, which must never be reached from here.
	 *
	 * @param mixed $term Whatever it was handed.
	 *
	 * @return array<int, string>
	 */
	public static function search( $term ): array {
		self::$ran['search'] = $term;

		return [];
	}

	/**
	 * An action's handler.
	 *
	 * @param array<string, mixed> $params The request.
	 *
	 * @return array<string, mixed>
	 */
	public static function refund( array $params ): array {
		self::$ran['refund'] = $params;

		return [ 'message' => 'Refunded.' ];
	}

	/**
	 * A notes handler.
	 *
	 * @param array<string, mixed> $params The request.
	 *
	 * @return array<string, mixed>
	 */
	public static function add_note( array $params ): array {
		self::$ran['add_note'] = $params;

		return [ 'note' => [ 'id' => 7 ] ];
	}

	/**
	 * A flyout with a searching field, a refund form and a notes panel.
	 *
	 * @param string $prefix The manager's prefix.
	 *
	 * @return Manager
	 */
	private function manager( string $prefix ): Manager {
		$manager = new Manager( $prefix );

		$manager->register_flyout(
			'order',
			[
				'title'      => 'Order',
				'capability' => 'edit_shop_orders',
				'fields'     => [
					'customer' => [
						'type'     => 'ajax_select',
						'callback' => [ self::class, 'search' ],
					],
					'refund'   => [
						'type'              => 'refund_form',
						'action'            => 'issue_refund',
						'action_capability' => 'refund_shop_orders',
						'callback'          => [ self::class, 'refund' ],
					],
					'notes'    => [
						'type'              => 'notes',
						'add_action'        => 'add_note',
						'add_note_callback' => [ self::class, 'add_note' ],
					],
				],
			]
		);

		return $manager;
	}

	/**
	 * Press one action.
	 *
	 * @param string $prefix     The manager.
	 * @param string $action_key The action.
	 *
	 * @return mixed A response or an error.
	 */
	private function press( string $prefix, string $action_key ) {
		$request = new WP_REST_Request( 'POST', '/action' );
		$request->set_param( 'manager', $prefix );
		$request->set_param( 'flyout', 'order' );
		$request->set_param( 'action_key', $action_key );
		$request->set_param( 'item_id', 12 );

		return RestApi::handle_action( $request );
	}

	/**
	 * A search callback is not an action, whatever the key says.
	 *
	 * The whole finding. `search` is not something the flyout declared as an
	 * action, so it is not one — however many fields carry a callable of
	 * that name.
	 */
	public function test_a_search_callback_is_not_reachable_as_an_action(): void {
		$this->manager( 'ra_search' );

		$result = $this->press( 'ra_search', 'search' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'flyout_action_not_found', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'search', self::$ran, 'The search callback ran as an action.' );
	}

	/**
	 * Nor is anything else a field happens to spell `_callback`.
	 */
	public function test_other_suffixed_callbacks_are_not_actions_either(): void {
		$this->manager( 'ra_suffix' );

		foreach ( [ 'data', 'sanitize', 'add_note_callback' ] as $key ) {
			$result = $this->press( 'ra_suffix', $key );

			$this->assertInstanceOf( \WP_Error::class, $result, sprintf( '"%s" resolved to an action.', $key ) );
		}

		$this->assertSame( [], self::$ran );
	}

	/**
	 * A declared action runs, and is handed the request.
	 */
	public function test_a_declared_action_runs(): void {
		$this->manager( 'ra_runs' );

		$result = $this->press( 'ra_runs', 'issue_refund' );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 'Refunded.', $result->get_data()['message'] ?? null );
		$this->assertSame( 12, self::$ran['refund']['id'] ?? null );
		$this->assertSame( 'issue_refund', self::$ran['refund']['action_key'] ?? null );
	}

	/**
	 * A component's named action resolves under the key it was given.
	 *
	 * Notes calls its add action whatever the consumer says — `add_note`
	 * here — and carries the handler under `{key}_callback`. That is a
	 * declaration, and the only shape of it this route still honours.
	 */
	public function test_a_components_named_action_resolves(): void {
		$this->manager( 'ra_notes' );

		$result = $this->press( 'ra_notes', 'add_note' );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 7, $result->get_data()['note']['id'] ?? null );
	}

	/**
	 * An action's own capability is enforced.
	 *
	 * The permission callback checks the flyout's. This person has that one
	 * and not the refund's, and the refund used to run anyway.
	 */
	public function test_an_action_capability_the_user_lacks_is_refused(): void {
		$this->manager( 'ra_forbidden' );

		$GLOBALS['wf_caps'] = [ 'edit_shop_orders' ];

		$result = $this->press( 'ra_forbidden', 'issue_refund' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] ?? null );
		$this->assertArrayNotHasKey( 'refund', self::$ran, 'The refund ran for somebody who may not issue one.' );
	}

	/**
	 * And with it, the action runs.
	 */
	public function test_an_action_capability_the_user_has_is_enough(): void {
		$this->manager( 'ra_allowed' );

		$GLOBALS['wf_caps'] = [ 'edit_shop_orders', 'refund_shop_orders' ];

		$this->assertInstanceOf( \WP_REST_Response::class, $this->press( 'ra_allowed', 'issue_refund' ) );
	}

	/**
	 * An action that names no capability asks for the flyout's.
	 *
	 * Not for a fixed one: a panel only a shop manager may open should not
	 * have a button anybody with `manage_options` may press, nor one that
	 * anybody with less may.
	 */
	public function test_an_action_without_a_capability_asks_for_the_flyouts(): void {
		$this->manager( 'ra_fallback' );

		$GLOBALS['wf_caps'] = [ 'manage_options' ];
		$result             = $this->press( 'ra_fallback', 'add_note' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );

		$GLOBALS['wf_caps'] = [ 'edit_shop_orders' ];

		$this->assertInstanceOf( \WP_REST_Response::class, $this->press( 'ra_fallback', 'add_note' ) );
	}

	/**
	 * An item id keeps its shape and loses its markup.
	 *
	 * Integers stay integers, so a callback comparing strictly against what
	 * it stored still matches; a string id is a line of text; anything that
	 * is neither is nothing.
	 */
	public function test_an_item_id_is_sanitized_without_being_retyped(): void {
		$this->assertSame( 12, RestApi::sanitize_item_id( 12 ) );
		$this->assertSame( 'lic_abc123', RestApi::sanitize_item_id( 'lic_abc123' ) );
		$this->assertStringNotContainsString( '<', (string) RestApi::sanitize_item_id( '<script>alert(1)</script>' ) );
		$this->assertSame( '', RestApi::sanitize_item_id( [ 'id' => 1 ] ) );
	}
}
