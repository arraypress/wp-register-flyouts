<?php
/**
 * ActionBar Component
 *
 * Renders action buttons for forms and dialogs.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

use ArrayPress\FieldKit\Support\Button;
use ArrayPress\RegisterFlyouts\Renderable;

class ActionBar implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Default configuration
     *
     * @var array
     */
    private const DEFAULTS = [
		'id'      => '',
		'actions' => [],
		'class'   => 'wp-flyout-actions',
		'align'   => 'stretch', // stretch, left, right, center, space-between
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::DEFAULTS );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'action-bar-' . wp_generate_uuid4();
        }

        // Process actions for defaults
        $this->config['actions'] = array_map( [ $this, 'process_action' ], $this->config['actions'] );
    }

    /**
     * Process action defaults
     *
     * @param array $action Action config
     *
     * @return array
     */
    private function process_action( array $action ): array {
        return wp_parse_args( $action, [
			'type'    => 'button',
			'text'    => '',
			'style'   => 'secondary',
			'icon'    => '',
			'class'   => '',
			'onclick' => '',
			'attrs'   => [],
        ] );
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['actions'] ) ) {
            return '';
        }

        $classes = [ $this->config['class'] ];
        if ( $this->config['align'] !== 'stretch' ) {
            $classes[] = 'align-' . $this->config['align'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
            class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php foreach ( $this->config['actions'] as $action ) : ?>
                <?php $this->render_button( $action ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single button
     *
     * @param array $action Action configuration
     */
    private function render_button( array $action ): void {
        $attributes = (array) $action['attrs'];

        if ( ! empty( $action['onclick'] ) ) {
            $attributes['onclick'] = $action['onclick'];
        }

        $html = Button::render(
            [
                'label'      => $action['text'],
                'variant'    => $action['style'],
                'icon'       => $action['icon'],
                'type'       => $action['type'],
                'class'      => $action['class'],
                'attributes' => $attributes,
            ]
        );

        // The kit escapes the label and the attributes; the rest is its own
        // markup.
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}