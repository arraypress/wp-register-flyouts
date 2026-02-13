/**
 * AJAX Select Component - Select2 Integration
 *
 * Initializes Select2 on ajax select elements with search
 * and hydration support via WordPress REST API.
 *
 * Supports two attribute patterns:
 * - New: data-ajax-url + data-ajax-params (REST API)
 * - Legacy: data-ajax (used by LineItems product selector)
 *
 * @package ArrayPress\WPFlyout
 * @version 6.0.0
 */
(function ($) {
    'use strict';

    const AjaxSelect = {

        init: function () {
            var self = this;

            $(function () {
                self.initAll(document);
            });

            $(document).on('wpflyout:opened', function (e, data) {
                self.initAll(data.element);
            });
        },

        initAll: function (container) {
            var self = this;

            // REST pattern: .wp-flyout-ajax-select with data-ajax-url
            $(container).find('.wp-flyout-ajax-select').each(function () {
                if (!$(this).data('select2')) {
                    self.initOne($(this));
                }
            });
        },

        initOne: function ($select) {
            if ($select.data('select2')) {
                return;
            }

            var ajaxUrl = $select.data('ajax-url') || '';
            var ajaxParams = $select.data('ajax-params') || {};
            var placeholder = $select.data('placeholder') || 'Type to search...';
            var tags = $select.data('tags') === true || $select.data('tags') === 'true';

            if (!ajaxUrl) {
                console.warn('AjaxSelect: No ajax URL specified', $select[0]);
                return;
            }

            var config = {
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                tags: tags,
                minimumInputLength: 0,
                ajax: {
                    url: ajaxUrl,
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    headers: {
                        'X-WP-Nonce': wpFlyout.restNonce
                    },
                    data: function (params) {
                        var data = $.extend({}, ajaxParams, {
                            term: params.term || ''
                        });
                        return data;
                    },
                    processResults: function (response) {
                        if (response.success && Array.isArray(response.results)) {
                            return {
                                results: response.results.map(function (item) {
                                    return {
                                        id: String(item.id || item.value),
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

            // Scope dropdown to flyout body if inside one
            var $flyoutBody = $select.closest('.wp-flyout-body');
            if ($flyoutBody.length) {
                config.dropdownParent = $flyoutBody;
            }

            $select.select2(config);

            // Hydrate pre-selected options that need labels
            this.hydrateIfNeeded($select, ajaxUrl, ajaxParams);
        },

        hydrateIfNeeded: function ($select, ajaxUrl, ajaxParams) {
            var $options = $select.find('option[selected], option:selected');
            if (!$options.length) {
                return;
            }

            var needsHydration = [];
            $options.each(function () {
                var text = $.trim($(this).text());
                var val = $(this).val();
                if (val && (text === 'Loading...' || text === '' || text === val)) {
                    needsHydration.push(val);
                }
            });

            if (!needsHydration.length) {
                return;
            }

            // Build hydration URL with params
            var params = $.extend({}, ajaxParams, {
                include: needsHydration.join(',')
            });

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: params,
                headers: {
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                success: function (response) {
                    if (response.success && Array.isArray(response.results)) {
                        response.results.forEach(function (item) {
                            var id = String(item.id || item.value);
                            var $option = $select.find('option[value="' + id + '"]');
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