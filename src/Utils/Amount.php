<?php
/**
 * Amount Normalisation
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Utils;

use ArrayPress\Money\Currencies;
use ArrayPress\Money\Money;

/**
 * Class Amount
 *
 * Turns a caller's amount into the minor units `format_money()` wants.
 *
 * Every component here takes amounts in **major units** — 148.00 is one
 * hundred and forty-eight dollars, 5 is five dollars. That is a stated
 * contract rather than a guess, for a reason.
 *
 * What this replaced inferred the unit from the value itself:
 *
 *     fmod( $amount, 1.0 ) !== 0.0 ? $amount * 100 : $amount
 *
 * — a fractional value was major units, a whole number was already minor.
 * So a $148.00 subtotal rendered as $1.48 while the 14.80 discount on the
 * line below it rendered correctly, and any round amount was silently
 * divided by a hundred: $50.00 showed as $0.50. The rule cannot be made to
 * work, because nothing distinguishes 148 meaning $148.00 from 148 meaning
 * $1.48 — and round amounts are exactly the ones a person types.
 *
 * It also hard-coded two decimal places, which is wrong for the ~20% of
 * ISO-4217 currencies that do not have them: yen has none, Kuwaiti dinar
 * has three. The exponent comes from the currency here.
 */
final class Amount {

	/**
	 * Convert a major-unit amount to minor units.
	 *
	 * Rounded to the currency's own exponent before parsing, for two
	 * reasons. Money::parse() truncates a longer fraction, so 14.999 would
	 * become 1499 rather than 1500; and number_format() emits fixed
	 * notation, so a very large float never reaches parse() as "1.0e+25",
	 * where everything that is not a digit or separator is stripped and it
	 * would read as 1025.
	 *
	 * @param mixed  $amount   Amount in major units.
	 * @param string $currency ISO-4217 code.
	 *
	 * @return int Amount in the smallest unit of $currency.
	 */
	public static function minor( $amount, string $currency ): int {
		if ( ! is_numeric( $amount ) ) {
			return 0;
		}

		$decimals = Currencies::decimals( $currency );

		return Money::parse( number_format( (float) $amount, $decimals, '.', '' ), $currency );
	}
}
