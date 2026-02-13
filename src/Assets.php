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
 * @version     3.0.0
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
	 * Core CSS file
	 *
	 * Single consolidated stylesheet covering flyout shell, layout,
	 * form fields, tabs, footer, and responsive breakpoints.
	 *
	 * @var string
	 * @since 3.0.0
	 */
	private static string $core_style = 'css/wp-flyout.css';

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
		'gallery'        => [
			'script' => 'js/components/gallery.js',
			'style'  => 'css/components/gallery.css',
			'deps'   => [ 'jquery-ui-sortable', 'wp-mediaelement' ]
		],
		'image-picker'   => [
			'script' => 'js/components/image-picker.js',
			'style'  => '',
			'deps'   => [ 'wp-mediaelement' ]
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
			'script' => '',
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
		'price-config'   => [
			'script' => 'js/components/price-config.js',
			'style'  => 'css/components/price-config.css',
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

		// Register core CSS (single consolidated file)
		wp_register_composer_style(
			'wp-flyout',
			$base_file,
			self::$core_style,
			[ 'dashicons' ],
			$version
		);
		self::$last_handles['style'] = 'wp-flyout';

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
	 * @param string $version Version string for cache busting
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private static function register_select2( string $version ): void {
		if ( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_composer_script(
				'select2',
				__FILE__,
				'js/libraries/select2.min.js',
				[ 'jquery' ],
				$version
			);
		}

		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_composer_style(
				'select2',
				__FILE__,
				'css/libraries/select2.min.css',
				[],
				$version
			);
		}
	}

	/**
	 * Register component assets
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
	 * @return void
	 * @since 1.0.0
	 */
	public static function enqueue(): void {
		// Ensure assets are registered
		if ( ! wp_style_is( self::$last_handles['style'], 'registered' ) ) {
			self::register_assets();
		}

		// Enqueue core style
		wp_enqueue_style( self::$last_handles['style'] );

		// Enqueue all core scripts
		wp_enqueue_script( self::$last_handles['script'] );
	}

	/**
	 * Enqueue specific component assets
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

		// Handle special requirements (media library)
		if ( in_array( $component, [ 'file-manager', 'image-picker', 'gallery' ], true ) ) {
			wp_enqueue_media();
		}

		return true;
	}

}