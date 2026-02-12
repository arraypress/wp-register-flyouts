/**
 * WP Flyout Image Picker Component
 *
 * Handles interactive image selection via the WordPress Media Library.
 * Used by both the header component (editable mode) and the standalone
 * image field component.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */
(function ($) {
    'use strict';

    window.WPFlyoutImagePicker = {

        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            // Select image via media library
            $(document).on('click', '.image-picker [data-action="select-image"]', this.handleSelect.bind(this));

            // Remove image
            $(document).on('click', '.image-picker [data-action="remove-image"]', this.handleRemove.bind(this));

            // Re-init on flyout open
            $(document).on('wpflyout:opened flyout:ready', this.onFlyoutOpen.bind(this));
        },

        onFlyoutOpen: function () {
            // Nothing special needed on open for now, but hook is here for future use
        },

        handleSelect: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $picker = $(e.currentTarget).closest('.image-picker');
            this.openMediaLibrary($picker);
        },

        handleRemove: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $picker = $(e.currentTarget).closest('.image-picker');
            this.clearImage($picker);
        },

        openMediaLibrary: function ($picker) {
            var self = this;

            var frame = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use This Image'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                self.setImage($picker, attachment);
            });

            frame.open();
        },

        /**
         * Set the selected image
         *
         * @param {jQuery} $picker    Picker container
         * @param {Object} attachment WordPress attachment object
         */
        setImage: function ($picker, attachment) {
            var size = $picker.data('size') || 'thumbnail';

            // Get the URL for the configured size, falling back to full
            var url = attachment.url;
            if (attachment.sizes && attachment.sizes[size]) {
                url = attachment.sizes[size].url;
            } else if (attachment.sizes && attachment.sizes.thumbnail) {
                url = attachment.sizes.thumbnail.url;
            }

            // Update hidden input
            $picker.find('.image-picker-value').val(attachment.id);

            // Update preview
            var $preview = $picker.find('.image-picker-preview');
            $preview.html(
                '<img src="' + url + '" alt="" class="image-picker-img">'
            );

            // Add has-image class
            $picker.addClass('has-image');

            // Ensure remove button exists in overlay
            var $overlay = $picker.find('.image-picker-overlay');
            if (!$overlay.find('[data-action="remove-image"]').length) {
                $overlay.append(
                    '<button type="button" class="image-picker-btn image-picker-remove" data-action="remove-image" title="Remove image">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>'
                );
            }

            // Update select button icon to "change"
            $overlay.find('[data-action="select-image"] .dashicons')
                .removeClass('dashicons-plus-alt2')
                .addClass('dashicons-update');

            // Trigger event
            $picker.trigger('image-picker:changed', {
                attachmentId: attachment.id,
                url: url
            });
        },

        /**
         * Clear the image, reverting to fallback or placeholder
         *
         * @param {jQuery} $picker Picker container
         */
        clearImage: function ($picker) {
            var fallbackImage = $picker.data('fallback-image') || '';

            // Clear the hidden input (set to 0, not the fallback)
            $picker.find('.image-picker-value').val('0');

            var $preview = $picker.find('.image-picker-preview');

            if (fallbackImage) {
                // Show fallback image
                $preview.html(
                    '<img src="' + fallbackImage + '" alt="" class="image-picker-img image-picker-fallback">'
                );
                // Keep has-image for visual consistency but mark as fallback
                $picker.addClass('has-image').addClass('is-fallback');
            } else {
                // Show placeholder — read icon from data attribute or default
                var icon = $picker.data('icon') || 'format-image';
                $preview.html(
                    '<div class="image-picker-placeholder">' +
                    '<span class="dashicons dashicons-' + icon + '"></span>' +
                    '</div>'
                );
                $picker.removeClass('has-image').removeClass('is-fallback');
            }

            // Remove the remove button
            $picker.find('[data-action="remove-image"]').remove();

            // Update select button icon
            $picker.find('[data-action="select-image"] .dashicons')
                .removeClass('dashicons-update')
                .addClass('dashicons-plus-alt2');

            // Trigger event
            $picker.trigger('image-picker:removed');
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyoutImagePicker.init();
    });

})(jQuery);