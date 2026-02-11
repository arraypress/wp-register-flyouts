/**
 * AJAX Select Component - Select2 Integration
 *
 * Initializes Select2 on .wp-flyout-ajax-select elements with AJAX search
 * and hydration support via WordPress admin-ajax.php.
 *
 * Replaces the custom WPAjaxSelect class with Select2 for better
 * accessibility, keyboard navigation, mobile support, and RTL handling.
 *
 * @package ArrayPress\WPFlyout
 * @version 4.0.0
 */
(function ($) {
    'use strict';

    const AjaxSelect = {

        /**
         * Initialize the component
         */
        init: function () {
            const self = this;

            // Init on page ready
            $(function () {
                self.initAll(document);
            });

            // Init when flyout opens
            $(document).on('wpflyout:opened', function (e, data) {
                self.initAll(data.element);
            });
        },

        /**
         * Initialize all ajax selects within a container
         *
         * @param {HTMLElement|jQuery} container
         */
        initAll: function (container) {
            const self = this;
            $(container).find('.wp-flyout-ajax-select').each(function () {
                if (!$(this).data('select2')) {
                    self.initOne($(this));
                }
            });
        },

        /**
         * Initialize a single Select2 instance
         *
         * @param {jQuery} $select
         */
        initOne: function ($select) {
            const action = $select.data('ajax-action');
            const nonce = $select.data('nonce') || '';
            const placeholder = $select.data('placeholder') || 'Type to search...';
            const tags = $select.data('tags') === true || $select.data('tags') === 'true';
            const multiple = $select.prop('multiple');

            if (!action) {
                console.warn('AjaxSelect: No data-ajax-action specified', $select[0]);
                return;
            }

            const config = {
                placeholder: placeholder,
                allowClear: true,
                minimumInputLength: 1,
                tags: tags,
                width: '100%',
                ajax: {
                    url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            action: action,
                            search: params.term || '',
                            _wpnonce: nonce
                        };
                    },
                    processResults: function (response) {
                        if (response.success && Array.isArray(response.data)) {
                            return {
                                results: response.data.map(function (item) {
                                    return {
                                        id: String(item.id),
                                        text: String(item.text)
                                    };
                                })
                            };
                        }
                        return { results: [] };
                    },
                    cache: true
                }
            };

            // Scope dropdown to flyout panel if inside one
            const $flyoutBody = $select.closest('.wp-flyout-body');
            if ($flyoutBody.length) {
                config.dropdownParent = $flyoutBody;
            }

            $select.select2(config);

            // Hydrate if there are pre-selected values without labels
            this.hydrateIfNeeded($select, action, nonce);
        },

        /**
         * Hydrate pre-selected options that only have IDs (no labels)
         *
         * If the server rendered <option value="42" selected>Loading...</option>
         * or similar, fetch the real labels via the same endpoint.
         *
         * @param {jQuery} $select
         * @param {string} action
         * @param {string} nonce
         */
        hydrateIfNeeded: function ($select, action, nonce) {
            const $options = $select.find('option[selected]');
            if (!$options.length) {
                return;
            }

            // Check if any selected options need hydration (have placeholder text)
            const needsHydration = [];
            $options.each(function () {
                const text = $.trim($(this).text());
                if (text === 'Loading...' || text === '' || text === $(this).val()) {
                    needsHydration.push($(this).val());
                }
            });

            if (!needsHydration.length) {
                return;
            }

            // Fetch labels via the include parameter
            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: action,
                    include: needsHydration.join(','),
                    _wpnonce: nonce
                },
                success: function (response) {
                    if (response.success && Array.isArray(response.data)) {
                        response.data.forEach(function (item) {
                            const $option = $select.find('option[value="' + item.id + '"]');
                            if ($option.length) {
                                $option.text(item.text);
                            }
                        });
                        // Refresh Select2 display
                        $select.trigger('change.select2');
                    }
                }
            });
        },

        /**
         * Programmatically set a value with label (no AJAX needed)
         *
         * @param {jQuery} $select
         * @param {string|number} value
         * @param {string} text
         */
        setValue: function ($select, value, text) {
            // Add option if it doesn't exist
            if (!$select.find('option[value="' + value + '"]').length) {
                $select.append(new Option(text, String(value), true, true));
            } else {
                $select.val(String(value));
            }
            $select.trigger('change');
        },

        /**
         * Clear the select
         *
         * @param {jQuery} $select
         */
        clear: function ($select) {
            $select.val(null).trigger('change');
        }
    };

    // Initialize
    AjaxSelect.init();

    // Export for external use
    window.WPFlyoutAjaxSelect = AjaxSelect;

})(jQuery);