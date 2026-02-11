<?php
/**
 * Price Config Component
 *
 * Simple pricing configuration for Stripe-compatible one-time and recurring prices.
 * Outputs amount, currency, and optional recurring interval fields.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class PriceConfig implements Renderable {

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Stripe-supported intervals
	 *
	 * @var array<string, string>
	 */
	private const INTERVALS = [
		'day'   => 'Day(s)',
		'week'  => 'Week(s)',
		'month' => 'Month(s)',
		'year'  => 'Year(s)',
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, [
			'name'                    => 'price',
			'amount'                  => 0,
			'currency'                => 'USD',
			'recurring_interval'      => null,
			'recurring_interval_count' => 1,
			'currencies'              => [],
			'label'                   => 'Pricing',
			'description'             => '',
			'class'                   => '',
		] );

		// Load currencies from library if available and none provided
		if ( empty( $this->config['currencies'] ) && function_exists( 'get_currency_options' ) ) {
			$all = get_currency_options();
			foreach ( $all as $code => $info ) {
				$label = is_array( $info ) ? ( $info['name'] ?? $code ) : $info;
				$this->config['currencies'][ strtoupper( $code ) ] = strtoupper( $code ) . ' — ' . $label;
			}
		}

		// Fallback to common currencies
		if ( empty( $this->config['currencies'] ) ) {
			$this->config['currencies'] = [
				'USD' => 'USD — US Dollar',
				'EUR' => 'EUR — Euro',
				'GBP' => 'GBP — British Pound',
				'CAD' => 'CAD — Canadian Dollar',
				'AUD' => 'AUD — Australian Dollar',
				'JPY' => 'JPY — Japanese Yen',
			];
		}
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		$name     = esc_attr( $this->config['name'] );
		$amount   = $this->config['amount'];
		$currency = strtoupper( $this->config['currency'] );
		$interval = $this->config['recurring_interval'];
		$count    = max( 1, (int) $this->config['recurring_interval_count'] );
		$is_recurring = ! empty( $interval );

		// Convert cents to decimal for display
		$display_amount = '';
		if ( $amount > 0 ) {
			if ( function_exists( 'from_currency_cents' ) ) {
				$display_amount = number_format( from_currency_cents( $amount, $currency ), 2, '.', '' );
			} else {
				$display_amount = number_format( $amount / 100, 2, '.', '' );
			}
		}

		$classes = 'wp-flyout-price-config';
		if ( ! empty( $this->config['class'] ) ) {
			$classes .= ' ' . $this->config['class'];
		}

		ob_start();
		?>
		<div class="wp-flyout-field">
			<?php if ( ! empty( $this->config['label'] ) ) : ?>
				<label><?php echo esc_html( $this->config['label'] ); ?></label>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $classes ); ?>"
			     data-name="<?php echo $name; ?>">

				<?php // ---- Price Type ---- ?>
				<div class="price-config-type">
					<label class="price-config-type-option <?php echo ! $is_recurring ? 'is-active' : ''; ?>">
						<input type="radio"
						       name="<?php echo $name; ?>_type"
						       value="one_time"
						       class="price-config-type-input"
							<?php checked( ! $is_recurring ); ?>>
						<span class="price-config-type-label">
							<span class="dashicons dashicons-money-alt"></span>
							One-time
						</span>
					</label>
					<label class="price-config-type-option <?php echo $is_recurring ? 'is-active' : ''; ?>">
						<input type="radio"
						       name="<?php echo $name; ?>_type"
						       value="recurring"
						       class="price-config-type-input"
							<?php checked( $is_recurring ); ?>>
						<span class="price-config-type-label">
							<span class="dashicons dashicons-controls-repeat"></span>
							Recurring
						</span>
					</label>
				</div>

				<?php // ---- Amount + Currency row ---- ?>
				<div class="price-config-amount-row">
					<div class="price-config-amount">
						<label for="<?php echo $name; ?>_amount">Amount</label>
						<input type="text"
						       id="<?php echo $name; ?>_amount"
						       name="<?php echo $name; ?>[amount]"
						       value="<?php echo esc_attr( $display_amount ); ?>"
						       placeholder="0.00"
						       inputmode="decimal"
						       autocomplete="off">
					</div>
					<div class="price-config-currency">
						<label for="<?php echo $name; ?>_currency">Currency</label>
						<select id="<?php echo $name; ?>_currency"
						        name="<?php echo $name; ?>[currency]">
							<?php foreach ( $this->config['currencies'] as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>"
									<?php selected( $currency, $code ); ?>>
									<?php echo esc_html( $code ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<?php // ---- Recurring interval (hidden by default) ---- ?>
				<div class="price-config-interval" style="<?php echo $is_recurring ? '' : 'display:none;'; ?>">
					<label>Billing period</label>
					<div class="price-config-interval-row">
						<span class="price-config-interval-prefix">Every</span>
						<input type="number"
						       name="<?php echo $name; ?>[recurring_interval_count]"
						       value="<?php echo esc_attr( (string) $count ); ?>"
						       min="1"
						       max="365"
						       class="price-config-interval-count">
						<select name="<?php echo $name; ?>[recurring_interval]"
						        class="price-config-interval-select">
							<?php foreach ( self::INTERVALS as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"
									<?php selected( $interval, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

			</div>

			<?php if ( ! empty( $this->config['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

}