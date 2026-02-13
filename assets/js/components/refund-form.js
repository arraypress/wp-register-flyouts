/**
 * WP Flyout - Refund Form Component
 *
 * Handles refund panel toggling, amount validation,
 * live confirm button text, and REST API submission.
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    const RefundForm = {

        init: function () {
            $(document)
                .on('click', '.refund-trigger', this.togglePanel)
                .on('click', '.refund-cancel', this.closePanel)
                .on('click', '.refund-submit', this.handleSubmit.bind(this))
                .on('input', '.refund-amount-input', this.onAmountChange)
                .on('change', '.refund-reason-select', this.onReasonChange);
        },

        /**
         * Toggle the refund panel
         */
        togglePanel: function () {
            var $form = $(this).closest('.wp-flyout-refund-form');
            var $panel = $form.find('.refund-panel');

            if ($panel.is(':visible')) {
                $panel.slideUp(150);
            } else {
                $panel.slideDown(150);
                $panel.find('.refund-amount-input').focus();
            }
        },

        /**
         * Close the refund panel
         */
        closePanel: function () {
            $(this).closest('.wp-flyout-refund-form').find('.refund-panel').slideUp(150);
        },

        /**
         * Handle amount input changes — update confirm button text
         */
        onAmountChange: function () {
            var $form = $(this).closest('.wp-flyout-refund-form');
            var $submit = $form.find('.refund-submit');
            var template = $submit.data('template');
            var amount = parseFloat($(this).val()) || 0;
            var currency = $form.data('currency') || 'USD';

            var formatted = amount.toFixed(2);
            $submit.find('.button-text').text(template.replace('%s', currency + ' ' + formatted));
        },

        /**
         * Handle reason dropdown — show/hide custom input
         */
        onReasonChange: function () {
            var $form = $(this).closest('.wp-flyout-refund-form');
            var $custom = $form.find('.refund-custom-reason');

            if ($(this).val() === 'other') {
                $custom.slideDown(150);
                $custom.find('input').focus();
            } else {
                $custom.slideUp(150);
            }
        },

        /**
         * Submit refund via REST API
         */
        handleSubmit: function (e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $form = $button.closest('.wp-flyout-refund-form');
            var $flyout = $form.closest('.wp-flyout');
            var config = $flyout.data() || {};

            var refundable = parseFloat($form.data('refundable')) || 0;
            var amountInput = parseFloat($form.find('.refund-amount-input').val()) || 0;
            var currency = $form.data('currency') || 'USD';

            // Convert display amount to cents
            var amountCents = Math.round(amountInput * 100);
            var refundableCents = parseInt($form.data('refundable'), 10) || 0;

            // Validate amount
            if (amountCents <= 0) {
                this.showAlert($flyout, 'Please enter a refund amount.', 'error');
                return;
            }

            if (amountCents > refundableCents) {
                this.showAlert($flyout, 'Amount exceeds the refundable balance.', 'error');
                return;
            }

            // Gather reason
            var reason = $form.find('.refund-reason-select').val();
            var customReason = '';

            if (reason === 'other') {
                customReason = $form.find('.refund-custom-input').val() || '';
                reason = '';
            }

            // Get item ID from flyout
            var itemId = $flyout.find('input[name="id"]').val() || config.data?.id || 0;

            // Set loading state
            this.setLoading($button, true);

            var self = this;

            fetch(wpFlyout.restUrl + '/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    manager: config.manager,
                    flyout: config.flyout,
                    action_key: $form.data('action'),
                    item_id: itemId,
                    amount: amountCents,
                    currency: currency,
                    reason: reason,
                    custom_reason: customReason
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
                    self.handleResponse(response, $form, $flyout);
                })
                .catch(function (error) {
                    self.showAlert($flyout, error.message || 'Connection failed. Please try again.', 'error');
                })
                .finally(function () {
                    self.setLoading($button, false);
                });
        },

        /**
         * Handle successful response
         */
        handleResponse: function (response, $form, $flyout) {
            var $body = $flyout.find('.wp-flyout-body');

            if (response.success) {
                var message = response.message || 'Refund processed successfully.';
                this.showAlert($flyout, message, 'success');
                $body.animate({scrollTop: 0}, 300);

                // Close the panel
                $form.find('.refund-panel').slideUp(150);

                if (response.reload === true) {
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else if (response.refresh_flyout === true) {
                    setTimeout(function () {
                        RefundForm.reloadFlyout($flyout);
                    }, 1500);
                }
            } else {
                var errorMsg = response.message || 'Refund failed.';
                this.showAlert($flyout, errorMsg, 'error');
                $body.animate({scrollTop: 0}, 300);
            }
        },

        /**
         * Set button loading state
         */
        setLoading: function ($button, loading) {
            if (loading) {
                $button.prop('disabled', true).addClass('loading');
                $button.find('.button-text').hide();
                $button.find('.button-spinner').show();
            } else {
                $button.prop('disabled', false).removeClass('loading');
                $button.find('.button-text').show();
                $button.find('.button-spinner').hide();
            }
        },

        /**
         * Show alert in flyout
         */
        showAlert: function ($flyout, message, type) {
            if (window.WPFlyoutAlert) {
                $flyout.find('.wp-flyout-alert').remove();
                WPFlyoutAlert.show(message, type, {
                    target: $flyout.find('.wp-flyout-body'),
                    prepend: true,
                    timeout: type === 'success' ? 3000 : 0,
                    dismissible: true
                });
            } else {
                console[type === 'error' ? 'error' : 'log'](message);
            }
        },

        /**
         * Reload flyout content
         */
        reloadFlyout: function ($flyout) {
            var flyoutId = $flyout.attr('id');
            var $trigger = $('[data-flyout-instance="' + flyoutId + '"]');

            if ($trigger.length) {
                WPFlyout.close(flyoutId);
                setTimeout(function () {
                    $trigger.click();
                }, 300);
            } else {
                location.reload();
            }
        }
    };

    $(function () {
        RefundForm.init();
    });

    window.WPFlyoutRefundForm = RefundForm;

})(jQuery);