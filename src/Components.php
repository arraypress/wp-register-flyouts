<?php
/**
 * Component Registry
 *
 * Central registry for all flyout components.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use ArrayPress\RegisterFlyouts\Components\ActionButtons;
use ArrayPress\RegisterFlyouts\Components\ActionMenu;
use ArrayPress\RegisterFlyouts\Components\Articles;
use ArrayPress\RegisterFlyouts\Components\FeatureList;
use ArrayPress\RegisterFlyouts\Components\Image;
use ArrayPress\RegisterFlyouts\Components\Gallery;
use ArrayPress\RegisterFlyouts\Components\KeyValueList;
use ArrayPress\RegisterFlyouts\Components\PaymentMethod;
use ArrayPress\RegisterFlyouts\Components\PriceSummary;
use ArrayPress\RegisterFlyouts\Components\CardChoice;
use ArrayPress\RegisterFlyouts\Components\FormField;
use ArrayPress\RegisterFlyouts\Components\FileManager;
use ArrayPress\RegisterFlyouts\Components\Notes;
use ArrayPress\RegisterFlyouts\Components\LineItems;
use ArrayPress\RegisterFlyouts\Components\Accordion;
use ArrayPress\RegisterFlyouts\Components\Stats;
use ArrayPress\RegisterFlyouts\Components\Timeline;
use ArrayPress\RegisterFlyouts\Components\Header;
use ArrayPress\RegisterFlyouts\Components\Separator;
use ArrayPress\RegisterFlyouts\Components\EmptyState;
use ArrayPress\RegisterFlyouts\Components\DataTable;
use ArrayPress\RegisterFlyouts\Components\InfoGrid;
use ArrayPress\RegisterFlyouts\Components\Alert;
use ArrayPress\RegisterFlyouts\Components\PriceConfig;
use InvalidArgumentException;

/**
 * Class Components
 *
 * Manages registration and instantiation of flyout components.
 */
class Components {

	// =========================================================================
	// PROPERTIES
	// =========================================================================

	/**
	 * Registered component configurations
	 *
	 * @var array<string, array>
	 */
	private static array $components = [];

