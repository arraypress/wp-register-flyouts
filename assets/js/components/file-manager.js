/**
 * WP Flyout File Manager Component
 *
 * Handles file management with media library integration,
 * external URL support, and drag-drop sorting.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */
(function ($) {
    'use strict';

    window.WPFlyoutFileManager = {

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function () {
            $(document).on('click', '.file-manager-add', this.handleAdd.bind(this));
            $(document).on('click', '.file-manager-item [data-action="browse"]', this.handleBrowse.bind(this));
            $(document).on('click', '.file-manager-item [data-action="remove"]', this.handleRemove.bind(this));
            $(document).on('file-manager:update', '.wp-flyout-file-manager', this.updateUI.bind(this));
            $(document).on('wpflyout:opened flyout:ready', this.initSortable.bind(this));
        },

        initSortable: function () {
            setTimeout(function () {
                $('.wp-flyout-file-manager.is-sortable .file-manager-items').each(function () {
                    if ($(this).hasClass('ui-sortable')) {
                        return;
                    }

                    $(this).sortable({
                        handle: '.file-handle',
                        items: '.file-manager-item',
                        placeholder: 'file-manager-item ui-sortable-placeholder',
                        tolerance: 'pointer',
                        forcePlaceholderSize: true,
                        update: function () {
                            $(this).find('.file-manager-item').each(function (index) {
                                $(this).attr('data-index', index);
                                $(this).find('input').each(function () {
                                    var name = $(this).attr('name');
                                    if (name) {
                                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                                    }
                                });
                            });
                        }
                    });
                });
            }, 100);
        },

        // ------------------------------------------------------------------
        // Handlers
        // ------------------------------------------------------------------

        handleAdd: function (e) {
            e.preventDefault();

            var $manager = $(e.currentTarget).closest('.wp-flyout-file-manager');
            var $items = $manager.find('.file-manager-items');
            var index = $items.find('.file-manager-item').length;
            var lookupKey = 'file_' + Math.random().toString(36).substring(2, 15);

            var $item = this.createItem($manager, index, {
                name: '',
                url: '',
                attachment_id: '',
                lookup_key: lookupKey
            });

            $items.append($item);
            $item.hide().fadeIn(200, function () {
                $item.find('.file-name-input').focus();
            });

            if ($items.hasClass('ui-sortable')) {
                $items.sortable('refresh');
            }

            $manager.trigger('file-manager:update');
        },

        handleBrowse: function (e) {
            e.preventDefault();

            var $item = $(e.currentTarget).closest('.file-manager-item');
            var $manager = $item.closest('.wp-flyout-file-manager');

            var frame = wp.media({
                title: 'Select File',
                button: { text: 'Select' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();

                $item.find('.file-name-input').val(attachment.title || attachment.filename || '');
                $item.find('.file-url-input').val(attachment.url || '');
                $item.find('.file-attachment-id').val(attachment.id || '');

                frame.close();
                $manager.trigger('file-manager:update');
            });

            frame.open();
        },

        handleRemove: function (e) {
            e.preventDefault();

            var $item = $(e.currentTarget).closest('.file-manager-item');
            var $manager = $item.closest('.wp-flyout-file-manager');

            $item.fadeOut(200, function () {
                $(this).remove();
                $manager.trigger('file-manager:update');
            });
        },

        // ------------------------------------------------------------------
        // DOM Creation
        // ------------------------------------------------------------------

        createItem: function ($manager, index, data) {
            var fieldName = $manager.data('name');
            var sortable = $manager.hasClass('is-sortable');

            var $item = $('<div>', {
                'class': 'file-manager-item',
                'data-index': index
            });

            // Drag handle
            if (sortable) {
                $item.append(
                    $('<span>', { 'class': 'file-handle', title: 'Drag to reorder' }).append(
                        $('<span>', { 'class': 'dashicons dashicons-menu' })
                    )
                );
            }

            // Fields container
            var $fields = $('<div>', { 'class': 'file-fields' });

            $fields.append(
                $('<input>', {
                    type: 'text',
                    name: fieldName + '[' + index + '][name]',
                    value: data.name,
                    placeholder: 'File name',
                    'class': 'file-name-input'
                }),
                $('<input>', {
                    type: 'url',
                    name: fieldName + '[' + index + '][url]',
                    value: data.url,
                    placeholder: 'URL or browse media library',
                    'class': 'file-url-input'
                }),
                $('<input>', {
                    type: 'hidden',
                    name: fieldName + '[' + index + '][attachment_id]',
                    value: data.attachment_id,
                    'class': 'file-attachment-id'
                }),
                $('<input>', {
                    type: 'hidden',
                    name: fieldName + '[' + index + '][lookup_key]',
                    value: data.lookup_key,
                    'class': 'file-lookup-key'
                })
            );

            $item.append($fields);

            // Actions
            var $actions = $('<div>', { 'class': 'file-actions' });

            $actions.append(
                $('<button>', {
                    type: 'button',
                    'class': 'file-action-btn',
                    'data-action': 'browse',
                    title: 'Browse media library'
                }).append($('<span>', { 'class': 'dashicons dashicons-admin-media' })),

                $('<button>', {
                    type: 'button',
                    'class': 'file-action-btn file-remove',
                    'data-action': 'remove',
                    title: 'Remove file'
                }).append($('<span>', { 'class': 'dashicons dashicons-trash' }))
            );

            $item.append($actions);

            return $item;
        },

        // ------------------------------------------------------------------
        // UI Updates
        // ------------------------------------------------------------------

        updateUI: function (e) {
            var $manager = $(e.currentTarget);
            var $list = $manager.find('.file-manager-list');
            var count = $manager.find('.file-manager-item').length;
            var maxFiles = parseInt($manager.data('max-files')) || 0;

            // Empty state
            $list.toggleClass('is-empty', count === 0);

            // Count display
            $manager.find('.current-count').text(count);

            // Add button limit
            var $addBtn = $manager.find('.file-manager-add');
            var atLimit = maxFiles > 0 && count >= maxFiles;
            $addBtn.prop('disabled', atLimit);

            // Re-index all items
            $manager.find('.file-manager-item').each(function (index) {
                $(this).attr('data-index', index);
                $(this).find('input').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }
    };

    $(document).ready(function () {
        WPFlyoutFileManager.init();
    });

})(jQuery);