/**
 * WP Flyout Manager - Simplified version
 *
 * Handles AJAX flyout loading, saving, and deletion.
 * Always reloads page after successful save/delete operations.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */
(function ($) {
    'use strict';

    const WPFlyoutManager = {

        /**
         * Initialize manager
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            $(document).on('click', '.wp-flyout-trigger', this.handleTrigger.bind(this));
        },

        /**
         * Handle trigger click
         *
         * @since 1.0.0
         * @param {jQuery.Event} e Click event
         * @return {void}
         */
        handleTrigger: function (e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const config = this.extractConfig($btn);

            this.loadFlyout(config);
        },

        /**
         * Extract configuration from trigger button
         *
         * @since 1.0.0
         * @param {jQuery} $btn Trigger button element
         * @return {Object} Configuration object
         */
        extractConfig: function ($btn) {
            const config = {
                flyout: $btn.data('flyout'),
                manager: $btn.data('flyout-manager'),
                nonce: $btn.data('flyout-nonce'),
                data: {}
            };

            // Collect additional data attributes
            $.each($btn[0].dataset, (key, value) => {
                if (!['flyout', 'flyoutManager', 'flyoutNonce'].includes(key)) {
                    config.data[key] = value;
                }
            });

            return config;
        },

        /**
         * Load flyout via AJAX
         *
         * @since 1.0.0
         * @param {Object} config Flyout configuration
         * @return {void}
         */
        loadFlyout: function (config) {
            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'load',
                nonce: config.nonce,
                ...config.data
            })
                .done(response => {
                    if (response.success) {
                        this.displayFlyout(response.data.html, config);
                    } else {
                        alert(response.data || 'Failed to load flyout');
                    }
                })
                .fail(() => alert('Connection failed'));
        },

        /**
         * Display flyout and setup handlers
         *
         * @since 1.0.0
         * @param {string} html   Flyout HTML content
         * @param {Object} config Flyout configuration
         * @return {void}
         */
        displayFlyout: function (html, config) {
            // Remove existing flyouts and add new one
            $('.wp-flyout').remove();
            $('body').append(html);

            const $flyout = $('.wp-flyout').last();
            const flyoutId = $flyout.attr('id');

            // Open it
            WPFlyout.open(flyoutId);

            // Store config
            $flyout.data(config);

            // Ensure form wrapper exists
            this.ensureForm($flyout);

            // Bind handlers
            this.bindHandlers($flyout, flyoutId, config);
        },

        /**
         * Ensure form wrapper exists
         *
         * @since 1.0.0
         * @param {jQuery} $flyout Flyout element
         * @return {void}
         */
        ensureForm: function ($flyout) {
            if (!$flyout.find('form').length) {
                const $body = $flyout.find('.wp-flyout-body');
                const $form = $('<form class="wp-flyout-form" novalidate></form>');
                $form.append($body.children());
                $body.append($form);
            }
        },

        /**
         * Bind event handlers
         *
         * @since 1.0.0
         * @param {jQuery} $flyout  Flyout element
         * @param {string} flyoutId Flyout ID
         * @param {Object} config   Flyout configuration
         * @return {void}
         */
        bindHandlers: function ($flyout, flyoutId, config) {
            // Save button
            $flyout.on('click', '.wp-flyout-save', e => {
                e.preventDefault();
                this.handleSave($flyout, flyoutId, config);
            });

            // Delete button
            $flyout.on('click', '.wp-flyout-delete', e => {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this item?')) {
                    this.handleDelete($flyout, flyoutId, config);
                }
            });

            // Close button (redundant since flyout.js handles this, but kept for safety)
            $flyout.on('click', '.wp-flyout-close', e => {
                e.preventDefault();
                WPFlyout.close(flyoutId);
            });

            // Clear error class on change
            $flyout.on('input change', '.error', function () {
                $(this).removeClass('error');
            });
        },

        /**
         * Validate form
         *
         * @since 1.0.0
         * @param {jQuery} $form Form element
         * @return {Object} Validation result with isValid flag and first invalid field
         */
        validateForm: function ($form) {
            let isValid = true;
            let firstInvalid = null;

            $form.find('[required]:visible:enabled').each(function () {
                const $field = $(this);
                const value = $field.val();

                if (!value || (Array.isArray(value) && !value.length)) {
                    isValid = false;
                    $field.addClass('error');
                    firstInvalid = firstInvalid || $field;
                } else {
                    $field.removeClass('error');
                }
            });

            return {isValid, firstInvalid};
        },

        /**
         * Handle save action
         *
         * Validates form, sends save request, and always reloads page on success.
         *
         * @since 1.0.0
         * @since 1.0.0 Removed conditional reload - always reloads on success
         * @param {jQuery} $flyout  Flyout element
         * @param {string} flyoutId Flyout ID
         * @param {Object} config   Flyout configuration
         * @return {void}
         */
        handleSave: function ($flyout, flyoutId, config) {
            const $form = $flyout.find('form').first();
            const $saveBtn = $flyout.find('.wp-flyout-save');
            const $body = $flyout.find('.wp-flyout-body');

            // Validate
            const validation = this.validateForm($form);
            if (!validation.isValid) {
                $body.animate({scrollTop: 0}, 300);
                this.showAlert($flyout, 'Please fill in all required fields.', 'error');
                if (validation.firstInvalid) {
                    validation.firstInvalid.focus();
                }
                return;
            }

            // Save
            this.setButtonState($saveBtn, true, 'Saving...');

            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'save',
                nonce: config.nonce,
                form_data: $form.serialize(),
                ...config.data
            })
                .done(response => {
                    this.setButtonState($saveBtn, false);

                    if (response.success) {
                        $body.animate({scrollTop: 0}, 300);
                        const message = response.data?.message || 'Saved successfully!';
                        this.showAlert($flyout, message, 'success');

                        // Always close and reload after delay
                        setTimeout(() => {
                            WPFlyout.close(flyoutId);
                            location.reload();
                        }, 1500);
                    } else {
                        $body.animate({scrollTop: 0}, 300);
                        this.showAlert($flyout, response.data || 'An error occurred', 'error');
                    }
                })
                .fail(() => {
                    this.setButtonState($saveBtn, false);
                    $body.animate({scrollTop: 0}, 300);
                    this.showAlert($flyout, 'Connection error', 'error');
                });
        },

        /**
         * Handle delete action
         *
         * Sends delete request and always reloads page on success.
         *
         * @since 1.0.0
         * @since 1.0.0 Removed conditional reload - always reloads on success
         * @param {jQuery} $flyout  Flyout element
         * @param {string} flyoutId Flyout ID
         * @param {Object} config   Flyout configuration
         * @return {void}
         */
        handleDelete: function ($flyout, flyoutId, config) {
            const $deleteBtn = $flyout.find('.wp-flyout-delete');
            const deleteId = $flyout.find('input[name="id"]').val() || config.data.id;
            const $body = $flyout.find('.wp-flyout-body');

            this.setButtonState($deleteBtn, true, 'Deleting...');

            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'delete',
                nonce: config.nonce,
                id: deleteId,
                ...config.data
            })
                .done(response => {
                    if (response.success) {
                        const message = response.data?.message || 'Deleted successfully!';
                        this.showAlert($flyout, message, 'success');
                        $body.animate({scrollTop: 0}, 300);

                        // Always close and reload after delay
                        setTimeout(() => {
                            WPFlyout.close(flyoutId);
                            location.reload();
                        }, 1000);
                    } else {
                        this.setButtonState($deleteBtn, false);
                        this.showAlert($flyout, response.data || 'Failed to delete', 'error');
                        $body.animate({scrollTop: 0}, 300);
                    }
                })
                .fail(() => {
                    this.setButtonState($deleteBtn, false);
                    this.showAlert($flyout, 'Connection error', 'error');
                    $body.animate({scrollTop: 0}, 300);
                });
        },

        /**
         * Show alert message
         *
         * @since 1.0.0
         * @param {jQuery} $flyout Flyout element
         * @param {string} message Alert message text
         * @param {string} type    Alert type (success, error, warning, info)
         * @return {void}
         */
        showAlert: function ($flyout, message, type) {
            if (window.WPFlyoutAlert) {
                WPFlyoutAlert.show(message, type, {
                    target: $flyout.find('.wp-flyout-body'),
                    prepend: true,
                    timeout: type === 'success' ? 3000 : 0,
                    dismissible: true
                });
            }
        },

        /**
         * Set button loading state
         *
         * @since 1.0.0
         * @param {jQuery} $btn     Button element
         * @param {boolean} disabled Whether button should be disabled
         * @param {string} text     Optional text to show when disabled
         * @return {void}
         */
        setButtonState: function ($btn, disabled, text) {
            if (!$btn.length) return;

            if (disabled) {
                $btn.data('original-text', $btn.text());
                $btn.prop('disabled', true).text(text);
            } else {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Save');
            }
        }
    };

    // Initialize
    $(document).ready(() => WPFlyoutManager.init());

})(jQuery);