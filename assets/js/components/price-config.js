/**
 * Price Config Component JavaScript
 *
 * Handles toggling between one-time and recurring pricing.
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    $(document).on('change', '.price-config-type-input', function () {
        const $component = $(this).closest('.wp-flyout-price-config');
        const $interval = $component.find('.price-config-interval');
        const isRecurring = $(this).val() === 'recurring';

        // Update active state on labels
        $component.find('.price-config-type-option').removeClass('is-active');
        $(this).closest('.price-config-type-option').addClass('is-active');

        // Toggle interval section
        if (isRecurring) {
            $interval.slideDown(150);
        } else {
            $interval.slideUp(150);
        }
    });

})(jQuery);