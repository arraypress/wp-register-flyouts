/**
 * Price Config Component JavaScript
 *
 * Handles toggling between one-off and recurring pricing,
 * and billing period preset selection.
 *
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    const BILLING_PRESETS = {
        daily: {count: 1, interval: 'day'},
        weekly: {count: 1, interval: 'week'},
        monthly: {count: 1, interval: 'month'},
        quarterly: {count: 3, interval: 'month'},
        semiannual: {count: 6, interval: 'month'},
        yearly: {count: 1, interval: 'year'}
    };

    $(document).on('change', '.price-config-type-input', function () {
        const $component = $(this).closest('.wp-flyout-price-config');
        const $interval = $component.find('.price-config-interval');
        const isRecurring = $(this).val() === 'recurring';

        // Update active state on labels
        $component.find('.price-config-type-option').removeClass('is-active');
        $(this).closest('.price-config-type-option').addClass('is-active');

        if (isRecurring) {
            $interval.slideDown(150);
        } else {
            $interval.slideUp(150);

            // Clear interval values so sanitizer nulls them out
            $component.find('.price-config-interval-count').val('');
            $component.find('.price-config-interval-select').val('');
        }
    });

    $(document).on('change', '.price-config-preset-select', function () {
        const $component = $(this).closest('.wp-flyout-price-config');
        const $customRow = $component.find('.price-config-interval-row');
        const preset = $(this).val();

        if (preset === 'custom') {
            $customRow.slideDown(150);
        } else {
            $customRow.slideUp(150);

            const mapping = BILLING_PRESETS[preset];
            if (mapping) {
                $component.find('.price-config-interval-count').val(mapping.count);
                $component.find('.price-config-interval-select').val(mapping.interval);
            }
        }
    });

})(jQuery);