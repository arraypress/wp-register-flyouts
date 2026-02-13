<?php
/**
 * ActionMenu Component
 *
 * Dropdown menu for multiple actions, common in ecommerce interfaces.
 * Provides a cleaner alternative to multiple buttons.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class ActionMenu implements Renderable {

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
            $this->config['id'] = 'action-menu-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'           => '',
                'items'        => [],
                'button_text'  => 'Actions',
                'button_icon'  => 'menu-alt',
                'button_style' => 'secondary',
                'position'     => 'left', // left or right
                'class'        => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['items'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-action-menu',
                'position-' . $this->config['position']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <button type="button"
                    class="button button-<?php echo esc_attr( $this->config['button_style'] ); ?> action-menu-trigger"
                    aria-expanded="false">
                <?php if ( $this->config['button_icon'] ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $this->config['button_icon'] ); ?>"></span>
                <?php endif; ?>
                <span class="button-text"><?php echo esc_html( $this->config['button_text'] ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>

            <div class="action-menu-dropdown" style="display:none;">
                <?php foreach ( $this->config['items'] as $item ) : ?>
                    <?php $this->render_menu_item( $item ); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render menu item
     *
     * @param array $item Item configuration
     */
    private function render_menu_item( array $item ): void {
        // Handle separator
        if ( isset( $item['type'] ) && $item['type'] === 'separator' ) {
            echo '<div class="action-menu-separator"></div>';

            return;
        }

        $defaults = [
                'text'    => '',
                'action'  => '',
                'icon'    => '',
                'class'   => '',
                'data'    => [],
                'confirm' => '',
                'enabled' => true,
                'danger'  => false
        ];

        $item = wp_parse_args( $item, $defaults );

        if ( empty( $item['text'] ) ) {
            return;
        }

        $classes = [ 'action-menu-item' ];
        if ( $item['danger'] ) {
            $classes[] = 'is-danger';
        }
        if ( ! $item['enabled'] ) {
            $classes[] = 'is-disabled';
        }
        if ( ! empty( $item['class'] ) ) {
            $classes[] = $item['class'];
        }

        // Build data attributes — no per-item nonce, REST nonce is global.
        $data_attrs = '';
        if ( $item['action'] ) {
            $data_attrs .= sprintf( ' data-action="%s"', esc_attr( $item['action'] ) );

            if ( $item['confirm'] ) {
                $data_attrs .= sprintf( ' data-confirm="%s"', esc_attr( $item['confirm'] ) );
            }

            foreach ( $item['data'] as $key => $value ) {
                $data_attrs .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
            }
        }
        ?>
        <button type="button"
                class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php echo $data_attrs; ?>
                <?php echo ! $item['enabled'] ? 'disabled' : ''; ?>>
            <?php if ( $item['icon'] ) : ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span>
            <?php endif; ?>
            <span class="item-text"><?php echo esc_html( $item['text'] ); ?></span>
        </button>
        <?php
    }

}