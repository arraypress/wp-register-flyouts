<?php
/**
 * Assets Manager for WP Flyout
 *
 * Handles registration and enqueuing of CSS and JavaScript assets for the flyout system.
 * Manages both core assets and component-specific assets with dependency resolution.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

/**
 * Assets Manager Class
 *
 * Manages all CSS and JavaScript assets for the WP Flyout system,
 * including core files and optional component assets.
 *
 * @since 1.0.0
 */
class Assets {

	/**
	 * Core CSS files to always load
	 *
	 * @var array<string>
	 * @since 1.0.0
	 */
	private static array $core_styles = [
		'css/flyout/core.css',
		'css/flyout/form-fields.css',
		'css/flyout/ui-elements.css',
		'css/flyout/data-display.css'
	];

	/**
	 * Core JavaScript files
	 *
	 * @var array<string>
	 * @since 1.0.0
	 */
	private static array $core_scripts = [
		'js/wp-flyout.js',
		'js/core/forms.js',
		'js/core/manager.js',
		'js/core/alert.js',
		'js/core/conditional-fields.js'
	];

	/**
	 * Available components and their assets
	 *
	 * @var array<string, array{script: string, style: string, deps: array}>
	 * @since 1.0.0
	 */
	private static array $components = [
		'file-manager'   => [
			'script' => 'js/components/file-manager.js',
			'style'  => 'css/components/file-manager.css',
			'deps'   => [ 'jquery-ui-sortable' ]
		],
		'image-gallery'  => [
			'script' => 'js/components/image-gallery.js',
			'style'  => 'css/components/image-gallery.css',
			'deps'   => [ 'jquery-ui-sortable', 'wp-mediaelement' ]
		],
		'notes'          => [
			'script' => 'js/components/notes.js',
			'style'  => 'css/components/notes.css',
			'deps'   => []
		],
		'line-items'     => [
			'script' => 'js/components/line-items.js',
			'style'  => 'css/components/line-items.css',
			'deps'   => [ 'wp-flyout-ajax-select' ]
		],
		'feature-list'   => [
			'script' => 'js/components/feature-list.js',
			'style'  => 'css/components/feature-list.css',
			'deps'   => [ 'jquery-ui-sortable' ]
		],
		'key-value-list' => [
			'script' => 'js/components/key-value-list.js',
			'style'  => 'css/components/key-value-list.css',
			'deps'   => [ 'jquery-ui-sortable' ]
		],
		'ajax-select'    => [
			'script' => 'js/components/ajax-select.js',
			'style'  => 'css/components/ajax-select.css',
			'deps'   => [ 'select2' ]
		],
		'tags'           => [
			'script' => 'js/components/tags.js',
			'style'  => 'css/components/tags.css',
			'deps'   => []
		],
		'accordion'      => [
			'script' => 'js/components/accordion.js',
			'style'  => 'css/components/accordion.css',
			'deps'   => []
		],
		'card-choice'    => [
			'script' => '',
			'style'  => 'css/components/card-choice.css',
			'deps'   => []
		],
		'timeline'       => [
			'script' => '',
			'style'  => 'css/components/timeline.css',
			'deps'   => []
		],
		'price-summary'  => [
			'script' => 'js/components/price-summary.js',
			'style'  => 'css/components/price-summary.css',
			'deps'   => []
		],
		'payment-method' => [
			'script' => '',
			'style'  => 'css/components/payment-method.css',
			'deps'   => []
		],
		'action-buttons' => [
			'script' => 'js/components/action-buttons.js',
			'style'  => 'css/components/action-buttons.css',
			'deps'   => []
		],
		'action-menu'    => [
			'script' => 'js/components/action-menu.js',
			'style'  => 'css/components/action-menu.css',
			'deps'   => []
		],
		'articles'       => [
			'script' => '',
			'style'  => 'css/components/articles.css',
			'deps'   => []
		],
		'stats'          => [
			'script' => '',
			'style'  => 'css/components/stats.css',
			'deps'   => []
		],
		'progress-steps' => [
			'script' => '',
			'style'  => 'css/components/progress-steps.css',
			'deps'   => []
		],
	];

	/**
	 * Track last registered handles
	 *
	 * @var array{style: string, script: string}
	 * @since 1.0.0
	 */
	private static array $last_handles = [
		'style'  => '',
		'script' => ''
	];

