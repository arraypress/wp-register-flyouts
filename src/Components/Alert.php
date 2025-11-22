<?php
/**
 * Alert Component
 *
 * Displays simple alert messages with various styles.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class Alert implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Icon mappings for alert types
     *
     * @var array
     */
    private const ICONS = [
            'success' => 'yes-alt',
            'error'   => 'dismiss',
            'warning' => 'warning',
            'info'    => 'info-outline'
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'alert-' . wp_generate_uuid4();
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
                'style'       => 'info',
                'message'     => '',
                'title'       => '',
                'dismissible' => true,
                'class'       => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['message'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-alert',
                'alert-' . $this->config['style']
        ];

        if ( $this->config['dismissible'] ) {
            $classes[] = 'is-dismissible';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $icon = self::ICONS[ $this->config['style'] ] ?? 'info-outline';

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             role="alert">

            <div class="alert-content-wrapper">
                <div class="alert-icon">
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                </div>

                <div class="alert-content">
                    <?php if ( ! empty( $this->config['title'] ) ) : ?>
                        <h4 class="alert-title"><?php echo esc_html( $this->config['title'] ); ?></h4>
                    <?php endif; ?>

                    <div class="alert-message">
                        <?php echo wp_kses_post( $this->config['message'] ); ?>
                    </div>
                </div>
            </div>

            <?php if ( $this->config['dismissible'] ) : ?>
                <button type="button" class="alert-dismiss" data-action="dismiss-alert">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}