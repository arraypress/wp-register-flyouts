<?php
/**
 * ProgressSteps Component
 *
 * Displays step indicators for multi-step processes with visual progress tracking.
 * Supports completed, current, and upcoming step states.
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
 * Class ProgressSteps
 *
 * Renders a visual progress indicator for multi-step workflows.
 *
 * @since 1.0.0
 */
class ProgressSteps implements Renderable {

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array{
     *     id: string,
     *     steps: array<string>,
     *     current: int,
     *     style: string,
     *     clickable: bool,
     *     class: string
     * }
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config    {
     *                         Configuration options for the progress steps.
     *
     * @type string $id        Unique identifier for the component
     * @type array  $steps     Array of step labels
     * @type int    $current   Current step number (1-based)
     * @type string $style     Display style: 'numbers', 'icons', 'simple'
     * @type bool   $clickable Whether steps are clickable for navigation
     * @type string $class     Additional CSS classes
     *                         }
     * @since 1.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'progress-' . wp_generate_uuid4();
        }

        // Ensure current step is within bounds
        $step_count = count( $this->config['steps'] );
        if ( $step_count > 0 ) {
            $this->config['current'] = max( 1, min( $this->config['current'], $step_count ) );
        }
    }

    /**
     * Get default configuration values
     *
     * @return array Default configuration array
     * @since  1.0.0
     * @access private
     *
     */
    private static function get_defaults(): array {
        return [
                'id'        => '',
                'steps'     => [],
                'current'   => 1,
                'style'     => 'numbers', // 'numbers', 'icons', 'simple'
                'clickable' => false,
                'class'     => ''
        ];
    }

    /**
     * Render the progress steps component
     *
     * @return string HTML output of the component
     * @since 1.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->config['steps'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-progress-steps',
                'style-' . $this->config['style']
        ];

        if ( $this->config['clickable'] ) {
            $classes[] = 'is-clickable';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-current="<?php echo esc_attr( (string) $this->config['current'] ); ?>"
             role="navigation"
             aria-label="<?php esc_attr_e( 'Progress steps', 'wp-flyout' ); ?>">

            <ol class="progress-steps-track">
                <?php $this->render_steps(); ?>
            </ol>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render individual step items
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_steps(): void {
        $total_steps = count( $this->config['steps'] );

        foreach ( $this->config['steps'] as $index => $step ) {
            $step_number = $index + 1;
            $this->render_single_step( $step, $step_number, $total_steps );
        }
    }

    /**
     * Render a single step item
     *
     * @param string $label       Step label text
     * @param int    $step_number Current step number (1-based)
     * @param int    $total_steps Total number of steps
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_single_step( string $label, int $step_number, int $total_steps ): void {
        $is_complete = $step_number < $this->config['current'];
        $is_current  = $step_number === $this->config['current'];
        $is_last     = $step_number === $total_steps;

        $step_classes = [ 'progress-step' ];
        if ( $is_complete ) {
            $step_classes[] = 'is-complete';
        }
        if ( $is_current ) {
            $step_classes[] = 'is-current';
        }

        $aria_current = $is_current ? 'step' : 'false';
        $aria_label   = sprintf(
        /* translators: 1: step number, 2: total steps, 3: step label */
                __( 'Step %1$d of %2$d: %3$s', 'wp-flyout' ),
                $step_number,
                $total_steps,
                $label
        );
        ?>
        <li class="<?php echo esc_attr( implode( ' ', $step_classes ) ); ?>"
            data-step="<?php echo esc_attr( (string) $step_number ); ?>"
            aria-current="<?php echo esc_attr( $aria_current ); ?>">

            <?php if ( $this->config['clickable'] && ! $is_current ) : ?>
            <button type="button"
                    class="step-button"
                    data-step="<?php echo esc_attr( (string) $step_number ); ?>"
                    aria-label="<?php echo esc_attr( $aria_label ); ?>">
                <?php else : ?>
                <div class="step-content" aria-label="<?php echo esc_attr( $aria_label ); ?>">
                    <?php endif; ?>

                    <?php $this->render_step_marker( $step_number, $is_complete ); ?>

                    <span class="step-label">
                    <?php echo esc_html( $label ); ?>
                </span>

                    <?php if ( $this->config['clickable'] && ! $is_current ) : ?>
            </button>
        <?php else : ?>
            </div>
        <?php endif; ?>

            <?php if ( ! $is_last ) : ?>
                <div class="step-connector" aria-hidden="true"></div>
            <?php endif; ?>
        </li>
        <?php
    }

    /**
     * Render the step marker (number or icon)
     *
     * @param int  $step_number Current step number
     * @param bool $is_complete Whether the step is complete
     *
     * @return void
     * @since  1.0.0
     * @access private
     *
     */
    private function render_step_marker( int $step_number, bool $is_complete ): void {
        ?>
        <span class="step-marker">
            <?php if ( $is_complete ) : ?>
                <span class="dashicons dashicons-yes"
                      aria-label="<?php esc_attr_e( 'Completed', 'wp-flyout' ); ?>"></span>
            <?php else : ?>
                <span class="step-number"><?php echo esc_html( (string) $step_number ); ?></span>
            <?php endif; ?>
        </span>
        <?php
    }

    /**
     * Get the current step number
     *
     * @return int Current step number (1-based)
     * @since 1.0.0
     *
     */
    public function get_current_step(): int {
        return $this->config['current'];
    }

    /**
     * Get total number of steps
     *
     * @return int Total number of steps
     * @since 1.0.0
     *
     */
    public function get_total_steps(): int {
        return count( $this->config['steps'] );
    }

    /**
     * Check if progress is complete
     *
     * @return bool True if all steps are complete
     * @since 1.0.0
     *
     */
    public function is_complete(): bool {
        return $this->config['current'] > count( $this->config['steps'] );
    }
    
}