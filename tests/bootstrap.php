<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

/*
 * Dependencies guard their files-autoloaded entrypoints with an ABSPATH
 * check. Composer runs those on require of the autoloader, so the constant
 * has to exist before it or their helpers are never declared.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/*
 * The kit's WordPress stubs, so the components that now render through it
 * have the escaping and sanitizing helpers they call. Required before the
 * autoloader for the same reason ABSPATH is.
 */
require_once dirname( __DIR__ ) . '/vendor/arraypress/wp-field-kit/tests/stubs.php';

/*
 * Filters, which the kit's stubs record but never run — this library's
 * sanitizer applies its own per-type ones and expects them back.
 */
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		foreach ( $GLOBALS['fk_filters'][ $hook ] ?? [] as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		return ! empty( $GLOBALS['fk_filters'][ $hook ] );
	}
}

/*
 * The handful core provides that the kit's stubs have no reason to: the kit
 * renders fields, and these are what the *components* around them call.
 */
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		// Not random — a test that renders twice wants the same markup twice,
		// and nothing here depends on uniqueness across a process.
		return '00000000-0000-4000-8000-000000000000';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return esc_url_raw( $url );
	}
}

if ( ! function_exists( 'esc_currency_e' ) ) {
	function esc_currency_e( $amount, $currency = 'USD' ) {
		echo esc_html( number_format_i18n( (int) $amount / 100, 2 ) );
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
