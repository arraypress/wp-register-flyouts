/**
 * Line Items Component JavaScript
 *
 * Manages line items with AJAX product selection, quantities, and editable pricing.
 * All amounts are handled as integers in cents.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */
(function ($) {
    'use strict';

    /**
     * Line Items component controller
     *
     * @namespace LineItems
     */
    const LineItems = {

        /**
         * Initialize the Line Items component
         */
        init: function () {
            const self = this;

            // Use delegation for dynamic content
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
                .on('blur', '.wp-flyout-line-items [data-action="update-price"]', function (e) {
                    self.handlePriceChange(e);
                })
                .on('wpflyout:opened', function (e, data) {
                    self.initComponent($(data.element));
                });

            // Initialize existing components on page load
            $(function () {
                $('.wp-flyout-line-items').each(function () {
                    self.initComponent($(this).parent());
                });
            });
        },

        /**
         * Initialize Line Items component instance
         */
        initComponent: function ($container) {
            const self = this;

            $container.find('.wp-flyout-line-items').each(function () {
                const $component = $(this);

                // Skip if already initialized
                if ($component.data('lineItemsInitialized')) {
                    return;
                }

                $component.data('lineItemsInitialized', true);

                const $select = $component.find('.product-ajax-select');

                // Initialize AJAX select if present
                if ($select.length && !$select.data('wpAjaxSelectInitialized')) {
                    if (typeof WPAjaxSelect !== 'undefined') {
                        const ajaxSelect = new WPAjaxSelect($select[0]);
                        $select.data('wpAjaxSelect', ajaxSelect);
                    } else if ($.fn.wpAjaxSelect) {
                        $select.wpAjaxSelect();
                    } else {
                        console.warn('WPAjaxSelect not available for Line Items');
                    }
                }

                // Calculate initial totals
                self.recalculateTotals($component);

                // Trigger initialization event
                $component.trigger('lineitems:initialized', {
                    component: $component[0],
                    itemCount: $component.find('.line-item').length
                });
            });
        },

        /**
         * Handle add item action
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-line-items');
            const $select = $component.find('.product-ajax-select');
            const itemId = $select.val();

            // Validate selection
            if (!itemId) {
                $component.trigger('lineitems:error', {
                    type: 'no_selection',
                    message: 'Please select a product first'
                });
                alert('Please select a product first');
                return;
            }

            // Fire before add event (cancellable)
            const beforeAddEvent = $.Event('lineitems:beforeadd');
            $component.trigger(beforeAddEvent, {itemId: itemId});

            if (beforeAddEvent.isDefaultPrevented()) {
                return;
            }

            // Check for existing item
            const existingItem = this.findExistingItem($component, itemId);
            if (existingItem.length) {
                // Increment quantity instead
                const $qtyInput = existingItem.find('.quantity-input');
                const currentQty = parseInt($qtyInput.val()) || 1;
                const newQty = currentQty + 1;

                $qtyInput.val(newQty).trigger('change');
                this.clearAjaxSelect($select);

                $component.trigger('lineitems:quantityincremented', {
                    itemId: itemId,
                    oldQuantity: currentQty,
                    newQuantity: newQty,
                    row: existingItem[0]
                });
                return;
            }

            // Fetch product details
            this.fetchProductDetails($component, itemId);
        },

        /**
         * Fetch product details via AJAX
         */
        fetchProductDetails: function ($component, itemId) {
            const self = this;
            const $button = $component.find('[data-action="add-item"]');
            const originalHtml = $button.html();

            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

            const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
            const detailsAction = $component.data('details-action');
            const $select = $component.find('.product-ajax-select');
            const nonce = $select.data('details-nonce') || '';

            $component.trigger('lineitems:fetchstart', {itemId: itemId});

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: detailsAction,
                    item_id: String(itemId),
                    _wpnonce: nonce
                },
                success: function (response) {
                    if (response.success && response.data) {
                        self.addItemToTable($component, response.data);
                        self.clearAjaxSelect($select);

                        $component.trigger('lineitems:fetchsuccess', {
                            itemId: itemId,
                            product: response.data
                        });
                    } else {
                        const errorMsg = response.data || 'Product details not found';
                        $component.trigger('lineitems:fetcherror', {
                            itemId: itemId,
                            error: errorMsg
                        });
                        alert('Error: ' + errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $component.trigger('lineitems:fetcherror', {
                        itemId: itemId,
                        error: error,
                        xhr: xhr
                    });
                    alert('Error loading product details: ' + error);
                },
                complete: function () {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        },

        /**
         * Add item to table
         */
        addItemToTable: function ($component, product) {
            let $tbody = $component.find('.line-items-list');
            const $emptyMessage = $component.find('.line-items-empty');

            // Create table structure if starting from empty
            if ($emptyMessage.length) {
                const tableHtml = this.buildTableStructure($component);
                $component.find('.line-items-table').html(tableHtml);
                $tbody = $component.find('.line-items-list');
            }

            // Get template
            const template = $component.find('.line-item-template').html();
            if (!template) {
                console.error('Line Items: Template not found');
                return;
            }

            // Prepare data
            const index = $tbody.find('.line-item').length;
            const price = parseInt(product.price) || 0; // Price in cents

            // Display prices as simple decimals (no currency symbol)
            const priceDisplay = (price / 100).toFixed(2);
            const totalDisplay = priceDisplay; // Same for qty 1

            const thumbnailHtml = product.thumbnail ?
                '<img src="' + this.escapeHtml(product.thumbnail) + '" alt="' +
                this.escapeHtml(product.name) + '" class="item-thumbnail">' :
                '<div class="item-thumbnail-placeholder">' +
                '<span class="dashicons dashicons-format-image"></span></div>';

            // Replace template placeholders
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{item_id}}/g, product.id || '')
                .replace(/{{name}}/g, this.escapeHtml(product.name || ''))
                .replace(/{{price}}/g, price) // Store cents value
                .replace(/{{price_formatted}}/g, priceDisplay) // Display as decimal
                .replace(/{{total_formatted}}/g, totalDisplay) // Display as decimal
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            // Add row
            const $newRow = $(html);
            $tbody.append($newRow);

            // Visual feedback
            $newRow.css('background', '#ffffcc');
            setTimeout(function () {
                $newRow.css('background', '');
            }, 1000);

            // Update totals
            this.recalculateTotals($component);

            // Trigger added event
            $component.trigger('lineitems:added', {
                product: product,
                row: $newRow[0],
                index: index,
                price: price,
                quantity: 1
            });
        },

        /**
         * Build table structure
         */
        buildTableStructure: function ($component) {
            return `
                <table>
                    <thead>
                        <tr>
                            <th class="column-item">Item</th>
                            <th class="column-quantity">Qty</th>
                            <th class="column-price">Price</th>
                            <th class="column-total">Total</th>
                            <th class="column-actions"></th>
                        </tr>
                    </thead>
                    <tbody class="line-items-list"></tbody>
                </table>
            `;
        },

        /**
         * Handle remove item
         */
        handleRemove: function (e) {
            e.preventDefault();
            const self = this;

            const $button = $(e.currentTarget);
            const $row = $button.closest('.line-item');
            const $component = $button.closest('.wp-flyout-line-items');
            const $tbody = $row.closest('.line-items-list');

            const itemId = $row.data('item-id');
            const itemIndex = $row.index();

            // Fire before remove event
            const beforeRemoveEvent = $.Event('lineitems:beforeremove');
            $component.trigger(beforeRemoveEvent, {
                itemId: itemId,
                row: $row[0],
                index: itemIndex
            });

            if (beforeRemoveEvent.isDefaultPrevented()) {
                return;
            }

            // Animate removal
            $row.fadeOut(300, function () {
                $row.remove();
                self.reindexItems($component);

                // Check if empty
                if ($tbody.find('.line-item').length === 0) {
                    const emptyHtml = '<div class="line-items-empty">' +
                        '<span class="dashicons dashicons-cart"></span>' +
                        '<p>No items added yet.</p></div>';
                    $component.find('.line-items-table').html(emptyHtml);
                }

                self.recalculateTotals($component);

                // Trigger removed event
                $component.trigger('lineitems:removed', {
                    itemId: itemId,
                    index: itemIndex,
                    remainingCount: $tbody.find('.line-item').length
                });
            });
        },

        /**
         * Handle quantity change
         */
        handleQuantityChange: function (e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-line-items');
            const $row = $input.closest('.line-item');

            const quantity = Math.max(1, parseInt($input.val()) || 1);
            $input.val(quantity);

            // Update row total
            this.updateRowTotal($row);

            // Recalculate overall total
            this.recalculateTotals($component);

            // Trigger event
            $component.trigger('lineitems:quantitychanged', {
                itemId: $row.data('item-id'),
                row: $row[0],
                quantity: quantity
            });
        },

        /**
         * Handle price change (for editable prices)
         * User enters price as decimal (e.g., "19.99")
         */
        handlePriceChange: function (e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-line-items');
            const $row = $input.closest('.line-item');

            // Parse decimal input to cents
            const priceValue = parseFloat($input.val()) || 0;
            const priceInCents = Math.round(priceValue * 100);

            // Store cents for calculations
            $input.attr('data-cents', priceInCents);

            // Keep display as decimal
            $input.val((priceInCents / 100).toFixed(2));

            // Update row total
            this.updateRowTotal($row);

            // Recalculate overall total
            this.recalculateTotals($component);

            // Trigger event
            $component.trigger('lineitems:pricechanged', {
                itemId: $row.data('item-id'),
                row: $row[0],
                price: priceInCents
            });
        },

        /**
         * Update row total (display as simple decimal)
         */
        updateRowTotal: function ($row) {
            const $priceInput = $row.find('.price-input');

            let price;
            if ($priceInput.length) {
                // Editable price
                price = parseInt($priceInput.attr('data-cents')) || 0;
            } else {
                // Fixed price
                price = parseInt($row.find('[data-price]').data('price')) || 0;
            }

            const quantity = parseInt($row.find('.quantity-input').val()) || 1;
            const total = price * quantity;

            // Display as decimal (no currency symbol)
            $row.find('.item-total').text((total / 100).toFixed(2));
        },

        /**
         * Recalculate and display totals
         */
        recalculateTotals: function ($component) {
            let total = 0;

            // Calculate total (all in cents)
            $component.find('.line-item').each(function () {
                const $row = $(this);
                const $priceInput = $row.find('.price-input');

                let price;
                if ($priceInput.length) {
                    price = parseInt($priceInput.attr('data-cents')) || 0;
                } else {
                    price = parseInt($row.find('[data-price]').data('price')) || 0;
                }

                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                total += price * quantity;
            });

            // Format total with native Intl.NumberFormat
            const currency = $component.data('currency') || 'USD';
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Convert cents to dollars for formatting
            const formattedTotal = formatter.format(total / 100);

            // Update total display
            $component.find('.total-amount')
                .text(formattedTotal)
                .attr('data-value', total);

            // Trigger updated event
            $component.trigger('lineitems:updated', {
                total: total,
                itemCount: $component.find('.line-item').length
            });
        },

        /**
         * Find existing item by item ID
         */
        findExistingItem: function ($component, itemId) {
            let $found = null;

            $component.find('.line-item').each(function () {
                const $row = $(this);
                const rowItemId = $row.data('item-id') ||
                    $row.find('[name*="[id]"]').val();

                if (rowItemId == itemId) {
                    $found = $row;
                    return false; // Break loop
                }
            });

            return $found ? $($found) : $();
        },

        /**
         * Clear AJAX select input
         */
        clearAjaxSelect: function ($select) {
            const instance = $select.data('wpAjaxSelect');
            if (instance && instance.clear) {
                instance.clear();
            }
        },

        /**
         * Reindex form field names after add/remove
         */
        reindexItems: function ($component) {
            const namePrefix = $component.data('prefix') || 'line_items';

            $component.find('.line-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);

                // Update all input names with new index
                $item.find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });

            // Trigger reindex event
            $component.trigger('lineitems:reindexed', {
                count: $component.find('.line-item').length
            });
        },

        /**
         * Escape HTML for security
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(function () {
        LineItems.init();
    });

    // Export for external use
    window.WPFlyoutLineItems = LineItems;

})(jQuery);