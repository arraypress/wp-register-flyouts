<?php
/**
 * InfoGrid Component
 *
 * Displays information in a clean grid layout.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;
use ArrayPress\RegisterFlyouts\Traits\Formatter;

class InfoGrid implements Renderable {
    use Formatter;

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
            $this->config['id'] = 'info-grid-' . wp_generate_uuid4();
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
                'class'       => '',
                'items'       => [],
                'columns'     => 2,
                'empty_value' => '—'
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
                'wp-flyout-info-grid',
                'columns-' . $this->config['columns']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-columns="<?php echo esc_attr( $this->config['columns'] ); ?>">
            <?php foreach ( $this->config['items'] as $item ) : ?>
                <?php $this->render_item( $item ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single grid item
     *
     * @param array $item Item configuration
     */
    private function render_item( array $item ): void {
        if ( empty( $item['label'] ) ) {
            return;
        }

        $value = $item['value'] ?? '';

        if ( empty( $value ) ) {
            $value = $this->format_value( $this->config['empty_value'] );
        } else {
            $value = esc_html( $value );
        }
        ?>
        <div class="wp-flyout-info-item">
            <div class="wp-flyout-info-label">
                <?php echo esc_html( $item['label'] ); ?>
            </div>
            <div class="wp-flyout-info-value">
                <?php echo $value; ?>
            </div>
        </div>
        <?php
    }

}