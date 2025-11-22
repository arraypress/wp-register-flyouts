<?php
/**
 * KeyValueList Component
 *
 * Displays editable key-value pairs similar to Stripe's metadata interface.
 * Component automatically removes empty rows on save.
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
 * Class KeyValueList
 *
 * Manages key-value metadata pairs with dynamic add/remove functionality.
 *
 * @since 1.0.0
 */
class KeyValueList implements Renderable {

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
            $this->config['id'] = 'meta-key-value-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }

        // Clean up any malformed items (but this should rarely be needed)
        $this->config['items'] = array_filter( $this->config['items'], function ( $item ) {
            return is_array( $item ) && ( ! empty( $item['key'] ) || ! empty( $item['value'] ) );
        } );

        // Reindex
        $this->config['items'] = array_values( $this->config['items'] );
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'              => '',
                'name'            => 'metadata',
                'items'           => [],
                'key_label'       => __( 'Key', 'wp-flyout' ),
                'value_label'     => __( 'Value', 'wp-flyout' ),
                'add_text'        => __( 'Add metadata', 'wp-flyout' ),
                'empty_text'      => __( 'No metadata added yet', 'wp-flyout' ),
                'key_placeholder' => __( 'Enter key', 'wp-flyout' ),
                'val_placeholder' => __( 'Enter value', 'wp-flyout' ),
                'max_items'       => 0, // 0 = unlimited
                'sortable'        => true,
                'required_key'    => false, // Keys are optional by default
                'class'           => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-meta-key-value' ];

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
             data-key-placeholder="<?php echo esc_attr( $this->config['key_placeholder'] ); ?>"
             data-val-placeholder="<?php echo esc_attr( $this->config['val_placeholder'] ); ?>"
             data-sortable="<?php echo esc_attr( $this->config['sortable'] ? 'true' : 'false' ); ?>"
             data-required-key="<?php echo esc_attr( $this->config['required_key'] ? 'true' : 'false' ); ?>">

            <div class="meta-kv-header">
                <div class="meta-kv-labels">
                    <span class="meta-kv-label-key"><?php echo esc_html( $this->config['key_label'] ); ?></span>
                    <span class="meta-kv-label-value"><?php echo esc_html( $this->config['value_label'] ); ?></span>
                </div>
            </div>

            <div class="meta-kv-list <?php echo empty( $this->config['items'] ) ? 'is-empty' : ''; ?>">
                <div class="meta-kv-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>

                <div class="meta-kv-items">
                    <?php if ( ! empty( $this->config['items'] ) ) : ?>
                        <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                            <?php $this->render_item( $item, $index ); ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <?php // Render one empty row by default ?>
                        <?php $this->render_item( [ 'key' => '', 'value' => '' ], 0 ); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="meta-kv-footer">
                <button type="button"
                        class="button button-secondary meta-kv-add"
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
     * Render single key-value item
     *
     * @param array $item  Item data with 'key' and 'value'
     * @param int   $index Item index
     */
    private function render_item( array $item, int $index ): void {
        $key   = $item['key'] ?? '';
        $value = $item['value'] ?? '';
        ?>
        <div class="meta-kv-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
            <?php if ( $this->config['sortable'] ) : ?>
                <span class="meta-kv-handle" title="<?php esc_attr_e( 'Drag to reorder', 'wp-flyout' ); ?>">
					<span class="dashicons dashicons-menu"></span>
				</span>
            <?php endif; ?>

            <div class="meta-kv-fields">
                <input type="text"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][key]"
                       value="<?php echo esc_attr( $key ); ?>"
                       placeholder="<?php echo esc_attr( $this->config['key_placeholder'] ); ?>"
                       class="meta-kv-key"
                       data-field="key"
                        <?php echo $this->config['required_key'] ? 'required' : ''; ?>>

                <input type="text"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][value]"
                       value="<?php echo esc_attr( $value ); ?>"
                       placeholder="<?php echo esc_attr( $this->config['val_placeholder'] ); ?>"
                       class="meta-kv-value"
                       data-field="value">
            </div>

            <button type="button"
                    class="meta-kv-remove"
                    title="<?php esc_attr_e( 'Remove', 'wp-flyout' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <?php
    }

}