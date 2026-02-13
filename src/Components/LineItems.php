<?php
/**
 * Line Items Component
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     4.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;
use ArrayPress\RegisterFlyouts\RestApi;
use ArrayPress\RegisterFlyouts\Traits\HtmlAttributes;
use function esc_currency_e;

class LineItems implements Renderable {
    use HtmlAttributes;

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
            $this->config['id'] = 'line-items-' . wp_generate_uuid4();
        }

        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'            => '',
                'name'          => 'line_items',
                'items'         => [],
                'currency'      => 'USD',
                'show_quantity' => true,
                'search_key'    => '',       // Field key for the ajax_select search
                'details_key'   => '',       // Action key for fetching product details
                'manager'       => '',       // Manager prefix (set by normalize_ajax_fields)
                'flyout'        => '',       // Flyout ID (set by normalize_ajax_fields)
                'placeholder'   => 'Search for products...',
                'empty_text'    => 'No items added yet.',
                'add_text'      => 'Add Item',
                'class'         => ''
        ];
    }

    /**
     * Calculate total
     *
     * @return int Total in cents
     */
    private function calculate_total(): int {
        $total = 0;
        foreach ( $this->config['items'] as $item ) {
            $price    = (int) ( $item['price'] ?? 0 );
            $quantity = (int) ( $item['quantity'] ?? 1 );
            $total    += $price * $quantity;
        }

        return $total;
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-line-items' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $data = [
                'name'          => $this->config['name'],
                'currency'      => $this->config['currency'],
                'show-quantity' => $this->config['show_quantity'] ? '1' : '0',
                'manager'       => $this->config['manager'],
                'flyout'        => $this->config['flyout'],
                'details-key'   => $this->config['details_key'],
        ];

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php echo $this->build_data_attributes( $data ); ?>>

            <?php if ( $this->config['search_key'] ) : ?>
                <?php $this->render_product_selector(); ?>
            <?php endif; ?>

            <div class="line-items-table">
                <?php $this->render_items_table(); ?>
            </div>

            <?php $this->render_total(); ?>
            <?php $this->render_item_template(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render product selector
     *
     * Uses REST /search endpoint via data-ajax-url and data-ajax-params.
     */
    private function render_product_selector(): void {
        $search_url = rest_url( RestApi::NAMESPACE . '/search' );

        $ajax_params = wp_json_encode( [
                'manager'   => $this->config['manager'],
                'flyout'    => $this->config['flyout'],
                'field_key' => $this->config['search_key'],
        ] );
        ?>
        <div class="line-items-selector">
            <select class="wp-flyout-ajax-select product-ajax-select"
                    data-ajax-url="<?php echo esc_url( $search_url ); ?>"
                    data-ajax-params='<?php echo esc_attr( $ajax_params ); ?>'
                    data-placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>">
            </select>
            <button type="button" class="button button-primary" data-action="add-item">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html( $this->config['add_text'] ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render items table
     */
    private function render_items_table(): void {
        if ( empty( $this->config['items'] ) ) {
            ?>
            <div class="line-items-empty">
                <span class="dashicons dashicons-cart"></span>
                <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
            </div>
            <?php
            return;
        }
        ?>
        <table>
            <thead>
            <tr>
                <th class="column-item">Item</th>
                <?php if ( $this->config['show_quantity'] ) : ?>
                    <th class="column-quantity">Qty</th>
                <?php endif; ?>
                <th class="column-price">Price</th>
                <?php if ( $this->config['show_quantity'] ) : ?>
                    <th class="column-total">Total</th>
                <?php endif; ?>
                <th class="column-actions"></th>
            </tr>
            </thead>
            <tbody class="line-items-list">
            <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                <?php $this->render_item_row( $item, $index ); ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render single item row
     *
     * @param array $item  Item data
     * @param int   $index Item index
     */
    private function render_item_row( array $item, int $index ): void {
        $price    = (int) ( $item['price'] ?? 0 );
        $quantity = (int) ( $item['quantity'] ?? 1 );
        $total    = $price * $quantity;
        ?>
        <tr class="line-item" data-index="<?php echo $index; ?>"
            data-item-id="<?php echo esc_attr( $item['id'] ?? '' ); ?>">

            <td class="column-item">
                <div>
                    <?php if ( ! empty( $item['thumbnail'] ) ) : ?>
                        <img src="<?php echo esc_url( $item['thumbnail'] ); ?>"
                             alt="<?php echo esc_attr( $item['name'] ?? '' ); ?>"
                             class="item-thumbnail">
                    <?php else : ?>
                        <div class="item-thumbnail-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                    <span><?php echo esc_html( $item['name'] ?? '' ); ?></span>
                </div>
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][id]"
                       value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $item['name'] ?? '' ); ?>">
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][thumbnail]"
                       value="<?php echo esc_url( $item['thumbnail'] ?? '' ); ?>">
            </td>

            <?php if ( $this->config['show_quantity'] ) : ?>
                <td class="column-quantity">
                    <input type="number"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][quantity]"
                           value="<?php echo esc_attr( (string) $quantity ); ?>"
                           min="1"
                           class="quantity-input small-text"
                           data-action="update-quantity">
                </td>
            <?php else : ?>
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][quantity]"
                       value="1">
            <?php endif; ?>

            <td class="column-price">
                <span data-price="<?php echo esc_attr( (string) $price ); ?>">
                    <?php esc_currency_e( $price, $this->config['currency'] ); ?>
                </span>
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][price]"
                       value="<?php echo esc_attr( (string) $price ); ?>">
            </td>

            <?php if ( $this->config['show_quantity'] ) : ?>
                <td class="column-total">
                    <span class="item-total"><?php esc_currency_e( $total, $this->config['currency'] ); ?></span>
                </td>
            <?php endif; ?>

            <td class="column-actions">
                <button type="button" class="button-link" data-action="remove-item">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </td>
        </tr>
        <?php
    }

    /**
     * Render total section
     */
    private function render_total(): void {
        $total = $this->calculate_total();
        ?>
        <div class="line-items-total">
            <span class="total-label">Total:</span>
            <span class="total-amount" data-value="<?php echo esc_attr( (string) $total ); ?>">
                <?php esc_currency_e( $total, $this->config['currency'] ); ?>
            </span>
        </div>
        <?php
    }

    /**
     * Render JavaScript template for dynamic items
     */
    private function render_item_template(): void {
        ?>
        <script type="text/template" class="line-item-template">
            <tr class="line-item" data-item-id="{{item_id}}">
                <td class="column-item">
                    <div>
                        {{thumbnail_html}}
                        <span>{{name}}</span>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][id]"
                           value="{{item_id}}">
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]"
                           value="{{name}}">
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][thumbnail]"
                           value="{{thumbnail}}">
                </td>

                <?php if ( $this->config['show_quantity'] ) : ?>
                    <td class="column-quantity">
                        <input type="number"
                               name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][quantity]"
                               value="1"
                               min="1"
                               class="quantity-input small-text"
                               data-action="update-quantity">
                    </td>
                <?php else : ?>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][quantity]"
                           value="1">
                <?php endif; ?>

                <td class="column-price">
                    <span data-price="{{price}}">{{price_formatted}}</span>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][price]"
                           value="{{price}}">
                </td>

                <?php if ( $this->config['show_quantity'] ) : ?>
                    <td class="column-total">
                        <span class="item-total">{{total_formatted}}</span>
                    </td>
                <?php endif; ?>

                <td class="column-actions">
                    <button type="button" class="button-link" data-action="remove-item">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        </script>
        <?php
    }

}