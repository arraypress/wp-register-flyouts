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
    // This build's REST config; see the resolver in wp-flyout.js.
    var wpFlyout = window.ArrayPressFlyouts.resolve(document.currentScript);


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
         * How many minor units make one major unit of this form's currency.
         *
         * Emitted by PHP from the currency's own ISO-4217 exponent, so this
         * is 100 for dollars, 1 for yen and 1000 for Kuwaiti dinar.
         */
        factor: function ($form) {
            return Math.pow(10, parseInt($form.data('decimals'), 10) || 0);
        },

        /**
         * Format a major-unit amount in the page's locale.
         */
        money: function ($form, amount) {
            var currency = $form.data('currency') || 'USD';
            var locale = $form.data('locale') || undefined;

            try {
                return new Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            } catch (e) {
                // An unrecognised currency code throws rather than degrading.
                return currency + ' ' + amount.toFixed(parseInt($form.data('decimals'), 10) || 0);
            }
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

            $submit.find('.button-text').text(template.replace('%s', RefundForm.money($form, amount)));
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

            var currency = $form.data('currency') || 'USD';
            var amountInput = parseFloat($form.find('.refund-amount-input').val()) || 0;

            // The input is in major units and the endpoint takes minor ones.
            // The scale comes from the currency rather than being a hard 100:
            // yen has no minor unit, so multiplying by a hundred there asked
            // for a refund a hundred times the size of the payment.
            var amountMinor = Math.round(amountInput * RefundForm.factor($form));
            var refundableMinor = parseInt($form.data('refundable'), 10) || 0;

            if (amountMinor <= 0) {
                this.showAlert($flyout, $form.data('error-empty'), 'error');
                return;
            }

            if (amountMinor > refundableMinor) {
                this.showAlert($flyout, $form.data('error-exceeds'), 'error');
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
                    amount: amountMinor,
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