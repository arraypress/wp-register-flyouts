/**
 * WP Flyout Image Gallery Component JavaScript
 * Handles media library integration, drag-drop sorting, and gallery management
 * Simplified to only manage attachment IDs
 */
(function ($) {
    'use strict';

    window.WPFlyoutImageGallery = {

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function () {
            // Add images button
            $(document).on('click', '.gallery-add-btn', this.handleAdd.bind(this));

            // Edit image button
            $(document).on('click', '.gallery-item-edit', this.handleEdit.bind(this));

            // Remove image button
            $(document).on('click', '.gallery-item-remove', this.handleRemove.bind(this));

            // Update UI when items change
            $(document).on('image-gallery:update', '.wp-flyout-image-gallery', this.updateUI.bind(this));

            // Re-initialize sortable when flyout opens
            $(document).on('wpflyout:opened flyout:ready', this.initSortable.bind(this));
        },

        initSortable: function () {
            setTimeout(function () {
                $('.wp-flyout-image-gallery.is-sortable .gallery-grid').each(function () {
                    if (!$(this).hasClass('ui-sortable')) {
                        $(this).sortable({
                            items: '.gallery-item',
                            handle: '.gallery-item-handle',
                            placeholder: 'gallery-item ui-sortable-placeholder',
                            tolerance: 'pointer',
                            forcePlaceholderSize: true,
                            update: function (event, ui) {
                                // Re-index items after sorting
                                $(this).find('.gallery-item').each(function (index) {
                                    $(this).attr('data-index', index);
                                    $(this).find('input[type="hidden"]').each(function () {
                                        const name = $(this).attr('name');
                                        if (name) {
                                            $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                                        }
                                    });
                                });
                            }
                        });
                    }
                });
            }, 100);
        },

        handleAdd: function (e) {
            e.preventDefault();
            const $gallery = $(e.currentTarget).closest('.wp-flyout-image-gallery');
            const multiple = $gallery.data('multiple') === true || $gallery.data('multiple') === 'true';
            this.openMediaLibrary($gallery, null, multiple);
        },

        handleEdit: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.gallery-item');
            const $gallery = $item.closest('.wp-flyout-image-gallery');
            this.openMediaLibrary($gallery, $item, false);
        },

        handleRemove: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.gallery-item');
            const $gallery = $item.closest('.wp-flyout-image-gallery');

            $item.fadeOut(200, function () {
                $(this).remove();
                $gallery.trigger('image-gallery:update');
            });
        },

        openMediaLibrary: function ($gallery, $item, multiple) {
            const self = this;
            const isEdit = !!$item;

            // Create media frame
            const frame = wp.media({
                title: isEdit ? 'Replace Image' : 'Select Images',
                button: {
                    text: isEdit ? 'Replace' : 'Add to Gallery'
                },
                library: {
                    type: 'image'
                },
                multiple: multiple
            });

            // If editing, pre-select the image
            if (isEdit) {
                frame.on('open', function () {
                    const selection = frame.state().get('selection');
                    const attachmentId = $item.data('attachment-id');
                    if (attachmentId) {
                        const attachment = wp.media.attachment(attachmentId);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    }
                });
            }

            // Handle selection
            frame.on('select', function () {
                const selection = frame.state().get('selection');

                if (isEdit) {
                    // Replace existing item
                    const attachment = selection.first().toJSON();
                    self.updateImageItem($item, attachment);
                } else {
                    // Add new items
                    selection.each(function (attachment) {
                        self.addImageItem($gallery, attachment.toJSON());
                    });
                }

                $gallery.trigger('image-gallery:update');
            });

            frame.open();
        },

        addImageItem: function ($gallery, attachment) {
            const $grid = $gallery.find('.gallery-grid');
            const name = $gallery.data('name');
            const index = $grid.find('.gallery-item').length;
            const size = $gallery.data('size') || 'thumbnail';

            // Get the appropriate thumbnail URL
            const thumbnail = attachment.sizes && attachment.sizes[size] ?
                attachment.sizes[size].url : attachment.url;

            // Build HTML for new item
            let html = '<div class="gallery-item" data-index="' + index + '" data-attachment-id="' + attachment.id + '">';

            // Sortable handle
            if ($gallery.hasClass('is-sortable')) {
                html += '<div class="gallery-item-handle">' +
                    '<span class="dashicons dashicons-move"></span>' +
                    '</div>';
            }

            // Image preview with overlay
            html += '<div class="gallery-item-preview">' +
                '<img src="' + thumbnail + '" alt="' + (attachment.alt || '') + '" class="gallery-thumbnail">' +
                '<div class="gallery-item-overlay">' +
                '<button type="button" class="gallery-item-edit" data-action="edit" title="Change image">' +
                '<span class="dashicons dashicons-edit"></span>' +
                '</button>' +
                '<button type="button" class="gallery-item-remove" data-action="remove" title="Remove image">' +
                '<span class="dashicons dashicons-trash"></span>' +
                '</button>' +
                '</div>' +
                '</div>';

            // Hidden input with just the attachment ID
            html += '<input type="hidden" name="' + name + '[' + index + ']" value="' + attachment.id + '">';

            html += '</div>';

            // Add to grid
            const $newItem = $(html);
            $grid.append($newItem);

            // Animate in
            $newItem.hide().fadeIn(200);

            // Refresh sortable
            if ($grid.hasClass('ui-sortable')) {
                $grid.sortable('refresh');
            }
        },

        updateImageItem: function ($item, attachment) {
            const $gallery = $item.closest('.wp-flyout-image-gallery');
            const size = $gallery.data('size') || 'thumbnail';

            // Get the appropriate thumbnail URL
            const thumbnail = attachment.sizes && attachment.sizes[size] ?
                attachment.sizes[size].url : attachment.url;

            // Update preview image
            $item.find('.gallery-thumbnail').attr({
                'src': thumbnail,
                'alt': attachment.alt || ''
            });

            // Update data attribute and hidden input
            $item.attr('data-attachment-id', attachment.id);
            $item.find('input[type="hidden"]').val(attachment.id);
        },

        updateUI: function (e) {
            const $gallery = $(e.currentTarget);
            const $container = $gallery.find('.gallery-container');
            const $items = $gallery.find('.gallery-item');
            const count = $items.length;
            const maxImages = parseInt($gallery.data('max-images')) || 0;

            // Update empty state
            if (count === 0) {
                $container.addClass('is-empty');
            } else {
                $container.removeClass('is-empty');
            }

            // Update count display
            $gallery.find('.current-count').text(count);

            // Update add button state
            const $addBtn = $gallery.find('.gallery-add-btn');
            if (maxImages > 0 && count >= maxImages) {
                $addBtn.prop('disabled', true);
            } else {
                $addBtn.prop('disabled', false);
            }
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyoutImageGallery.init();
    });

})(jQuery);