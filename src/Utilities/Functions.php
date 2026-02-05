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
	 * @type array    $tabs        Tab configurations (optional)
	 * @type array    $fields      Field configurations
	 * @type array    $actions     Footer action buttons
	 * @type string   $capability  Required capability (default: 'manage_options')
	 * @type array    $admin_pages Admin page hooks to load on
	 * @type callable $load        Function to load data: function($id)
	 * @type callable $save        Function to save data: function($id, $data)
	 * @type callable $delete      Function to delete data: function($id)
	 *                             }
	 * @return Manager|null The manager instance or null if registration failed
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
	 * @param string $id      Full flyout identifier (same as used in register_flyout)
	 * @param array  $args    {
	 *                        Mixed configuration for button and data attributes
	 *
	 * @type string  $text    Button text (default: 'Open')
	 * @type string  $class   Additional CSS classes
	 * @type string  $icon    Dashicon name (without 'dashicons-' prefix)
	 * @type mixed   ...$data Any other keys become data attributes (id, title, subtitle, etc)
	 *                        }
	 *
	 * @return string Button HTML or empty string if flyout not found
	 * @since 1.0.0
	 * */
	function get_flyout_button( string $id, array $args = [] ): string {
		try {
			$components = Registry::parse_flyout_id( $id );
			$registry   = Registry::get_instance();

			if ( ! $registry->has_manager( $components['prefix'] ) ) {
				return '';
			}

			$manager = $registry->get_manager( $components['prefix'] );

			// Separate button config from data attributes
			$button_config = [ 'text', 'class', 'icon' ];
			$button_args   = [];
			$data          = [];

			foreach ( $args as $key => $value ) {
				if ( in_array( $key, $button_config, true ) ) {
					$button_args[ $key ] = $value;
				} else {
					$data[ $key ] = $value;
				}
			}

			return $manager->get_button( $components['flyout_id'], $data, $button_args );

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
	 * @param string $id   Full flyout identifier
	 * @param array  $args Mixed configuration (see get_flyout_button)
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function render_flyout_button( string $id, array $args = [] ): void {
		echo get_flyout_button( $id, $args );
	}
}

if ( ! function_exists( 'get_flyout_link' ) ) {
	/**
	 * Get a flyout trigger link HTML
	 *
	 * @param string $id      Full flyout identifier
	 * @param array  $args    {
	 *                        Mixed configuration for link and data attributes
	 *
	 * @type string  $text    Link text (required)
	 * @type string  $class   Additional CSS classes
	 * @type string  $target  Link target attribute
	 * @type mixed   ...$data Any other keys become data attributes
	 *                        }
	 *
	 * @return string Link HTML or empty string if flyout not found
	 * @since 1.0.0
	 */
	function get_flyout_link( string $id, array $args = [] ): string {
		try {
			$components = Registry::parse_flyout_id( $id );
			$registry   = Registry::get_instance();

			if ( ! $registry->has_manager( $components['prefix'] ) ) {
				return '';
			}

			$manager = $registry->get_manager( $components['prefix'] );

			// Extract text (required)
			$text = $args['text'] ?? '';
			if ( empty( $text ) ) {
				return '';
			}

			// Separate link config from data attributes
			$link_config = [ 'text', 'class', 'target' ];
			$link_args   = [];
			$data        = [];

			foreach ( $args as $key => $value ) {
				if ( $key === 'text' ) {
					continue; // Already extracted
				}
				if ( in_array( $key, $link_config, true ) ) {
					$link_args[ $key ] = $value;
				} else {
					$data[ $key ] = $value;
				}
			}

			return $manager->link( $components['flyout_id'], $text, $data, $link_args );

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
	 * @param string $id   Full flyout identifier
	 * @param array  $args Mixed configuration (see get_flyout_link)
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function render_flyout_link( string $id, array $args = [] ): void {
		echo get_flyout_link( $id, $args );
	}
}