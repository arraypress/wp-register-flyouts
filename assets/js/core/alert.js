/**
 * WP Flyout Alert Component JavaScript
 *
 * Handles dismissible alert functionality
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Alert Component Handler
     */
    const Alert = {
        /**
         * Initialize all alerts
         */
        init: function () {
            // Bind dismiss action
            $(document).on('click', '.wp-flyout-alert [data-action="dismiss-alert"]', function (e) {
                e.preventDefault();
                Alert.dismiss($(this).closest('.wp-flyout-alert'));
            });
        },

        /**
         * Dismiss an alert
         *
         * @param {jQuery} $alert Alert element
         */
        dismiss: function ($alert) {
            // Trigger before dismiss event (cancellable)
            const beforeDismissEvent = $.Event('alert:beforedismiss');
            $alert.trigger(beforeDismissEvent);

            if (beforeDismissEvent.isDefaultPrevented()) {
                return;
            }

            // Add dismissing class for animation
            $alert.addClass('is-dismissing');

            // Remove after animation
            setTimeout(function () {
                $alert.slideUp(200, function () {
                    // Trigger dismissed event
                    $alert.trigger('alert:dismissed');
                    $alert.remove();
                });
            }, 300);
        },

        /**
         * Show an alert programmatically
         *
         * @param {string} message Alert message
         * @param {string} type    Alert type (success, info, warning, error)
         * @param {Object} options Additional options
         * @return {jQuery} Alert element
         */
        show: function (message, type, options) {
            type = type || 'info';
            options = options || {};

            const icons = {
                success: 'yes-alt',
                info: 'info',
                warning: 'warning',
                error: 'dismiss'
            };

            const icon = options.icon || icons[type] || icons.info;
            const dismissible = options.dismissible !== false;

            const $alert = $(`
                <div class="wp-flyout-alert alert-${type} ${dismissible ? 'is-dismissible' : ''}" role="alert">
                    <div class="alert-content-wrapper">
                        <div class="alert-icon">
                            <span class="dashicons dashicons-${icon}"></span>
                        </div>
                        <div class="alert-content">
                            <div class="alert-message">${message}</div>
                        </div>
                        ${dismissible ? `
                            <button type="button" class="alert-dismiss" data-action="dismiss-alert">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `);

            // Append to target or default location
            const $target = options.target ? $(options.target) : $('.wp-flyout-body');

            if (options.prepend) {
                $target.prepend($alert);
            } else {
                $target.append($alert);
            }

            // Trigger shown event
            $alert.trigger('alert:shown');

            // Auto-dismiss if timeout specified
            if (options.timeout) {
                setTimeout(function () {
                    Alert.dismiss($alert);
                }, options.timeout);
            }

            return $alert;
        }
    };

    // Initialize when ready
    $(function () {
        Alert.init();
    });

    // Export
    window.WPFlyoutAlert = Alert;

})(jQuery);