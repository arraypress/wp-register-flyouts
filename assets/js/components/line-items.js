/**
 * Line Items Component JavaScript
 *
 * Uses Select2 (via WPFlyoutAjaxSelect) for product search via REST API.
 * Fetches product details via REST /action endpoint.
 *
 * @version 5.0.0
 */
(function ($) {
    'use strict';

    const LineItems = {

        init: function () {
            var self = this;

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

            $(function () {
                $('.wp-flyout-line-items').each(function () {
                    self.initComponent($(this).parent());
                });
            });
        },

        initComponent: function ($container) {
            var self = this;

            $container.find('.wp-flyout-line-items').each(function () {
                var $component = $(this);

                if ($component.data('lineItemsInitialized')) {
                    return;
                }

                $component.data('lineItemsInitialized', true);

                // Initialize Select2 on the product search select
                var $select = $component.find('.product-ajax-select');
                if ($select.length && !$select.data('select2')) {
                    if (typeof WPFlyoutAjaxSelect !== 'undefined') {
                        WPFlyoutAjaxSelect.initOne($select);
                    }
                }

                self.recalculateTotals($component);
            });
        },

        handleAdd: function (e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $component = $button.closest('.wp-flyout-line-items');
            var $select = $component.find('.product-ajax-select');

            var itemId = $select.val();

            if (!itemId) {
                alert('Please select a product first');
                return;
            }

            var existingItem = this.findExistingItem($component, itemId);
            if (existingItem.length) {
                var $qtyInput = existingItem.find('.quantity-input');
                var currentQty = parseInt($qtyInput.val()) || 1;
                $qtyInput.val(currentQty + 1).trigger('change');
                this.clearSelect($select);
                return;
            }

            this.fetchProductDetails($component, itemId);
        },

        /**
         * Fetch product details via REST /action endpoint
         */
        fetchProductDetails: function ($component, itemId) {
            var self = this;
            var $button = $component.find('[data-action="add-item"]');
            var originalHtml = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

            var manager = $component.data('manager');
            var flyout = $component.data('flyout');
            var detailsKey = $component.data('details-key');

            fetch(wpFlyout.restUrl + '/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    manager: manager,
                    flyout: flyout,
                    action_key: detailsKey,
                    item_id: String(itemId)
                })
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Request failed');
                        }
                        return json;
                    });
                })
                .then(function (response) {
                    if (response.success && response.product) {
                        self.addItemToTable($component, response.product);
                        self.clearSelect($component.find('.product-ajax-select'));
                    } else {
                        alert(response.message || 'Product details not found');
                    }
                })
                .catch(function (error) {
                    alert(error.message || 'Error loading product details');
                })
                .finally(function () {
                    $button.prop('disabled', false).html(originalHtml);
                });
        },

        addItemToTable: function ($component, product) {
            var $tbody = $component.find('.line-items-list');

            // Create table if it doesn't exist yet
            if (!$tbody.length) {
                var showQty = $component.data('show-quantity') !== '0' && $component.data('show-quantity') !== false;
                var tableHtml = '<table><thead><tr>' +
                    '<th class="column-item">Item</th>' +
                    (showQty ? '<th class="column-quantity">Qty</th>' : '') +
                    '<th class="column-price">Price</th>' +
                    (showQty ? '<th class="column-total">Total</th>' : '') +
                    '<th class="column-actions"></th>' +
                    '</tr></thead><tbody class="line-items-list"></tbody></table>';
                $component.find('.line-items-table').html(tableHtml);
                $tbody = $component.find('.line-items-list');
            }

            var template = $component.find('.line-item-template').html();
            if (!template) {
                console.error('Line Items: Template not found');
                return;
            }

            var index = $tbody.find('.line-item').length;
            var price = parseInt(product.price) || 0;
            var currency = $component.data('currency') || 'USD';
            var formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            var thumbnailHtml = product.thumbnail ?
                '<img src="' + this.escapeHtml(product.thumbnail) + '" alt="' +
                this.escapeHtml(product.name) + '" class="item-thumbnail">' :
                '<div class="item-thumbnail-placeholder">' +
                '<span class="dashicons dashicons-format-image"></span></div>';

            var html = template
                .replace(/{{index}}/g, index)
                .replace(/{{item_id}}/g, product.id || '')
                .replace(/{{name}}/g, this.escapeHtml(product.name || ''))
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, formatter.format(price / 100))
                .replace(/{{total_formatted}}/g, formatter.format(price / 100))
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            var $newRow = $(html);
            $tbody.append($newRow);

            $newRow.css('background', '#ffffcc');
            setTimeout(function () {
                $newRow.css('background', '');
            }, 1000);

            this.recalculateTotals($component);
        },

        handleRemove: function (e) {
            e.preventDefault();
            var self = this;

            var $row = $(e.currentTarget).closest('.line-item');
            var $component = $(e.currentTarget).closest('.wp-flyout-line-items');
            var $tbody = $row.closest('.line-items-list');

            $row.fadeOut(300, function () {
                $row.remove();
                self.reindexItems($component);

                if ($tbody.find('.line-item').length === 0) {
                    var emptyHtml = '<div class="line-items-empty">' +
                        '<span class="dashicons dashicons-cart"></span>' +
                        '<p>No items added yet.</p></div>';
                    $component.find('.line-items-table').html(emptyHtml);
                }

                self.recalculateTotals($component);
            });
        },

        handleQuantityChange: function (e) {
            var $input = $(e.currentTarget);
            var $component = $input.closest('.wp-flyout-line-items');
            var $row = $input.closest('.line-item');

            var quantity = Math.max(1, parseInt($input.val()) || 1);
            $input.val(quantity);

            this.updateRowTotal($row);
            this.recalculateTotals($component);
        },

        updateRowTotal: function ($row) {
            var price = parseInt($row.find('[data-price]').data('price')) || 0;
            var quantity = parseInt($row.find('.quantity-input').val()) || 1;
            var total = price * quantity;

            var $component = $row.closest('.wp-flyout-line-items');
            var currency = $component.data('currency') || 'USD';
            var formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            $row.find('.item-total').text(formatter.format(total / 100));
        },

        recalculateTotals: function ($component) {
            var total = 0;

            $component.find('.line-item').each(function () {
                var $row = $(this);
                var price = parseInt($row.find('[data-price]').data('price')) || 0;
                var quantity = parseInt($row.find('.quantity-input').val()) || 1;
                total += price * quantity;
            });

            var currency = $component.data('currency') || 'USD';
            var formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            $component.find('.total-amount')
                .text(formatter.format(total / 100))
                .attr('data-value', total);
        },

        findExistingItem: function ($component, itemId) {
            var $found = null;
            $component.find('.line-item').each(function () {
                var $row = $(this);
                var rowItemId = $row.data('item-id') || $row.find('[name*="[id]"]').val();
                if (rowItemId == itemId) {
                    $found = $row;
                    return false;
                }
            });
            return $found ? $($found) : $();
        },

        clearSelect: function ($select) {
            if ($select.data('select2')) {
                $select.val(null).trigger('change');
            }
        },

        reindexItems: function ($component) {
            $component.find('.line-item').each(function (index) {
                var $item = $(this);
                $item.attr('data-index', index);
                $item.find('input').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    $(function () {
        LineItems.init();
    });

    window.WPFlyoutLineItems = LineItems;

})(jQuery);