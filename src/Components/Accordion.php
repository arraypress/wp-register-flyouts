<?php
/**
 * Accordion Component
 *
 * Displays collapsible content sections with support for single or multiple open panels.
 * Simplified version that combines accordion and collapsible functionality.
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
 * Class Accordion
 *
 * Renders expandable/collapsible content sections.
 *
 * @since 1.0.0
 */
class Accordion implements Renderable {

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
     * @param array    $config       {
     *                               Configuration options
     *
     * @type string    $id           Component ID (auto-generated if empty)
     * @type array     $items        Array of accordion items
     * @type bool      $multiple     Allow multiple sections open (default: false)
     * @type int|int[] $default_open Index or array of indices to open by default
     * @type string    $class        Additional CSS classes
     *                               }
     * @since 1.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'accordion-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     * @since 1.0.0
     */
    private static function get_defaults(): array {
        return [
                'id'           => '',
                'items'        => [],
                'multiple'     => false,  // Allow multiple sections open
                'default_open' => null,   // Index or array of indices
                'class'        => ''
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

        $classes = [ 'wp-flyout-accordion' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-allow-multiple="<?php echo $this->config['multiple'] ? 'true' : 'false'; ?>">
            <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                <?php $this->render_item( $item, $index ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single accordion item
     *
     * @param array $item    {
     *                       Item configuration
     *
     * @type string $title   Item title (required)
     * @type string $content Item content HTML (required)
     * @type string $icon    Optional dashicon name (without 'dashicons-' prefix)
     *                       }
     *
     * @param int   $index   Item index for default open state
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_item( array $item, int $index ): void {
        $title   = $item['title'] ?? '';
        $content = $item['content'] ?? '';
        $icon    = $item['icon'] ?? '';

        if ( empty( $title ) || empty( $content ) ) {
            return;
        }

        // Check if this item should be open by default
        $is_open = false;
        if ( $this->config['default_open'] === $index ||
             ( is_array( $this->config['default_open'] ) &&
               in_array( $index, $this->config['default_open'], true ) ) ) {
            $is_open = true;
        }

        $item_classes = [ 'accordion-section' ];
        if ( $is_open ) {
            $item_classes[] = 'is-open';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">
            <button type="button"
                    class="accordion-header"
                    aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
                <?php if ( $icon ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                <?php endif; ?>
                <span class="accordion-title"><?php echo esc_html( $title ); ?></span>
                <span class="accordion-indicator">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </span>
            </button>
            <div class="accordion-content" <?php echo ! $is_open ? 'style="display:none"' : ''; ?>>
                <div class="accordion-content-inner">
                    <?php echo wp_kses_post( $content ); ?>
                </div>
            </div>
        </div>
        <?php
    }

}