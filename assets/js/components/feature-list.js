/**
 * FeatureList Component JavaScript
 *
 * Handles dynamic list items with sorting and row management.
 * Automatically removes empty rows on form submission.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 * @author      David Sherlock
 */
(function ($) {
    'use strict';

    const FeatureList = {

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
            $('.wp-flyout-feature-list').each(function () {
                const $component = $(this);
                FeatureList.updateEmptyState($component);
                FeatureList.updateAddButton($component);
            });
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // Add new item
            $(document).on('click', '.feature-list-add', this.handleAdd.bind(this));

            // Remove item
            $(document).on('click', '.feature-list-remove', this.handleRemove.bind(this));

            // Update empty state on input
            $(document).on('input', '.feature-list-input', function () {
                const $component = $(this).closest('.wp-flyout-feature-list');
                FeatureList.updateEmptyState($component);
            });

            // Re-initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                FeatureList.initSortable();
                FeatureList.initializeComponents();
            });

            // Clean up empty rows before form submission
            $(document).on('submit', 'form', this.cleanupEmptyRows.bind(this));
        },

        /**
         * Initialize sortable functionality
         */
        initSortable: function () {
            $('.wp-flyout-feature-list.is-sortable .feature-list-items').each(function () {
                if (!$(this).hasClass('ui-sortable')) {
                    $(this).sortable({
                        handle: '.feature-list-handle',
                        items: '.feature-list-item',
                        placeholder: 'feature-list-item-placeholder',
                        tolerance: 'pointer',
                        forcePlaceholderSize: true,
                        update: function (event, ui) {
                            const $component = $(this).closest('.wp-flyout-feature-list');
                            FeatureList.updateIndexes($component);

                            // Trigger event
                            $component.trigger('featurelist:reordered');
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
            const $component = $button.closest('.wp-flyout-feature-list');

            this.addEmptyItem($component);
        },

        /**
         * Add empty item
         */
        addEmptyItem: function ($component) {
            const $items = $component.find('.feature-list-items');
            const index = $items.find('.feature-list-item').length;

            // Get configuration from data attributes
            const name = $component.data('name');
            const sortable = $component.data('sortable') === true || $component.data('sortable') === 'true';
            const icon = $component.data('icon');
            const placeholder = $component.data('placeholder') || 'Enter item';

            const html = this.getItemTemplate(name, index, '', sortable, icon, placeholder);
            const $newItem = $(html);

            $items.append($newItem);

            // Animate in and focus
            $newItem.hide().fadeIn(200, function () {
                $newItem.find('.feature-list-input').focus();
            });

            // Refresh sortable
            if ($items.hasClass('ui-sortable')) {
                $items.sortable('refresh');
            }

            this.updateEmptyState($component);
            this.updateAddButton($component);
            this.updateIndexes($component);

            // Trigger event
            $component.trigger('featurelist:added', {
                index: index,
                item: $newItem[0]
            });
        },

        /**
         * Handle remove button click
         */
        handleRemove: function (e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $item = $button.closest('.feature-list-item');
            const $component = $button.closest('.wp-flyout-feature-list');

            // Confirm if item has content
            const value = $item.find('.feature-list-input').val();

            if (value) {
                if (!confirm('Remove this item?')) {
                    return;
                }
            }

            // Animate removal
            $item.fadeOut(200, function () {
                $item.remove();
                FeatureList.updateIndexes($component);
                FeatureList.updateEmptyState($component);
                FeatureList.updateAddButton($component);

                // Add empty item if no items left
                if ($component.find('.feature-list-item').length === 0) {
                    FeatureList.addEmptyItem($component);
                }

                // Trigger event
                $component.trigger('featurelist:removed');
            });
        },

        /**
         * Update item indexes
         */
        updateIndexes: function ($component) {
            $component.find('.feature-list-item').each(function (index) {
                $(this).attr('data-index', index);
            });
        },

        /**
         * Update empty state visibility
         */
        updateEmptyState: function ($component) {
            const $container = $component.find('.feature-list-container');
            const hasItems = $component.find('.feature-list-item').length > 0;
            $container.toggleClass('is-empty', !hasItems);
        },

        /**
         * Update add button state based on max items
         */
        updateAddButton: function ($component) {
            const maxItems = parseInt($component.data('max-items')) || 0;

            if (maxItems > 0) {
                const currentCount = $component.find('.feature-list-item').length;
                const $addBtn = $component.find('.feature-list-add');

                $addBtn.prop('disabled', currentCount >= maxItems);

                // Optional: Show count
                if ($addBtn.find('.item-count').length === 0) {
                    $addBtn.append(`<span class="item-count"> (${currentCount}/${maxItems})</span>`);
                } else {
                    $addBtn.find('.item-count').text(` (${currentCount}/${maxItems})`);
                }
            }
        },

        /**
         * Clean up empty rows before form submission
         */
        cleanupEmptyRows: function (e) {
            const $form = $(e.currentTarget);

            $form.find('.wp-flyout-feature-list').each(function () {
                const $component = $(this);
                const name = $component.data('name');

                // Remove items with empty values completely
                $component.find('.feature-list-item').each(function () {
                    const $item = $(this);
                    const value = $item.find('.feature-list-input').val().trim();

                    if (!value) {
                        // Remove input to prevent submission
                        $item.find('input').remove();
                        $item.remove();
                    }
                });

                // Reindex remaining items
                $component.find('.feature-list-item').each(function (index) {
                    const $item = $(this);
                    $item.attr('data-index', index);
                    // Update the name to have correct index
                    $item.find('.feature-list-input').attr('name', `${name}[${index}]`);
                });
            });
        },

        /**
         * Get item template HTML
         */
        getItemTemplate: function (name, index, value, sortable, icon, placeholder) {
            let html = `<div class="feature-list-item" data-index="${index}">`;

            if (sortable) {
                html += `
                    <span class="feature-list-handle" title="Drag to reorder">
                        <span class="dashicons dashicons-menu"></span>
                    </span>`;
            }

            if (icon) {
                html += `
                    <span class="feature-list-icon">
                        <span class="dashicons dashicons-${icon}"></span>
                    </span>`;
            }

            html += `
                <input type="text"
                       name="${name}[]"
                       value="${value}"
                       placeholder="${placeholder}"
                       class="feature-list-input">
                
                <button type="button"
                        class="feature-list-remove"
                        title="Remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>`;

            return html;
        }
    };

    // Initialize on document ready
    $(function () {
        FeatureList.init();
    });

    // Export for external use
    window.WPFlyoutFeatureList = FeatureList;

})(jQuery);