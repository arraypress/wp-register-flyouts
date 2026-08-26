<?php
/**
 * Every registered component renders.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Components;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A smoke test over the whole registry.
 *
 * It exists because several components had never been rendered by any test
 * at all. RefundForm called esc_html_e() and PriceSummary called a currency
 * helper from a library that had been deleted; both are fatal the moment the
 * panel is opened, and both suites were green. The gap was not that the
 * assertions were weak — it was that the code never ran.
 *
 * Driven off Components::all(), so a component added later is covered
 * without anybody remembering to add it here.
 */
final class RenderEveryComponentTest extends TestCase {

	/**
	 * Enough configuration for each component to render something.
	 *
	 * Deliberately minimal: the point is to execute the render path, not to
	 * assert what it produces. Where a component needs a value to get past
	 * an early return — a refund form with nothing left to refund renders a
	 * different, much shorter branch — it is given one.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function config_for( string $type ): array {
		$configs = [
			'stats'          => [ 'stats' => [ [ 'label' => 'Orders', 'value' => '12' ] ] ],
			'alert'          => [ 'message' => 'Something happened' ],
			'header'         => [ 'title' => 'An order' ],
			'timeline'       => [ 'items' => [ [ 'title' => 'Created', 'date' => '2026-01-01' ] ] ],
			'empty_state'    => [ 'title' => 'Nothing here' ],
			'action_buttons' => [ 'buttons' => [ [ 'label' => 'Save' ] ] ],
			'notes'          => [ 'items' => [ [ 'content' => 'A note' ] ] ],
			'line_items'     => [
				'items'    => [ [ 'id' => 1, 'name' => 'A thing', 'quantity' => 2, 'price' => 99.00 ] ],
				'currency' => 'USD',
			],
			'refund_form'    => [ 'amount_paid' => 159.84, 'amount_refunded' => 0, 'currency' => 'USD' ],
			'accordion'      => [ 'items' => [ [ 'title' => 'A section', 'content' => 'Body' ] ] ],
			'data_table'     => [
				'columns' => [ 'name' => 'Name' ],
				'data'    => [ [ 'name' => 'A row' ] ],
			],
			'info_grid'      => [ 'items' => [ [ 'label' => 'Email', 'value' => 'a@example.com' ] ] ],
			'payment_method' => [ 'payment_method' => 'card', 'payment_brand' => 'visa' ],
			'price_summary'  => [
				'subtotal' => 148.00,
				'discount' => 14.80,
				'tax'      => 26.64,
				'total'    => 159.84,
				'currency' => 'USD',
			],
			'form_field'     => [ 'name' => 'title', 'label' => 'Title' ],
		];

		return $configs[ $type ] ?? [];
	}

	/**
	 * Every type in the registry.
	 *
	 * @return array<string, array{0: string, 1: class-string}>
	 */
	public static function components(): array {
		$cases = [];

		foreach ( Components::all() as $type => $config ) {
			$cases[ $type ] = [ $type, $config['class'] ];
		}

		return $cases;
	}

	/**
	 * The component renders without reaching for anything undefined.
	 *
	 * No output buffer is opened here on purpose. A component that starts
	 * one and returns without closing it leaves the buffer on the stack,
	 * and wrapping the call would hide exactly that.
	 *
	 * @param string $type  Component type.
	 * @param string $class Component class.
	 */
	#[DataProvider( 'components' )]
	public function test_it_renders( string $type, string $class ): void {
		$depth = ob_get_level();

		$component = new $class( self::config_for( $type ) );
		$markup    = $component->render();

		$this->assertIsString( $markup, $type . ' did not return markup' );
		$this->assertSame( $depth, ob_get_level(), $type . ' left an output buffer open' );
	}
}
