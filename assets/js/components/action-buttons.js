/**
 * ActionButtons Component JavaScript
 *
 * Handles action button clicks via REST API with automatic success/error handling.
 *
 * @package ArrayPress\WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    const ActionButtons = {

        /**
         * Initialize the component
         */
        init: function () {
            var self = this;

            $(document).on('click', '.wp-flyout-action-btn', function (e) {
                e.preventDefault();
                self.handleAction($(this));
            });
        },

        /**
         * Handle action button click
         */
        handleAction: function ($button) {
            // Check for confirmation
            var confirmMsg = $button.data('confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            var action = $button.data('action');
            if (!action) {
                console.error('ActionButtons: No action specified');
                return;
            }

            // Get flyout context
            var $flyout = $button.closest('.wp-flyout');
            var config = $flyout.data() || {};
            var itemId = $flyout.find('input[name="id"]').val() || config.data?.id || 0;

            // Set loading state
            this.setButtonState($button, true);

            // Make REST API request
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
                    action_key: action,
                    item_id: itemId
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
                    self.handleResponse(response, $button);
                })
                .catch(function (error) {
                    self.handleError(error.message || 'Connection failed. Please try again.', $button);
                })
                .finally(function () {
                    self.setButtonState($button, false);
                });
        },

        /**
         * Handle response
         */
        handleResponse: function (response, $button) {
            var $flyout = $button.closest('.wp-flyout');
            var $body = $flyout.find('.wp-flyout-body');

            if (response.success) {
                var message = response.message || 'Action completed successfully';
                this.showAlert($flyout, message, 'success');
                $body.animate({ scrollTop: 0 }, 300);

                if (response.reload === true) {
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else if (response.refresh_flyout === true) {
                    setTimeout(function () {
                        ActionButtons.reloadFlyout($flyout);
                    }, 1500);
                }

                if (response.updates) {
                    this.updateUI(response.updates);
                }

                $button.trigger('actionbuttons:success', response);
            } else {
                var errorMsg = response.message || 'An error occurred';
                this.showAlert($flyout, errorMsg, 'error');
                $body.animate({ scrollTop: 0 }, 300);
                $button.trigger('actionbuttons:error', errorMsg);
            }
        },

        /**
         * Update specific UI elements
         */
        updateUI: function (updates) {
            Object.keys(updates).forEach(function (selector) {
                $(selector).html(updates[selector]);
            });
        },

        /**
         * Handle connection error
         */
        handleError: function (message, $button) {
            var $flyout = $button.closest('.wp-flyout');
            var $body = $flyout.find('.wp-flyout-body');

            this.showAlert($flyout, message, 'error');
            $body.animate({ scrollTop: 0 }, 300);

            $button.trigger('actionbuttons:error', message);
        },

        /**
         * Set button loading state
         */
        setButtonState: function ($button, loading) {
            if (loading) {
                $button.prop('disabled', true).addClass('loading');
                $button.find('.button-text, .dashicons:not(.spin)').hide();
                $button.find('.button-spinner').show();
            } else {
                $button.prop('disabled', false).removeClass('loading');
                $button.find('.button-text, .dashicons:not(.spin)').show();
                $button.find('.button-spinner').hide();
            }
        },

        /**
         * Show alert message in flyout
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
        ActionButtons.init();
    });

    window.WPFlyoutActionButtons = ActionButtons;

})(jQuery);