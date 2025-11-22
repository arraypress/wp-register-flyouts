/**
 * WP Flyout File Manager Component JavaScript
 * Handles manual entry, media library integration, and drag-drop sorting
 */
(function ($) {
    'use strict';

    window.WPFlyoutFileManager = {

        // File icon mapping
        fileIcons: {
            // Documents
            'pdf': 'pdf',
            'doc': 'media-document',
            'docx': 'media-document',
            'txt': 'media-text',

            // Images
            'jpg': 'format-image',
            'jpeg': 'format-image',
            'png': 'format-image',
            'gif': 'format-image',
            'svg': 'format-image',
            'webp': 'format-image',

            // Media
            'mp3': 'format-audio',
            'wav': 'format-audio',
            'ogg': 'format-audio',
            'mp4': 'format-video',
            'mov': 'format-video',
            'avi': 'format-video',
            'webm': 'format-video',

            // Archives
            'zip': 'media-archive',
            'rar': 'media-archive',
            '7z': 'media-archive',
            'tar': 'media-archive',
            'gz': 'media-archive',

            // Code
            'js': 'media-code',
            'css': 'media-code',
            'php': 'media-code',
            'html': 'media-code',
            'json': 'media-code',
            'xml': 'media-code',

            // Spreadsheets
            'xls': 'media-spreadsheet',
            'xlsx': 'media-spreadsheet',
            'csv': 'media-spreadsheet',
        },

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function () {
            // Add file button - adds empty row
            $(document).on('click', '.file-manager-add', this.handleAdd.bind(this));

            // Browse button - opens media library
            $(document).on('click', '.file-manager-item [data-action="browse"]', this.handleBrowse.bind(this));

            // Remove button
            $(document).on('click', '.file-manager-item [data-action="remove"]', this.handleRemove.bind(this));

            // URL change - update icon
            $(document).on('blur change', '.file-url-input', this.handleUrlChange.bind(this));

            // Update file count when items change
            $(document).on('file-manager:update', '.wp-flyout-file-manager', this.updateUI.bind(this));

            // Re-initialize sortable when flyout opens
            $(document).on('wpflyout:opened flyout:ready', this.initSortable.bind(this));
        },

        initSortable: function () {
            setTimeout(function () {
                $('.wp-flyout-file-manager.is-sortable .file-manager-items').each(function () {
                    if (!$(this).hasClass('ui-sortable')) {
                        $(this).sortable({
                            handle: '.file-handle',
                            items: '.file-manager-item',
                            placeholder: 'file-manager-item ui-sortable-placeholder',
                            tolerance: 'pointer',
                            forcePlaceholderSize: true,
                            update: function (event, ui) {
                                // Re-index items after sorting
                                $(this).find('.file-manager-item').each(function (index) {
                                    $(this).attr('data-index', index);
                                    $(this).find('input, select, textarea').each(function () {
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
            const $manager = $(e.currentTarget).closest('.wp-flyout-file-manager');
            this.addEmptyItem($manager);
        },

        handleBrowse: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');
            this.openMediaLibrary($manager, $item);
        },

        handleRemove: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');

            $item.fadeOut(200, function () {
                $(this).remove();
                $manager.trigger('file-manager:update');
            });
        },

        handleUrlChange: function (e) {
            const $input = $(e.currentTarget);
            const $item = $input.closest('.file-manager-item');
            const url = $input.val();

            // Update icon based on URL
            this.updateItemIcon($item, url);
        },

        addEmptyItem: function ($manager) {
            const template = $manager.data('template');
            const $items = $manager.find('.file-manager-items');
            const index = $items.find('.file-manager-item').length;

            // Generate unique lookup_key for new items
            const lookupKey = 'file_' + Math.random().toString(36).substring(2, 15);

            // Replace template variables
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{name}}/g, '')
                .replace(/{{url}}/g, '')
                .replace(/{{attachment_id}}/g, '')
                .replace(/{{lookup_key}}/g, lookupKey)
                .replace(/{{extension}}/g, '')
                .replace(/{{extension_upper}}/g, '')
                .replace(/{{extension_display}}/g, 'display:none')
                .replace(/{{icon}}/g, 'media-default');

            const $newItem = $(html);
            $items.append($newItem);

            // Animate in and focus on name field
            $newItem.hide().fadeIn(200, function () {
                $newItem.find('.file-name-input').focus();
            });

            // Refresh sortable if exists
            if ($items.hasClass('ui-sortable')) {
                $items.sortable('refresh');
            }

            $manager.trigger('file-manager:update');
        },

        openMediaLibrary: function ($manager, $item) {
            const self = this;

            // Create media frame
            const frame = wp.media({
                title: 'Select File',
                button: {
                    text: 'Select'
                },
                multiple: false
            });

            // Handle selection
            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                self.updateFileItem($item, attachment);
                $manager.trigger('file-manager:update');
            });

            frame.open();
        },

        updateFileItem: function ($item, attachment) {
            // Update inputs
            $item.find('[data-field="name"]').val(attachment.title || attachment.filename || '');
            $item.find('[data-field="url"]').val(attachment.url || '');
            $item.find('[data-field="id"]').val(attachment.id || '');

            // Update icon based on URL
            this.updateItemIcon($item, attachment.url || '');
        },

        updateItemIcon: function ($item, url) {
            const extension = this.getFileExtension(url);
            const icon = this.getFileIcon(extension);

            // Update icon
            const $icon = $item.find('.file-icon');
            $icon.attr('data-extension', extension);
            $icon.find('.dashicons')
                .removeClass()
                .addClass('dashicons dashicons-' + icon);

            // Update extension badge
            let $extension = $icon.find('.file-extension');
            if (extension) {
                if (!$extension.length) {
                    $extension = $('<span class="file-extension"></span>');
                    $icon.append($extension);
                }
                $extension.text(extension.toUpperCase()).show();
            } else {
                $extension.hide();
            }
        },

        updateUI: function (e) {
            const $manager = $(e.currentTarget);
            const $list = $manager.find('.file-manager-list');
            const $items = $manager.find('.file-manager-item');
            const count = $items.length;
            const maxFiles = parseInt($manager.data('max-files')) || 0;

            // Update empty state
            if (count === 0) {
                $list.addClass('is-empty');
            } else {
                $list.removeClass('is-empty');
            }

            // Update count display
            $manager.find('.current-count').text(count);

            // Update add button state
            const $addBtn = $manager.find('.file-manager-add');
            if (maxFiles > 0 && count >= maxFiles) {
                $addBtn.prop('disabled', true);
                $list.addClass('max-reached');
            } else {
                $addBtn.prop('disabled', false);
                $list.removeClass('max-reached');
            }
        },

        getFileExtension: function (url) {
            if (!url) return '';
            const path = url.split('?')[0]; // Remove query string
            const filename = path.split('/').pop();
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        },

        getFileIcon: function (extension) {
            return this.fileIcons[extension] || 'media-default';
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyoutFileManager.init();
    });

})(jQuery);