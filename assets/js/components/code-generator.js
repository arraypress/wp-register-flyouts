/**
 * Code Generator Enhancement for Text Fields
 *
 * Adds a "Generate" button to text inputs with data-generate attributes.
 * Produces random codes in various formats for discount codes, API keys, etc.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 * @author      David Sherlock
 */
(function ($) {
    'use strict';

    const CodeGenerator = {

        /**
         * Character sets for different formats
         */
        charsets: {
            alphanumeric_upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            alphanumeric:       'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
            alpha_upper:        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            hex:                '0123456789ABCDEF',
            numeric:            '0123456789',
        },

        /**
         * Initialize
         */
        init: function () {
            $(document).on('click', '.code-generate-btn', this.handleGenerate);
        },

        /**
         * Handle generate button click
         *
         * @param {Event} e Click event
         */
        handleGenerate: function (e) {
            e.preventDefault();

            const $btn = $(this);
            const $wrapper = $btn.closest('.code-generator-wrapper');
            const $input = $wrapper.find('input[type="text"]');

            const length = parseInt($btn.data('length')) || 8;
            const format = $btn.data('format') || 'alphanumeric_upper';
            const prefix = $btn.data('prefix') || '';
            const separator = $btn.data('separator') || '';
            const segmentLength = parseInt($btn.data('segment-length')) || 0;

            let code = CodeGenerator.generate(length, format);

            // Insert separators (e.g. XXXX-XXXX-XXXX)
            if (separator && segmentLength > 0) {
                code = code.match(new RegExp('.{1,' + segmentLength + '}', 'g')).join(separator);
            }

            // Add prefix
            if (prefix) {
                code = prefix + code;
            }

            $input.val(code).trigger('change').trigger('input');

            // Brief visual feedback
            $btn.addClass('is-generated');
            setTimeout(function () {
                $btn.removeClass('is-generated');
            }, 600);
        },

        /**
         * Generate random string
         *
         * @param {number} length  Desired length
         * @param {string} format  Character set key
         * @returns {string}
         */
        generate: function (length, format) {
            const charset = this.charsets[format] || this.charsets.alphanumeric_upper;
            let result = '';

            // Use crypto API if available for better randomness
            if (window.crypto && window.crypto.getRandomValues) {
                const array = new Uint32Array(length);
                window.crypto.getRandomValues(array);
                for (let i = 0; i < length; i++) {
                    result += charset[array[i] % charset.length];
                }
            } else {
                for (let i = 0; i < length; i++) {
                    result += charset[Math.floor(Math.random() * charset.length)];
                }
            }

            return result;
        }
    };

    // Initialize on document ready
    $(function () {
        CodeGenerator.init();
    });

    // Export for external use
    window.WPFlyoutCodeGenerator = CodeGenerator;

})(jQuery);