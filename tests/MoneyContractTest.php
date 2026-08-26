<?php
/**
 * Amounts are major units, everywhere.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Components\LineItems;
use ArrayPress\RegisterFlyouts\Components\PriceSummary;
use ArrayPress\RegisterFlyouts\Components\RefundForm;
use ArrayPress\RegisterFlyouts\Utils\Amount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every component here takes amounts in major units.
 *
 * It was not always one contract. LineItems and PriceSummary shared a
 * heuristic that read a fractional value as major units and a whole number
 * as minor ones, and RefundForm took minor units outright — so a panel
 * holding all three needed its amounts written three different ways, and
 * the demo did exactly that.
 *
 * The heuristic's failure was visible on screen: a $148.00 subtotal rendered
 * as "$1.48" directly above a 14.80 discount that rendered correctly, because
 * 148 has no fractional part and 14.80 does. Every round amount was divided
 * by a hundred.
 */
final class MoneyContractTest extends TestCase {

	/**
	 * The conversion itself, including the amounts from that screen.
	 *
	 * @return array<string, array{0: mixed, 1: string, 2: int}>
	 */
	public static function amounts(): array {
		return [
			// The four that shared one panel. Under the old rule the first
			// gave 148 and the last three were right, which is what made it
			// look like a display quirk rather than a unit bug.
			'a round subtotal'          => [ 148.00, 'USD', 14800 ],
			'a fractional discount'     => [ 14.80, 'USD', 1480 ],
			'a fractional tax'          => [ 26.64, 'USD', 2664 ],
			'a fractional total'        => [ 159.84, 'USD', 15984 ],

			// Round amounts are the ones a person types, and every one of
			// them was a hundred times too small.
			'a whole-dollar price'      => [ 99, 'USD', 9900 ],
			'a whole-dollar float'      => [ 99.00, 'USD', 9900 ],
			'a numeric string'          => [ '49.00', 'USD', 4900 ],

			// The exponent comes from the currency, not from a literal 100.
			'yen has no minor unit'     => [ 5000, 'JPY', 5000 ],
			'dinar has three'           => [ 1.5, 'KWD', 1500 ],

			// Half a cent rounds rather than truncating.
			'a third decimal place'     => [ 14.999, 'USD', 1500 ],
			'a negative amount'         => [ -14.80, 'USD', -1480 ],
			'zero'                      => [ 0, 'USD', 0 ],
			'nothing numeric at all'    => [ 'free', 'USD', 0 ],
		];
	}

	/**
	 * @param mixed  $amount   Amount in major units.
	 * @param string $currency ISO-4217 code.
	 * @param int    $expected Amount in minor units.
	 */
	#[DataProvider( 'amounts' )]
	public function test_major_units_convert( $amount, string $currency, int $expected ): void {
		$this->assertSame( $expected, Amount::minor( $amount, $currency ) );
	}

	/**
	 * The subtotal that rendered as $1.48 renders as $148.00.
	 */
	public function test_a_round_subtotal_is_not_divided_by_a_hundred(): void {
		$markup = ( new PriceSummary( [
			'subtotal' => 148.00,
			'discount' => 14.80,
			'tax'      => 26.64,
			'total'    => 159.84,
			'currency' => 'USD',
		] ) )->render();

		$this->assertStringContainsString( '$148.00', $markup );
		$this->assertStringNotContainsString( '$1.48', $markup );

		// The lines that were already right stayed right.
		$this->assertStringContainsString( '$14.80', $markup );
		$this->assertStringContainsString( '$26.64', $markup );
		$this->assertStringContainsString( '$159.84', $markup );
	}

	/**
	 * Net is the difference of two converted amounts, not of two raw ones.
	 *
	 * Total went through the conversion and refunded did not, so a panel
	 * showing a refund printed a net figure computed from a mix of the two.
	 */
	public function test_net_of_a_refund_is_consistent_with_the_total(): void {
		$markup = ( new PriceSummary( [
			'total'    => 159.84,
			'refunded' => 59.84,
			'currency' => 'USD',
		] ) )->render();

		$this->assertStringContainsString( '$159.84', $markup );
		$this->assertStringContainsString( '-$59.84', $markup );
		$this->assertStringContainsString( '$100.00', $markup );
	}

