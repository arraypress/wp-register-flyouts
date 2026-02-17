/**
 * Discount Config Component JavaScript
 *
 * Handles rate type radio changes, dynamic unit display, duration visibility,
 * and currency symbol updates for the discount configuration component.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.1.0
 * @author      David Sherlock
 */
(function ($) {
    'use strict';

    const DiscountConfig = {

        /**
         * Initialize the component
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind event handlers using delegation
         */
        bindEvents: function () {
            // Rate type radio change
            $(document).on('change', '.discount-config-type-input', this.handleTypeChange);

            // Duration select
            $(document).on('change', '.discount-config-duration-select', this.handleDurationChange);

            // Currency change — update the prefix symbol
            $(document).on('change', '.discount-config-currency select', this.handleCurrencyChange);

            // Re-initialize on flyout open
            $(document).on('wpflyout:opened', function () {
                $('.wp-flyout-discount-config').each(function () {
                    DiscountConfig.syncState($(this));
                });
            });
        },

        /**
         * Handle rate type radio change
         *
         * @param {Event} e Change event
         */
        handleTypeChange: function (e) {
            const $input = $(this);
            const $config = $input.closest('.wp-flyout-discount-config');
            const rateType = $input.val();

            // Toggle unit display
            if (rateType === 'percent') {
                $config.find('.discount-config-unit-prefix').hide();
                $config.find('.discount-config-unit-suffix').show();
                $config.find('.discount-config-currency').hide();
            } else {
                $config.find('.discount-config-unit-prefix').show();
                $config.find('.discount-config-unit-suffix').hide();
                $config.find('.discount-config-currency').show();
            }

            // Clear and refocus amount
            $config.find('.discount-config-amount-input').val('').focus();

            // Trigger custom event
            $config.trigger('discountconfig:typechanged', {rateType: rateType});
        },

        /**
         * Handle duration select change
         *
         * @param {Event} e Change event
         */
        handleDurationChange: function (e) {
            const $select = $(this);
            const $config = $select.closest('.wp-flyout-discount-config');
            const duration = $select.val();

            const $months = $config.find('.discount-config-months');

            if (duration === 'repeating') {
                $months.slideDown(200, function () {
                    $months.find('input').focus();
                });
            } else {
                $months.slideUp(200);
                $months.find('input').val('');
            }

            $config.trigger('discountconfig:durationchanged', {duration: duration});
        },

        /**
         * Handle currency change — update the prefix symbol
         *
         * @param {Event} e Change event
         */
        handleCurrencyChange: function (e) {
            const $select = $(this);
            const $config = $select.closest('.wp-flyout-discount-config');
            const currency = $select.val();

            $config.find('.discount-config-unit-prefix').text(currency);
        },

        /**
         * Sync UI state (used after flyout loads)
         *
         * @param {jQuery} $config Config container
         */
        syncState: function ($config) {
            const rateType = $config.find('.discount-config-type-input:checked').val();
            const duration = $config.find('.discount-config-duration-select').val();

            // Sync type display
            if (rateType === 'percent') {
                $config.find('.discount-config-unit-prefix').hide();
                $config.find('.discount-config-unit-suffix').show();
                $config.find('.discount-config-currency').hide();
            } else {
                $config.find('.discount-config-unit-prefix').show();
                $config.find('.discount-config-unit-suffix').hide();
                $config.find('.discount-config-currency').show();
            }

            // Sync duration display
            if (duration === 'repeating') {
                $config.find('.discount-config-months').show();
            } else {
                $config.find('.discount-config-months').hide();
            }
        }
    };

    // Initialize on document ready
    $(function () {
        DiscountConfig.init();
    });

    // Export for external use
    window.WPFlyoutDiscountConfig = DiscountConfig;

})(jQuery);