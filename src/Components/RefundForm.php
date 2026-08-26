<?php
/**
 * Refund Form Component
 *
 * Inline expandable refund panel for issuing full or partial refunds.
 * Designed for use within flyouts displaying order/payment details.
 * Submits via the existing REST action endpoint.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\Money\Money;

use ArrayPress\RegisterFlyouts\Renderable;

class RefundForm implements Renderable {

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Stripe-supported refund reasons
	 *
	 * @var array<string, string>
	 */
	private const REASONS = [
		'requested_by_customer' => 'Requested by customer',
		'duplicate'             => 'Duplicate',
		'fraudulent'            => 'Fraudulent',
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, [
			'name'             => 'refund',
			'action'           => 'issue_refund',
			'amount_paid'      => 0,
			'amount_refunded'  => 0,
			'currency'         => 'USD',
			'reasons'          => self::REASONS,
			'allow_custom'     => true,
			'label'            => 'Refund',
			'class'            => '',
		] );

		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'refund-form-' . wp_generate_uuid4();
		}
	}

	/**
	 * Get the maximum refundable amount in cents
	 *
	 * @return int
	 */
	private function get_refundable(): int {
		// Cast, because what arrives is whatever the consumer had: a float
		// from a form, a numeric string out of a database. max() returns the
		// type it was given, so a float here was a TypeError against the int
		// return — and a TypeError inside a component takes the whole panel
		// down rather than just that component.
		$paid     = (float) ( $this->config['amount_paid'] ?? 0 );
		$refunded = (float) ( $this->config['amount_refunded'] ?? 0 );

		return (int) round( max( 0, $paid - $refunded ) );
	}

	/**
	 * Format cents to display amount
	 *
	 * @param int|float|string $amount Amount in cents, as whatever the consumer had.
	 *
	 * @return string
	 */
	private function format_amount( $amount ): string {
		$amount = (int) round( (float) $amount );

		$currency = strtoupper( $this->config['currency'] );

		if ( function_exists( 'format_money' ) ) {
			return format_money( $amount, [ 'currency' => $currency ] );
		}

		return number_format( $amount / 100, 2, '.', ',' );
	}

	/**
	 * Format cents to decimal for input value
	 *
	 * @param int|float|string $amount Amount in cents, as whatever the consumer had.
	 *
	 * @return string
	 */
	private function format_decimal( int $amount ): string {
		$currency = strtoupper( $this->config['currency'] );

		/*
		 * Through Money rather than dividing by 100: yen has no minor unit at
		 * all and Kuwaiti dinar has three, so the flat divide was wrong for
		 * both -- it turned ¥5000 into 50.00.
		 */
		return number_format( Money::to_float( $amount, $currency ), 2, '.', '' );
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		$refundable = $this->get_refundable();

		// Nothing to refund
		if ( $refundable <= 0 ) {
			return $this->render_fully_refunded();
		}

		$id              = esc_attr( $this->config['id'] );
		$name            = esc_attr( $this->config['name'] );
		$action          = esc_attr( $this->config['action'] );
		$currency        = strtoupper( $this->config['currency'] );
		$amount_paid     = $this->config['amount_paid'];
		$amount_refunded = $this->config['amount_refunded'];

		$classes = 'wp-flyout-refund-form';
		if ( ! empty( $this->config['class'] ) ) {
			$classes .= ' ' . $this->config['class'];
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $id ); ?>"
			class="<?php echo esc_attr( $classes ); ?>"
			data-action="<?php echo esc_attr( $action ); ?>"
			data-currency="<?php echo esc_attr( $currency ); ?>"
			data-paid="<?php echo esc_attr( (string) $amount_paid ); ?>"
			data-refunded="<?php echo esc_attr( (string) $amount_refunded ); ?>"
			data-refundable="<?php echo esc_attr( (string) $refundable ); ?>">

			<?php // ---- Trigger Button ---- ?>
			<button type="button" class="button button-secondary refund-trigger">
				<span class="dashicons dashicons-undo"></span>
				<span class="button-text"><?php echo esc_html( $this->config['label'] ); ?></span>
			</button>

			<?php // ---- Expandable Panel ---- ?>
			<div class="refund-panel" style="display: none;">

				<?php // ---- Summary ---- ?>
				<div class="refund-summary">
					<span class="refund-summary-item">
						<span class="refund-summary-label"><?php esc_html_e( 'Paid', 'wp-flyout' ); ?></span>
						<span class="refund-summary-value"><?php echo esc_html( $this->format_amount( $amount_paid ) ); ?></span>
					</span>
					<?php if ( $amount_refunded > 0 ) : ?>
						<span class="refund-summary-sep">&middot;</span>
						<span class="refund-summary-item">
							<span class="refund-summary-label"><?php esc_html_e( 'Refunded', 'wp-flyout' ); ?></span>
							<span class="refund-summary-value refund-summary-refunded"><?php echo esc_html( $this->format_amount( $amount_refunded ) ); ?></span>
						</span>
					<?php endif; ?>
					<span class="refund-summary-sep">&middot;</span>
					<span class="refund-summary-item">
						<span class="refund-summary-label"><?php esc_html_e( 'Available', 'wp-flyout' ); ?></span>
						<span class="refund-summary-value refund-summary-available"><?php echo esc_html( $this->format_amount( $refundable ) ); ?></span>
					</span>
				</div>

				<?php // ---- Amount Input ---- ?>
				<div class="refund-field">
					<label for="<?php echo esc_attr( $id ); ?>_amount"><?php esc_html_e( 'Refund amount', 'wp-flyout' ); ?></label>
					<div class="refund-amount-wrap">
						<span class="refund-currency-symbol"><?php echo esc_html( $currency ); ?></span>
						<input type="text"
								id="<?php echo esc_attr( $id ); ?>_amount"
								name="<?php echo esc_attr( $name ); ?>[amount]"
								class="refund-amount-input"
								value="<?php echo esc_attr( $this->format_decimal( $refundable ) ); ?>"
								placeholder="0.00"
								inputmode="decimal"
								autocomplete="off">
					</div>
				</div>

				<?php // ---- Reason Select ---- ?>
				<div class="refund-field">
					<label for="<?php echo esc_attr( $id ); ?>_reason"><?php esc_html_e( 'Reason', 'wp-flyout' ); ?></label>
					<select id="<?php echo esc_attr( $id ); ?>_reason"
					        name="<?php echo esc_attr( $name ); ?>[reason]"
					        class="refund-reason-select">
						<?php foreach ( $this->config['reasons'] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>">
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
						<?php if ( $this->config['allow_custom'] ) : ?>
							<option value="other"><?php esc_html_e( 'Other', 'wp-flyout' ); ?></option>
						<?php endif; ?>
					</select>
				</div>

				<?php // ---- Custom Reason (hidden by default) ---- ?>
				<?php if ( $this->config['allow_custom'] ) : ?>
					<div class="refund-field refund-custom-reason" style="display: none;">
						<label for="<?php echo esc_attr( $id ); ?>_custom_reason"><?php esc_html_e( 'Details', 'wp-flyout' ); ?></label>
						<input type="text"
								id="<?php echo esc_attr( $id ); ?>_custom_reason"
								name="<?php echo esc_attr( $name ); ?>[custom_reason]"
								class="refund-custom-input"
								placeholder="<?php esc_attr_e( 'Enter reason...', 'wp-flyout' ); ?>"
								autocomplete="off">
					</div>
				<?php endif; ?>

				<?php // ---- Actions ---- ?>
				<div class="refund-actions">
					<button type="button"
					        class="button button-primary refund-submit"
					        data-template="<?php /* translators: %s: formatted refund amount */ esc_attr_e( 'Refund %s', 'wp-flyout' ); ?>">
						<span class="button-text">
							<?php /* translators: %s: formatted refund amount */ printf( esc_html__( 'Refund %s', 'wp-flyout' ), esc_html( $this->format_amount( $refundable ) ) ); ?>
						</span>
						<span class="button-spinner" style="display: none;">
							<span class="dashicons dashicons-update spin"></span>
						</span>
					</button>
					<button type="button" class="button button-link refund-cancel">
						<?php esc_html_e( 'Cancel', 'wp-flyout' ); ?>
					</button>
				</div>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render fully refunded state
	 *
	 * @return string
	 */
	private function render_fully_refunded(): string {
		$amount_paid = $this->config['amount_paid'];

		ob_start();
		?>
		<div class="wp-flyout-refund-form is-fully-refunded">
			<div class="refund-summary">
				<span class="refund-summary-item">
					<span class="refund-summary-label"><?php esc_html_e( 'Refunded', 'wp-flyout' ); ?></span>
					<span class="refund-summary-value refund-summary-refunded"><?php echo esc_html( $this->format_amount( $amount_paid ) ); ?></span>
				</span>
				<span class="refund-summary-sep">&middot;</span>
				<span class="refund-summary-item">
					<span class="refund-fully-badge"><?php esc_html_e( 'Fully refunded', 'wp-flyout' ); ?></span>
				</span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}