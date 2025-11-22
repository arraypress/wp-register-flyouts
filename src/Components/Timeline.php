<?php
/**
 * Timeline Component
 *
 * Displays chronological events in a vertical timeline format.
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
 * Class Timeline
 *
 * Renders a vertical timeline of events.
 *
 * @since 1.0.0
 */
class Timeline implements Renderable {

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
     * @param array $config  {
     *                       Configuration options
     *
     * @type string $id      Component ID (auto-generated if empty)
     * @type array  $items   Array of timeline items (was 'events')
     * @type bool   $compact Use compact display mode
     * @type string $class   Additional CSS classes
     *                       }
     * @since 1.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'timeline-' . wp_generate_uuid4();
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
                'id'      => '',
                'items'   => [],  // Changed from 'events' to 'items'
                'compact' => false,
                'class'   => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->config['items'] ) ) {
            return '';
        }

        $classes = [ 'wp-flyout-timeline' ];
        if ( $this->config['compact'] ) {
            $classes[] = 'compact';
        }
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php
            $total = count( $this->config['items'] );
            $index = 0;
            foreach ( $this->config['items'] as $item ) :
                $this->render_item( $item, $index, $total );
                $index++;
            endforeach;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single timeline item
     *
     * @param array $item  Item data
     * @param int   $index Item index
     * @param int   $total Total items count
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_item( array $item, int $index, int $total ): void {
        $title       = $item['title'] ?? '';
        $description = $item['description'] ?? '';
        $date        = $item['date'] ?? '';
        $type        = $item['type'] ?? 'default';
        $icon        = $item['icon'] ?? 'marker';

        if ( empty( $title ) ) {
            return;
        }

        $classes = [
                'timeline-item',
                'timeline-item-' . sanitize_html_class( $type )
        ];

        // Mark last item for CSS
        if ( $index === $total - 1 ) {
            $classes[] = 'last-item';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="timeline-badge">
                <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
            </div>
            <div class="timeline-content">
                <?php if ( $date ) : ?>
                    <div class="timeline-date"><?php echo esc_html( $date ); ?></div>
                <?php endif; ?>

                <h4 class="timeline-title"><?php echo esc_html( $title ); ?></h4>

                <?php if ( $description ) : ?>
                    <p class="timeline-description"><?php echo wp_kses_post( $description ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

}