/**
 * Accordion Component JavaScript
 *
 * Handles expand/collapse interactions with smooth animations.
 * Simplified version using event delegation for better performance.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 * @author      David Sherlock
 */

(function ($) {
    'use strict';

    /**
     * Accordion Handler
     *
     * @namespace WPFlyoutAccordion
     * @since 1.0.0
     */
    const Accordion = {

        /**
         * Initialize accordion functionality
         *
         * Uses event delegation for better performance and dynamic content support.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            // Handle accordion clicks using delegation
            $(document).on('click.wpflyout.accordion', '.wp-flyout-accordion .accordion-header', this.handleClick);
        },

        /**
         * Handle accordion header click
         *
         * @since 1.0.0
         * @param {Event} e Click event
         * @return {void}
         */
        handleClick: function (e) {
            e.preventDefault();

            /** @type {jQuery} */
            const $header = $(this);
            /** @type {jQuery} */
            const $accordion = $header.closest('.wp-flyout-accordion');
            /** @type {jQuery} */
            const $section = $header.closest('.accordion-section');
            /** @type {jQuery} */
            const $content = $section.find('.accordion-content');
            /** @type {boolean} */
            const allowMultiple = $accordion.data('allow-multiple') === true;
            /** @type {boolean} */
            const isOpen = $section.hasClass('is-open');

            // Close others if not allowing multiple
            if (!allowMultiple && !isOpen) {
                Accordion.closeOthers($accordion, $section);
            }

            // Toggle current section
            if (isOpen) {
                Accordion.close($section, $content, $header);
            } else {
                Accordion.open($section, $content, $header);
            }

            // Trigger custom event
            $section.trigger('accordion:toggled', {isOpen: !isOpen});
        },

        /**
         * Open an accordion section
         *
         * @since 1.0.0
         * @param {jQuery} $section Section element
         * @param {jQuery} $content Content element
         * @param {jQuery} $header  Header button element
         * @return {void}
         */
        open: function ($section, $content, $header) {
            $section.addClass('is-open');
            $content.slideDown(300, function () {
                $section.trigger('accordion:opened');
            });
            $header.attr('aria-expanded', 'true');
        },

        /**
         * Close an accordion section
         *
         * @since 1.0.0
         * @param {jQuery} $section Section element
         * @param {jQuery} $content Content element
         * @param {jQuery} $header  Header button element
         * @return {void}
         */
        close: function ($section, $content, $header) {
            $section.removeClass('is-open');
            $content.slideUp(300, function () {
                $section.trigger('accordion:closed');
            });
            $header.attr('aria-expanded', 'false');
        },

        /**
         * Close all other sections in accordion
         *
         * @since 1.0.0
         * @param {jQuery} $accordion Accordion container
         * @param {jQuery} $current   Current section to keep open
         * @return {void}
         */
        closeOthers: function ($accordion, $current) {
            $accordion.find('.accordion-section.is-open').not($current).each(function () {
                /** @type {jQuery} */
                const $section = $(this);
                /** @type {jQuery} */
                const $content = $section.find('.accordion-content');
                /** @type {jQuery} */
                const $header = $section.find('.accordion-header');
                Accordion.close($section, $content, $header);
            });
        }
    };

    // Initialize when ready
    $(function () {
        Accordion.init();
    });

    // Export for external use
    window.WPFlyoutAccordion = Accordion;

})(jQuery);