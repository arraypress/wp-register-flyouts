<?php
/**
 * FeatureList Component
 *
 * Simple list management for single text items.
 * Automatically removes empty rows on save.
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
 * Class FeatureList
 *
 * Manages a simple list of text items with dynamic add/remove functionality.
 *
 * @since 1.0.0
 */
class FeatureList implements Renderable {

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
            $this->config['id'] = 'feature-list-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'          => '',
                'name'        => 'features',
                'items'       => [],
                'label'       => __( 'Features', 'wp-flyout' ),
                'add_text'    => __( 'Add item', 'wp-flyout' ),
                'empty_text'  => __( 'No items added yet', 'wp-flyout' ),
                'placeholder' => __( 'Enter item', 'wp-flyout' ),
                'max_items'   => 0, // 0 = unlimited
                'sortable'    => true,
                'class'       => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-feature-list' ];

        if ( $this->config['sortable'] ) {
            $classes[] = 'is-sortable';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-max-items="<?php echo esc_attr( (string) $this->config['max_items'] ); ?>"
             data-placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
             data-sortable="<?php echo esc_attr( $this->config['sortable'] ? 'true' : 'false' ); ?>">

            <?php if ( ! empty( $this->config['label'] ) ) : ?>
                <div class="feature-list-header">
                    <label class="feature-list-label">
                        <?php echo esc_html( $this->config['label'] ); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div class="feature-list-container <?php echo empty( $this->config['items'] ) ? 'is-empty' : ''; ?>">
                <div class="feature-list-empty">
                    <span class="dashicons dashicons-editor-ul"></span>
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>

                <div class="feature-list-items">
                    <?php if ( ! empty( $this->config['items'] ) ) : ?>
                        <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                            <?php $this->render_item( $item, $index ); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <?php // Render one empty row by default ?>
                        <?php $this->render_item( '', 0 ); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="feature-list-footer">
                <button type="button"
                        class="button button-secondary feature-list-add"
                        <?php if ( $this->config['max_items'] > 0 && count( $this->config['items'] ) >= $this->config['max_items'] ) : ?>
                            disabled
                        <?php endif; ?>>
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single list item
     *
     * @param string $value Item value
     * @param int    $index Item index
     */
    private function render_item( $value, int $index ): void {
        // Handle both string and array format
        if ( is_array( $value ) ) {
            $value = $value['value'] ?? $value['text'] ?? '';
        }
        ?>
        <div class="feature-list-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
            <?php if ( $this->config['sortable'] ) : ?>
                <span class="feature-list-handle" title="<?php esc_attr_e( 'Drag to reorder', 'wp-flyout' ); ?>">
					<span class="dashicons dashicons-menu"></span>
				</span>
            <?php endif; ?>

            <input type="text"
                   name="<?php echo esc_attr( $this->config['name'] ); ?>[]"
                   value="<?php echo esc_attr( $value ); ?>"
                   placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                   class="feature-list-input">

            <button type="button"
                    class="feature-list-remove"
                    title="<?php esc_attr_e( 'Remove', 'wp-flyout' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <?php
    }

}