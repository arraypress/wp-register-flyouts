<?php
/**
 * Field and Component Sanitizer - Enhanced
 *
 * Centralized sanitization logic with filterable sanitizers for extensibility.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use DateTime;

class Sanitizer {

	/**
	 * Field type sanitizers
	 *
	 * @var array<string, callable>
	 */
	private static array $field_sanitizers = [];

	/**
	 * Component sanitizers (only for components that submit data)
	 *
	 * @var array<string, callable>
	 */
	private static array $component_sanitizers = [];

	/**
	 * Whether sanitizers have been initialized
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize default sanitizers
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::register_field_sanitizers();
		self::register_component_sanitizers();

		// Apply filters to allow customization
		self::$field_sanitizers     = apply_filters( 'wp_flyout_field_sanitizers', self::$field_sanitizers );
		self::$component_sanitizers = apply_filters( 'wp_flyout_component_sanitizers', self::$component_sanitizers );

		self::$initialized = true;
	}

	/**
	 * Register field type sanitizers
	 */
	private static function register_field_sanitizers(): void {
		$sanitizers = [
			// Text inputs
			'text'        => 'sanitize_text_field',
			'textarea'    => 'sanitize_textarea_field',
			'email'       => 'sanitize_email',
			'url'         => 'esc_url_raw',
			'tel'         => 'sanitize_text_field',
			'password'    => [ self::class, 'sanitize_password' ],

			// Numeric inputs
			'number'      => [ self::class, 'sanitize_number' ],

			// Date/Time inputs
			'date'        => [ self::class, 'sanitize_date' ],

			// Selection inputs
			'select'      => 'sanitize_text_field',
			'ajax_select' => 'sanitize_text_field',
			'radio'       => 'sanitize_text_field',
			'toggle'      => [ self::class, 'sanitize_toggle' ],

			// Special inputs
			'color'       => 'sanitize_hex_color',
			'hidden'      => 'sanitize_text_field',
		];

		// Allow early filtering before assignment
		self::$field_sanitizers = apply_filters( 'wp_flyout_register_field_sanitizers', $sanitizers );
	}

	/**
	 * Register component sanitizers
	 *
	 * Only includes components that actually submit data with the form.
	 */
	private static function register_component_sanitizers(): void {
		$sanitizers = [
			'line_items'     => [ self::class, 'sanitize_line_items' ],
			'files'          => [ self::class, 'sanitize_files' ],
			'image_gallery'  => [ self::class, 'sanitize_image_gallery' ],
			'tags'           => [ self::class, 'sanitize_tags' ],
			'card_choice'    => [ self::class, 'sanitize_card_choice' ],
			'feature_list'   => [ self::class, 'sanitize_feature_list' ],
			'key_value_list' => [ self::class, 'sanitize_key_value_list' ]
		];

		// Allow early filtering before assignment
		self::$component_sanitizers = apply_filters( 'wp_flyout_register_component_sanitizers', $sanitizers );
	}

	/**
	 * Sanitize a value based on field configuration
	 *
	 * @param mixed $value        Value to sanitize
	 * @param array $field_config Field configuration
	 *
	 * @return mixed Sanitized value
	 */
	public static function sanitize_field( $value, array $field_config ) {
		self::ensure_initialized();

		// Use custom sanitizer if provided
		if ( ! empty( $field_config['sanitize_callback'] ) && is_callable( $field_config['sanitize_callback'] ) ) {
			return call_user_func( $field_config['sanitize_callback'], $value );
		}

		$type = $field_config['type'] ?? 'text';

		// Apply per-type filter
		$value = apply_filters( "wp_flyout_sanitize_field_{$type}", $value, $field_config );

		// Check if it's a data-submitting component
		if ( isset( self::$component_sanitizers[ $type ] ) ) {
			$sanitizer = apply_filters( "wp_flyout_component_sanitizer_{$type}", self::$component_sanitizers[ $type ] );

			return call_user_func( $sanitizer, $value );
		}

		// Check field sanitizers
		if ( isset( self::$field_sanitizers[ $type ] ) ) {
			$sanitizer = apply_filters( "wp_flyout_field_sanitizer_{$type}", self::$field_sanitizers[ $type ] );

			return call_user_func( $sanitizer, $value );
		}

		// Default fallback with filter
		$default_sanitizer = is_array( $value )
			? [ self::class, 'sanitize_array' ]
			: 'sanitize_text_field';

		$default_sanitizer = apply_filters( 'wp_flyout_default_sanitizer', $default_sanitizer, $value, $field_config );

		return call_user_func( $default_sanitizer, $value );
	}

	/**
	 * Sanitize array values
	 *
	 * @param array $value Array to sanitize
	 *
	 * @return array Sanitized array
	 */
	public static function sanitize_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize form data based on fields configuration
	 *
	 * @param array $raw_data Raw form data
	 * @param array $fields   Field configurations
	 *
	 * @return array Sanitized data
	 */
	public static function sanitize_form_data( array $raw_data, array $fields ): array {
		self::ensure_initialized();

		// Apply pre-sanitization filter
		$raw_data = apply_filters( 'wp_flyout_before_sanitize', $raw_data, $fields );

		$sanitized = [];

		// Sanitize configured fields
		foreach ( $fields as $field_key => $field_config ) {
			$field_name = $field_config['name'] ?? $field_key;

			if ( ! isset( $raw_data[ $field_name ] ) ) {
				continue;
			}

			$sanitized[ $field_name ] = self::sanitize_field(
				$raw_data[ $field_name ],
				$field_config
			);
		}

		// Sanitize any additional fields not in config (like 'id')
		foreach ( $raw_data as $key => $value ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( $value );
			}
		}

		// Apply post-sanitization filter
		return apply_filters( 'wp_flyout_after_sanitize', $sanitized, $raw_data, $fields );
	}

	// ========================================
	// Field Sanitization Methods
	// ========================================

	/**
	 * Sanitize number field
	 */
	public static function sanitize_number( $value ) {
		if ( str_contains( (string) $value, '.' ) ) {
			return floatval( $value );
		}

		return intval( $value );
	}

	/**
	 * Sanitize password field
	 */
	public static function sanitize_password( $value ): string {
		return trim( (string) $value );
	}

	/**
	 * Sanitize date field
	 */
	public static function sanitize_date( $value ): string {
		$date = DateTime::createFromFormat( 'Y-m-d', $value );

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/**
	 * Sanitize toggle/checkbox field
	 */
	public static function sanitize_toggle( $value ): string {
		return $value ? '1' : '0';
	}

	// ========================================
	// Component Sanitization Methods
	// ========================================

	/**
	 * Sanitize tags array
	 */
	public static function sanitize_tags( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize card choice selection
	 */
	public static function sanitize_card_choice( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize line items array
	 */
	public static function sanitize_line_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $items as $item ) {
			$item_id = absint( $item['id'] ?? 0 );

			if ( $item_id <= 0 ) {
				continue;
			}

			$sanitized[] = [
				'id'       => $item_id,
				'name'     => sanitize_text_field( $item['name'] ?? '' ),
				'quantity' => max( 1, absint( $item['quantity'] ?? 1 ) ),
				'price'    => absint( $item['price'] ?? 0 ),

			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize files array
	 */
	public static function sanitize_files( $files ): array {
		if ( ! is_array( $files ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $files as $file ) {
			$url           = esc_url_raw( $file['url'] ?? '' );
			$attachment_id = absint( $file['attachment_id'] ?? 0 );

			if ( empty( $url ) && $attachment_id <= 0 ) {
				continue;
			}

			$sanitized[] = [
				'name'          => sanitize_text_field( $file['name'] ?? '' ),
				'url'           => $url,
				'attachment_id' => $attachment_id,
				'lookup_key'    => sanitize_key( $file['lookup_key'] ?? '' ),
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize image gallery data
	 *
	 * Ensures only valid attachment IDs are stored.
	 * Accepts both simple array of IDs and legacy format with attachment_id/id keys.
	 *
	 * @param array|mixed $data Raw gallery data
	 *
	 * @return array Sanitized array of attachment IDs
	 */
	public static function sanitize_image_gallery( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $data as $item ) {
			$attachment_id = 0;

			if ( is_numeric( $item ) ) {
				// Simple ID format (preferred)
				$attachment_id = absint( $item );
			} elseif ( is_array( $item ) ) {
				// Legacy format with attachment_id or id key
				if ( isset( $item['attachment_id'] ) ) {
					$attachment_id = absint( $item['attachment_id'] );
				} elseif ( isset( $item['id'] ) ) {
					$attachment_id = absint( $item['id'] );
				}
			}

			// Verify it's a valid attachment
			if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
				$sanitized[] = $attachment_id;
			}
		}

		return array_values( $sanitized ); // Reset array keys
	}

	/**
	 * Sanitize MetaKeyValue component data
	 *
	 * Removes entries with empty keys and sanitizes both keys and values.
	 * Keys are required and will be slugified for consistency.
	 *
	 * @param array|mixed $data Raw metadata array
	 *
	 * @return array Sanitized metadata array
	 */
	public static function sanitize_key_value_list( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $data as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Get and sanitize key - required field
			$key = isset( $item['key'] ) ? sanitize_key( $item['key'] ) : '';

			// Skip if key is empty
			if ( empty( $key ) ) {
				continue;
			}

			// Sanitize value - can be empty
			$value = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';

			$sanitized[] = [
				'key'   => $key,
				'value' => $value
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize FeatureList component data
	 *
	 * Removes empty items and sanitizes text values.
	 *
	 * @param array|mixed $data Raw features array
	 *
	 * @return array Sanitized features array
	 */
	public static function sanitize_feature_list( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $data as $item ) {
			// Handle both string and array formats
			if ( is_array( $item ) ) {
				$value = $item['value'] ?? $item['text'] ?? '';
			} else {
				$value = $item;
			}

			// Sanitize and trim
			$value = sanitize_text_field( trim( $value ) );

			// Skip empty values
			if ( empty( $value ) ) {
				continue;
			}

			$sanitized[] = $value;
		}

		return $sanitized;
	}

	// ========================================
	// Registration Methods
	// ========================================

	/**
	 * Register custom field sanitizer
	 */
	public static function register_field_sanitizer( string $type, callable $sanitizer ): void {
		self::ensure_initialized();
		self::$field_sanitizers[ $type ] = $sanitizer;

		// Trigger action for tracking
		do_action( 'wp_flyout_registered_field_sanitizer', $type, $sanitizer );
	}

	/**
	 * Register custom component sanitizer
	 */
	public static function register_component_sanitizer( string $type, callable $sanitizer ): void {
		self::ensure_initialized();
		self::$component_sanitizers[ $type ] = $sanitizer;

		// Trigger action for tracking
		do_action( 'wp_flyout_registered_component_sanitizer', $type, $sanitizer );
	}

	/**
	 * Unregister field sanitizer
	 */
	public static function unregister_field_sanitizer( string $type ): bool {
		if ( isset( self::$field_sanitizers[ $type ] ) ) {
			unset( self::$field_sanitizers[ $type ] );

			return true;
		}

		return false;
	}

	/**
	 * Unregister component sanitizer
	 */
	public static function unregister_component_sanitizer( string $type ): bool {
		if ( isset( self::$component_sanitizers[ $type ] ) ) {
			unset( self::$component_sanitizers[ $type ] );

			return true;
		}

		return false;
	}

	/**
	 * Get all registered field sanitizers
	 */
	public static function get_field_sanitizers(): array {
		self::ensure_initialized();

		return self::$field_sanitizers;
	}

	/**
	 * Get all registered component sanitizers
	 */
	public static function get_component_sanitizers(): array {
		self::ensure_initialized();

		return self::$component_sanitizers;
	}

	/**
	 * Ensure sanitizers are initialized
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

}