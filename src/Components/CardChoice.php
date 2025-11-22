<?php
/**
 * CardChoice Component
 *
 * Visual card-style radio buttons and checkboxes.
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
 * Class CardChoice
 *
 * Renders card-based selection interface.
 *
 * @since 1.0.0
 */
class CardChoice implements Renderable {

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
            $this->config['id'] = 'card-choice-' . wp_generate_uuid4();
        }

        // Ensure value is array for checkboxes
        if ( $this->config['mode'] === 'checkbox' && ! is_array( $this->config['value'] ) ) {
            $this->config['value'] = $this->config['value'] ? [ $this->config['value'] ] : [];
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
                'name'    => '',
                'mode'    => 'radio', // Changed from 'type' to 'mode'
                'options' => [],
                'value'   => null,
                'columns' => 2,
                'class'   => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     */
    public function render(): string {
        if ( empty( $this->config['options'] ) || empty( $this->config['name'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-card-choice-group',
                'columns-' . $this->config['columns']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             role="<?php echo $this->config['mode'] === 'radio' ? 'radiogroup' : 'group'; ?>">
            <?php foreach ( $this->config['options'] as $value => $option ) : ?>
                <?php $this->render_option( $value, $option ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single option card
     *
     * @param string $value  Option value
     * @param mixed  $option Option config (string or array)
     *
     * @return void
     */
    private function render_option( string $value, $option ): void {
        // Handle simple string format
        if ( is_string( $option ) ) {
            $option = [ 'title' => $option ];
        }

        $title       = $option['title'] ?? '';
        $description = $option['description'] ?? '';
        $icon        = $option['icon'] ?? '';

        if ( empty( $title ) ) {
            return;
        }

        $input_id   = sanitize_key( $this->config['name'] . '_' . $value );
        $input_name = $this->config['name'];

        // FIXED: Use 'mode' instead of 'type' for input type
        $input_type = $this->config['mode'] ?? 'radio';

        // Array notation for checkboxes
        if ( $input_type === 'checkbox' ) {
            $input_name .= '[]';
        }

        $is_checked = $this->is_checked( $value );
        ?>
        <div class="card-choice">
            <input type="<?php echo esc_attr( $input_type ); ?>"
                   id="<?php echo esc_attr( $input_id ); ?>"
                   name="<?php echo esc_attr( $input_name ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                    <?php checked( $is_checked ); ?>>

            <label class="card-choice-label" for="<?php echo esc_attr( $input_id ); ?>">
                <div class="card-choice-header">
                    <?php if ( $icon ) : ?>
                        <div class="card-choice-icon">
                            <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                        </div>
                    <?php endif; ?>

                    <div class="card-choice-content">
                        <div class="card-choice-title">
                            <?php echo esc_html( $title ); ?>
                        </div>
                        <?php if ( $description ) : ?>
                            <p class="card-choice-description">
                                <?php echo esc_html( $description ); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="card-choice-check"></div>
                </div>
            </label>
        </div>
        <?php
    }

    /**
     * Check if option is selected
     *
     * @param string $value Option value
     *
     * @return bool True if checked
     */
    private function is_checked( string $value ): bool {
        if ( $this->config['mode'] === 'checkbox' ) {
            return is_array( $this->config['value'] ) &&
                   in_array( $value, $this->config['value'], true );
        }

        return $this->config['value'] === $value;
    }

}