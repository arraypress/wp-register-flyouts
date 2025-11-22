<?php
/**
 * Empty Value Formatter Trait
 *
 * Provides consistent formatting for empty, null, and various data types.
 * Ensures uniform display of missing or empty values across components.
 *
 * @package     ArrayPress\RegisterFlyouts\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Traits;

/**
 * Trait Formatter
 *
 * Formats values for display with consistent empty state handling.
 *
 * @since 1.0.0
 */
trait Formatter {

	/**
	 * Format value for display
	 *
	 * Handles various data types and empty states consistently.
	 * - Empty values return configured empty text
	 * - Booleans return Yes/No
	 * - Arrays are joined with commas
	 * - Everything else is cast to string
	 *
	 * @param mixed  $value      Value to format
	 * @param string $empty_text Text to display for empty values (default: '—')
	 *
	 * @return string Formatted value string
	 * @since 1.0.0
	 *
	 */
	protected function format_value( $value, string $empty_text = '—' ): string {
		// Handle empty values (but not 0 or '0')
		if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
			return $empty_text;
		}

		// Handle booleans
		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'wp-flyout' ) : __( 'No', 'wp-flyout' );
		}

		// Handle arrays
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		// Handle objects with __toString
		if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
			return (string) $value;
		}

		// Default: cast to string
		return (string) $value;
	}

}