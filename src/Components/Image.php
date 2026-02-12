<?php
/**
 * Image Component
 *
 * Standalone image picker field for selecting a single image via the
 * WordPress media library. Displays a preview thumbnail with hover
 * actions to select or remove the image. Stores the attachment ID
 * in a hidden input for form submission.
 *
 * Reuses the same picker markup and JavaScript as the header component's
 * editable image, but wrapped as a standard form field with label and
 * description support.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class Image implements Renderable {

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

		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'image-picker-' . wp_generate_uuid4();
		}

		// Resolve image URL from attachment_id
		if ( empty( $this->config['image'] ) && ! empty( $this->config['value'] ) ) {
			$url = wp_get_attachment_image_url(
				(int) $this->config['value'],
				$this->config['image_size']
			);
			if ( $url ) {
				$this->config['image'] = $url;
			}
		}
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	private static function get_defaults(): array {
		return [
			'id'              => '',
			'name'            => 'image',
			'label'           => '',
			'description'     => '',

			// Value
			'value'           => 0, // attachment_id

			// Image display
			'image'       => '',
			'image_size'  => 'medium',
			'image_shape' => 'rounded', // square, circle, rounded

			// Placeholder
			'icon'            => 'format-image',
			'empty_text'      => '',

			// Field wrapper
			'class'           => '',
			'required'        => false,
		];
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		$attachment_id = absint( $this->config['value'] );
		$current_image = $this->config['image'] ?? '';
		$has_image     = ! empty( $current_image );
		$shape_class   = 'shape-' . $this->config['image_shape'];

		$picker_classes = [
			'image-picker',
			'image-picker--field',
			$shape_class,
		];
		if ( $has_image ) {
			$picker_classes[] = 'has-image';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( implode( ' ', $picker_classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-size="<?php echo esc_attr( $this->config['image_size'] ); ?>"
             data-icon="<?php echo esc_attr( $this->config['icon'] ); ?>">

			<?php // Current image or placeholder ?>
            <div class="image-picker-preview">
				<?php if ( $has_image ) : ?>
                    <img src="<?php echo esc_url( $current_image ); ?>"
                         alt=""
                         class="image-picker-img">
				<?php else : ?>
                    <div class="image-picker-placeholder">
                        <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                    </div>
				<?php endif; ?>
            </div>

			<?php // Hover overlay with actions ?>
            <div class="image-picker-overlay">
                <button type="button"
                        class="image-picker-btn"
                        data-action="select-image"
                        title="<?php echo $has_image
					        ? esc_attr__( 'Change image', 'wp-flyout' )
					        : esc_attr__( 'Select image', 'wp-flyout' ); ?>">
                    <span class="dashicons dashicons-<?php echo $has_image ? 'update' : 'plus-alt2'; ?>"></span>
                </button>

				<?php if ( $has_image ) : ?>
                    <button type="button"
                            class="image-picker-btn image-picker-remove"
                            data-action="remove-image"
                            title="<?php esc_attr_e( 'Remove image', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
				<?php endif; ?>
            </div>

			<?php // Hidden input for form submission ?>
            <input type="hidden"
                   name="<?php echo esc_attr( $this->config['name'] ); ?>"
                   value="<?php echo esc_attr( (string) $attachment_id ); ?>"
                   class="image-picker-value">
        </div>

		<?php if ( ! empty( $this->config['empty_text'] ) && ! $has_image ) : ?>
            <p class="image-picker-empty-text">
				<?php echo esc_html( $this->config['empty_text'] ); ?>
            </p>
		<?php endif; ?>
		<?php

		return ob_get_clean();
	}

}