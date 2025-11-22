/**
 * PriceBreakdown Component JavaScript
 *
 * Handles refund actions and price recalculation
 */
(function($) {
    'use strict';

    window.WPFlyoutPriceBreakdown = {

        init: function() {
            $(document).on('click', '.price-breakdown-refund-btn', this.handleRefund.bind(this));
        },

        handleRefund: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const $item = $btn.closest('.price-breakdown-item');
            const $breakdown = $btn.closest('.price-breakdown');

            // Get refund data
            const data = {
                order_id: $breakdown.data('order-id'),
                item_id: $item.data('item-id'),
                product_id: $item.data('product-id'),
                price_id: $item.data('price-id'),
                amount: parseFloat($item.data('amount')) || 0,
                _wpnonce: $breakdown.data('refund-nonce')
            };

            const ajax_action = $breakdown.data('refund-ajax');

            if (!ajax_action) {
                console.error('No refund AJAX action specified');
                return;
            }

            // Confirm refund
            if (!confirm('Are you sure you want to refund this item?')) {
                return;
            }

            // Disable button and show processing
            $btn.prop('disabled', true)
                .find('.dashicons')
                .removeClass('dashicons-undo')
                .addClass('dashicons-update spin');

            // Make AJAX request
            $.post(ajaxurl, {
                action: ajax_action,
                ...data
            })
                .done((response) => {
                    if (response.success) {
                        this.markAsRefunded($item, $breakdown);
                        this.updateTotals($breakdown, data.amount);

                        // Show success message if WPFlyoutAlert is available
                        if (window.WPFlyoutAlert) {
                            WPFlyoutAlert.show('Item refunded successfully', 'success', {
                                target: $breakdown,
                                timeout: 3000
                            });
                        }

                        // Trigger custom event
                        $(document).trigger('pricebreakdown:refunded', {
                            item: $item[0],
                            data: data,
                            response: response.data
                        });
                    } else {
                        // Re-enable button on error
                        $btn.prop('disabled', false)
                            .find('.dashicons')
                            .removeClass('dashicons-update spin')
                            .addClass('dashicons-undo');

                        const message = response.data?.message || 'Refund failed. Please try again.';

                        if (window.WPFlyoutAlert) {
                            WPFlyoutAlert.show(message, 'error', {
                                target: $breakdown,
                                dismissible: true
                            });
                        } else {
                            alert(message);
                        }
                    }
                })
                .fail(() => {
                    // Re-enable button on error
                    $btn.prop('disabled', false)
                        .find('.dashicons')
                        .removeClass('dashicons-update spin')
                        .addClass('dashicons-undo');

                    if (window.WPFlyoutAlert) {
                        WPFlyoutAlert.show('Connection error. Please try again.', 'error', {
                            target: $breakdown,
                            dismissible: true
                        });
                    } else {
                        alert('Connection error. Please try again.');
                    }
                });
        },

        markAsRefunded: function($item, $breakdown) {
            // Mark item as refunded
            $item.addClass('refunded');

            // Add refunded badge
            const $label = $item.find('.item-label');
            if (!$label.find('.refund-badge').length) {
                $label.append(' <span class="refund-badge">Refunded</span>');
            }

            // Strike through the amount
            $item.find('.item-amount').addClass('strikethrough');

            // Remove refund button
            $item.find('.price-breakdown-refund-btn').remove();
        },

        updateTotals: function($breakdown, refundAmount) {
            // Update the total
            const $total = $breakdown.find('.price-breakdown-total .amount');
            if ($total.length) {
                const currentTotal = parseFloat($total.data('original-total')) || 0;
                const newTotal = Math.max(0, currentTotal - refundAmount);

                // Store new total
                $total.data('original-total', newTotal);

                // Animate the change
                $total.addClass('updating');

                // Update the displayed amount (you'll need to format this based on currency)
                this.updateFormattedAmount($total, newTotal, $breakdown);

                setTimeout(() => {
                    $total.removeClass('updating');
                }, 500);
            }

            // You could also update subtotal if needed
            this.recalculateSummary($breakdown, refundAmount);
        },

        recalculateSummary: function($breakdown, refundAmount) {
            // Update subtotal if it exists
            const $subtotal = $breakdown.find('.price-breakdown-subtotal .amount');
            if ($subtotal.length) {
                const currentSubtotal = parseFloat($subtotal.data('amount')) || 0;
                const newSubtotal = Math.max(0, currentSubtotal - refundAmount);
                $subtotal.data('amount', newSubtotal);
                this.updateFormattedAmount($subtotal, newSubtotal, $breakdown);
            }
        },

        updateFormattedAmount: function($element, amount, $breakdown) {
            const currency = $breakdown.data('currency') || 'USD';
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            });

            $element.text(formatter.format(amount));
        }
    };

    // Initialize on ready
    $(document).ready(function() {
        WPFlyoutPriceBreakdown.init();
    });

})(jQuery);