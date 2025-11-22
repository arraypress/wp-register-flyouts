/**
 * ActionButtons Component JavaScript
 *
 * Handles AJAX action button clicks with automatic success/error handling.
 * Works with WP_Error returns and standard wp_send_json responses.
 *
 * @package ArrayPress\WPFlyout
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    const ActionButtons = {

        /**
         * Initialize the component
         */
        init: function () {
            const self = this;

            // Bind button clicks using delegation
            $(document)
                .on('click', '.wp-flyout-action-btn', function (e) {
                    e.preventDefault();
                    self.handleAction($(this));
                });
        },

        /**
         * Handle action button click
         *
         * @param {jQuery} $button Button element
         */
        handleAction: function ($button) {
            // Check for confirmation
            const confirmMsg = $button.data('confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            // Get action and nonce
            const action = $button.data('action');
            const nonce = $button.data('nonce');

            if (!action) {
                console.error('ActionButtons: No action specified');
                return;
            }

            // Gather all data attributes
            const buttonData = $button.data();
            const requestData = {
                action: 'wp_flyout_action_' + action,
                _wpnonce: nonce
            };

            // Add all other data attributes (excluding our control attributes)
            Object.keys(buttonData).forEach(key => {
                if (!['action', 'nonce', 'confirm'].includes(key)) {
                    requestData[key] = buttonData[key];
                }
            });

            // Set loading state
            this.setButtonState($button, true);

            // Make AJAX request
            $.post(ajaxurl, requestData)
                .done((response) => {
                    this.handleResponse(response, $button);
                })
                .fail(() => {
                    this.handleError('Connection failed. Please try again.', $button);
                })
                .always(() => {
                    this.setButtonState($button, false);
                });
        },

        /**
         * Handle AJAX response
         *
         * @param {Object} response Server response
         * @param {jQuery} $button Button element
         */
        handleResponse: function (response, $button) {
            const $flyout = $button.closest('.wp-flyout');
            const $body = $flyout.find('.wp-flyout-body');

            if (response.success) {
                const message = response.data?.message || 'Action completed successfully';
                this.showAlert($flyout, message, 'success');
                $body.animate({scrollTop: 0}, 300);

                // Only reload if explicitly requested
                if (response.data?.reload === true) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else if (response.data?.refresh_flyout === true) {
                    // Optional: refresh just this flyout's content
                    setTimeout(() => {
                        this.reloadFlyout($flyout);
                    }, 1500);
                }

                // Update UI elements if data provided
                if (response.data?.updates) {
                    this.updateUI(response.data.updates);
                }

                $button.trigger('actionbuttons:success', response.data);
            } else {
                const errorMsg = response.data || 'An error occurred';
                this.showAlert($flyout, errorMsg, 'error');
                $body.animate({scrollTop: 0}, 300);
                $button.trigger('actionbuttons:error', errorMsg);
            }
        },

        // New method to update specific UI elements
        updateUI: function (updates) {
            Object.keys(updates).forEach(selector => {
                $(selector).html(updates[selector]);
            });
        },

        /**
         * Handle connection error
         *
         * @param {string} message Error message
         * @param {jQuery} $button Button element
         */
        handleError: function (message, $button) {
            const $flyout = $button.closest('.wp-flyout');
            const $body = $flyout.find('.wp-flyout-body');

            this.showAlert($flyout, message, 'error');
            $body.animate({scrollTop: 0}, 300);

            $button.trigger('actionbuttons:error', message);
        },

        /**
         * Set button loading state
         *
         * @param {jQuery} $button Button element
         * @param {boolean} loading Whether button is loading
         */
        setButtonState: function ($button, loading) {
            if (loading) {
                $button.prop('disabled', true).addClass('loading');
                $button.find('.button-text, .dashicons:not(.spin)').hide(); // Hide icon too
                $button.find('.button-spinner').show();
            } else {
                $button.prop('disabled', false).removeClass('loading');
                $button.find('.button-text, .dashicons:not(.spin)').show(); // Show icon again
                $button.find('.button-spinner').hide();
            }
        },

        /**
         * Show alert message in flyout
         *
         * @param {jQuery} $flyout Flyout container
         * @param {string} message Alert message
         * @param {string} type Alert type (success/error)
         */
        showAlert: function ($flyout, message, type) {
            // Use WPFlyoutAlert if available
            if (window.WPFlyoutAlert) {
                WPFlyoutAlert.show(message, type, {
                    target: $flyout.find('.wp-flyout-body'),
                    prepend: true,
                    timeout: type === 'success' ? 3000 : 0,
                    dismissible: true
                });
            } else {
                // Fallback to console
                console[type === 'error' ? 'error' : 'log'](message);
            }
        },

        /**
         * Reload flyout content
         *
         * @param {jQuery} $flyout Flyout container
         */
        reloadFlyout: function ($flyout) {
            // Find the original trigger that opened this flyout
            const flyoutId = $flyout.attr('id');
            const $trigger = $('[data-flyout-instance="' + flyoutId + '"]');

            if ($trigger.length) {
                // Close current flyout
                WPFlyout.close(flyoutId);

                // Re-trigger after brief delay
                setTimeout(() => {
                    $trigger.click();
                }, 300);
            } else {
                // If no trigger found, just reload the page as fallback
                location.reload();
            }
        }
    };

    // Initialize on document ready
    $(function () {
        ActionButtons.init();
    });

    // Export for external use
    window.WPFlyoutActionButtons = ActionButtons;

})(jQuery);