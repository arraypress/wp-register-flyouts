<?php
/**
 * Field and Component Sanitizer
 *
 * Centralized sanitization logic with filterable sanitizers for extensibility.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use DateTime;

class Sanitizer {

	/**
	 * Registered sanitizers for all field and component types.
	 *
	 * @var array<string, callable>
	 */
	private static array $sanitizers = [];

	/**
	 * Whether sanitizers have been initialized.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize default sanitizers.
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::register_defaults();

		self::$sanitizers = apply_filters( 'wp_flyout_sanitizers', self::$sanitizers );

		self::$initialized = true;
	}

	/**
	 * Register all default sanitizers.
	 *
	 * Field types and component types share a single registry.
	 * A type is a type — whether it renders as a "component" or "field"
	 * is a rendering distinction, not a sanitization one.
	 *
	 * @return void
	 */
	private static function register_defaults(): void {
		self::$sanitizers = [
			// Text inputs
			'text'           => 'sanitize_text_field',
			'textarea'       => 'sanitize_textarea_field',
			'email'          => 'sanitize_email',
			'url'            => 'esc_url_raw',
			'tel'            => 'sanitize_text_field',
			'password'       => [ self::class, 'sanitize_password' ],

			// Numeric inputs
			'number'         => [ self::class, 'sanitize_number' ],

			// Date/Time inputs
			'date'           => [ self::class, 'sanitize_date' ],

			// Selection inputs
			'select'         => 'sanitize_text_field',
			'ajax_select'    => [ self::class, 'sanitize_ajax_select' ],
			'radio'          => 'sanitize_text_field',
			'toggle'         => [ self::class, 'sanitize_toggle' ],

			// Special inputs
			'color'          => 'sanitize_hex_color',
			'hidden'         => 'sanitize_text_field',

			// Media
			'header'         => [ self::class, 'sanitize_image' ],
			'image'          => [ self::class, 'sanitize_image' ],

			// Components that submit data
			'price_config'   => [ self::class, 'sanitize_price_config' ],
			'line_items'     => [ self::class, 'sanitize_line_items' ],
			'files'          => [ self::class, 'sanitize_files' ],
			'gallery'        => [ self::class, 'sanitize_gallery' ],
			'card_choice'    => [ self::class, 'sanitize_card_choice' ],
			'feature_list'   => [ self::class, 'sanitize_feature_list' ],
			'key_value_list' => [ self::class, 'sanitize_key_value_list' ],
		];
	}

	/**
	 * Sanitize a value based on field configuration.
	 *
	 * @param mixed $value        Value to sanitize.
	 * @param array $field_config Field configuration.
	 *
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_field( $value, array $field_config ) {
		self::ensure_initialized();

		// Use custom sanitizer if provided.
		if ( ! empty( $field_config['sanitize_callback'] ) && is_callable( $field_config['sanitize_callback'] ) ) {
			return call_user_func( $field_config['sanitize_callback'], $value );
		}

		$type = $field_config['type'] ?? 'text';

		// Apply per-type filter.
		$value = apply_filters( "wp_flyout_sanitize_field_{$type}", $value, $field_config );

		// Look up sanitizer in the unified registry.
		if ( isset( self::$sanitizers[ $type ] ) ) {
			$sanitizer = apply_filters( "wp_flyout_sanitizer_{$type}", self::$sanitizers[ $type ] );

			return call_user_func( $sanitizer, $value );
		}

		// Default fallback.
		$default_sanitizer = is_array( $value )
			? [ self::class, 'sanitize_array' ]
			: 'sanitize_text_field';

		$default_sanitizer = apply_filters( 'wp_flyout_default_sanitizer', $default_sanitizer, $value, $field_config );

		return call_user_func( $default_sanitizer, $value );
	}

	/**
	 * Sanitize array values.
	 *
	 * @param array $value Array to sanitize.
	 *
	 * @return array Sanitized array.
	 */
	public static function sanitize_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize form data based on fields configuration.
	 *
	 * @param array $raw_data Raw form data.
	 * @param array $fields   Field configurations.
	 *
	 * @return array Sanitized data.
	 */
	public static function sanitize_form_data( array $raw_data, array $fields ): array {
		self::ensure_initialized();

		$raw_data = apply_filters( 'wp_flyout_before_sanitize', $raw_data, $fields );

		$sanitized = [];

		// Sanitize configured fields.
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

		// Sanitize any additional fields not in config (like 'id').
		foreach ( $raw_data as $key => $value ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( $value );
			}
		}

		return apply_filters( 'wp_flyout_after_sanitize', $sanitized, $raw_data, $fields );
	}

	// =========================================================================
	// FIELD SANITIZATION METHODS
	// =========================================================================

	/**
	 * Sanitize number field.
	 */
	public static function sanitize_number( $value ) {
		if ( str_contains( (string) $value, '.' ) ) {
			return floatval( $value );
		}

		return intval( $value );
	}

	/**
	 * Sanitize password field.
	 */
	public static function sanitize_password( $value ): string {
		return trim( (string) $value );
	}

	/**
	 * Sanitize date field.
	 */
	public static function sanitize_date( $value ): string {
		$date = DateTime::createFromFormat( 'Y-m-d', $value );

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/**
	 * Sanitize toggle/checkbox field.
	 */
	public static function sanitize_toggle( $value ): string {
		return $value ? '1' : '0';
	}

	// =========================================================================
	// COMPONENT SANITIZATION METHODS
	// =========================================================================

	/**
	 * Sanitize card choice selection.
	 */
	public static function sanitize_card_choice( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize line items array.
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
	 * Sanitize files array.
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
	 * Sanitize gallery data.
	 *
	 * Ensures only valid image attachment IDs are stored.
	 *
	 * @param array|mixed $data Raw gallery data.
	 *
	 * @return array Sanitized array of attachment IDs.
	 */
	public static function sanitize_gallery( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $data as $item ) {
			$attachment_id = 0;

			if ( is_numeric( $item ) ) {
				$attachment_id = absint( $item );
			} elseif ( is_array( $item ) ) {
				if ( isset( $item['attachment_id'] ) ) {
					$attachment_id = absint( $item['attachment_id'] );
				} elseif ( isset( $item['id'] ) ) {
					$attachment_id = absint( $item['id'] );
				}
			}

			if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
				$sanitized[] = $attachment_id;
			}
		}

		return array_values( $sanitized );
	}

	/**
	 * Sanitize key-value list component data.
	 *
	 * @param array|mixed $data Raw metadata array.
	 *
	 * @return array Sanitized metadata array.
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

			$key = isset( $item['key'] ) ? sanitize_key( $item['key'] ) : '';

			if ( empty( $key ) ) {
				continue;
			}

			$value = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';

			$sanitized[] = [
				'key'   => $key,
				'value' => $value,
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize feature list component data.
	 *
	 * @param array|mixed $data Raw features array.
	 *
	 * @return array Sanitized features array.
	 */
	public static function sanitize_feature_list( $data ): array {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $data as $item ) {
			if ( is_array( $item ) ) {
				$value = $item['value'] ?? $item['text'] ?? '';
			} else {
				$value = $item;
			}

			$value = sanitize_text_field( trim( $value ) );

			if ( empty( $value ) ) {
				continue;
			}

			$sanitized[] = $value;
		}

		return $sanitized;
	}

	/**
	 * Sanitize ajax_select field.
	 *
	 * Handles single values (string), multiple values (array), and tags.
	 *
	 * @param mixed $value Raw value from form submission.
	 *
	 * @return string|array Sanitized value.
	 */
	public static function sanitize_ajax_select( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize price config data.
	 *
	 * Converts decimal amounts to cents and validates interval.
	 */
	public static function sanitize_price_config( $value ): array {
		if ( ! is_array( $value ) ) {
			return [
				'amount'                   => 0,
				'compare_at_amount'        => 0,
				'currency'                 => 'USD',
				'recurring_interval'       => null,
				'recurring_interval_count' => null,
			];
		}

		$currency = strtoupper( sanitize_text_field( $value['currency'] ?? 'USD' ) );

		$raw_amount     = $value['amount'] ?? 0;
		$raw_compare_at = $value['compare_at_amount'] ?? 0;

		if ( function_exists( 'to_currency_cents' ) ) {
			$amount            = to_currency_cents( (float) $raw_amount, $currency );
			$compare_at_amount = to_currency_cents( (float) $raw_compare_at, $currency );
		} else {
			$amount            = (int) round( (float) $raw_amount * 100 );
			$compare_at_amount = (int) round( (float) $raw_compare_at * 100 );
		}

		if ( $compare_at_amount <= $amount ) {
			$compare_at_amount = 0;
		}

		$valid_intervals = [ 'day', 'week', 'month', 'year' ];
		$interval        = sanitize_text_field( $value['recurring_interval'] ?? '' );
		$interval_count  = absint( $value['recurring_interval_count'] ?? 1 );

		if ( ! in_array( $interval, $valid_intervals, true ) ) {
			$interval       = null;
			$interval_count = null;
		} else {
			$interval_count = max( 1, $interval_count );
		}

		return [
			'amount'                   => $amount,
			'compare_at_amount'        => $compare_at_amount,
			'currency'                 => $currency,
			'recurring_interval'       => $interval,
			'recurring_interval_count' => $interval_count,
		];
	}

	/**
	 * Sanitize image picker data (used by both header and image components).
	 *
	 * @param mixed $value Raw value (attachment ID).
	 *
	 * @return int Sanitized attachment ID (0 if invalid).
	 */
	public static function sanitize_image( $value ): int {
		$attachment_id = absint( $value );

		if ( $attachment_id <= 0 ) {
			return 0;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return 0;
		}

		return $attachment_id;
	}

	// =========================================================================
	// REGISTRATION API
	// =========================================================================

	/**
	 * Register a sanitizer for a field or component type.
	 *
	 * @param string   $type      Type identifier.
	 * @param callable $sanitizer Sanitizer callback.
	 *
	 * @return void
	 */
	public static function register( string $type, callable $sanitizer ): void {
		self::ensure_initialized();
		self::$sanitizers[ $type ] = $sanitizer;

		do_action( 'wp_flyout_registered_sanitizer', $type, $sanitizer );
	}

	/**
	 * Unregister a sanitizer.
	 *
	 * @param string $type Type identifier.
	 *
	 * @return bool True if sanitizer was unregistered.
	 */
	public static function unregister( string $type ): bool {
		if ( isset( self::$sanitizers[ $type ] ) ) {
			unset( self::$sanitizers[ $type ] );

			return true;
		}

		return false;
	}

	/**
	 * Get all registered sanitizers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_sanitizers(): array {
		self::ensure_initialized();

		return self::$sanitizers;
	}

	// =========================================================================
	// BACKWARDS COMPATIBILITY
	// =========================================================================

	/**
	 * Register a field sanitizer.
	 *
	 * @deprecated Use Sanitizer::register() instead.
	 *
	 * @param string   $type      Field type.
	 * @param callable $sanitizer Sanitizer callback.
	 *
	 * @return void
	 */
	public static function register_field_sanitizer( string $type, callable $sanitizer ): void {
		self::register( $type, $sanitizer );
	}

	/**
	 * Register a component sanitizer.
	 *
	 * @deprecated Use Sanitizer::register() instead.
	 *
	 * @param string   $type      Component type.
	 * @param callable $sanitizer Sanitizer callback.
	 *
	 * @return void
	 */
	public static function register_component_sanitizer( string $type, callable $sanitizer ): void {
		self::register( $type, $sanitizer );
	}

	// =========================================================================
	// UTILITY
	// =========================================================================

	/**
	 * Ensure sanitizers are initialized.
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

}