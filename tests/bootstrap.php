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

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
