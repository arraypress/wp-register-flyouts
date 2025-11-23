/**
 * Line Items Component JavaScript - Simplified
 *
 * Manages line items with AJAX product selection and quantities.
 * All amounts are handled as integers in cents.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */
(function ($) {
    'use strict';

    const LineItems = {

        init: function () {
            const self = this;

            // Event delegation
            $(document)
                .on('click', '.wp-flyout-line-items [data-action="add-item"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-line-items [data-action="remove-item"]', function (e) {
                    self.handleRemove(e);
                })
                .on('change', '.wp-flyout-line-items [data-action="update-quantity"]', function (e) {
                    self.handleQuantityChange(e);
                })
                .on('wpflyout:opened', function (e, data) {
                    self.initComponent($(data.element));
                });

            // Initialize on load
            $(function () {
                $('.wp-flyout-line-items').each(function () {
                    self.initComponent($(this).parent());
                });
            });
        },

        initComponent: function ($container) {
            const self = this;

            $container.find('.wp-flyout-line-items').each(function () {
                const $component = $(this);

                if ($component.data('lineItemsInitialized')) {
                    return;
                }

                $component.data('lineItemsInitialized', true);

                const $select = $component.find('.product-ajax-select');

                // Initialize AJAX select
                if ($select.length && !$select.data('wpAjaxSelectInitialized')) {
                    if (typeof WPAjaxSelect !== 'undefined') {
                        const ajaxSelect = new WPAjaxSelect($select[0]);
                        $select.data('wpAjaxSelect', ajaxSelect);
                    }
                }

                self.recalculateTotals($component);
            });
        },

        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-line-items');
            const $select = $component.find('.product-ajax-select');
            const itemId = $select.val();

            if (!itemId) {
                alert('Please select a product first');
                return;
            }

            // Check for existing item
            const existingItem = this.findExistingItem($component, itemId);
            if (existingItem.length) {
                const $qtyInput = existingItem.find('.quantity-input');
                const currentQty = parseInt($qtyInput.val()) || 1;
                $qtyInput.val(currentQty + 1).trigger('change');
                this.clearAjaxSelect($select);
                return;
            }

            this.fetchProductDetails($component, itemId);
        },

        fetchProductDetails: function ($component, itemId) {
            const self = this;
            const $button = $component.find('[data-action="add-item"]');
            const originalHtml = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

            const detailsAction = $component.data('details-action');
            const $select = $component.find('.product-ajax-select');
            const nonce = $select.data('details-nonce') || '';

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: detailsAction,
                    id: String(itemId),
                    _wpnonce: nonce
                },
                success: function (response) {
                    if (response.success && response.data) {
                        self.addItemToTable($component, response.data);
                        self.clearAjaxSelect($select);
                    } else {
                        alert('Error: ' + (response.data || 'Product details not found'));
                    }
                },
                error: function () {
                    alert('Error loading product details');
                },
                complete: function () {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        },

        addItemToTable: function ($component, product) {
            let $tbody = $component.find('.line-items-list');
            const $emptyMessage = $component.find('.line-items-empty');

            // Create table if empty
            if ($emptyMessage.length) {
                const showQty = $component.data('show-quantity') === '1';
                const tableHtml = `
                    <table>
                        <thead>
                            <tr>
                                <th class="column-item">Item</th>
                                ${showQty ? '<th class="column-quantity">Qty</th>' : ''}
                                <th class="column-price">Price</th>
                                ${showQty ? '<th class="column-total">Total</th>' : ''}
                                <th class="column-actions"></th>
                            </tr>
                        </thead>
                        <tbody class="line-items-list"></tbody>
                    </table>`;
                $component.find('.line-items-table').html(tableHtml);
                $tbody = $component.find('.line-items-list');
            }

            const template = $component.find('.line-item-template').html();
            if (!template) {
                console.error('Line Items: Template not found');
                return;
            }

            const index = $tbody.find('.line-item').length;
            const price = parseInt(product.price) || 0;
            const currency = $component.data('currency') || 'USD';
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            const thumbnailHtml = product.thumbnail ?
                '<img src="' + this.escapeHtml(product.thumbnail) + '" alt="' +
                this.escapeHtml(product.name) + '" class="item-thumbnail">' :
                '<div class="item-thumbnail-placeholder">' +
                '<span class="dashicons dashicons-format-image"></span></div>';

            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{item_id}}/g, product.id || '')
                .replace(/{{name}}/g, this.escapeHtml(product.name || ''))
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, formatter.format(price / 100))
                .replace(/{{total_formatted}}/g, formatter.format(price / 100))
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            const $newRow = $(html);
            $tbody.append($newRow);

            $newRow.css('background', '#ffffcc');
            setTimeout(function () {
                $newRow.css('background', '');
            }, 1000);

            this.recalculateTotals($component);
        },

        handleRemove: function (e) {
            e.preventDefault();
            const self = this;

            const $row = $(e.currentTarget).closest('.line-item');
            const $component = $(e.currentTarget).closest('.wp-flyout-line-items');
            const $tbody = $row.closest('.line-items-list');

            $row.fadeOut(300, function () {
                $row.remove();
                self.reindexItems($component);

                if ($tbody.find('.line-item').length === 0) {
                    const emptyHtml = '<div class="line-items-empty">' +
                        '<span class="dashicons dashicons-cart"></span>' +
                        '<p>No items added yet.</p></div>';
                    $component.find('.line-items-table').html(emptyHtml);
                }

                self.recalculateTotals($component);
            });
        },

        handleQuantityChange: function (e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-line-items');
            const $row = $input.closest('.line-item');

            const quantity = Math.max(1, parseInt($input.val()) || 1);
            $input.val(quantity);

            this.updateRowTotal($row);
            this.recalculateTotals($component);
        },

        updateRowTotal: function ($row) {
            const price = parseInt($row.find('[data-price]').data('price')) || 0;
            const quantity = parseInt($row.find('.quantity-input').val()) || 1;
            const total = price * quantity;

            const $component = $row.closest('.wp-flyout-line-items');
            const currency = $component.data('currency') || 'USD';
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            $row.find('.item-total').text(formatter.format(total / 100));
        },

        recalculateTotals: function ($component) {
            let total = 0;

            $component.find('.line-item').each(function () {
                const $row = $(this);
                const price = parseInt($row.find('[data-price]').data('price')) || 0;
                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                total += price * quantity;
            });

            const currency = $component.data('currency') || 'USD';
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            $component.find('.total-amount')
                .text(formatter.format(total / 100))
                .attr('data-value', total);
        },

        findExistingItem: function ($component, itemId) {
            let $found = null;
            $component.find('.line-item').each(function () {
                const $row = $(this);
                const rowItemId = $row.data('item-id') || $row.find('[name*="[id]"]').val();
                if (rowItemId == itemId) {
                    $found = $row;
                    return false;
                }
            });
            return $found ? $($found) : $();
        },

        clearAjaxSelect: function ($select) {
            const instance = $select.data('wpAjaxSelect');
            if (instance && instance.clear) {
                instance.clear();
            }
        },

        reindexItems: function ($component) {
            $component.find('.line-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);
                $item.find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    $(function () {
        LineItems.init();
    });

    window.WPFlyoutLineItems = LineItems;

})(jQuery);