	/**
	 * A line item's price is money, so a fractional one survives.
	 *
	 * It was cast with (int) before anything formatted it, which turned
	 * 99.99 into 99.
	 */
	public function test_a_fractional_line_item_price_is_not_truncated(): void {
		$markup = ( new LineItems( [
			'items'    => [ [ 'id' => 1, 'name' => 'A thing', 'quantity' => 3, 'price' => 99.99 ] ],
			'currency' => 'USD',
		] ) )->render();

		$this->assertStringContainsString( '$99.99', $markup );
		$this->assertStringContainsString( '$299.97', $markup );
	}

	/**
	 * The script is told the scale rather than assuming a hundred.
	 *
	 * data-price is major units on both sides now; the JS reads data-decimals
	 * to convert, so a yen panel does not ask for a refund a hundred times
	 * the size of the payment.
	 */
	public function test_a_refund_form_publishes_the_currencys_exponent(): void {
		$dollars = ( new RefundForm( [ 'amount_paid' => 159.84, 'currency' => 'USD' ] ) )->render();
		$this->assertStringContainsString( 'data-decimals="2"', $dollars );
		$this->assertStringContainsString( 'data-refundable="15984"', $dollars );

		$yen = ( new RefundForm( [ 'amount_paid' => 5000, 'currency' => 'JPY' ] ) )->render();
		$this->assertStringContainsString( 'data-decimals="0"', $yen );
		$this->assertStringContainsString( 'data-refundable="5000"', $yen );
	}

	/**
	 * Every figure on the panel is the same amount of money.
	 *
	 * The refundable balance is computed in minor units so the subtraction
	 * is exact, and the amounts around it arrive in major units. Handing the
	 * computed one to the major-unit formatter converted it a second time,
	 * and a $159.84 payment offered a refund of $15,984.00 — on the button,
	 * in the summary, and nowhere in the data attributes, which is why
	 * asserting those alone did not catch it.
	 */
	public function test_every_figure_on_a_refund_panel_agrees(): void {
		$markup = ( new RefundForm( [
			'amount_paid'     => 159.84,
			'amount_refunded' => 0,
			'currency'        => 'USD',
		] ) )->render();

		$this->assertStringNotContainsString( '$15,984.00', $markup );

		// Paid, available, and the button all say the same thing.
		preg_match_all( '/\$[\d,]+\.\d\d/', $markup, $found );

		$this->assertNotEmpty( $found[0] );
		$this->assertSame( [ '$159.84' ], array_values( array_unique( $found[0] ) ) );

		// And the input is pre-filled with the same figure, undecorated.
		$this->assertStringContainsString( 'value="159.84"', $markup );
	}

	/**
	 * A partial refund leaves the difference available.
	 */
	public function test_a_partial_refund_leaves_the_difference(): void {
		$markup = ( new RefundForm( [
			'amount_paid'     => 159.84,
			'amount_refunded' => 59.84,
			'currency'        => 'USD',
		] ) )->render();

		$this->assertStringContainsString( '$159.84', $markup );
		$this->assertStringContainsString( '$59.84', $markup );
		$this->assertStringContainsString( '$100.00', $markup );
		$this->assertStringContainsString( 'value="100.00"', $markup );
	}

	/**
	 * A zero-decimal currency is not given decimals it does not have.
	 */
	public function test_a_yen_panel_asks_for_whole_yen(): void {
		$markup = ( new RefundForm( [
			'amount_paid' => 5000,
			'currency'    => 'JPY',
		] ) )->render();

		$this->assertStringContainsString( 'value="5000"', $markup );
		$this->assertStringNotContainsString( 'value="5000.00"', $markup );
	}

	/**
	 * A refund of everything paid leaves nothing refundable.
	 */
	public function test_a_fully_refunded_payment_has_nothing_left(): void {
		$markup = ( new RefundForm( [
			'amount_paid'     => 159.84,
			'amount_refunded' => 159.84,
			'currency'        => 'USD',
		] ) )->render();

		$this->assertStringNotContainsString( 'refund-amount-input', $markup );
	}
}