	/**
	 * Whether default components have been initialized
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	// =========================================================================
	// INITIALIZATION
	// =========================================================================

	/**
	 * Initialize default components
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::register_display_components();
		self::register_interactive_components();
		self::register_form_components();
		self::register_layout_components();
		self::register_data_components();
		self::register_utility_components();

		self::$initialized = true;

		do_action( 'wp_flyout_components_init', self::$components );
	}

	// =========================================================================
	// COMPONENT REGISTRATION BY CATEGORY
	// =========================================================================

	/**
	 * Register display components
	 *
	 * Components that primarily display information without user interaction
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_display_components(): void {
		// ---- Headers & Titles ----
		self::register( 'header', [
			'class'       => Header::class,
			'data_fields' => [
				'title',
				'subtitle',
				'image',
				'icon',
				'badges',
				'meta',
				'description',
				'attachment_id',
				'editable',
				'image_size',
				'image_shape',
				'fallback_image',
				'fallback_attachment_id'
			],
			'asset'       => null,
			'category'    => 'display',
			'description' => 'Unified header for any entity with optional image picker'
		] );

		// ---- Alerts & Messages ----
		self::register( 'alert', [
			'class'       => Alert::class,
			'data_fields' => [ 'type', 'message', 'title' ],
			'asset'       => null,
			'category'    => 'display',
			'description' => 'Alert messages with various styles'
		] );

		self::register( 'empty_state', [
			'class'       => EmptyState::class,
			'data_fields' => [ 'icon', 'title', 'description', 'action_text' ],
			'asset'       => null,
			'category'    => 'display',
			'description' => 'Empty state messages'
		] );

		// ---- Content Lists ----
		self::register( 'articles', [
			'class'       => Articles::class,
			'data_fields' => 'items',
			'asset'       => 'articles',
			'category'    => 'display',
			'description' => 'Article cards with images and excerpts'
		] );

		self::register( 'timeline', [
			'class'       => Timeline::class,
			'data_fields' => 'items',
			'asset'       => 'timeline',
			'category'    => 'display',
			'description' => 'Chronological event timeline'
		] );

		// ---- Statistics & Metrics ----
		self::register( 'stats', [
			'class'       => Stats::class,
			'data_fields' => 'items',
			'asset'       => 'stats',
			'category'    => 'display',
			'description' => 'Statistical metric cards with trends'
		] );
	}

	/**
	 * Register interactive components
	 *
	 * Components that allow user interaction and data manipulation
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_interactive_components(): void {
		// ---- Action Components ----
		self::register( 'action_buttons', [
			'class'       => ActionButtons::class,
			'data_fields' => 'buttons',
			'asset'       => 'action-buttons',
			'category'    => 'interactive',
			'description' => 'Action buttons with AJAX callbacks for operations like refunds'
		] );

		self::register( 'action_menu', [
			'class'       => ActionMenu::class,
			'data_fields' => 'items',
			'asset'       => 'action-menu',
			'category'    => 'interactive',
			'description' => 'Dropdown menu for multiple actions with AJAX support'
		] );

		// ---- List Management ----
		self::register( 'notes', [
			'class'       => Notes::class,
			'data_fields' => 'items',
			'asset'       => 'notes',
			'category'    => 'interactive',
			'description' => 'Notes/comments with add/delete functionality'
		] );

		self::register( 'files', [
			'class'       => FileManager::class,
			'data_fields' => 'items',
			'asset'       => 'file-manager',
			'category'    => 'interactive',
			'description' => 'File attachments with drag-drop sorting'
		] );

		self::register( 'feature_list', [
			'class'       => FeatureList::class,
			'data_fields' => 'items',
			'asset'       => 'feature-list',
			'category'    => 'interactive',
			'description' => 'Feature list with drag-drop sorting'
		] );

		self::register( 'key_value_list', [
			'class'       => KeyValueList::class,
			'data_fields' => 'items',
			'asset'       => 'key-value-list',
			'category'    => 'interactive',
			'description' => 'Key value list with drag-drop sorting'
		] );

		// ---- Commerce Components ----
		self::register( 'line_items', [
			'class'       => LineItems::class,
			'data_fields' => 'items',
			'asset'       => 'line-items',
			'category'    => 'interactive',
			'description' => 'Order line items with quantities and pricing'
		] );

		self::register( 'gallery', [
			'class'       => Gallery::class,
			'data_fields' => 'items',
			'asset'       => 'gallery',
			'category'    => 'interactive',
			'description' => 'Multi-image gallery with media library and reordering'
		] );
	}

	/**
	 * Register form components
	 *
	 * Components specifically for form input and selection
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_form_components(): void {
		// ---- Selection Components ----
		self::register( 'card_choice', [
			'class'       => CardChoice::class,
			'data_fields' => [ 'options', 'value' ],
			'asset'       => 'card-choice',
			'category'    => 'form',
			'description' => 'Card-style radio/checkbox selections'
		] );

		self::register( 'ajax_select', [
			'class'       => FormField::class,
			'data_fields' => 'value',
			'asset'       => 'ajax-select',
			'category'    => 'form',
			'description' => 'AJAX-powered select field'
		] );

		// ---- Media Components ----
		self::register( 'image', [
			'class'       => Image::class,
			'data_fields' => 'value',
			'asset'       => 'image-picker',
			'category'    => 'form',
			'description' => 'Single image picker with media library integration'
		] );

		// ---- Pricing ----
		self::register( 'price_config', [
			'class'       => PriceConfig::class,
			'data_fields' => [ 'amount', 'currency', 'recurring_interval', 'recurring_interval_count' ],
			'asset'       => 'price-config',
			'category'    => 'form',
			'description' => 'Stripe-compatible pricing configuration'
		] );
	}

	/**
	 * Register layout components
	 *
	 * Components that organize and structure content
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_layout_components(): void {
		// ---- Collapsible Content ----
		self::register( 'accordion', [
			'class'       => Accordion::class,
			'data_fields' => 'items',
			'asset'       => 'accordion',
			'category'    => 'layout',
			'description' => 'Collapsible content sections'
		] );

		// ---- Visual Separators ----
		self::register( 'separator', [
			'class'       => Separator::class,
			'data_fields' => [ 'text', 'icon' ],
			'asset'       => null,
			'category'    => 'layout',
			'description' => 'Visual dividers with optional text'
		] );
	}

	/**
	 * Register data display components
	 *
	 * Components for structured data presentation
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_data_components(): void {
		// ---- Tables & Grids ----
		self::register( 'data_table', [
			'class'       => DataTable::class,
			'data_fields' => [ 'columns', 'data' ],
			'asset'       => null,
			'category'    => 'data',
			'description' => 'Structured data table display'
		] );

		self::register( 'info_grid', [
			'class'       => InfoGrid::class,
			'data_fields' => 'items',
			'asset'       => null,
			'category'    => 'data',
			'description' => 'Information grid layout'
		] );

		// ---- Domain-Specific Data ----
		self::register( 'payment_method', [
			'class'       => PaymentMethod::class,
			'data_fields' => [
				'payment_method',
				'payment_brand',
				'payment_last4',
				'stripe_risk_score',
				'stripe_risk_level'
			],
			'asset'       => 'payment-method',
			'category'    => 'data',
			'description' => 'Displays payment method with card brand icons and risk indicators'
		] );

		self::register( 'price_summary', [
			'class'       => PriceSummary::class,
			'data_fields' => [ 'items', 'subtotal', 'tax', 'discount', 'total', 'currency' ],
			'asset'       => 'price-summary',
			'category'    => 'data',
			'description' => 'Price summary with line items and totals'
		] );
	}

	/**
	 * Register utility components
	 *
	 * Helper components for specific use cases
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function register_utility_components(): void {
	}

	// =========================================================================
	// REGISTRATION & MANAGEMENT
	// =========================================================================

	/**
	 * Register a custom component
	 *
	 * @param string $type   Component type identifier
	 * @param array  $config Component configuration
	 *
	 * @return void
	 * @throws InvalidArgumentException If component class doesn't exist
	 */
	public static function register( string $type, array $config ): void {
		if ( ! isset( $config['class'] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Component "%s" must have a class defined', $type )
			);
		}

		if ( ! class_exists( $config['class'] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Component class "%s" does not exist', $config['class'] )
			);
		}

		self::$components[ $type ] = $config;
	}

