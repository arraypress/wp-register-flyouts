<?php
/**
 * PaymentMethod Component
 *
 * Displays payment method information with brand icons and optional risk indicators.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class PaymentMethod implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * SVG file mappings for card brands
     *
     * @var array
     */
    private const CARD_BRANDS = [
            'visa'       => 'visa.svg',
            'mastercard' => 'mastercard.svg',
            'amex'       => 'amex.svg',
            'discover'   => 'discover.svg',
            'diners'     => 'diners.svg',
            'jcb'        => 'jcb.svg',
            'unionpay'   => 'unionpay.svg',
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'payment-method-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'                => '',
                'payment_method'    => 'card',
                'payment_brand'     => '',
                'payment_last4'     => '',
                'stripe_risk_score' => null,
                'stripe_risk_level' => '',
                'class'             => '',
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['payment_method'] ) ) {
            return '';
        }

        $classes = [ 'wp-flyout-payment-method' ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <div class="payment-icon">
                <?php echo $this->get_payment_icon(); ?>
            </div>

            <div class="payment-details">
                <?php echo $this->get_payment_display(); ?>
                <?php $this->render_risk_indicator(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get payment icon HTML
     *
     * @return string
     */
    private function get_payment_icon(): string {
        if ( $this->config['payment_method'] === 'card' && $this->config['payment_brand'] ) {
            return $this->get_card_brand_svg( $this->config['payment_brand'] );
        }

        // Default card icon for unknown brands
        return '<span class="dashicons dashicons-credit-card"></span>';
    }

    /**
     * Get card brand SVG icon
     *
     * @param string $brand Card brand
     *
     * @return string
     */
    private function get_card_brand_svg( string $brand ): string {
        $brand = strtolower( $brand );

        if ( isset( self::CARD_BRANDS[ $brand ] ) ) {
            $svg_content = $this->load_svg( self::CARD_BRANDS[ $brand ] );
            if ( $svg_content ) {
                return $svg_content;
            }
        }

        return '<span class="dashicons dashicons-credit-card"></span>';
    }

    /**
     * Load SVG file content
     *
     * @param string $filename SVG filename
     *
     * @return string|false SVG content or false on failure
     */
    private function load_svg( string $filename ) {
        return wp_get_composer_file(
                __FILE__,
                'images/payment-methods/' . $filename,
                true
        );
    }

    /**
     * Get payment display text
     *
     * @return string
     */
    private function get_payment_display(): string {
        $display = '';

        if ( $this->config['payment_method'] === 'card' ) {
            if ( ! empty( $this->config['payment_brand'] ) ) {
                $brand_display = ucfirst( $this->config['payment_brand'] );
                $display       .= '<span class="payment-brand">' . esc_html( $brand_display ) . '</span>';
            }

            if ( ! empty( $this->config['payment_last4'] ) ) {
                $display .= ' <span class="payment-last4">•••• ' . esc_html( $this->config['payment_last4'] ) . '</span>';
            }

            return $display ?: '<span class="payment-type">Card</span>';
        }

        return '<span class="payment-type">' . esc_html( ucfirst( $this->config['payment_method'] ) ) . '</span>';
    }

    /**
     * Render risk indicator if available
     */
    private function render_risk_indicator(): void {
        if ( ! empty( $this->config['stripe_risk_level'] ) ) {
            $risk_class = 'risk-' . sanitize_html_class( $this->config['stripe_risk_level'] );
            ?>
            <span class="payment-risk <?php echo esc_attr( $risk_class ); ?>">
                <?php if ( $this->config['stripe_risk_score'] !== null ) : ?>
                    <span class="risk-score"><?php echo esc_html( $this->config['stripe_risk_score'] ); ?></span>
                <?php endif; ?>
                <span class="risk-level"><?php echo esc_html( ucfirst( $this->config['stripe_risk_level'] ) ); ?></span>
            </span>
            <?php
        }
    }

}