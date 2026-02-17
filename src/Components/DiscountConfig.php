<?php
/**
 * Discount Config Component
 *
 * Stripe-style discount configuration for coupons and promotion codes.
 * Features an integrated rate type selector embedded within the amount
 * input field (EDD-style), duration controls, and redemption limits.
 *
 * The rate type (percentage/fixed) is selected via a compact dropdown
 * built into the left side of the amount input, eliminating the need
 * for separate radio buttons and reducing vertical space.
 *
 * Currency is inherited from the store configuration rather than
 * selected per-coupon.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.2.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class DiscountConfig implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Duration options matching Stripe's coupon model
     *
     * @var array<string, string>
     */
    private const DURATIONS = [
            'once'      => 'Once',
            'forever'   => 'Forever',
            'repeating' => 'Multiple months',
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, [
                'name'               => 'discount',
                'rate_type'          => 'percent',
                'amount'             => 0,
                'currency'           => 'USD',
                'currency_symbol'    => '$',
                'duration'           => 'once',
                'duration_in_months' => null,
                'max_redemptions'    => null,
                'show_duration'      => true,
                'show_redemptions'   => false,
                'label'              => 'Discount',
                'description'        => '',
                'class'              => '',
        ] );
    }

    /**
     * Format cents/basis-points to decimal for display
     *
     * @param int    $amount   Amount in smallest unit
     * @param string $currency Currency code
     *
     * @return string
     */
    private function format_display_amount( int $amount, string $currency ): string {
        if ( $amount <= 0 ) {
            return '';
        }

        // Percentages are stored as basis points (e.g. 2500 = 25.00%)
        if ( $this->config['rate_type'] === 'percent' ) {
            return number_format( $amount / 100, 2, '.', '' );
        }

        if ( function_exists( 'from_currency_cents' ) ) {
            return number_format( from_currency_cents( $amount, $currency ), 2, '.', '' );
        }

        return number_format( $amount / 100, 2, '.', '' );
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $name     = esc_attr( $this->config['name'] );
        $amount   = (int) $this->config['amount'];
        $currency = strtoupper( $this->config['currency'] );
        $symbol   = $this->config['currency_symbol'];
        $duration = $this->config['duration'];
        $months   = $this->config['duration_in_months'];

        $display_amount = $this->format_display_amount( $amount, $currency );

        $classes = 'wp-flyout-discount-config';
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

                <?php $this->render_amount( $name, $display_amount, $symbol, $currency ); ?>

                <?php if ( $this->config['show_duration'] ) : ?>
                    <?php $this->render_duration( $name, $duration, $months ); ?>
                <?php endif; ?>

                <?php if ( $this->config['show_redemptions'] ) : ?>
                    <?php $this->render_redemptions( $name ); ?>
                <?php endif; ?>

            </div>

            <?php if ( ! empty( $this->config['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render amount input with integrated rate type selector
     *
     * The rate type dropdown is embedded on the left side of the input,
     * showing the currency symbol for fixed amounts or % for percentages.
     * This eliminates the need for separate radio buttons.
     *
     * @param string $name           Field name prefix
     * @param string $display_amount Formatted amount for display
     * @param string $symbol         Currency symbol (e.g. $, €, £)
     * @param string $currency       Currency code (e.g. USD) for hidden field
     *
     * @return void
     */
    private function render_amount( string $name, string $display_amount, string $symbol, string $currency ): void {
        $rate_type = $this->config['rate_type'];
        ?>
        <div class="discount-config-amount">
            <label for="<?php echo $name; ?>_amount">Amount</label>
            <div class="discount-config-amount-wrap">
                <select name="<?php echo $name; ?>[rate_type]"
                        class="discount-config-type-select"
                        aria-label="<?php esc_attr_e( 'Discount type', 'wp-flyout' ); ?>">
                    <option value="percent" <?php selected( $rate_type, 'percent' ); ?>>%</option>
                    <option value="fixed" <?php selected( $rate_type, 'fixed' ); ?>><?php echo esc_html( $symbol ); ?></option>
                </select>
                <input type="text"
                       id="<?php echo $name; ?>_amount"
                       name="<?php echo $name; ?>[amount]"
                       value="<?php echo esc_attr( $display_amount ); ?>"
                       placeholder="0.00"
                       inputmode="decimal"
                       autocomplete="off"
                       class="discount-config-amount-input">
            </div>
            <input type="hidden"
                   name="<?php echo $name; ?>[currency]"
                   value="<?php echo esc_attr( $currency ); ?>">
        </div>
        <?php
    }

    /**
     * Render duration controls
     *
     * @param string   $name     Field name prefix
     * @param string   $duration Current duration value
     * @param int|null $months   Duration in months for repeating
     *
     * @return void
     */
    private function render_duration( string $name, string $duration, ?int $months ): void {
        ?>
        <div class="discount-config-duration">
            <label for="<?php echo $name; ?>_duration">Duration</label>
            <select id="<?php echo $name; ?>_duration"
                    name="<?php echo $name; ?>[duration]"
                    class="discount-config-duration-select">
                <?php foreach ( self::DURATIONS as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"
                            <?php selected( $duration, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="discount-config-months"
                 style="<?php echo $duration === 'repeating' ? '' : 'display:none;'; ?>">
                <label for="<?php echo $name; ?>_months">Number of months</label>
                <input type="number"
                       id="<?php echo $name; ?>_months"
                       name="<?php echo $name; ?>[duration_in_months]"
                       value="<?php echo esc_attr( (string) ( $months ?? '' ) ); ?>"
                       min="1"
                       max="36"
                       placeholder="e.g. 3"
                       class="discount-config-months-input">
            </div>
        </div>
        <?php
    }

    /**
     * Render max redemptions field
     *
     * @param string $name Field name prefix
     *
     * @return void
     */
    private function render_redemptions( string $name ): void {
        ?>
        <div class="discount-config-redemptions">
            <label for="<?php echo $name; ?>_max_redemptions">Max redemptions</label>
            <input type="number"
                   id="<?php echo $name; ?>_max_redemptions"
                   name="<?php echo $name; ?>[max_redemptions]"
                   value="<?php echo esc_attr( (string) ( $this->config['max_redemptions'] ?? '' ) ); ?>"
                   min="1"
                   placeholder="Unlimited"
                   class="discount-config-redemptions-input">
            <p class="description"><?php esc_html_e( 'Leave empty for unlimited.', 'wp-flyout' ); ?></p>
        </div>
        <?php
    }

}