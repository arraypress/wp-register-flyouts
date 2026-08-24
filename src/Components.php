<?php
/**
 * Component Registry
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use ArrayPress\FieldKit\Support\Resolve;
use ArrayPress\RegisterFlyouts\Utils\Runtime;
use InvalidArgumentException;

/**
 * What a flyout can put on a panel that is not a field.
 *
 * A field collects a value and the kit draws it. A component displays
 * something — a timeline, a set of stats, a table of line items — and this is
 * the list of them, in the same shape as the kit's own type registry: a
 * constant map, plus whatever a consumer registered on top.
 *
 * Each entry says three things. The class that draws it. The `data` its
 * configuration expects, which is what lets a component be populated from
 * whatever the `load` callback returned rather than spelled out by hand. And
 * the asset handle it needs, or null.
 *
 * There used to be two more — a `category` and a `description` — and five
 * public methods for reading them back. Nothing ever called any of the five.
 * They described a component browser that was never built, and a description
 * nobody reads is a comment that has to be kept true.
 */
class Components {

	/**
	 * Component type => class, data keys, asset handle.
	 *
	 * @var array<string, array{class: class-string, data: string|string[], asset: string|null}>
	 */
	private const COMPONENTS = [
		// ---- Display ----
		'header'         => [
			'class' => Components\Header::class,
			'data'  => [
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
				'fallback_attachment_id',
			],

			// The one asset that depends on configuration rather than type:
			// a header only needs the media frame when it is editable.
			'asset' => null,
		],
		'alert'          => [
			'class' => Components\Alert::class,
			'data'  => [ 'type', 'message', 'title' ],
			'asset' => null,
		],
		'empty_state'    => [
			'class' => Components\EmptyState::class,
			'data'  => [ 'icon', 'title', 'description', 'action_text' ],
			'asset' => null,
		],
		'timeline'       => [
			'class' => Components\Timeline::class,
			'data'  => 'items',
			'asset' => 'timeline',
		],
		'stats'          => [
			'class' => Components\Stats::class,
			'data'  => 'items',
			'asset' => 'stats',
		],

		// ---- Interactive ----
		'action_buttons' => [
			'class' => Components\ActionButtons::class,
			'data'  => 'buttons',
			'asset' => 'action-buttons',
		],
		'notes'          => [
			'class' => Components\Notes::class,
			'data'  => 'items',
			'asset' => 'notes',
		],
		'line_items'     => [
			'class' => Components\LineItems::class,
			'data'  => 'items',
			'asset' => 'line-items',
		],
		'refund_form'    => [
			'class' => Components\RefundForm::class,
			'data'  => [ 'amount_paid', 'amount_refunded', 'currency' ],
			'asset' => 'refund-form',
		],


		// ---- Layout ----
		'accordion'      => [
			'class' => Components\Accordion::class,
			'data'  => 'items',
			'asset' => 'accordion',
		],

		// ---- Data ----
		'data_table'     => [
			'class' => Components\DataTable::class,
			'data'  => [ 'columns', 'data' ],
			'asset' => null,
		],
		'info_grid'      => [
			'class' => Components\InfoGrid::class,
			'data'  => 'items',
			'asset' => null,
		],
		'payment_method' => [
			'class' => Components\PaymentMethod::class,
			'data'  => [
				'payment_method',
				'payment_brand',
				'payment_last4',
				'stripe_risk_score',
				'stripe_risk_level',
			],
			'asset' => 'payment-method',
		],
		'price_summary'  => [
			'class' => Components\PriceSummary::class,
			'data'  => [ 'items', 'subtotal', 'tax', 'discount', 'total', 'currency' ],
			'asset' => 'price-summary',
		],
	];

	/*
	 * Dropped rather than moved.
	 *
	 * `action_menu` was a dropdown of actions inside a panel that already has
	 * an action bar along the bottom and an `action_buttons` component — a
	 * third way to trigger the same thing, and the one that did not work.
	 *
	 * `articles` drew article cards with images and excerpts, which is a
	 * content-listing widget and has nothing to do with editing a record.
	 */

	/*
	 * Not registered, deliberately, and each for the same reason.
	 *
	 * `feature_list`, `key_value_list` and `file_manager` were lists with add,
	 * remove and reorder written out by hand — about 1,700 lines of stylesheet
	 * and script between them — and each is a repeater the kit has with its
	 * columns already decided: `list`, `key_value` and `files`.
	 *
	 * `gallery` and `image` duplicated a kit type each, with their own markup,
	 * their own unprefixed class names and 290 lines of stylesheet — a picker
	 * that looked like neither core nor the rest of the panel.
	 *
	 * `card_choice`, `ajax_select` and `separator` likewise.
	 *
	 * `unit_input` was a number with a unit, which is what `amount_type` is,
	 * and `code_generator` is a kit type now — both were carrying a stylesheet
	 * with a hand-rolled chevron SVG of their own.
	 *
	 * `price_config` and `discount_config` were compound fields all along: an
	 * amount with a unit beside it, and a couple of things that only matter
	 * depending on what the unit says. That is a group with a `depends`, and
	 * FormField expands them into one — same submitted shape, no markup, no
	 * stylesheet and no script.
	 *
	 * FormField aliases every one of the old names, so a configuration
	 * written against them still works; it reaches the kit instead of a
	 * second implementation.
	 */