	/**
	 * Initialize assets
	 *
	 * Hooks into WordPress to register assets when admin scripts are enqueued.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	/**
	 * Register all flyout assets
	 *
	 * Registers core CSS/JS files and component assets for later enqueuing.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function register_assets(): void {
		$base_file = __FILE__;
		$version   = defined( 'WP_DEBUG' ) && WP_DEBUG ? (string) time() : '1.0.0';

		// Register Select2 if not already registered
		self::register_select2( $version );

		// Register core CSS files
		$css_deps = [ 'dashicons' ];
		foreach ( self::$core_styles as $css_file ) {
			$handle = 'wp-flyout-' . basename( $css_file, '.css' );
			wp_register_composer_style(
				$handle,
				$base_file,
				$css_file,
				$css_deps,
				$version
			);
			$css_deps                    = [ $handle ];
			self::$last_handles['style'] = $handle;
		}

		// Register core JavaScript files
		$js_deps = [ 'jquery' ];
		foreach ( self::$core_scripts as $js_file ) {
			$handle = 'wp-flyout-' . basename( $js_file, '.js' );

			wp_register_composer_script(
				$handle,
				$base_file,
				$js_file,
				$js_deps,
				$version
			);

			// Add global object to first script
			if ( $js_file === self::$core_scripts[0] ) {
				wp_add_inline_script(
					$handle,
					'window.WPFlyout = window.WPFlyout || {};',
					'before'
				);
			}

			$js_deps                      = [ $handle ];
			self::$last_handles['script'] = $handle;
		}

		// Register component assets
		self::register_components( $base_file, $version );
	}

	/**
	 * Register Select2 assets if not already available
	 *
	 * Select2 may already be registered by WooCommerce or other plugins.
	 * We only register it if it's not already available.
	 *
	 * @param string $version Version string for cache busting
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private static function register_select2( string $version ): void {
		if ( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				[ 'jquery' ],
				'4.1.0-rc.0',
				true
			);
		}

		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				[],
				'4.1.0-rc.0'
			);
		}
	}

	/**
	 * Register component assets
	 *
	 * Registers individual component CSS and JavaScript files.
	 *
	 * @param string $base_file Base file path for asset resolution
	 * @param string $version   Version string for cache busting
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_components( string $base_file, string $version ): void {
		foreach ( self::$components as $name => $config ) {
			$handle = 'wp-flyout-' . $name;

			// Register component script if exists
			if ( ! empty( $config['script'] ) ) {
				$deps = array_merge(
					[ 'jquery', self::$last_handles['script'] ],
					$config['deps'] ?? []
				);
				wp_register_composer_script(
					$handle,
					$base_file,
					$config['script'],
					$deps,
					$version
				);
			}

			// Register component style if exists
			if ( ! empty( $config['style'] ) ) {
				$style_deps = [ self::$last_handles['style'] ];

				// Add select2 CSS as dependency for ajax-select
				if ( $name === 'ajax-select' ) {
					$style_deps[] = 'select2';
				}

				wp_register_composer_style(
					$handle,
					$base_file,
					$config['style'],
					$style_deps,
					$version
				);
			}
		}
	}

	/**
	 * Enqueue core flyout assets
	 *
	 * Loads all core CSS and JavaScript files required for flyout functionality.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function enqueue(): void {
		// Ensure assets are registered
		if ( ! wp_style_is( self::$last_handles['style'], 'registered' ) ) {
			self::register_assets();
		}

		// Enqueue all core styles
		wp_enqueue_style( self::$last_handles['style'] );

		// Enqueue all core scripts
		wp_enqueue_script( self::$last_handles['script'] );
	}

	/**
	 * Enqueue specific component assets
	 *
	 * Loads CSS and JavaScript for a specific flyout component,
	 * including any dependencies.
	 *
	 * @param string $component Component name to enqueue
	 *
	 * @return bool True if component was enqueued, false if not found
	 * @since 1.0.0
	 */
	public static function enqueue_component( string $component ): bool {
		if ( ! isset( self::$components[ $component ] ) ) {
			return false;
		}

		// Ensure core is loaded first
		self::enqueue();

		$handle = 'wp-flyout-' . $component;
		$config = self::$components[ $component ];

		// Handle dependencies
		if ( ! empty( $config['deps'] ) ) {
			foreach ( $config['deps'] as $dep ) {
				// Check if it's another component dependency
				if ( str_starts_with( $dep, 'wp-flyout-' ) ) {
					$dep_component = str_replace( 'wp-flyout-', '', $dep );
					if ( isset( self::$components[ $dep_component ] ) ) {
						self::enqueue_component( $dep_component );
					}
				} else {
					// Enqueue WordPress/external dependencies (includes select2)
					wp_enqueue_script( $dep );
					// Also enqueue matching style if registered (e.g. select2 CSS)
					if ( wp_style_is( $dep, 'registered' ) ) {
						wp_enqueue_style( $dep );
					}
				}
			}
		}

		// Enqueue component assets
		if ( wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}

		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}

		// Handle special requirements
		if ( $component === 'file-manager' ) {
			wp_enqueue_media();
		}

		return true;
	}

}