/**
 * Tag Input Component - Simplified
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Tag Input Handler
     *
     * @namespace WPFlyoutTagInput
     * @since 1.0.0
     */
    const TagInput = {
        /**
         * Initialize tag inputs
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            // Use event delegation for all interactions
            $(document)
                .on('click', '.tag-input-container', this.handleContainerClick)
                .on('keydown', '.tag-input-field', this.handleKeydown)
                .on('paste', '.tag-input-field', this.handlePaste)
                .on('click', '.tag-remove', this.handleRemove);
        },

        /**
         * Handle container click to focus input
         *
         * @since 1.0.0
         * @param {Event} e Click event
         * @return {void}
         */
        handleContainerClick: function (e) {
            if (e.target === this) {
                $(this).find('.tag-input-field').focus();
            }
        },

        /**
         * Handle keyboard input
         *
         * @since 1.0.0
         * @param {Event} e Keydown event
         * @return {void}
         */
        handleKeydown: function (e) {
            const $input = $(this);
            const $container = $input.closest('.wp-flyout-tag-input');
            const value = $input.val().trim();

            switch (e.key) {
                case 'Enter':
                case ',':
                    if (value) {
                        e.preventDefault();
                        TagInput.addTag($container, value);
                        $input.val('');
                    }
                    break;

                case 'Backspace':
                    if (!value) {
                        e.preventDefault();
                        TagInput.removeLastTag($container);
                    }
                    break;

                case 'Escape':
                    $input.val('').blur();
                    break;
            }
        },

        /**
         * Handle paste event
         *
         * @since 1.0.0
         * @param {Event} e Paste event
         * @return {void}
         */
        handlePaste: function (e) {
            e.preventDefault();
            const $input = $(this);
            const $container = $input.closest('.wp-flyout-tag-input');
            const pastedText = (e.originalEvent.clipboardData || window.clipboardData).getData('text');

            // Split by commas and add each tag
            const tags = pastedText.split(',').map(t => t.trim()).filter(t => t);
            tags.forEach(tag => TagInput.addTag($container, tag));

            $input.val('');
        },

        /**
         * Handle tag removal
         *
         * @since 1.0.0
         * @param {Event} e Click event
         * @return {void}
         */
        handleRemove: function (e) {
            e.preventDefault();
            const $tag = $(this).closest('.tag-item');
            TagInput.removeTag($tag);
        },

        /**
         * Add a tag
         *
         * @since 1.0.0
         * @param {jQuery} $container Container element
         * @param {string} value      Tag value
         * @return {boolean} True if added successfully
         */
        addTag: function ($container, value) {
            value = value.trim();
            if (!value) return false;

            // Check for duplicates
            const exists = $container.find('.tag-item').filter(function () {
                return $(this).data('tag').toLowerCase() === value.toLowerCase();
            }).length > 0;

            if (exists) {
                $container.find('.tag-input-field').addClass('error');
                setTimeout(() => $container.find('.tag-input-field').removeClass('error'), 300);
                return false;
            }

            const name = $container.data('name') || 'tags';

            // Create tag HTML
            const $tag = $(`
                <span class="tag-item" data-tag="${value}">
                    <span class="tag-text">${$('<div>').text(value).html()}</span>
                    <button type="button" class="tag-remove" aria-label="Remove">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </span>
            `);

            // Create hidden input
            const $hidden = $(`<input type="hidden" name="${name}[]" value="${value}">`);

            // Add to container
            $container.find('.tag-input-field').before($tag);
            $container.append($hidden);

            // Animate in
            $tag.hide().fadeIn(200);

            return true;
        },

        /**
         * Remove a tag
         *
         * @since 1.0.0
         * @param {jQuery} $tag Tag element
         * @return {void}
         */
        removeTag: function ($tag) {
            const $container = $tag.closest('.wp-flyout-tag-input');
            const value = $tag.data('tag');

            $tag.fadeOut(200, function () {
                $(this).remove();
                $container.find(`input[type="hidden"][value="${value}"]`).remove();
            });
        },

        /**
         * Remove last tag
         *
         * @since 1.0.0
         * @param {jQuery} $container Container element
         * @return {void}
         */
        removeLastTag: function ($container) {
            const $lastTag = $container.find('.tag-item').last();
            if ($lastTag.length) {
                TagInput.removeTag($lastTag);
            }
        }
    };

    // Initialize when ready
    $(function () {
        TagInput.init();
    });

    // Export
    window.WPFlyoutTagInput = TagInput;

})(jQuery);