	/**
	 * Components a consumer registered at runtime.
	 *
	 * @var array<string, array{class: class-string, data: string|string[], asset: string|null}>
	 */
	private static array $custom = [];

	/**
	 * Register a component of your own.
	 *
	 * @param string $type   Component type identifier.
	 * @param array  $config Component configuration.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the class is missing or does not exist.
	 */
	public static function register( string $type, array $config ): void {
		if ( ! isset( $config['class'] ) ) {
			throw new InvalidArgumentException(
				esc_html( sprintf( 'Component "%s" must have a class defined', $type ) )
			);
		}

		if ( ! class_exists( $config['class'] ) ) {
			throw new InvalidArgumentException(
				esc_html( sprintf( 'Component class "%s" does not exist', $config['class'] ) )
			);
		}

		self::$custom[ $type ] = [
			'class' => $config['class'],

			// `data_fields` was the old spelling, and a consumer's array is
			// the one place it could still appear.
			'data'  => $config['data'] ?? $config['data_fields'] ?? 'value',
			'asset' => $config['asset'] ?? null,
		];
	}

	/**
	 * Every registered component.
	 *
	 * @return array<string, array{class: class-string, data: string|string[], asset: string|null}>
	 */
	public static function all(): array {
		return array_merge( self::COMPONENTS, self::$custom );
	}

	/**
	 * A component's configuration.
	 *
	 * @param string $type Component type.
	 *
	 * @return array{class: class-string, data: string|string[], asset: string|null}|null
	 */
	public static function get( string $type ): ?array {
		return self::$custom[ $type ] ?? self::COMPONENTS[ $type ] ?? null;
	}

	/**
	 * Whether a type is a component rather than a field.
	 *
	 * @param string $type Component type to check.
	 *
	 * @return bool
	 */
	public static function is_component( string $type ): bool {
		return null !== self::get( $type );
	}

	/**
	 * Build a component.
	 *
	 * @param string $type   Component type.
	 * @param array  $config Component configuration.
	 *
	 * @return object|null Null when the type is not a component.
	 */
	public static function create( string $type, array $config ) {
		$component = self::get( $type );

		if ( null === $component ) {
			return null;
		}

		$class = $component['class'];

		$config = apply_filters( Runtime::hook( 'component_config' ), $config, $type, $class );
		$config = apply_filters( "wp_flyout_component_{$type}_config", $config );

		return new $class( $config );
	}

	/**
	 * The asset handle a component needs, if any.
	 *
	 * @param string $type   Component type.
	 * @param array  $config The field's configuration.
	 *
	 * @return string|null
	 */
	public static function get_asset( string $type, array $config = [] ): ?string {
		// A header only needs the media frame when it can be edited, which is
		// the one asset that depends on configuration rather than type.
		if ( 'header' === $type ) {
			return empty( $config['editable'] ) ? null : 'image-picker';
		}

		return self::get( $type )['asset'] ?? null;
	}

	/**
	 * Populate a component's configuration from a data source.
	 *
	 * A component is configured rather than valued: a timeline wants `items`,
	 * a payment method wants five keys. This finds them on whatever the
	 * `load` callback returned, so a flyout does not have to spell out what
	 * the row already knows.
	 *
	 * Two shapes are accepted, because both are natural. A source may hold
	 * the whole configuration at the field's own key — a `timeline_data()`
	 * method returning `[ 'items' => [ ... ] ]` — or it may hold each key
	 * separately, which is what a plain database row looks like.
	 *
	 * @param string $type      Component type.
	 * @param string $field_key Field identifier.
	 * @param mixed  $data      Data source, object or array.
	 *
	 * @return array<string, mixed>
	 */
	public static function resolve_data( string $type, string $field_key, $data ): array {
		$component = self::get( $type );

		if ( null === $component ) {
			return [ 'value' => self::resolve_value( $field_key, $data ) ];
		}

		$keys     = $component['data'];
		$resolved = self::resolve_value( $field_key, $data );

		// The whole configuration, at the field's own key.
		if ( is_array( $resolved ) ) {
			foreach ( (array) $keys as $key ) {
				if ( isset( $resolved[ $key ] ) ) {
					return $resolved;
				}
			}
		}

		// A component with one key takes whatever was there.
		if ( is_string( $keys ) ) {
			return [ $keys => $resolved ];
		}

		// Otherwise each key is looked up in its own right. `value` is the
		// exception: it means the field's own key, not a column called
		// "value".
		$result = [];

		foreach ( $keys as $key ) {
			$result[ $key ] = self::resolve_value( 'value' === $key ? $field_key : $key, $data );
		}

		return $result;
	}

	/**
	 * Find a named value on whatever the `load` callback returned.
	 *
	 * The walk is the kit's — `{key}_data()`, an array key, `get_{key}()`,
	 * then a property — because it is the same question a field set asks of
	 * the same object, and the two answers had better agree.
	 *
	 * @param string $key  Property, method or array key to resolve.
	 * @param mixed  $data Data source, object or array.
	 *
	 * @return mixed Null when it is not there.
	 */
	public static function resolve_value( string $key, $data ) {
		return Resolve::value( $data, $key );
	}
}
