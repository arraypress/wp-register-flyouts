<?php
/**
 * WP Flyout Core Helper Functions
 *
 * Thin wrappers around Registry and Manager for simplified global usage.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\RegisterFlyouts\Manager;
use ArrayPress\RegisterFlyouts\Registry;

if ( ! function_exists( 'register_flyout' ) ) {
	/**
	 * Register a flyout with automatic manager handling.
	 *
	 * @param string $id     Full flyout identifier (prefix_name format).
	 * @param array  $config Flyout configuration array.
	 *
	 * @return Manager|null The manager instance or null if invalid ID.
	 */
	function register_flyout( string $id, array $config = [] ): ?Manager {
		$resolved = Registry::resolve( $id, true );

		if ( ! $resolved ) {
			return null;
		}

		$resolved['manager']->register_flyout( $resolved['flyout_id'], $config );

		return $resolved['manager'];
	}
}

if ( ! function_exists( 'get_flyout_button' ) ) {
	/**
	 * Get a flyout trigger button HTML.
	 *
	 * @param string $id   Full flyout identifier.
	 * @param array  $args Button config (text, class, icon) and data attributes.
	 *
	 * @return string Button HTML or empty string.
	 */
	function get_flyout_button( string $id, array $args = [] ): string {
		$resolved = Registry::resolve( $id );

		if ( ! $resolved ) {
			return '';
		}

		$button_keys = [ 'text', 'class', 'icon' ];
		$button_args = array_intersect_key( $args, array_flip( $button_keys ) );
		$data        = array_diff_key( $args, $button_args );

		return $resolved['manager']->get_button( $resolved['flyout_id'], $data, $button_args );
	}
}

if ( ! function_exists( 'render_flyout_button' ) ) {
	/**
	 * Render a flyout trigger button.
	 *
	 * @param string $id   Full flyout identifier.
	 * @param array  $args Button config and data attributes.
	 *
	 * @return void
	 */
	function render_flyout_button( string $id, array $args = [] ): void {
		echo get_flyout_button( $id, $args );
	}
}

if ( ! function_exists( 'get_flyout_link' ) ) {
	/**
	 * Get a flyout trigger link HTML.
	 *
	 * @param string $id   Full flyout identifier.
	 * @param array  $args Link config (text, class) and data attributes.
	 *
	 * @return string Link HTML or empty string.
	 */
	function get_flyout_link( string $id, array $args = [] ): string {
		$resolved = Registry::resolve( $id );

		if ( ! $resolved ) {
			return '';
		}

		$text = $args['text'] ?? '';
		if ( empty( $text ) ) {
			return '';
		}

		$link_keys = [ 'text', 'class', 'target' ];
		$link_args = array_intersect_key( $args, array_flip( $link_keys ) );
		$data      = array_diff_key( $args, $link_args );

		return $resolved['manager']->link( $resolved['flyout_id'], $text, $data, $link_args );
	}
}

if ( ! function_exists( 'render_flyout_link' ) ) {
	/**
	 * Render a flyout trigger link.
	 *
	 * @param string $id   Full flyout identifier.
	 * @param array  $args Link config and data attributes.
	 *
	 * @return void
	 */
	function render_flyout_link( string $id, array $args = [] ): void {
		echo get_flyout_link( $id, $args );
	}
}