<?php
/**
 * Form Field Component
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\FieldKit\Field;
use ArrayPress\FieldKit\Registry;
use ArrayPress\FieldKit\Renderer;
use ArrayPress\RegisterFlyouts\Renderable;

/**
 * One form control inside a flyout.
 *
 * This was seventeen render methods and six hundred lines, rendering the same
 * seventeen types wp-field-kit already rendered — the third copy of that job
 * in this codebase, and the one that had drifted furthest. It kept its own
 * spelling of a link's fields, its own idea of what a toggle is, and no
 * accessibility at all beyond a `<label for>`.
 *
 * What is left is the translation between a flyout's configuration and the
 * kit's, and the flyout wrapper the panel's own stylesheet expects. Every
 * type the kit knows is now available here rather than seventeen, and each
 * one arrives with its labelling, its description association, its required
 * state and its conditional logic already correct.
 *
 * Two things in the old configuration have no equivalent and are translated
 * rather than dropped: `ajax_select` is the kit's `ajax`, and `data_callback`
 * is a value resolved at render time, which the kit has no notion of because
 * a field set reads its values from a context.
 */
class FormField implements Renderable {

	/**
	 * Field configuration, in this library's shape.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The type registry.
	 *
	 * @var Registry
	 */
	private Registry $registry;

	/**
	 * Spellings this library used that the kit names differently.
	 *
	 * @var array<string, string>
	 */
	private const TYPE_ALIASES = [
		'ajax_select' => 'ajax',

		// A number with a unit, which is what the kit's amount type is. This
		// library's was 376 lines and a stylesheet with a chevron SVG of its
		// own.
		'unit_input'  => 'amount_type',

		// Three lists this library drew by hand — add, remove and reorder,
		// three times over — which the kit now has as repeaters with their
		// columns already decided.
		'feature_list'   => 'list',
		'key_value_list' => 'key_value',
		'file_manager'   => 'files',
	];

	/**
	 * Construct.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 */
	public function __construct( array $config ) {
		$this->registry = new Registry();
		$this->config   = $this->normalize( $config );
	}

