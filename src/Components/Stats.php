<?php
/**
 * Stats Component
 *
 * Displays metric cards with values, labels and optional trend indicators.
 * Useful for dashboards, KPIs, analytics summaries and performance metrics.
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
 * Class Stats
 *
 * Renders statistical metric cards in a grid layout.
 *
 * @since 1.0.0
 */
class Stats implements Renderable {

	/**
	 * Component configuration
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::get_defaults() );

		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'stats-' . wp_generate_uuid4();
		}

		if ( ! is_array( $this->config['items'] ) ) {
			$this->config['items'] = [];
		}
	}

	/**
	 * Get default configuration
	 *
	 * @return array Default configuration values
	 * @since 1.0.0
	 */
	private static function get_defaults(): array {
		return [
			'id'      => '',
			'items'   => [],
			'columns' => 3, // 2, 3, or 4
			'class'   => ''
		];
	}

	/**
	 * Render the component
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		if ( empty( $this->config['items'] ) ) {
			return '';
		}

		$classes   = [ 'wp-flyout-stats' ];
		$classes[] = 'columns-' . $this->config['columns'];

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		ob_start();
		?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="stats-grid">
				<?php foreach ( $this->config['items'] as $stat ) : ?>
					<?php $this->render_stat( $stat ); ?>
				<?php endforeach; ?>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single stat card
	 *
	 * @param array $stat Stat data with keys: label, value, change, trend, icon
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function render_stat( array $stat ): void {
		$trend       = $stat['trend'] ?? '';
		$trend_class = '';
		$trend_icon  = '';

		if ( $trend === 'up' ) {
			$trend_class = 'trend-up';
			$trend_icon  = 'arrow-up-alt';
		} elseif ( $trend === 'down' ) {
			$trend_class = 'trend-down';
			$trend_icon  = 'arrow-down-alt';
		}
		?>
        <div class="stat-card">
            <div class="stat-header">
				<?php if ( ! empty( $stat['icon'] ) ) : ?>
                    <span class="stat-icon dashicons dashicons-<?php echo esc_attr( $stat['icon'] ); ?>"></span>
				<?php endif; ?>
                <span class="stat-label"><?php echo esc_html( $stat['label'] ?? '' ); ?></span>
            </div>

            <div class="stat-body">
                <div class="stat-value"><?php echo esc_html( $stat['value'] ?? '0' ); ?></div>

				<?php if ( ! empty( $stat['change'] ) ) : ?>
                    <div class="stat-change <?php echo esc_attr( $trend_class ); ?>">
						<?php if ( $trend_icon ) : ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $trend_icon ); ?>"></span>
						<?php endif; ?>
                        <span><?php echo esc_html( $stat['change'] ); ?></span>
                    </div>
				<?php endif; ?>
            </div>

			<?php if ( ! empty( $stat['description'] ) ) : ?>
                <div class="stat-footer">
					<?php echo esc_html( $stat['description'] ); ?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

}