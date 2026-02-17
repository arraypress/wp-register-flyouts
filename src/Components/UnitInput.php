<?php
/**
 * UnitInput Component
 *
 * Numeric input with a unit indicator (prefix or suffix).
 * Supports fixed units (e.g. always "kg") or selectable units
 * (e.g. dropdown of "kg", "lb", "oz").
 *
 * Common uses: currency amounts, percentages, weights, dimensions,
 * durations, data sizes, temperatures, etc.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class UnitInput implements Renderable {

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::get_defaults() );

		if ( empty( $this->config['id'] ) && ! empty( $this->config['name'] ) ) {
			$this->config['id'] = sanitize_key( $this->config['name'] );
		}
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	private static function get_defaults(): array {
		return [
			'id'            => '',
			'name'          => '',
			'label'         => '',
			'description'   => '',
			'value'         => '',
			'placeholder'   => '0.00',
			'inputmode'     => 'decimal', // decimal, numeric
			'min'           => null,
			'max'           => null,
			'step'          => null,
			'required'      => false,
			'disabled'      => false,
			'readonly'      => false,

			// Unit configuration
			'unit'          => '',         // Fixed unit string (e.g. '%', 'kg')
			'units'         => [],         // Selectable units: [ 'kg' => 'kg', 'lb' => 'lb' ]
			'unit_value'    => '',         // Currently selected unit (when using units)
			'unit_name'     => '',         // Form name for unit select (auto-generated if empty)
			'unit_position' => 'suffix',   // prefix or suffix

			// Wrapper
			'class'         => '',
			'wrapper_class' => '',
		];
	}

	/**
	 * Check if unit is selectable (dropdown) vs fixed (static text)
	 *
	 * @return bool
	 */
	private function is_selectable(): bool {
		return ! empty( $this->config['units'] ) && is_array( $this->config['units'] ) && count( $this->config['units'] ) > 1;
	}

	/**
	 * Get the display unit string for fixed mode
	 *
	 * @return string
	 */
	private function get_fixed_unit(): string {
		if ( ! empty( $this->config['unit'] ) ) {
			return $this->config['unit'];
		}

		// If units array has exactly one entry, treat as fixed
		if ( ! empty( $this->config['units'] ) && count( $this->config['units'] ) === 1 ) {
			return (string) array_key_first( $this->config['units'] );
		}

		return '';
	}

	/**
	 * Get the form name for the unit select
	 *
	 * @return string
	 */
	private function get_unit_name(): string {
		if ( ! empty( $this->config['unit_name'] ) ) {
			return $this->config['unit_name'];
		}

		return $this->config['name'] . '_unit';
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		$wrapper_classes = [
			'wp-flyout-field',
			'field-type-unit-input',
			$this->config['wrapper_class'],
		];

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>">
			<?php if ( ! empty( $this->config['label'] ) ) : ?>
				<label for="<?php echo esc_attr( $this->config['id'] ); ?>">
					<?php echo esc_html( $this->config['label'] ); ?>
					<?php if ( $this->config['required'] ) : ?>
						<span class="required">*</span>
					<?php endif; ?>
				</label>
			<?php endif; ?>

			<?php $this->render_input_group(); ?>

			<?php if ( ! empty( $this->config['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the input group (unit + input combined)
	 *
	 * @return void
	 */
	private function render_input_group(): void {
		$is_prefix    = $this->config['unit_position'] === 'prefix';
		$is_selectable = $this->is_selectable();
		$fixed_unit   = $this->get_fixed_unit();
		$has_unit     = $is_selectable || ! empty( $fixed_unit );

		$group_classes = [ 'unit-input-group' ];
		if ( $has_unit ) {
			$group_classes[] = 'has-unit';
			$group_classes[] = $is_prefix ? 'unit-prefix' : 'unit-suffix';
		}
		if ( $is_selectable ) {
			$group_classes[] = 'unit-selectable';
		}
		if ( ! empty( $this->config['class'] ) ) {
			$group_classes[] = $this->config['class'];
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $group_classes ) ); ?>">
			<?php if ( $has_unit && $is_prefix ) : ?>
				<?php $this->render_unit( $is_selectable, $fixed_unit ); ?>
			<?php endif; ?>

			<?php $this->render_input(); ?>

			<?php if ( $has_unit && ! $is_prefix ) : ?>
				<?php $this->render_unit( $is_selectable, $fixed_unit ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the numeric input element
	 *
	 * @return void
	 */
	private function render_input(): void {
		$attrs = [
			'type'        => 'text',
			'id'          => $this->config['id'],
			'name'        => $this->config['name'],
			'value'       => $this->config['value'],
			'placeholder' => $this->config['placeholder'],
			'inputmode'   => $this->config['inputmode'],
		];

		if ( $this->config['min'] !== null ) {
			$attrs['min'] = $this->config['min'];
		}
		if ( $this->config['max'] !== null ) {
			$attrs['max'] = $this->config['max'];
		}
		if ( $this->config['step'] !== null ) {
			$attrs['step'] = $this->config['step'];
		}

		echo '<input class="unit-input-field"';
		foreach ( $attrs as $key => $value ) {
			if ( $value !== '' && $value !== null ) {
				printf( ' %s="%s"', $key, esc_attr( (string) $value ) );
			}
		}
		if ( $this->config['required'] ) {
			echo ' required';
		}
		if ( $this->config['disabled'] ) {
			echo ' disabled';
		}
		if ( $this->config['readonly'] ) {
			echo ' readonly';
		}
		echo ' autocomplete="off">';
	}

	/**
	 * Render the unit element (fixed text or select dropdown)
	 *
	 * @param bool   $is_selectable Whether to render a select
	 * @param string $fixed_unit    Fixed unit string (used when not selectable)
	 *
	 * @return void
	 */
	private function render_unit( bool $is_selectable, string $fixed_unit ): void {
		if ( $is_selectable ) {
			$unit_name  = $this->get_unit_name();
			$unit_value = $this->config['unit_value'];
			?>
			<select name="<?php echo esc_attr( $unit_name ); ?>"
			        class="unit-input-select"
				<?php echo $this->config['disabled'] ? 'disabled' : ''; ?>>
				<?php foreach ( $this->config['units'] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"
						<?php selected( $unit_value, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		} else {
			?>
			<span class="unit-input-label"><?php echo esc_html( $fixed_unit ); ?></span>
			<?php
		}
	}

}