	/**
	 * Translate one flyout field configuration into the kit's.
	 *
	 * Public and static because the flyout renders through a field set now:
	 * the set builds the Field and reads its value from a context, and this
	 * is the only part that is still about the difference between the two
	 * libraries' spellings.
	 *
	 * @param array<string, mixed> $config Flyout field configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function to_kit( array $config ): array {
		$type = (string) ( $config['type'] ?? 'text' );

		$config['type'] = self::TYPE_ALIASES[ $type ] ?? $type;

		// The unit input's spellings. Same three ideas under different
		// names: what the units are, which one is chosen, and what the chosen
		// one is called in the submission.
		if ( 'amount_type' === $config['type'] ) {
			foreach (
				[
					'units'      => 'type_options',
					'unit_value' => 'current_type',
					'unit_name'  => 'type_meta_key',
				] as $theirs => $ours
			) {
				if ( isset( $config[ $theirs ] ) && ! isset( $config[ $ours ] ) ) {
					$config[ $ours ] = $config[ $theirs ];
				}

				unset( $config[ $theirs ] );
			}

			// A single unit is a fixed one, not a select of one option.
			if ( isset( $config['type_options'] ) && 1 === count( (array) $config['type_options'] ) ) {
				$config['unit'] = (string) reset( $config['type_options'] );

				unset( $config['type_options'], $config['current_type'] );
			}
		}

		// This library has always spelled a search callback `callback`, and
		// the kit spells it `search_callback` — the name it registers a
		// search source under. Without the translation an ajax field in a
		// flyout rendered a combobox pointed at a source nothing had ever
		// registered, and every search came back empty.
		if ( isset( $config['callback'] ) && ! isset( $config['search_callback'] ) ) {
			$config['search_callback'] = $config['callback'];
		}

		// Two components that were compound fields all along. Each was a
		// couple of hundred lines of PHP, a stylesheet with its own
		// hand-rolled chevron SVG, and a script to show one control when
		// another changed — which is what `depends` does, and what the kit
		// has done for every other field for a while.
		if ( in_array( $config['type'], [ 'price_config', 'discount_config' ], true ) ) {
			$config = self::to_kit_group( $config );
		}

		// These three carry their rows in configuration under `items`, where
		// the kit reads a repeater's rows from the context. Handed over as
		// the field's default so a flyout that passes them still shows them.
		//
		// One spelling differs: a file manager's rows call the URL `url` and
		// the kit's files type calls it `file`.
		if ( isset( $config['items'] ) && is_array( $config['items'] )
			&& in_array( $config['type'], [ 'list', 'key_value', 'files' ], true ) ) {

			$config['default'] = 'files' === $config['type']
				? array_map( [ self::class, 'to_kit_file' ], $config['items'] )
				: $config['items'];

			unset( $config['items'] );
		}

		// A value resolved at render time. A field set reads from a context,
		// so this is resolved here and handed over as the field's default —
		// the context has nothing to read for it.
		if ( isset( $config['data_callback'] ) && is_callable( $config['data_callback'] ) ) {
			$config['default'] = call_user_func( $config['data_callback'] );
			unset( $config['data_callback'] );
		}

		// And a literal one, for the same reason. This library has always let
		// a field carry its own `value`, which the kit has no notion of — a
		// field set reads from a context and falls back to a default. Without
		// the translation every field written that way rendered empty, which
		// is what the demo's own coupon code and weight did.
		if ( isset( $config['value'] ) && ! isset( $config['default'] )
			&& '' !== $config['value'] && null !== $config['value'] ) {

			$config['default'] = $config['value'];
		}

		unset( $config['value'] );

		return $config;
	}

	/**
	 * A price or discount configuration, as a group of kit fields.
	 *
	 * Both are the same shape: an amount with a unit beside it, and a couple
	 * of things that only matter depending on what the unit says. That is a
	 * group with a `depends` on one of its children, and it needs no markup,
	 * no stylesheet and no script of its own.
	 *
	 * The submitted shape is unchanged — `price[amount]`, `price[currency]`
	 * — because a group stores its children under one key, which is what the
	 * components already did.
	 *
	 * @param array<string, mixed> $config Flyout field configuration.
	 *
	 * @return array<string, mixed>
	 */
	private static function to_kit_group( array $config ): array {
		$discount = 'discount_config' === $config['type'];
		$currency = (string) ( $config['currency'] ?? 'USD' );

		$fields = [
			'amount' => [
				'type'          => 'amount_type',
				'label'         => __( 'Amount', 'arraypress' ),
				'default'       => $config['amount'] ?? 0,
				'min'           => 0,
				'step'          => 0.01,
				'type_meta_key' => $discount ? 'rate_type' : 'currency',
				'type_default'  => $discount ? 'percent' : $currency,
				'current_type'  => $discount
					? (string) ( $config['rate_type'] ?? 'percent' )
					: $currency,
				'type_options'  => $discount
					? [
						'percent' => '%',
						'fixed'   => (string) ( $config['currency_symbol'] ?? '$' ),
					]
					: self::currency_options( $config ),
			],
		];

		if ( $discount ) {
			$fields['duration'] = [
				'type'    => 'select',
				'label'   => __( 'Duration', 'arraypress' ),
				'default' => $config['duration'] ?? 'once',
				'options' => [
					'once'      => __( 'Once', 'arraypress' ),
					'forever'   => __( 'Forever', 'arraypress' ),
					'repeating' => __( 'Multiple months', 'arraypress' ),
				],
			];

			// Shown only when the duration says so. A script used to do this
			// by toggling an inline style, which meant the field was in the
			// tab order and readable by a screen reader while invisible.
			$fields['duration_in_months'] = [
				'type'    => 'number',
				'label'   => __( 'Months', 'arraypress' ),
				'default' => $config['duration_in_months'] ?? 1,
				'min'     => 1,
				'depends' => [ 'duration' => 'repeating' ],
			];

			if ( ! empty( $config['show_redemptions'] ) ) {
				$fields['max_redemptions'] = [
					'type'        => 'number',
					'label'       => __( 'Maximum redemptions', 'arraypress' ),
					'default'     => $config['max_redemptions'] ?? '',
					'min'         => 1,
					'description' => __( 'Leave empty for no limit.', 'arraypress' ),
				];
			}
		} else {
			$fields['recurring_interval'] = [
				'type'        => 'select',
				'label'       => __( 'Billing period', 'arraypress' ),
				'default'     => $config['recurring_interval'] ?? '',
				'empty_label' => __( 'One-off', 'arraypress' ),
				'options'     => [
					'day'   => __( 'Daily', 'arraypress' ),
					'week'  => __( 'Weekly', 'arraypress' ),
					'month' => __( 'Monthly', 'arraypress' ),
					'year'  => __( 'Yearly', 'arraypress' ),
				],
			];

			$fields['recurring_interval_count'] = [
				'type'        => 'number',
				'label'       => __( 'Every', 'arraypress' ),
				'default'     => $config['recurring_interval_count'] ?? 1,
				'min'         => 1,
				'depends'     => 'recurring_interval',
				'description' => __( 'How many periods between charges.', 'arraypress' ),
			];
		}

		return [
			'type'          => 'group',
			'name'          => $config['name'] ?? ( $discount ? 'discount' : 'price' ),
			'label'         => $config['label'] ?? '',
			'description'   => $config['description'] ?? '',
			'wrapper_class' => $config['wrapper_class'] ?? '',
			'fields'        => $fields,
		];
	}

	/**
	 * The currencies a price can be in.
	 *
	 * @param array<string, mixed> $config Flyout field configuration.
	 *
	 * @return array<string, string>
	 */
	private static function currency_options( array $config ): array {
		$codes = (array) ( $config['currencies'] ?? [] );

		if ( [] === $codes ) {
			$codes = [ (string) ( $config['currency'] ?? 'USD' ) ];
		}

		// A list of codes, or a map of code to label — both are natural, and
		// which one a caller passes depends on where they got it from.
		return array_is_list( $codes ) ? array_combine( $codes, $codes ) : $codes;
	}