	/**
	 * Unregister a component
	 *
	 * @param string $type Component type to unregister
	 *
	 * @return bool True if component was unregistered
	 */
	public static function unregister( string $type ): bool {
		if ( isset( self::$components[ $type ] ) ) {
			unset( self::$components[ $type ] );

			return true;
		}

		return false;
	}

	// =========================================================================
	// COMPONENT RETRIEVAL
	// =========================================================================

	/**
	 * Get component configuration
	 *
	 * @param string $type Component type
	 *
	 * @return array|null Component configuration or null if not found
	 */
	public static function get( string $type ): ?array {
		self::ensure_initialized();

		return self::$components[ $type ] ?? null;
	}

	/**
	 * Get all registered components
	 *
	 * @return array<string, array> All registered components
	 */
	public static function get_all(): array {
		self::ensure_initialized();

		return self::$components;
	}

	/**
	 * Check if type is a registered component
	 *
	 * @param string $type Component type to check
	 *
	 * @return bool True if component is registered
	 */
	public static function is_component( string $type ): bool {
		self::ensure_initialized();

		return isset( self::$components[ $type ] );
	}

	/**
	 * Get components by category
	 *
	 * @param string $category Category name (display, interactive, form, layout, data, utility)
	 *
	 * @return array Components in that category
	 * @since 1.0.0
	 */
	public static function get_by_category( string $category ): array {
		self::ensure_initialized();

		return array_filter( self::$components, function ( $component ) use ( $category ) {
			return isset( $component['category'] ) && $component['category'] === $category;
		} );
	}

	// =========================================================================
	// COMPONENT INSTANTIATION
	// =========================================================================

	/**
	 * Create component instance
	 *
	 * @param string $type   Component type
	 * @param array  $config Component configuration
	 *
	 * @return object|null Component instance or null if type not found
	 */
	public static function create( string $type, array $config ) {
		self::ensure_initialized();

		$component_config = self::get( $type );
		if ( ! $component_config || ! isset( $component_config['class'] ) ) {
			return null;
		}

		$class = $component_config['class'];

		$config = apply_filters( 'wp_flyout_component_config', $config, $type, $class );
		$config = apply_filters( "wp_flyout_component_{$type}_config", $config );

		return new $class( $config );
	}

	// =========================================================================
	// DATA RESOLUTION
	// =========================================================================

	/**
	 * Resolve component data from a data source
	 *
	 * Attempts to resolve data for a component from various sources:
	 * 1. Looks for a method that returns the complete data structure (field_key_data)
	 * 2. Tries to get a pre-built array/object at field_key
	 * 3. Falls back to resolving individual fields
	 *
	 * @param string $type      Component type
	 * @param string $field_key Field identifier
	 * @param mixed  $data      Data source (object or array)
	 *
	 * @return array Resolved data array
	 */
	public static function resolve_data( string $type, string $field_key, $data ): array {
		self::ensure_initialized();

		$component = self::get( $type );

		// Non-components default to 'value'
		if ( ! $component || ! isset( $component['data_fields'] ) ) {
			return [ 'value' => self::resolve_value( $field_key, $data ) ];
		}

		$data_fields = $component['data_fields'];

		// Try to get pre-built array/object at field_key
		$resolved = self::resolve_value( $field_key, $data );

		// If we got an array with the fields we need, use it
		if ( is_array( $resolved ) ) {
			if ( is_string( $data_fields ) ) {
				// Single field - check if array contains it
				if ( isset( $resolved[ $data_fields ] ) ) {
					return $resolved;
				}
			} else {
				// Multiple fields - check if array has any
				foreach ( $data_fields as $field ) {
					if ( isset( $resolved[ $field ] ) ) {
						return $resolved;
					}
				}
			}
		}

		// Build result from individual fields
		if ( is_string( $data_fields ) ) {
			// Single field component
			return [ $data_fields => $resolved ];
		}

		// Multiple field component - resolve each
		$result = [];
		foreach ( $data_fields as $field ) {
			$key              = ( $field === 'value' ) ? $field_key : $field;
			$result[ $field ] = self::resolve_value( $key, $data );
		}

		return $result;
	}

