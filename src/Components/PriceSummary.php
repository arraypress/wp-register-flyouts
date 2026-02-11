<?php
/**
 * PriceSummary Component
 *
 * Displays a summary of prices with line items and totals.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;
use function esc_currency_e;

class PriceSummary implements Renderable {

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
            $this->config['id'] = 'price-summary-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'       => '',
                'items'    => [],
                'subtotal' => null,
                'tax'      => null,
                'discount' => null,
                'total'    => 0,
                'currency' => 'USD',
                'class'    => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-price-summary' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <table class="price-summary-table">
                <?php if ( ! empty( $this->config['items'] ) ) : ?>
                    <tbody class="price-summary-items">
                    <?php foreach ( $this->config['items'] as $item ) : ?>
                        <?php $this->render_line_item( $item ); ?>
                    <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>

                <?php if ( $this->has_summary_lines() ) : ?>
                    <tbody class="price-summary-details">
                    <?php $this->render_summary_lines(); ?>
                    </tbody>
                <?php endif; ?>

                <?php if ( $this->config['total'] !== null ) : ?>
                    <tfoot class="price-summary-footer">
                    <tr class="price-summary-total">
                        <td class="label"><?php esc_html_e( 'Total', 'wp-flyout' ); ?></td>
                        <td class="amount">
                            <?php esc_currency_e( $this->config['total'], $this->config['currency'] ); ?>
                        </td>
                    </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if we have summary lines to render
     *
     * @return bool
     */
    private function has_summary_lines(): bool {
        return $this->config['subtotal'] !== null ||
               $this->config['tax'] !== null ||
               $this->config['discount'] !== null;
    }

    /**
     * Render a line item
     *
     * @param array $item Item configuration
     */
    private function render_line_item( array $item ): void {
        $label    = $item['label'] ?? '';
        $amount   = $item['amount'] ?? 0;
        $quantity = $item['quantity'] ?? null;

        if ( empty( $label ) ) {
            return;
        }

        ?>
        <tr class="price-summary-item">
            <td class="item-label">
                <?php echo esc_html( $label ); ?>
                <?php if ( $quantity !== null && $quantity > 1 ) : ?>
                    <span class="item-quantity">× <?php echo esc_html( $quantity ); ?></span>
                <?php endif; ?>
            </td>
            <td class="item-amount">
                <?php esc_currency_e( $amount, $this->config['currency'] ); ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Render summary lines
     */
    private function render_summary_lines(): void {
        $lines = [
                'subtotal' => __( 'Subtotal', 'wp-flyout' ),
                'discount' => __( 'Discount', 'wp-flyout' ),
                'tax'      => __( 'Tax', 'wp-flyout' ),
        ];

        foreach ( $lines as $key => $label ) {
            if ( $this->config[ $key ] === null ) {
                continue;
            }

            $amount = $this->config[ $key ];

            // Show discounts as negative
            if ( $key === 'discount' && $amount > 0 ) {
                $amount = - $amount;
            }

            ?>
            <tr class="price-summary-<?php echo esc_attr( $key ); ?>">
                <td class="label"><?php echo esc_html( $label ); ?></td>
                <td class="amount <?php echo $amount < 0 ? 'negative' : ''; ?>">
                    <?php esc_currency_e( $amount, $this->config['currency'] ); ?>
                </td>
            </tr>
            <?php
        }
    }

}