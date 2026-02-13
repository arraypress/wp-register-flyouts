<?php
/**
 * ActionButtons Component
 *
 * Renders action buttons with REST API callback functionality.
 * Supports confirmations, loading states, and automatic success/error handling.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class ActionButtons implements Renderable {

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
            $this->config['id'] = 'action-buttons-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'      => '',
                'buttons' => [],
                'layout'  => 'inline', // inline, stacked, grid
                'align'   => 'left',   // left, center, right, justify
                'class'   => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['buttons'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-action-buttons',
                'layout-' . $this->config['layout'],
                'align-' . $this->config['align']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php foreach ( $this->config['buttons'] as $button ) : ?>
                <?php $this->render_button( $button ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single button
     *
     * @param array $button Button configuration
     */
    private function render_button( array $button ): void {
        $defaults = [
                'text'    => '',
                'action'  => '',
                'style'   => 'secondary', // primary, secondary, link, danger
                'icon'    => '',
                'data'    => [],
                'confirm' => '',
                'enabled' => true,
        ];

        $button = wp_parse_args( $button, $defaults );

        if ( empty( $button['text'] ) || empty( $button['action'] ) ) {
            return;
        }

        $classes = [
                'button',
                'button-' . $button['style'],
                'wp-flyout-action-btn'
        ];

        // Build data attributes — no per-button nonce, REST nonce is global.
        $data_attrs = [
                'action' => $button['action'],
        ];

        if ( ! empty( $button['confirm'] ) ) {
            $data_attrs['confirm'] = $button['confirm'];
        }

        // Add custom data attributes.
        foreach ( $button['data'] as $key => $value ) {
            $data_attrs[ $key ] = $value;
        }
        ?>
        <button type="button"
                class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php foreach ( $data_attrs as $key => $value ) : ?>
                    data-<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( (string) $value ); ?>"
                <?php endforeach; ?>
                <?php echo ! $button['enabled'] ? 'disabled' : ''; ?>>
            <?php if ( $button['icon'] ) : ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $button['icon'] ); ?>"></span>
            <?php endif; ?>
            <span class="button-text"><?php echo esc_html( $button['text'] ); ?></span>
            <span class="button-spinner" style="display:none;">
				<span class="dashicons dashicons-update spin"></span>
			</span>
        </button>
        <?php
    }

}