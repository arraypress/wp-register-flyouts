/**
 * WP Flyout Manager
 *
 * Handles flyout loading, saving, and deletion via REST API.
 * Always reloads page after successful save/delete operations.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */
(function ($) {
    'use strict';

    const WPFlyoutManager = {

        /**
         * Initialize manager
         */
        init: function () {
            $(document).on('click', '.wp-flyout-trigger', this.handleTrigger.bind(this));
        },

        /**
         * Make a REST API request
         *
         * @param {string} endpoint REST endpoint path (e.g. '/load')
         * @param {Object} data     Request body data
         * @param {string} method   HTTP method (default: 'POST')
         * @return {Promise} Resolves with parsed JSON response
         */
        api: function (endpoint, data, method) {
            method = method || 'POST';

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                credentials: 'same-origin'
            };

            if (method === 'GET') {
                const params = new URLSearchParams(data);
                endpoint = endpoint + '?' + params.toString();
            } else {
                options.body = JSON.stringify(data);
            }

            return fetch(wpFlyout.restUrl + endpoint, options)
                .then(function (response) {
                    return response.json().then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Request failed');
                        }
                        return json;
                    });
                });
        },

        /**
         * Handle trigger click
         */
        handleTrigger: function (e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var config = this.extractConfig($btn);

            this.loadFlyout(config);
        },

        /**
         * Extract configuration from trigger button
         */
        extractConfig: function ($btn) {
            var config = {
                flyout: $btn.data('flyout'),
                manager: $btn.data('flyout-manager'),
                data: {}
            };

            // Collect additional data attributes
            $.each($btn[0].dataset, function (key, value) {
                if (['flyout', 'flyoutManager'].indexOf(key) === -1) {
                    config.data[key] = value;
                }
            });

            return config;
        },

        /**
         * Load flyout via REST API
         */
        loadFlyout: function (config) {
            var self = this;

            var requestData = {
                manager: config.manager,
                flyout: config.flyout,
                item_id: config.data.id || 0
            };

            // Pass through title/subtitle overrides
            if (config.data.title) {
                requestData.title = config.data.title;
            }
            if (config.data.subtitle) {
                requestData.subtitle = config.data.subtitle;
            }

            this.api('/load', requestData)
                .then(function (response) {
                    if (response.success) {
                        self.displayFlyout(response.html, config);
                    } else {
                        alert(response.message || 'Failed to load flyout');
                    }
                })
                .catch(function (error) {
                    alert(error.message || 'Failed to load flyout');
                });
        },

        /**
         * Display flyout and setup handlers
         */
        displayFlyout: function (html, config) {
            // Remove existing flyouts and add new one
            $('.wp-flyout').remove();
            $('body').append(html);

            var $flyout = $('.wp-flyout').last();
            var flyoutId = $flyout.attr('id');

            // Open it
            WPFlyout.open(flyoutId);

            // Store config
            $flyout.data(config);

            // Ensure form wrapper exists
            this.ensureForm($flyout);

            // Bind handlers
            this.bindHandlers($flyout, flyoutId, config);
        },

        /**
         * Ensure form wrapper exists
         */
        ensureForm: function ($flyout) {
            if (!$flyout.find('form').length) {
                var $body = $flyout.find('.wp-flyout-body');
                var $form = $('<form class="wp-flyout-form" novalidate></form>');
                $form.append($body.children());
                $body.append($form);
            }
        },

        /**
         * Bind event handlers
         */
        bindHandlers: function ($flyout, flyoutId, config) {
            var self = this;

            // Save button
            $flyout.on('click', '.wp-flyout-save', function (e) {
                e.preventDefault();
                self.handleSave($flyout, flyoutId, config);
            });

            // Delete button
            $flyout.on('click', '.wp-flyout-delete', function (e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this item?')) {
                    self.handleDelete($flyout, flyoutId, config);
                }
            });

            // Close button
            $flyout.on('click', '.wp-flyout-close', function (e) {
                e.preventDefault();
                WPFlyout.close(flyoutId);
            });

            // Clear error class on change
            $flyout.on('input change', '.error', function () {
                $(this).removeClass('error');
            });
        },

        /**
         * Validate form
         */
        validateForm: function ($form) {
            var isValid = true;
            var firstInvalid = null;

            $form.find('[required]:visible:enabled').each(function () {
                var $field = $(this);
                var value = $field.val();

                if (!value || (Array.isArray(value) && !value.length)) {
                    isValid = false;
                    $field.addClass('error');
                    firstInvalid = firstInvalid || $field;
                } else {
                    $field.removeClass('error');
                }
            });

            return { isValid: isValid, firstInvalid: firstInvalid };
        },

        /**
         * Collect form data as a plain object for JSON submission
         *
         * Handles nested field names like files[0][name], metadata[1][key], etc.
         */
        collectFormData: function ($form) {
            var data = {};
            var serialized = $form.serializeArray();

            serialized.forEach(function (item) {
                var name = item.name;

                // Handle array fields: name[] or name[key]
                if (name.indexOf('[') !== -1) {
                    var keys = name.replace(/\]/g, '').split('[');
                    var current = data;

                    for (var i = 0; i < keys.length - 1; i++) {
                        var key = keys[i];
                        if (!current[key]) {
                            var nextKey = keys[i + 1];
                            // Empty next key (name[]) means array, numeric means array, otherwise object
                            current[key] = (nextKey === '' || /^\d+$/.test(nextKey)) ? [] : {};
                        }
                        current = current[key];
                    }

                    var lastKey = keys[keys.length - 1];

                    // Empty last key means append to array (name[])
                    if (lastKey === '') {
                        if (Array.isArray(current)) {
                            current.push(item.value);
                        }
                    } else {
                        current[lastKey] = item.value;
                    }
                } else {
                    // Simple field — handle duplicate names as arrays
                    if (data[name] !== undefined) {
                        if (!Array.isArray(data[name])) {
                            data[name] = [data[name]];
                        }
                        data[name].push(item.value);
                    } else {
                        data[name] = item.value;
                    }
                }
            });

            // Also collect unchecked checkboxes (they don't appear in serializeArray)
            $form.find('input[type="checkbox"]:not(:checked)').each(function () {
                var name = $(this).attr('name');
                if (name && data[name] === undefined) {
                    data[name] = '0';
                }
            });

            return data;
        },

        /**
         * Handle save action
         */
        handleSave: function ($flyout, flyoutId, config) {
            var self = this;
            var $form = $flyout.find('form').first();
            var $saveBtn = $flyout.find('.wp-flyout-save');
            var $body = $flyout.find('.wp-flyout-body');

            // Validate
            var validation = this.validateForm($form);
            if (!validation.isValid) {
                $body.animate({ scrollTop: 0 }, 300);
                this.showAlert($flyout, 'Please fill in all required fields.', 'error');
                if (validation.firstInvalid) {
                    validation.firstInvalid.focus();
                }
                return;
            }

            // Collect form data as object
            var formData = this.collectFormData($form);

            // Save
            this.setButtonState($saveBtn, true, 'Saving...');

            this.api('/save', {
                manager: config.manager,
                flyout: config.flyout,
                item_id: config.data.id || formData.id || 0,
                form_data: formData
            })
                .then(function (response) {
                    self.setButtonState($saveBtn, false);

                    if (response.success) {
                        $body.animate({ scrollTop: 0 }, 300);
                        var message = response.message || 'Saved successfully!';
                        self.showAlert($flyout, message, 'success');

                        // Always close and reload after delay
                        setTimeout(function () {
                            WPFlyout.close(flyoutId);
                            location.reload();
                        }, 1500);
                    } else {
                        $body.animate({ scrollTop: 0 }, 300);
                        self.showAlert($flyout, response.message || 'An error occurred', 'error');
                    }
                })
                .catch(function (error) {
                    self.setButtonState($saveBtn, false);
                    $body.animate({ scrollTop: 0 }, 300);
                    self.showAlert($flyout, error.message || 'An error occurred', 'error');
                });
        },

        /**
         * Handle delete action
         */
        handleDelete: function ($flyout, flyoutId, config) {
            var self = this;
            var $deleteBtn = $flyout.find('.wp-flyout-delete');
            var deleteId = $flyout.find('input[name="id"]').val() || config.data.id;
            var $body = $flyout.find('.wp-flyout-body');

            this.setButtonState($deleteBtn, true, 'Deleting...');

            this.api('/delete', {
                manager: config.manager,
                flyout: config.flyout,
                item_id: deleteId
            })
                .then(function (response) {
                    if (response.success) {
                        var message = response.message || 'Deleted successfully!';
                        self.showAlert($flyout, message, 'success');
                        $body.animate({ scrollTop: 0 }, 300);

                        setTimeout(function () {
                            WPFlyout.close(flyoutId);
                            location.reload();
                        }, 1000);
                    } else {
                        self.setButtonState($deleteBtn, false);
                        self.showAlert($flyout, response.message || 'Failed to delete', 'error');
                        $body.animate({ scrollTop: 0 }, 300);
                    }
                })
                .catch(function (error) {
                    self.setButtonState($deleteBtn, false);
                    $body.animate({ scrollTop: 0 }, 300);
                    self.showAlert($flyout, error.message || 'Failed to delete', 'error');
                });
        },

        /**
         * Show alert message
         */
        showAlert: function ($flyout, message, type) {
            if (window.WPFlyoutAlert) {
                $flyout.find('.wp-flyout-alert').remove();

                WPFlyoutAlert.show(message, type, {
                    target: $flyout.find('.wp-flyout-body'),
                    prepend: true,
                    timeout: type === 'success' ? 3000 : 0,
                    dismissible: true
                });
            }
        },

        /**
         * Set button loading state
         */
        setButtonState: function ($btn, disabled, text) {
            if (!$btn.length) return;

            if (disabled) {
                $btn.data('original-text', $btn.text());
                $btn.prop('disabled', true).text(text);
            } else {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Save');
            }
        }
    };

    // Initialize
    $(document).ready(function () {
        WPFlyoutManager.init();
    });

})(jQuery);