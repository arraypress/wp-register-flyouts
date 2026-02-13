<?php
/**
 * Price Config Component
 *
 * Stripe-style pricing configuration for one-off and recurring prices.
 * Matches the Stripe Dashboard price creation interface with type toggle,
 * amount/compare-at/currency row, and billing period preset selector.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
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
     * Pre-defined billing period presets matching the Stripe Dashboard
     *
     * @var array<string, array{count: int, interval: string}>
     */
    private const BILLING_PRESETS = [
            'daily'      => [ 'count' => 1, 'interval' => 'day' ],
            'weekly'     => [ 'count' => 1, 'interval' => 'week' ],
            'monthly'    => [ 'count' => 1, 'interval' => 'month' ],
            'quarterly'  => [ 'count' => 3, 'interval' => 'month' ],
            'semiannual' => [ 'count' => 6, 'interval' => 'month' ],
            'yearly'     => [ 'count' => 1, 'interval' => 'year' ],
    ];

    /**
     * Labels for billing presets
     *
     * @var array<string, string>
     */
    private const BILLING_PRESET_LABELS = [
            'daily'      => 'Daily',
            'weekly'     => 'Weekly',
            'monthly'    => 'Monthly',
            'quarterly'  => 'Every 3 months',
            'semiannual' => 'Every 6 months',
            'yearly'     => 'Yearly',
            'custom'     => 'Custom',
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, [
                'name'                     => 'price',
                'amount'                   => 0,
                'compare_at_amount'        => 0,
                'currency'                 => 'USD',
                'recurring_interval'       => null,
                'recurring_interval_count' => 1,
                'currencies'               => [],
                'label'                    => 'Pricing',
                'description'              => '',
                'class'                    => '',
        ] );

        // Load currencies from library if available and none provided
        if ( empty( $this->config['currencies'] ) && function_exists( 'get_currency_options' ) ) {
            $all = get_currency_options();
            foreach ( $all as $code => $info ) {
                $label                                             = is_array( $info ) ? ( $info['name'] ?? $code ) : $info;
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
     * Format cents to decimal for display
     *
     * @param int    $amount   Amount in cents
     * @param string $currency Currency code
     *
     * @return string
     */
    private function format_display_amount( int $amount, string $currency ): string {
        if ( $amount <= 0 ) {
            return '';
        }

        if ( function_exists( 'from_currency_cents' ) ) {
            return number_format( from_currency_cents( $amount, $currency ), 2, '.', '' );
        }

        return number_format( $amount / 100, 2, '.', '' );
    }

    /**
     * Determine which billing preset matches the current interval settings
     *
     * @param string|null $interval Recurring interval
     * @param int         $count    Interval count
     *
     * @return string Preset key or 'custom'
     */
    private function resolve_billing_preset( ?string $interval, int $count ): string {
        if ( empty( $interval ) ) {
            return 'monthly';
        }

        foreach ( self::BILLING_PRESETS as $key => $preset ) {
            if ( $preset['interval'] === $interval && $preset['count'] === $count ) {
                return $key;
            }
        }

        return 'custom';
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $name              = esc_attr( $this->config['name'] );
        $amount            = (int) $this->config['amount'];
        $compare_at_amount = (int) $this->config['compare_at_amount'];
        $currency          = strtoupper( $this->config['currency'] );
        $interval          = $this->config['recurring_interval'];
        $count             = max( 1, (int) $this->config['recurring_interval_count'] );
        $is_recurring      = ! empty( $interval );

        $display_amount     = $this->format_display_amount( $amount, $currency );
        $display_compare_at = $this->format_display_amount( $compare_at_amount, $currency );

        $billing_preset = $this->resolve_billing_preset( $interval, $count );
        $is_custom      = $billing_preset === 'custom';

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

                <?php // ---- Price Type Toggle ---- ?>
                <div class="price-config-type">
                    <label class="price-config-type-option <?php echo ! $is_recurring ? 'is-active' : ''; ?>">
                        <input type="radio"
                               name="<?php echo $name; ?>_type"
                               value="one_time"
                               class="price-config-type-input"
                                <?php checked( ! $is_recurring ); ?>>
                        <span class="price-config-type-label">One off</span>
                    </label>
                    <label class="price-config-type-option <?php echo $is_recurring ? 'is-active' : ''; ?>">
                        <input type="radio"
                               name="<?php echo $name; ?>_type"
                               value="recurring"
                               class="price-config-type-input"
                                <?php checked( $is_recurring ); ?>>
                        <span class="price-config-type-label">Recurring</span>
                    </label>
                </div>

                <?php // ---- Amount + Compare At + Currency ---- ?>
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
                    <div class="price-config-compare-at">
                        <label for="<?php echo $name; ?>_compare_at">Compare at</label>
                        <input type="text"
                               id="<?php echo $name; ?>_compare_at"
                               name="<?php echo $name; ?>[compare_at_amount]"
                               value="<?php echo esc_attr( $display_compare_at ); ?>"
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

                <?php // ---- Billing Period (visible when recurring) ---- ?>
                <div class="price-config-interval" style="<?php echo $is_recurring ? '' : 'display:none;'; ?>">
                    <label>Billing period</label>

                    <select name="<?php echo $name; ?>_billing_preset"
                            class="price-config-preset-select">
                        <?php foreach ( self::BILLING_PRESET_LABELS as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"
                                    <?php selected( $billing_preset, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="price-config-interval-row" style="<?php echo $is_custom ? '' : 'display:none;'; ?>">
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