	/**
	 * Resolve a single value from data source
	 *
	 * Resolution order (optimized for explicit data methods first):
	 * 1. Explicit data method (field_data()) - Most specific, returns complete data
	 * 2. Array key access - Direct array access (fast, no method calls)
	 * 3. Getter method (get_field()) - Standard getter pattern
	 * 4. Direct property access - Public property check
	 * 5. Direct method call (field()) - Method with field name
	 * 6. CamelCase for underscore properties - Legacy compatibility
	 *
	 * @param string $key  Property/method name to resolve
	 * @param mixed  $data Data source (object or array)
	 *
	 * @return mixed Resolved value or null if not found
	 */
	public static function resolve_value( string $key, $data ) {
		if ( ! $data ) {
			return null;
		}

		// 1. Try explicit data method first (field_data())
		if ( is_object( $data ) ) {
			$data_method = $key . '_data';
			if ( method_exists( $data, $data_method ) ) {
				return $data->$data_method();
			}
		}

		// 2. Array key access (fast, no method calls needed)
		if ( is_array( $data ) && isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		// Only try remaining methods if we have an object
		if ( ! is_object( $data ) ) {
			return null;
		}

		// 3. Getter method (get_field)
		$getter = 'get_' . $key;
		if ( method_exists( $data, $getter ) ) {
			return $data->$getter();
		}

		// 4. Direct property access
		if ( property_exists( $data, $key ) ) {
			return $data->$key;
		}

		// 5. Direct method call (field())
		if ( method_exists( $data, $key ) ) {
			return $data->$key();
		}

		// 6. For underscore properties, try camelCase
		if ( str_contains( $key, '_' ) ) {
			$camelCase = lcfirst( str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) ) );
			if ( method_exists( $data, $camelCase ) ) {
				return $data->$camelCase();
			}
		}

		return null;
	}

	// =========================================================================
	// ASSET MANAGEMENT
	// =========================================================================

	/**
	 * Get required asset for component
	 *
	 * For the header component, the asset is conditionally required
	 * only when the 'editable' flag is set in the field config.
	 * This is handled by the Manager's detect_components method.
	 *
	 * @param string $type   Component type
	 * @param array  $config Optional field config for conditional asset detection
	 *
	 * @return string|null Asset handle or null if no asset required
	 */
	public static function get_asset( string $type, array $config = [] ): ?string {
		self::ensure_initialized();

		// Header component conditionally needs assets only when editable
		if ( $type === 'header' ) {
			return ! empty( $config['editable'] ) ? 'image-picker' : null;
		}

		return self::$components[ $type ]['asset'] ?? null;
	}

	/**
	 * Get all required assets for registered components
	 *
	 * @return array<string> Unique asset handles
	 */
	public static function get_all_assets(): array {
		self::ensure_initialized();

		$assets = [];
		foreach ( self::$components as $component ) {
			if ( ! empty( $component['asset'] ) ) {
				$assets[] = $component['asset'];
			}
		}

		return array_unique( $assets );
	}

	// =========================================================================
	// COMPONENT INFORMATION
	// =========================================================================

	/**
	 * Get component description
	 *
	 * @param string $type Component type
	 *
	 * @return string|null Component description or null if not found
	 * @since 1.0.0
	 */
	public static function get_description( string $type ): ?string {
		self::ensure_initialized();

		$component = self::get( $type );

		return $component['description'] ?? null;
	}

	/**
	 * Get available categories
	 *
	 * @return array List of available categories
	 * @since 1.0.0
	 */
	public static function get_categories(): array {
		return [ 'display', 'interactive', 'form', 'layout', 'data', 'utility' ];
	}

	// =========================================================================
	// UTILITY METHODS
	// =========================================================================

	/**
	 * Ensure components are initialized
	 *
	 * @return void
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

	/**
	 * Reset registry (for testing)
	 *
	 * @return void
	 * @internal
	 */
	public static function reset(): void {
		self::$components  = [];
		self::$initialized = false;
	}

}