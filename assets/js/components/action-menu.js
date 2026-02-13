/**
 * ActionMenu Component JavaScript
 *
 * Handles dropdown menu interactions and REST API actions.
 *
 * @package ArrayPress\WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    const ActionMenu = {

        init: function () {
            $(document).on('click', '.action-menu-trigger', this.toggleMenu.bind(this));
            $(document).on('click', '.action-menu-item', this.handleAction.bind(this));
            $(document).on('click', this.handleOutsideClick.bind(this));
            $(document).on('keydown', this.handleEscape.bind(this));
        },

        toggleMenu: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $trigger = $(e.currentTarget);
            var $menu = $trigger.siblings('.action-menu-dropdown');
            var isOpen = $trigger.attr('aria-expanded') === 'true';

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

            var $item = $(e.currentTarget);

            if ($item.hasClass('is-disabled')) {
                return;
            }

            var confirmMsg = $item.data('confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            var action = $item.data('action');
            if (!action) {
                this.closeAllMenus();
                return;
            }

            // Get flyout context
            var $flyout = $item.closest('.wp-flyout');
            var config = $flyout.data() || {};
            var itemId = $flyout.find('input[name="id"]').val() || config.data?.id || 0;

            this.setItemState($item, true);

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
                    self.handleResponse(response, $item);
                })
                .catch(function (error) {
                    self.handleError(error.message || 'Connection failed. Please try again.', $item);
                })
                .finally(function () {
                    self.setItemState($item, false);
                    self.closeAllMenus();
                });
        },

        handleResponse: function (response, $item) {
            var $flyout = $item.closest('.wp-flyout');
            var $body = $flyout.find('.wp-flyout-body');

            if (response.success) {
                var message = response.message || 'Action completed successfully';
                this.showAlert($flyout, message, 'success');
                $body.animate({ scrollTop: 0 }, 300);

                if (response.reload === true) {
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                }

                if (response.updates) {
                    this.updateUI(response.updates);
                }

                $item.trigger('actionmenu:success', response);
            } else {
                var errorMsg = response.message || 'An error occurred';
                this.showAlert($flyout, errorMsg, 'error');
                $body.animate({ scrollTop: 0 }, 300);
                $item.trigger('actionmenu:error', errorMsg);
            }
        },

        handleError: function (message, $item) {
            var $flyout = $item.closest('.wp-flyout');
            var $body = $flyout.find('.wp-flyout-body');

            this.showAlert($flyout, message, 'error');
            $body.animate({ scrollTop: 0 }, 300);

            $item.trigger('actionmenu:error', message);
        },

        setItemState: function ($item, loading) {
            if (loading) {
                $item.addClass('loading');
                var $icon = $item.find('.dashicons').first();
                if ($icon.length) {
                    $icon.data('original-class', $icon.attr('class'));
                    $icon.attr('class', 'dashicons dashicons-update');
                }
            } else {
                $item.removeClass('loading');
                var $icon = $item.find('.dashicons').first();
                if ($icon.length && $icon.data('original-class')) {
                    $icon.attr('class', $icon.data('original-class'));
                }
            }
        },

        updateUI: function (updates) {
            Object.keys(updates).forEach(function (selector) {
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

    $(function () {
        ActionMenu.init();
    });

    window.WPFlyoutActionMenu = ActionMenu;

})(jQuery);