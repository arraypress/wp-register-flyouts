<?php
/**
 * Separator Component
 *
 * Visual dividers with optional text labels.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class Separator implements Renderable {

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

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'separator-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'     => '',
                'text'   => '',
                'icon'   => '',
                'class'  => '',
                'margin' => '20px',
                'style'  => 'line', // line, dotted, dashed, double
                'align'  => 'center' // left, center, right (for text)
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [
                'wp-flyout-separator',
                'separator-' . $this->config['style']
        ];

        if ( $this->config['text'] ) {
            $classes[] = 'has-text';
            $classes[] = 'text-' . $this->config['align'];
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $style = sprintf( 'margin: %s 0;', esc_attr( $this->config['margin'] ) );

        if ( empty( $this->config['text'] ) ) {
            return sprintf(
                    '<hr id="%s" class="%s" style="%s">',
                    esc_attr( $this->config['id'] ),
                    esc_attr( implode( ' ', $classes ) ),
                    $style
            );
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             style="<?php echo esc_attr( $style ); ?>">
			<span class="separator-text">
				<?php if ( $this->config['icon'] ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                <?php endif; ?>
                <?php echo esc_html( $this->config['text'] ); ?>
			</span>
        </div>
        <?php
        return ob_get_clean();
    }

}