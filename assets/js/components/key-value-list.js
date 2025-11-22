/**
 * MetaKeyValue Component JavaScript
 *
 * Handles dynamic key-value pairs with optional validation, sorting, and row management.
 * Empty rows are automatically cleaned up on form submission.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 * @author      David Sherlock
 */
(function ($) {
    'use strict';

    const MetaKeyValue = {

        /**
         * Initialize the component
         */
        init: function () {
            this.bindEvents();
            this.initSortable();
            this.initializeComponents();
        },

        /**
         * Initialize existing components on page load
         */
        initializeComponents: function () {
            $('.wp-flyout-meta-key-value').each(function () {
                const $component = $(this);
                MetaKeyValue.updateEmptyState($component);
                MetaKeyValue.updateAddButton($component);
            });
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // Add new row
            $(document).on('click', '.meta-kv-add', this.handleAdd.bind(this));

            // Remove row
            $(document).on('click', '.meta-kv-remove', this.handleRemove.bind(this));

            // Key field validation
            $(document).on('blur', '.meta-kv-key', this.validateKey.bind(this));

            // Update empty state on input
            $(document).on('input', '.meta-kv-key, .meta-kv-value', function () {
                const $component = $(this).closest('.wp-flyout-meta-key-value');
                MetaKeyValue.updateEmptyState($component);
            });

            // Re-initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                MetaKeyValue.initSortable();
                MetaKeyValue.initializeComponents();
            });

            // Clean up empty rows before form submission
            $(document).on('submit', 'form', this.cleanupEmptyRows.bind(this));
        },

        /**
         * Initialize sortable functionality
         */
        initSortable: function () {
            $('.wp-flyout-meta-key-value.is-sortable .meta-kv-items').each(function () {
                if (!$(this).hasClass('ui-sortable')) {
                    $(this).sortable({
                        handle: '.meta-kv-handle',
                        items: '.meta-kv-item',
                        placeholder: 'meta-kv-item-placeholder',
                        tolerance: 'pointer',
                        forcePlaceholderSize: true,
                        update: function (event, ui) {
                            MetaKeyValue.reindexItems($(this).closest('.wp-flyout-meta-key-value'));
                        }
                    });
                }
            });
        },

        /**
         * Handle add button click
         */
        handleAdd: function (e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-meta-key-value');

            this.addEmptyRow($component);
        },

        /**
         * Add empty row
         */
        addEmptyRow: function ($component) {
            const $items = $component.find('.meta-kv-items');
            const index = $items.find('.meta-kv-item').length;

            // Get configuration from data attributes
            const name = $component.data('name');
            const sortable = $component.data('sortable') === true || $component.data('sortable') === 'true';
            const keyPlaceholder = $component.data('key-placeholder') || 'Enter key';
            const valPlaceholder = $component.data('val-placeholder') || 'Enter value';
            const requiredKey = $component.data('required-key') === true || $component.data('required-key') === 'true';

            const html = this.getRowTemplate(name, index, '', '', sortable, keyPlaceholder, valPlaceholder, requiredKey);
            const $newRow = $(html);

            $items.append($newRow);

            // Animate in
            $newRow.hide().fadeIn(200, function () {
                $newRow.find('.meta-kv-key').focus();
            });

            // Refresh sortable
            if ($items.hasClass('ui-sortable')) {
                $items.sortable('refresh');
            }

            this.updateEmptyState($component);
            this.updateAddButton($component);

            // Trigger event
            $component.trigger('metakeyvalue:added', {
                index: index,
                row: $newRow[0]
            });
        },

        /**
         * Handle remove button click
         */
        handleRemove: function (e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('.meta-kv-item');
            const $component = $button.closest('.wp-flyout-meta-key-value');

            // Confirm if row has data
            const key = $row.find('.meta-kv-key').val();
            const value = $row.find('.meta-kv-value').val();

            if (key || value) {
                if (!confirm('Remove this item?')) {
                    return;
                }
            }

            // Animate removal
            $row.fadeOut(200, function () {
                $row.remove();
                MetaKeyValue.reindexItems($component);
                MetaKeyValue.updateEmptyState($component);
                MetaKeyValue.updateAddButton($component);

                // Add empty row if no rows left
                if ($component.find('.meta-kv-item').length === 0) {
                    MetaKeyValue.addEmptyRow($component);
                }

                // Trigger event
                $component.trigger('metakeyvalue:removed');
            });
        },

        /**
         * Validate key field (only if required)
         */
        validateKey: function (e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-meta-key-value');
            const requiredKey = $component.data('required-key') === true || $component.data('required-key') === 'true';
            const value = $input.val().trim();

            // Remove any previous error state
            $input.removeClass('error');

            // Only validate if required and empty
            if (requiredKey && !value) {
                $input.addClass('error');
                return false;
            }

            // Check for duplicate keys only if there's a value
            if (value) {
                const $otherKeys = $component.find('.meta-kv-key').not($input);

                let isDuplicate = false;
                $otherKeys.each(function () {
                    if ($(this).val().trim().toLowerCase() === value.toLowerCase()) {
                        isDuplicate = true;
                        return false;
                    }
                });

                if (isDuplicate) {
                    $input.addClass('error');
                    alert('This key already exists');
                    return false;
                }
            }

            return true;
        },

        /**
         * Reindex all items after add/remove/sort
         */
        reindexItems: function ($component) {
            const name = $component.data('name');

            $component.find('.meta-kv-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);

                // Update field names
                $item.find('.meta-kv-key').attr('name', `${name}[${index}][key]`);
                $item.find('.meta-kv-value').attr('name', `${name}[${index}][value]`);
            });
        },

        /**
         * Update empty state visibility
         */
        updateEmptyState: function ($component) {
            const $list = $component.find('.meta-kv-list');
            // Show empty state only when there are NO items at all
            const hasItems = $component.find('.meta-kv-item').length > 0;
            $list.toggleClass('is-empty', !hasItems);
        },

        /**
         * Update add button state based on max items
         */
        updateAddButton: function ($component) {
            const maxItems = parseInt($component.data('max-items')) || 0;

            if (maxItems > 0) {
                const currentCount = $component.find('.meta-kv-item').length;
                const $addBtn = $component.find('.meta-kv-add');

                $addBtn.prop('disabled', currentCount >= maxItems);
            }
        },

        /**
         * Clean up empty rows before form submission
         */
        cleanupEmptyRows: function (e) {
            const $form = $(e.currentTarget);

            $form.find('.wp-flyout-meta-key-value').each(function () {
                const $component = $(this);
                const name = $component.data('name');

                // Remove ALL existing items from the DOM
                $component.find('.meta-kv-item').each(function () {
                    const $item = $(this);
                    const key = $item.find('.meta-kv-key').val().trim();
                    const value = $item.find('.meta-kv-value').val().trim();

                    // Remove items where BOTH are empty
                    if (!key && !value) {
                        // Remove all inputs to prevent submission
                        $item.find('input').remove();
                        $item.remove();
                    }
                });

                // Reindex remaining items with sequential indexes
                $component.find('.meta-kv-item').each(function (index) {
                    const $item = $(this);
                    $item.attr('data-index', index);
                    $item.find('.meta-kv-key').attr('name', `${name}[${index}][key]`);
                    $item.find('.meta-kv-value').attr('name', `${name}[${index}][value]`);
                });
            });
        },

        /**
         * Get row template HTML
         */
        getRowTemplate: function (name, index, key, value, sortable, keyPlaceholder, valPlaceholder, requiredKey) {
            let html = `<div class="meta-kv-item" data-index="${index}">`;

            if (sortable) {
                html += `
                    <span class="meta-kv-handle" title="Drag to reorder">
                        <span class="dashicons dashicons-menu"></span>
                    </span>`;
            }

            html += `
                <div class="meta-kv-fields">
                    <input type="text"
                           name="${name}[${index}][key]"
                           value="${key}"
                           placeholder="${keyPlaceholder}"
                           class="meta-kv-key"
                           data-field="key"
                           ${requiredKey ? 'required' : ''}>
                    
                    <input type="text"
                           name="${name}[${index}][value]"
                           value="${value}"
                           placeholder="${valPlaceholder}"
                           class="meta-kv-value"
                           data-field="value">
                </div>
                
                <button type="button"
                        class="meta-kv-remove"
                        title="Remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>`;

            return html;
        }
    };

    // Initialize on document ready
    $(function () {
        MetaKeyValue.init();
    });

    // Export for external use
    window.WPFlyoutMetaKeyValue = MetaKeyValue;

})(jQuery);