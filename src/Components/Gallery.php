<?php
/**
 * Image Gallery Component
 *
 * Grid-based image gallery with media library integration and drag-drop reordering.
 * Stores only attachment IDs - all metadata managed in WordPress Media Library.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

/**
 * Class Gallery
 *
 * Manages image collections with visual preview grid and drag-drop sorting.
 * Stores only attachment IDs, letting WordPress manage all image metadata.
 *
 * @since 1.0.0
 */
class Gallery implements Renderable {

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'image-gallery-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }

        // Convert to simple array of IDs if needed
        $this->config['items'] = $this->normalize_items( $this->config['items'] );
    }

    /**
     * Get default configuration
     *
     * @return array Default configuration values
     * @since 1.0.0
     *
     */
    private static function get_defaults(): array {
        return [
                'id'         => '',
                'name'       => 'gallery',
                'items'      => [],  // Array of attachment IDs
                'max_images' => 0,   // 0 = unlimited
                'sortable'   => true,
                'columns'    => 4,   // Grid columns (2-6)
                'size'       => 'thumbnail', // WordPress image size for preview
                'add_text'   => __( 'Add Images', 'wp-flyout' ),
                'empty_text' => __( 'No images added yet', 'wp-flyout' ),
                'empty_icon' => 'format-gallery',
                'multiple'   => true, // Allow multiple selection in media library
                'class'      => ''
        ];
    }

    /**
     * Normalize items to simple array of IDs
     *
     * @param array $items Items array
     *
     * @return array Array of attachment IDs
     * @since 1.0.0
     *
     */
    private function normalize_items( array $items ): array {
        return array_values( array_filter( array_map( 'intval', $items ) ) );
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        $classes = [ 'wp-flyout-image-gallery' ];

        if ( $this->config['sortable'] ) {
            $classes[] = 'is-sortable';
        }

        $classes[] = 'columns-' . $this->config['columns'];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-max-images="<?php echo esc_attr( (string) $this->config['max_images'] ); ?>"
             data-size="<?php echo esc_attr( $this->config['size'] ); ?>"
             data-multiple="<?php echo esc_attr( $this->config['multiple'] ? 'true' : 'false' ); ?>">

            <div class="gallery-header">
                <div class="gallery-title">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e( 'Image Gallery', 'wp-flyout' ); ?>
                    <?php if ( $this->config['max_images'] > 0 ) : ?>
                        <span class="image-count">
							(<span class="current-count"><?php echo count( $this->config['items'] ); ?></span>/<?php echo $this->config['max_images']; ?>)
						</span>
                    <?php endif; ?>
                </div>

                <button type="button"
                        class="button button-secondary gallery-add-btn"
                        data-action="add"
                        <?php if ( $this->config['max_images'] > 0 && count( $this->config['items'] ) >= $this->config['max_images'] ) : ?>
                            disabled
                        <?php endif; ?>>
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>
            </div>

            <div class="gallery-container <?php echo empty( $this->config['items'] ) ? 'is-empty' : ''; ?>">
                <div class="gallery-empty">
                    <span class="dashicons dashicons-<?php echo esc_attr( $this->config['empty_icon'] ); ?>"></span>
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>

                <div class="gallery-grid">
                    <?php foreach ( $this->config['items'] as $index => $attachment_id ) : ?>
                        <?php $this->render_image_item( $attachment_id, $index ); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single image item
     *
     * @param int $attachment_id Attachment ID
     * @param int $index         Item index
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_image_item( int $attachment_id, int $index ): void {
        // Get thumbnail URL
        $thumbnail = wp_get_attachment_image_url( $attachment_id, $this->config['size'] );

        // Get alt text for accessibility
        $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

        // Use placeholder if no thumbnail
        if ( ! $thumbnail ) {
            $thumbnail = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjUwIiB5PSI1MCIgc3R5bGU9ImZpbGw6I2FhYTtmb250LXdlaWdodDpib2xkO2ZvbnQtc2l6ZToxM3B4O2ZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmO2RvbWluYW50LWJhc2VsaW5lOmNlbnRyYWwiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
        }
        ?>
        <div class="gallery-item" data-index="<?php echo esc_attr( (string) $index ); ?>"
             data-attachment-id="<?php echo esc_attr( (string) $attachment_id ); ?>">
            <?php if ( $this->config['sortable'] ) : ?>
                <div class="gallery-item-handle">
                    <span class="dashicons dashicons-move"></span>
                </div>
            <?php endif; ?>

            <div class="gallery-item-preview">
                <img src="<?php echo esc_url( $thumbnail ); ?>"
                     alt="<?php echo esc_attr( $alt ); ?>"
                     class="gallery-thumbnail">

                <div class="gallery-item-overlay">
                    <button type="button"
                            class="gallery-item-edit"
                            data-action="edit"
                            title="<?php esc_attr_e( 'Change image', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>

                    <button type="button"
                            class="gallery-item-remove"
                            data-action="remove"
                            title="<?php esc_attr_e( 'Remove image', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>

            <!-- Only store the attachment ID -->
            <input type="hidden"
                   name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>]"
                   value="<?php echo esc_attr( (string) $attachment_id ); ?>">
        </div>
        <?php
    }

}