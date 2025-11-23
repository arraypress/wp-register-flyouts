<?php
/**
 * WP Flyout Core Helper Functions
 *
 * Core functionality helpers for flyout system initialization and management.
 * These global functions provide a simplified API for flyout registration and usage.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\RegisterFlyouts\Manager;
use ArrayPress\RegisterFlyouts\Registry;

if ( ! function_exists( 'register_flyout' ) ) {
	/**
	 * Register a flyout with automatic manager handling
	 *
	 * This is the simplified global registration method that automatically
	 * creates and manages Manager instances through the Registry pattern.
	 *
	 * The ID should follow the pattern: 'prefix_flyout_name'
	 * (e.g., 'myapp_edit_customer', 'shop_add_product')
	 *
	 * The prefix (first part before underscore) becomes the manager namespace,
	 * and the rest becomes the flyout ID within that manager.
	 *
	 * @param string  $id          Full flyout identifier (prefix_name format)
	 * @param array   $config      {
	 *                             Flyout configuration array
	 *
	 * @type string   $title       Flyout title
	 * @type string   $width       Width: 'small', 'medium', 'large', 'full'
	 * @type array    $panels      Panel configurations (optional)
	 * @type array    $fields      Field configurations
	 * @type array    $actions     Footer action buttons
	 * @type string   $capability  Required capability (default: 'manage_options')
	 * @type array    $admin_pages Admin page hooks to load on
	 * @type callable $load        Function to load data: function($id)
	 * @type callable $save        Function to save data: function($id, $data)
	 * @type callable $delete      Function to delete data: function($id)
	 *                             }
	 * @return Manager|null The manager instance or null if registration failed
	 * @since 9.0.0 Updated to use Registry pattern instead of global variable
	 *
	 * @since 1.0.0
	 */
	function register_flyout( string $id, array $config = [] ): ?Manager {
		try {
			// Use registry helper to parse ID
			$components = Registry::parse_flyout_id( $id );

			// Get manager from registry
			$registry = Registry::get_instance();
			$manager  = $registry->get_manager( $components['prefix'] );

			// Register the flyout with the manager
			$manager->register_flyout( $components['flyout_id'], $config );

			return $manager;

		} catch ( Exception $e ) {
			error_log( sprintf(
				'WP Flyout: Failed to register flyout "%s" - %s',
				$id,
				$e->getMessage()
			) );

			return null;
		}
	}
}

if ( ! function_exists( 'get_flyout_button' ) ) {
	/**
	 * Get a flyout trigger button HTML
	 *
	 * This is a convenience function that works with the global registration.
	 * It automatically determines the correct manager from the flyout ID using the Registry.
	 *
	 * @param string $id    Full flyout identifier (same as used in register_flyout)
	 * @param array  $data  Data attributes to pass to the flyout
	 * @param array  $args  {
	 *                      Button configuration
	 *
	 * @type string  $text  Button text (default: 'Open')
	 * @type string  $class Additional CSS classes
	 * @type string  $icon  Dashicon name (without 'dashicons-' prefix)
	 *                      }
	 * @return string Button HTML or empty string if flyout not found
	 * @since 1.0.0
	 * @since 9.0.0 Updated to use Registry pattern
	 *
	 */
	function get_flyout_button( string $id, array $data = [], array $args = [] ): string {
		try {
			$components = Registry::parse_flyout_id( $id );
			$registry   = Registry::get_instance();

			if ( ! $registry->has_manager( $components['prefix'] ) ) {
				return '';
			}

			$manager = $registry->get_manager( $components['prefix'] );

			// Get button HTML from manager
			return $manager->get_button( $components['flyout_id'], $data, $args );

		} catch ( Exception $e ) {
			error_log( sprintf(
				'WP Flyout: Failed to get button for flyout "%s" - %s',
				$id,
				$e->getMessage()
			) );

			return '';
		}
	}
}

if ( ! function_exists( 'render_flyout_button' ) ) {
	/**
	 * Render a flyout trigger button
	 *
	 * Outputs the button HTML directly.
	 *
	 * @param string $id   Full flyout identifier
	 * @param array  $data Data attributes to pass
	 * @param array  $args Button configuration
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 9.0.0 Updated to use Registry pattern
	 *
	 */
	function render_flyout_button( string $id, array $data = [], array $args = [] ): void {
		echo get_flyout_button( $id, $data, $args );
	}
}

if ( ! function_exists( 'get_flyout_link' ) ) {
	/**
	 * Get a flyout trigger link HTML
	 *
	 * This is a convenience function that works with the global registration.
	 * It automatically determines the correct manager from the flyout ID using the Registry.
	 *
	 * @param string $id     Full flyout identifier (same as used in register_flyout)
	 * @param string $text   Link text to display
	 * @param array  $data   Data attributes to pass to the flyout
	 * @param array  $args   {
	 *                       Link configuration
	 *
	 * @type string  $class  Additional CSS classes
	 * @type string  $target Link target attribute (e.g., '_blank')
	 *                       }
	 * @return string Link HTML or empty string if flyout not found
	 * @since 9.0.0 Updated to use Registry pattern
	 *
	 * @since 1.0.0
	 */
	function get_flyout_link( string $id, string $text, array $data = [], array $args = [] ): string {
		try {
			$components = Registry::parse_flyout_id( $id );
			$registry   = Registry::get_instance();

			if ( ! $registry->has_manager( $components['prefix'] ) ) {
				return '';
			}

			$manager = $registry->get_manager( $components['prefix'] );

			// Get link HTML from manager
			return $manager->link( $components['flyout_id'], $text, $data, $args );

		} catch ( Exception $e ) {
			error_log( sprintf(
				'WP Flyout: Failed to get link for flyout "%s" - %s',
				$id,
				$e->getMessage()
			) );

			return '';
		}
	}
}

if ( ! function_exists( 'render_flyout_link' ) ) {
	/**
	 * Render a flyout trigger link
	 *
	 * Outputs the link HTML directly.
	 *
	 * @param string $id   Full flyout identifier
	 * @param string $text Link text to display
	 * @param array  $data Data attributes to pass
	 * @param array  $args Link configuration
	 *
	 * @return void
	 * @since 9.0.0 Updated to use Registry pattern
	 *
	 * @since 1.0.0
	 */
	function render_flyout_link( string $id, string $text, array $data = [], array $args = [] ): void {
		echo get_flyout_link( $id, $text, $data, $args );
	}
}