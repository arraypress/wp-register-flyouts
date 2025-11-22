/**
 * WP Flyout Color Input Enhancement
 *
 * Syncs color picker with text preview
 * Add to assets/js/flyout/form-enhancements.js or similar
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Color Input Handler
     */
    const ColorInput = {
        /**
         * Initialize color inputs
         */
        init: function () {
            // Sync color input changes to preview
            $(document).on('input change', '.wp-flyout-color-input', function () {
                const $input = $(this);
                const $preview = $input.siblings('.wp-flyout-color-preview');

                if ($preview.length) {
                    $preview.val($input.val().toUpperCase());
                }
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                $(data.element).find('.wp-flyout-color-input').each(function () {
                    const $input = $(this);
                    const $preview = $input.siblings('.wp-flyout-color-preview');

                    if ($preview.length) {
                        $preview.val($input.val().toUpperCase());
                    }
                });
            });
        }
    };

    // Initialize when ready
    $(function () {
        ColorInput.init();
    });

})(jQuery);