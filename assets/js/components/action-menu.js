/**
 * ActionMenu Component JavaScript
 *
 * Handles dropdown menu interactions and AJAX actions
 */
(function ($) {
    'use strict';

    const ActionMenu = {

        init: function () {
            // Toggle menu
            $(document).on('click', '.action-menu-trigger', this.toggleMenu.bind(this));

            // Handle menu item clicks
            $(document).on('click', '.action-menu-item', this.handleAction.bind(this));

            // Close on outside click
            $(document).on('click', this.handleOutsideClick.bind(this));

            // Close on escape
            $(document).on('keydown', this.handleEscape.bind(this));
        },

        toggleMenu: function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $trigger = $(e.currentTarget);
            const $menu = $trigger.siblings('.action-menu-dropdown');
            const isOpen = $trigger.attr('aria-expanded') === 'true';

            // Close all other menus
            $('.action-menu-trigger[aria-expanded="true"]').not($trigger).each(function () {
                $(this).attr('aria-expanded', 'false');
                $(this).siblings('.action-menu-dropdown').slideUp(150);
            });

            if (isOpen) {
                $trigger.attr('aria-expanded', 'false');
                $menu.slideUp(150);
            } else {
                $trigger.attr('aria-expanded', 'true');
                $menu.slideDown(150);
            }
        },

        handleAction: function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $item = $(e.currentTarget);

            // Skip disabled items
            if ($item.hasClass('is-disabled')) {
                return;
            }

            // Check for confirmation
            const confirmMsg = $item.data('confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            // Get action and nonce
            const action = $item.data('action');
            const nonce = $item.data('nonce');

            if (!action) {
                // Close menu if no action (might be a regular link)
                this.closeAllMenus();
                return;
            }

            // Gather all data attributes
            const itemData = $item.data();
            const requestData = {
                action: 'wp_flyout_action_' + action,
                _wpnonce: nonce
            };

            // Add all other data attributes
            Object.keys(itemData).forEach(key => {
                if (!['action', 'nonce', 'confirm'].includes(key)) {
                    requestData[key] = itemData[key];
                }
            });

            // Set loading state
            this.setItemState($item, true);

            // Make AJAX request
            $.post(ajaxurl, requestData)
                .done((response) => {
                    this.handleResponse(response, $item);
                })
                .fail(() => {
                    this.handleError('Connection failed. Please try again.', $item);
                })
                .always(() => {
                    this.setItemState($item, false);
                    this.closeAllMenus();
                });
        },

        handleResponse: function (response, $item) {
            const $flyout = $item.closest('.wp-flyout');
            const $body = $flyout.find('.wp-flyout-body');

            if (response.success) {
                const message = response.data?.message || 'Action completed successfully';
                this.showAlert($flyout, message, 'success');
                $body.animate({scrollTop: 0}, 300);

                // Handle reload/refresh as needed
                if (response.data?.reload === true) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }

                // Update UI elements if data provided
                if (response.data?.updates) {
                    this.updateUI(response.data.updates);
                }

                $item.trigger('actionmenu:success', response.data);
            } else {
                const errorMsg = response.data || 'An error occurred';
                this.showAlert($flyout, errorMsg, 'error');
                $body.animate({scrollTop: 0}, 300);
                $item.trigger('actionmenu:error', errorMsg);
            }
        },

        handleError: function (message, $item) {
            const $flyout = $item.closest('.wp-flyout');
            const $body = $flyout.find('.wp-flyout-body');

            this.showAlert($flyout, message, 'error');
            $body.animate({scrollTop: 0}, 300);

            $item.trigger('actionmenu:error', message);
        },

        setItemState: function ($item, loading) {
            if (loading) {
                $item.addClass('loading');
                const $icon = $item.find('.dashicons').first();
                if ($icon.length) {
                    $icon.data('original-class', $icon.attr('class'));
                    $icon.attr('class', 'dashicons dashicons-update');
                }
            } else {
                $item.removeClass('loading');
                const $icon = $item.find('.dashicons').first();
                if ($icon.length && $icon.data('original-class')) {
                    $icon.attr('class', $icon.data('original-class'));
                }
            }
        },

        updateUI: function (updates) {
            Object.keys(updates).forEach(selector => {
                $(selector).html(updates[selector]);
            });
        },

        showAlert: function ($flyout, message, type) {
            if (window.WPFlyoutAlert) {
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

        handleOutsideClick: function (e) {
            if (!$(e.target).closest('.wp-flyout-action-menu').length) {
                this.closeAllMenus();
            }
        },

        handleEscape: function (e) {
            if (e.key === 'Escape') {
                this.closeAllMenus();
            }
        },

        closeAllMenus: function () {
            $('.action-menu-trigger[aria-expanded="true"]').each(function () {
                $(this).attr('aria-expanded', 'false');
                $(this).siblings('.action-menu-dropdown').slideUp(150);
            });
        }
    };

    // Initialize on document ready
    $(function () {
        ActionMenu.init();
    });

    // Export for external use
    window.WPFlyoutActionMenu = ActionMenu;

})(jQuery);