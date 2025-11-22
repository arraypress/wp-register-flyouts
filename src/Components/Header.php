<?php
/**
 * EntityHeader Component
 *
 * Displays a unified header for any entity without action buttons.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class Header implements Renderable {

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
            $this->config['id'] = 'entity-header-' . wp_generate_uuid4();
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
                'title'       => '',
                'subtitle'    => '',
                'image'       => '',
                'icon'        => '',
                'badges'      => [],
                'meta'        => [],
                'description' => '',
                'class'       => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['title'] ) ) {
            return '';
        }

        $classes = [ 'entity-header' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <?php if ( $this->config['image'] || $this->config['icon'] ) : ?>
                <div class="entity-header-visual">
                    <?php if ( $this->config['image'] ) : ?>
                        <img src="<?php echo esc_url( $this->config['image'] ); ?>"
                             alt="<?php echo esc_attr( $this->config['title'] ); ?>"
                             class="entity-header-image">
                    <?php elseif ( $this->config['icon'] ) : ?>
                        <span class="entity-header-icon dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="entity-header-content">
                <h2 class="entity-header-title">
                    <?php echo esc_html( $this->config['title'] ); ?>
                    <?php $this->render_badges(); ?>
                </h2>

                <?php if ( $this->config['subtitle'] ) : ?>
                    <div class="entity-header-subtitle">
                        <?php echo esc_html( $this->config['subtitle'] ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $this->config['meta'] ) ) : ?>
                    <div class="entity-header-meta">
                        <?php $this->render_meta(); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $this->config['description'] ) : ?>
                    <div class="entity-header-description">
                        <?php echo wp_kses_post( $this->config['description'] ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render badges
     */
    private function render_badges(): void {
        foreach ( $this->config['badges'] as $badge ) {
            if ( is_array( $badge ) ) {
                $text = $badge['text'] ?? '';
                $type = $badge['type'] ?? 'default';
            } else {
                $text = $badge;
                $type = 'default';
            }

            if ( empty( $text ) ) {
                continue;
            }
            ?>
            <span class="badge badge-<?php echo esc_attr( $type ); ?>">
                <?php echo esc_html( $text ); ?>
            </span>
            <?php
        }
    }

    /**
     * Render meta items
     */
    private function render_meta(): void {
        foreach ( $this->config['meta'] as $meta ) {
            $label = $meta['label'] ?? '';
            $value = $meta['value'] ?? '';
            $icon  = $meta['icon'] ?? '';

            if ( empty( $value ) ) {
                continue;
            }
            ?>
            <span class="entity-header-meta-item">
                <?php if ( $icon ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                <?php endif; ?>
                <?php if ( $label ) : ?>
                    <span class="meta-label"><?php echo esc_html( $label ); ?>:</span>
                <?php endif; ?>
                <span class="meta-value"><?php echo esc_html( $value ); ?></span>
            </span>
            <?php
        }
    }

}