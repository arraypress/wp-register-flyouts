/**
 * WP Flyout Core JavaScript - Simplified
 *
 * Handles flyout mechanics for Manager-based flyouts only
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    window.WPFlyout = {
        // Configuration
        config: {
            animationDuration: 300,
            animationDelay: 10,
            focusDelay: 350
        },

        // Track active flyouts
        active: [],

        /**
         * Open a flyout by ID (flyout must already exist in DOM)
         */
        open: function(id) {
            const $flyout = $('#' + id);

            if (!$flyout.length) {
                console.warn('WP Flyout: Element not found with ID:', id);
                return false;
            }

            // Show overlay
            this.showOverlay();

            // Add body class
            $('body').addClass('wp-flyout-open');

            // Activate with animation delay
            setTimeout(() => {
                $flyout.addClass('active');
            }, this.config.animationDelay);

            // Track as active
            if (!this.active.includes(id)) {
                this.active.push(id);
            }

            // Initialize tabs if present
            this.initTabs($flyout);

            // Focus management after animation
            setTimeout(() => {
                // Focus first visible input
                $flyout.find('input:visible:enabled, select:visible:enabled, textarea:visible:enabled')
                    .first()
                    .focus();

                // Trigger events
                $(document).trigger('wpflyout:opened', { id: id, element: $flyout[0] });
                $flyout.trigger('flyout:ready');
            }, this.config.focusDelay);

            return $flyout;
        },

        /**
         * Close a flyout
         */
        close: function(id) {
            const $flyout = $('#' + id);

            if (!$flyout.length) return false;

            // Trigger closing event (cancelable)
            const event = $.Event('wpflyout:closing');
            $(document).trigger(event, { id: id, element: $flyout[0] });

            if (event.isDefaultPrevented()) {
                return false;
            }

            // Start close animation
            $flyout.removeClass('active');

            // Remove from active list
            this.active = this.active.filter(activeId => activeId !== id);

            // Clean up after animation
            setTimeout(() => {
                // Remove dynamically created flyouts
                if ($flyout.hasClass('wp-flyout-dynamic')) {
                    $flyout.remove();
                }

                // Remove overlay if no more flyouts
                if (this.active.length === 0) {
                    this.hideOverlay();
                }

                // Trigger closed event
                $(document).trigger('wpflyout:closed', { id: id });
            }, this.config.animationDuration);

            return true;
        },

        /**
         * Close all flyouts
         */
        closeAll: function() {
            [...this.active].forEach(id => this.close(id));
        },

        /**
         * Get the last opened flyout ID
         */
        getLastId: function() {
            return this.active[this.active.length - 1] || null;
        },

        /**
         * Show overlay
         */
        showOverlay: function() {
            let $overlay = $('.wp-flyout-overlay');

            if (!$overlay.length) {
                $overlay = $('<div class="wp-flyout-overlay"></div>').appendTo('body');
            }

            // Delay for animation
            setTimeout(() => $overlay.addClass('active'), 10);
        },

        /**
         * Hide overlay
         */
        hideOverlay: function() {
            const $overlay = $('.wp-flyout-overlay');
            $overlay.removeClass('active');
            $('body').removeClass('wp-flyout-open');

            setTimeout(() => $overlay.remove(), this.config.animationDuration);
        },

        /**
         * Initialize tab switching
         */
        initTabs: function($flyout) {
            $flyout.off('click.tabs').on('click.tabs', '.wp-flyout-tab', function(e) {
                e.preventDefault();

                const $tab = $(this);
                if ($tab.hasClass('disabled')) return;

                const tabId = $tab.data('tab');

                // Update active states
                $flyout.find('.wp-flyout-tab').removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');

                // Switch content
                $flyout.find('.wp-flyout-tab-content').removeClass('active');
                $flyout.find('#tab-' + tabId).addClass('active');

                // Trigger event
                $(document).trigger('wpflyout:tab-changed', {
                    flyoutId: $flyout.attr('id'),
                    tabId: tabId
                });
            });
        },

        /**
         * Initialize global event handlers
         */
        init: function() {
            // Close button
            $(document).on('click.wpflyout', '.wp-flyout-close', (e) => {
                const flyoutId = $(e.currentTarget).closest('.wp-flyout').attr('id');
                this.close(flyoutId);
            });

            // Overlay click to close
            $(document).on('click.wpflyout', '.wp-flyout-overlay', () => {
                const lastId = this.getLastId();
                if (lastId) this.close(lastId);
            });

            // Escape key to close
            $(document).on('keydown.wpflyout', (e) => {
                if (e.key === 'Escape' && this.active.length) {
                    this.close(this.getLastId());
                }
            });
        }
    };

    // Initialize on ready
    $(document).ready(() => WPFlyout.init());

})(jQuery);