/**
 * Discount Config Component JavaScript
 *
 * Handles toggling between percentage and fixed amount,
 * dynamic unit display, and duration visibility.
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    // Rate type toggle
    $(document).on('change', '.discount-config-type-input', function () {
        const $component = $(this).closest('.wp-flyout-discount-config');
        const rateType = $(this).val();

        // Update active state on labels
        $component.find('.discount-config-type-option').removeClass('is-active');
        $(this).closest('.discount-config-type-option').addClass('is-active');

        if (rateType === 'percent') {
            $component.find('.discount-config-unit-prefix').hide();
            $component.find('.discount-config-unit-suffix').show();
            $component.find('.discount-config-currency').hide();
        } else {
            $component.find('.discount-config-unit-prefix').show();
            $component.find('.discount-config-unit-suffix').hide();
            $component.find('.discount-config-currency').show();
        }

        // Clear amount on type switch
        $component.find('.discount-config-amount-input').val('').focus();
    });

    // Duration select
    $(document).on('change', '.discount-config-duration-select', function () {
        const $component = $(this).closest('.wp-flyout-discount-config');
        const $months = $component.find('.discount-config-months');

        if ($(this).val() === 'repeating') {
            $months.slideDown(150);
        } else {
            $months.slideUp(150);
            $months.find('input').val('');
        }
    });

    // Currency change — update the prefix symbol
    $(document).on('change', '.discount-config-currency select', function () {
        const $component = $(this).closest('.wp-flyout-discount-config');
        $component.find('.discount-config-unit-prefix').text($(this).val());
    });

})(jQuery);