	/**
	 * One file row, in the kit's spelling.
	 *
	 * @param mixed $item A file manager row.
	 *
	 * @return array<string, mixed>
	 */
	private static function to_kit_file( $item ): array {
		$item = (array) $item;

		if ( isset( $item['url'] ) && ! isset( $item['file'] ) ) {
			$item['file'] = $item['url'];
		}

		return $item;
	}

	/**
	 * The flyout's own wrapper around a rendered control.
	 *
	 * The panel's stylesheet keys on this; the kit's wrapper sits inside it
	 * and carries the field's conditions.
	 *
	 * @param array<string, mixed> $config Flyout field configuration.
	 * @param string               $inner  The rendered control.
	 *
	 * @return string
	 */
	public static function wrap( array $config, string $inner ): string {
		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr(
				trim(
					'wp-flyout-field field-type-' . (string) ( $config['type'] ?? 'text' )
					. ' ' . (string) ( $config['wrapper_class'] ?? '' )
				)
			),
			$inner
		);
	}

	/**
	 * Normalize the configuration.
	 *
	 * @param array<string, mixed> $config Raw configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function normalize( array $config ): array {
		$config = array_merge(
			[
				'type'          => 'text',
				'name'          => '',
				'id'            => '',
				'label'         => '',
				'value'         => '',
				'description'   => '',
				'placeholder'   => '',
				'required'      => false,
				'disabled'      => false,
				'readonly'      => false,
				'class'         => '',
				'wrapper_class' => '',
				'data_callback' => null,
			],
			$config
		);

		$type = (string) $config['type'];

		$config['type'] = self::TYPE_ALIASES[ $type ] ?? $type;

		if ( '' === (string) $config['id'] && '' !== (string) $config['name'] ) {
			$config['id'] = sanitize_key( (string) $config['name'] );
		}

		// A value resolved at render time. The kit has no notion of one — a
		// field set reads from a context — so it is resolved here, before the
		// field is built.
		if ( is_callable( $config['data_callback'] ) ) {
			$config['value'] = call_user_func( $config['data_callback'] );
		}

		return $config;
	}

	/**
	 * Render the field.
	 *
	 * @return string
	 */
	public function render(): string {
		$field = $this->field();

		if ( null === $field ) {
			return '';
		}

		$inner = ( new Renderer() )->render( $field );

		// The flyout's own wrapper, which its stylesheet and its conditional
		// script both key on. The kit's wrapper is inside it and carries the
		// conditions; this one carries the panel's layout.
		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr(
				trim(
					'wp-flyout-field field-type-' . (string) $this->config['type']
					. ' ' . (string) $this->config['wrapper_class']
				)
			),
			$this->wrapper_attributes(),
			$inner
		);
	}

	/**
	 * The kit field this configuration describes.
	 *
	 * @return Field|null
	 */
	private function field(): ?Field {
		$type = (string) $this->config['type'];

		if ( ! $this->registry->has( $type ) ) {
			return null;
		}

		$resolved = $this->registry->get( $type );
		$key      = (string) ( $this->config['name'] ?: $this->config['id'] );

		return new Field(
			$key,
			$resolved,
			array_merge(
				$resolved->defaults(),

				// Without the keys this library owns. `name` and `id` are
				// what the kit is about to be handed as input_name and
				// input_id, `value` is resolved above because the kit reads
				// from a context instead, and the other two never leave here
				// -- so passing them on is handing the kit configuration
				// nothing reads, which it reports under WP_DEBUG once per
				// field per render. A panel of a dozen fields buried every
				// real warning on the screen under its own noise.
				array_diff_key(
					$this->config,
					array_flip( [ 'name', 'id', 'value', 'wrapper_class', 'data_callback' ] )
				),
				[
					'input_name' => (string) $this->config['name'],
					'input_id'   => (string) $this->config['id'],
				]
			),
			$this->config['value']
		);
	}

	/**
	 * Attributes the flyout puts on the wrapper.
	 *
	 * `data-depends` carries JSON and is written with single quotes, which is
	 * why it is not simply escaped alongside the rest.
	 *
	 * @return string
	 */
	private function wrapper_attributes(): string {
		$attributes = (array) ( $this->config['wrapper_attrs'] ?? [] );
		$markup     = '';

		foreach ( $attributes as $name => $value ) {
			if ( 'class' === $name ) {
				continue;
			}

			$markup .= 'data-depends' === $name
				? sprintf( " %s='%s'", esc_attr( (string) $name ), esc_attr( (string) $value ) )
				: sprintf( ' %s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
		}

		return $markup;
	}

	/**
	 * The types this component can render.
	 *
	 * Every type the kit knows, plus the spellings this library used for two
	 * of them. Read rather than listed, so it cannot fall behind the kit.
	 *
	 * @return string[]
	 */
	public static function types(): array {
		return array_values(
			array_unique(
				array_merge(
					( new Registry() )->accepted_ids(),
					array_keys( self::TYPE_ALIASES )
				)
			)
		);
	}
}
