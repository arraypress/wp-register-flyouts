<?php
/**
 * Code Generator Component
 *
 * Text input with an attached "Generate" button that produces random codes.
 * Useful for discount codes, API keys, license keys, referral codes, etc.
 *
 * Renders a wrapper containing a text input and a generate button that
 * triggers client-side code generation via the code-generator JS module.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class CodeGenerator implements Renderable {

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Valid format options
	 *
	 * @var array<string, string>
	 */
	private const FORMATS = [
		'alphanumeric_upper' => 'A-Z, 0-9',
		'alphanumeric'       => 'A-Z, a-z, 0-9',
		'alpha_upper'        => 'A-Z',
		'hex'                => '0-9, A-F',
		'numeric'            => '0-9',
	];

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
			'id'             => '',
			'name'           => '',
			'label'          => '',
			'description'    => '',
			'value'          => '',
			'placeholder'    => '',
			'required'       => false,
			'disabled'       => false,
			'readonly'       => false,

			// Generator settings
			'length'         => 8,
			'format'         => 'alphanumeric_upper', // See FORMATS constant
			'prefix'         => '',                    // e.g. 'PROMO-'
			'separator'      => '',                    // e.g. '-'
			'segment_length' => 0,                     // e.g. 4 → XXXX-XXXX
			'button_text'    => 'Generate',

			// Wrapper
			'class'          => '',
			'wrapper_class'  => '',
		];
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		$wrapper_classes = [
			'wp-flyout-field',
			'field-type-code-generator',
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
	 * Render the input + generate button group
	 *
	 * @return void
	 */
	private function render_input_group(): void {
		$group_classes = [ 'code-generator-wrapper' ];
		if ( ! empty( $this->config['class'] ) ) {
			$group_classes[] = $this->config['class'];
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $group_classes ) ); ?>">
			<input type="text"
			       id="<?php echo esc_attr( $this->config['id'] ); ?>"
			       name="<?php echo esc_attr( $this->config['name'] ); ?>"
			       value="<?php echo esc_attr( $this->config['value'] ); ?>"
			       placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
				<?php echo $this->config['required'] ? 'required' : ''; ?>
				<?php echo $this->config['disabled'] ? 'disabled' : ''; ?>
				<?php echo $this->config['readonly'] ? 'readonly' : ''; ?>>

			<button type="button"
			        class="code-generate-btn"
			        data-length="<?php echo esc_attr( (string) $this->config['length'] ); ?>"
			        data-format="<?php echo esc_attr( $this->config['format'] ); ?>"
			        data-prefix="<?php echo esc_attr( $this->config['prefix'] ); ?>"
			        data-separator="<?php echo esc_attr( $this->config['separator'] ); ?>"
			        data-segment-length="<?php echo esc_attr( (string) $this->config['segment_length'] ); ?>"
				<?php echo $this->config['disabled'] ? 'disabled' : ''; ?>>
				<?php echo esc_html( $this->config['button_text'] ); ?>
			</button>
		</div>
		<?php
	}

}