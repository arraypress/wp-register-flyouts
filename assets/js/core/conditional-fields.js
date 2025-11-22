/**
 * Conditional Fields Core JavaScript
 *
 * Core functionality for dynamic field visibility based on dependencies.
 * Automatically handles toggle checks, value matching, and contains operations.
 *
 * @package     ArrayPress\WPFlyout
 * @subpackage  Core
 * @version     1.0.0
 * @author      David Sherlock
 */

(function ($) {
    'use strict';

    /**
     * Conditional Fields Handler
     *
     * Core flyout functionality for managing field dependencies and visibility.
     *
     * @namespace WPFlyout.ConditionalFields
     * @memberof  WPFlyout
     * @since     1.0.0
     */
    window.WPFlyout = window.WPFlyout || {};

    WPFlyout.ConditionalFields = {

        /**
         * Field dependency configurations
         *
         * @since 1.0.0
         * @type {Object}
         */
        dependencies: {},

        /**
         * Initialize conditional fields
         *
         * Automatically called when DOM is ready.
         * Scans for fields with dependencies and sets up listeners.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            const self = this;

            // Find all fields with data-depends attribute
            $('[data-depends]').each(function () {
                self.registerField($(this));
            });

            // Bind change listeners using delegation
            $(document).on('change.wpflyout.conditional',
                'input, select, textarea',
                this.handleFieldChange.bind(this)
            );

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                self.initializeFlyout($(data.element));
            });

            // Initial evaluation
            this.evaluateAll();
        },

        /**
         * Initialize conditional fields within a flyout
         *
         * Called when a flyout opens to register any new conditional fields.
         *
         * @since 1.0.0
         * @param {jQuery} $flyout Flyout element
         * @return {void}
         */
        initializeFlyout: function ($flyout) {
            const self = this;

            $flyout.find('[data-depends]').each(function () {
                self.registerField($(this));
            });

            // Trigger evaluation for this flyout's fields
            setTimeout(() => {
                self.evaluateAll($flyout);
            }, 50); // Small delay to ensure form values are set
        },

        /**
         * Register a dependent field
         *
         * Parses the dependency configuration and stores it for evaluation.
         *
         * @since 1.0.0
         * @param {jQuery} $field Field wrapper element
         * @return {void}
         */
        registerField: function ($field) {
            const dependsData = $field.data('depends');
            const fieldId = $field.attr('id') ||
                $field.find('input, select, textarea').first().attr('name');

            if (!fieldId || !dependsData) {
                return;
            }

            // Parse dependency configuration
            const dependency = this.parseDependency(dependsData);
            if (dependency) {
                this.dependencies[fieldId] = {
                    element: $field,
                    config: dependency
                };

                // Log for debugging
                if (window.WPFlyout.debug) {
                    console.log('Registered conditional field:', fieldId, dependency);
                }
            }
        },

        /**
         * Parse dependency configuration from data attribute
         *
         * Handles three formats:
         * - String: "field_name" (truthy check)
         * - Object with value: {field: "name", value: "x"} (equals check)
         * - Object with contains: {field: "name", contains: "x"} (array contains)
         *
         * @since 1.0.0
         * @param {string|Object} data Dependency data
         * @return {Object|null} Parsed dependency configuration
         */
        parseDependency: function (data) {
            // Handle string format: "field_name"
            if (typeof data === 'string') {
                return {
                    type: 'truthy',
                    field: data
                };
            }

            // Handle object format
            if (typeof data === 'object' && data !== null) {
                // Determine dependency type
                if (data.contains !== undefined) {
                    return {
                        type: 'contains',
                        field: data.field,
                        value: data.contains
                    };
                } else if (data.value !== undefined) {
                    return {
                        type: 'equals',
                        field: data.field,
                        value: data.value
                    };
                }
            }

            return null;
        },

        /**
         * Handle field change event
         *
         * Triggered when any form field changes to re-evaluate dependencies.
         *
         * @since 1.0.0
         * @param {Event} e Change event
         * @return {void}
         */
        handleFieldChange: function (e) {
            const $changed = $(e.target);
            const name = $changed.attr('name');

            if (!name) {
                return;
            }

            // Clean array notation from name
            const cleanName = name.replace(/\[\]$/, '');

            // Check all dependencies
            this.evaluateDependents(cleanName);
        },

        /**
         * Evaluate fields dependent on a specific field
         *
         * @since 1.0.0
         * @param {string} fieldName Name of field that changed
         * @return {void}
         */
        evaluateDependents: function (fieldName) {
            const self = this;

            Object.keys(this.dependencies).forEach(function (key) {
                const dep = self.dependencies[key];
                if (dep.config.field === fieldName) {
                    self.evaluateField(key);
                }
            });
        },

        /**
         * Evaluate all registered fields
         *
         * @since 1.0.0
         * @param {jQuery} $context Optional context to limit evaluation
         * @return {void}
         */
        evaluateAll: function ($context) {
            const self = this;

            Object.keys(this.dependencies).forEach(function (key) {
                const dep = self.dependencies[key];

                // Skip if context provided and field not within context
                if ($context && !$context.find(dep.element).length && !$context.is(dep.element)) {
                    return;
                }

                self.evaluateField(key);
            });
        },

        /**
         * Evaluate a single field's visibility
         *
         * @since 1.0.0
         * @param {string} fieldId Field identifier
         * @return {void}
         */
        evaluateField: function (fieldId) {
            const dep = this.dependencies[fieldId];
            if (!dep) {
                return;
            }

            const shouldShow = this.checkCondition(dep.config);
            this.toggleField(dep.element, shouldShow);

            // Log for debugging
            if (window.WPFlyout.debug) {
                console.log('Evaluated field:', fieldId, 'Show:', shouldShow);
            }
        },

        /**
         * Check if condition is met
         *
         * @since 1.0.0
         * @param {Object} config Dependency configuration
         * @return {boolean} True if condition is met
         */
        checkCondition: function (config) {
            const value = this.getFieldValue(config.field);

            switch (config.type) {
                case 'truthy':
                    return this.isTruthy(value);

                case 'equals':
                    return this.checkEquals(value, config.value);

                case 'contains':
                    return this.checkContains(value, config.value);

                default:
                    return false;
            }
        },

        /**
         * Get field value
         *
         * Handles different input types and returns appropriate value.
         *
         * @since 1.0.0
         * @param {string} fieldName Field name
         * @return {*} Field value
         */
        getFieldValue: function (fieldName) {
            // Try different selectors
            let $field = $(`[name="${fieldName}"]`);

            if (!$field.length) {
                $field = $(`[name="${fieldName}[]"]`);
            }

            if (!$field.length) {
                $field = $(`#${fieldName}`);
            }

            if (!$field.length) {
                return null;
            }

            // Handle different input types
            const type = $field.attr('type') || $field.prop('tagName').toLowerCase();

            if (type === 'checkbox') {
                if ($field.length > 1) {
                    // Multiple checkboxes - return array of checked values
                    const values = [];
                    $field.filter(':checked').each(function () {
                        values.push($(this).val());
                    });
                    return values;
                } else {
                    // Single checkbox (toggle)
                    return $field.is(':checked');
                }
            } else if (type === 'radio') {
                return $field.filter(':checked').val();
            } else {
                return $field.val();
            }
        },

        /**
         * Check if value is truthy
         *
         * @since 1.0.0
         * @param {*} value Value to check
         * @return {boolean} True if truthy
         */
        isTruthy: function (value) {
            if (value === null || value === undefined) {
                return false;
            }

            if (typeof value === 'boolean') {
                return value;
            }

            if (typeof value === 'string') {
                return value !== '' && value !== '0' && value !== 'false';
            }

            if (Array.isArray(value)) {
                return value.length > 0;
            }

            return !!value;
        },

        /**
         * Check if values are equal
         *
         * Handles single value and array of values (IN operation).
         *
         * @since 1.0.0
         * @param {*} value Field value
         * @param {*} expected Expected value(s)
         * @return {boolean} True if equal
         */
        checkEquals: function (value, expected) {
            // Handle array of expected values (IN operation)
            if (Array.isArray(expected)) {
                return expected.includes(value) ||
                    expected.includes(String(value));
            }

            // Direct comparison (with type coercion for string/number)
            return value === expected ||
                value == expected ||
                String(value) === String(expected);
        },

        /**
         * Check if array contains value
         *
         * @since 1.0.0
         * @param {*} value Field value (should be array)
         * @param {*} search Value to search for
         * @return {boolean} True if contains
         */
        checkContains: function (value, search) {
            if (!Array.isArray(value)) {
                return false;
            }

            return value.includes(search) ||
                value.includes(String(search));
        },

        /**
         * Toggle field visibility
         *
         * Shows or hides field with animation and manages disabled state.
         *
         * @since 1.0.0
         * @param {jQuery} $field Field element
         * @param {boolean} show Whether to show or hide
         * @return {void}
         */
        toggleField: function ($field, show) {
            const isVisible = $field.is(':visible');

            if (show && !isVisible) {
                $field.slideDown(200, function () {
                    // Enable inputs when shown
                    $field.find('input, select, textarea')
                        .prop('disabled', false)
                        .removeClass('conditional-disabled');

                    // Trigger event
                    $field.trigger('conditional:shown');
                    $(document).trigger('wpflyout:conditional:shown', [$field]);
                });
            } else if (!show && isVisible) {
                $field.slideUp(200, function () {
                    // Disable inputs when hidden to prevent submission
                    $field.find('input, select, textarea')
                        .prop('disabled', true)
                        .addClass('conditional-disabled');

                    // Clear values for certain field types
                    $field.find('input[type="text"], input[type="email"], input[type="url"], textarea')
                        .val('');
                    $field.find('input[type="checkbox"], input[type="radio"]')
                        .prop('checked', false);

                    // Trigger event
                    $field.trigger('conditional:hidden');
                    $(document).trigger('wpflyout:conditional:hidden', [$field]);
                });
            }
        },

        /**
         * Manually trigger evaluation of a specific field
         *
         * Public API method for external use.
         *
         * @since 1.0.0
         * @param {string} fieldId Field identifier to evaluate
         * @return {void}
         */
        evaluate: function (fieldId) {
            this.evaluateField(fieldId);
        },

        /**
         * Refresh all conditional fields
         *
         * Public API method to re-evaluate all fields.
         *
         * @since 1.0.0
         * @return {void}
         */
        refresh: function () {
            this.evaluateAll();
        }
    };

    // Initialize when DOM is ready
    $(function () {
        WPFlyout.ConditionalFields.init();
    });

})(jQuery);