/**
 * AJAX Select Component - Select2 Integration
 *
 * Initializes Select2 on ajax select elements with AJAX search
 * and hydration support via WordPress admin-ajax.php.
 *
 * Supports two attribute patterns:
 * - New: data-ajax-action (used by FormField ajax_select)
 * - Legacy: data-ajax (used by LineItems product selector)
 *
 * @package ArrayPress\WPFlyout
 * @version 5.0.0
 */
(function ($) {
    'use strict';

    const AjaxSelect = {

        init: function () {
            const self = this;

            $(function () {
                self.initAll(document);
            });

            $(document).on('wpflyout:opened', function (e, data) {
                self.initAll(data.element);
            });
        },

        initAll: function (container) {
            const self = this;

            // New pattern: .wp-flyout-ajax-select with data-ajax-action
            $(container).find('.wp-flyout-ajax-select').each(function () {
                if (!$(this).data('select2')) {
                    self.initOne($(this));
                }
            });

            // Legacy pattern: select[data-ajax] (e.g. line items product selector)
            $(container).find('select[data-ajax]').each(function () {
                if (!$(this).data('select2')) {
                    self.initOne($(this));
                }
            });
        },

        initOne: function ($select) {
            // Skip if already initialized
            if ($select.data('select2')) {
                return;
            }

            // Support both attribute names
            const action = $select.data('ajax-action') || $select.data('ajax') || '';
            const nonce = $select.data('nonce') || '';
            const placeholder = $select.data('placeholder') || 'Type to search...';
            const tags = $select.data('tags') === true || $select.data('tags') === 'true';

            if (!action) {
                console.warn('AjaxSelect: No ajax action specified', $select[0]);
                return;
            }

            const config = {
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                tags: tags,
                minimumInputLength: 0,
                ajax: {
                    url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
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
                                    // Handle both {id,text} and {value,text} formats
                                    return {
                                        id: String(item.id || item.value),
                                        text: String(item.text)
                                    };
                                })
                            };
                        }
                        return {results: []};
                    },
                    cache: true
                }
            };

            // Scope dropdown to flyout body if inside one
            const $flyoutBody = $select.closest('.wp-flyout-body');
            if ($flyoutBody.length) {
                config.dropdownParent = $flyoutBody;
            }

            $select.select2(config);

            // Hydrate pre-selected options that need labels
            this.hydrateIfNeeded($select, action, nonce);
        },

        hydrateIfNeeded: function ($select, action, nonce) {
            const $options = $select.find('option[selected], option:selected');
            if (!$options.length) {
                return;
            }

            const needsHydration = [];
            $options.each(function () {
                const text = $.trim($(this).text());
                const val = $(this).val();
                if (val && (text === 'Loading...' || text === '' || text === val)) {
                    needsHydration.push(val);
                }
            });

            if (!needsHydration.length) {
                return;
            }

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
                            const id = String(item.id || item.value);
                            const $option = $select.find('option[value="' + id + '"]');
                            if ($option.length) {
                                $option.text(item.text);
                            }
                        });
                        $select.trigger('change.select2');
                    }
                }
            });
        },

        setValue: function ($select, value, text) {
            if (!$select.find('option[value="' + value + '"]').length) {
                $select.append(new Option(text, String(value), true, true));
            } else {
                $select.val(String(value));
            }
            $select.trigger('change');
        },

        clear: function ($select) {
            $select.val(null).trigger('change');
        }
    };

    AjaxSelect.init();

    window.WPFlyoutAjaxSelect = AjaxSelect;

})(jQuery);