/**
 * Conditional Fields Core JavaScript
 *
 * Core functionality for dynamic field visibility based on dependencies.
 * Supports the full operator set matching the settings library:
 * =, ==, ===, !=, !==, >, >=, <, <=, in, not_in, contains, not_contains, empty, not_empty
 *
 * Also supports legacy data-depends format for backwards compatibility.
 *
 * @package     ArrayPress\WPFlyout
 * @subpackage  Core
 * @version     2.0.0
 * @author      David Sherlock
 */

(function ($) {
    'use strict';

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
         * Scans for fields with data-conditions (preferred) or data-depends (legacy)
         * attributes and sets up change listeners.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            const self = this;

            // Register fields with data-conditions (matches settings library)
            $('[data-conditions]').each(function () {
                self.registerConditionsField($(this));
            });

            // Register legacy data-depends fields for backwards compatibility
            $('[data-depends]').each(function () {
                // Skip if already registered via data-conditions
                if ($(this).data('conditions')) {
                    return;
                }
                self.registerDependsField($(this));
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

            $flyout.find('[data-conditions]').each(function () {
                self.registerConditionsField($(this));
            });

            $flyout.find('[data-depends]').each(function () {
                if ($(this).data('conditions')) {
                    return;
                }
                self.registerDependsField($(this));
            });

            // Trigger evaluation for this flyout's fields
            setTimeout(() => {
                self.evaluateAll($flyout);
            }, 50);
        },

        // =====================================================================
        // FIELD REGISTRATION
        // =====================================================================

        /**
         * Register a field using data-conditions format (settings library format).
         *
         * Expects an array of condition objects:
         * [{ field: "name", value: "x", operator: "=" }, ...]
         *
         * @since 2.0.0
         * @param {jQuery} $field Field wrapper element
         * @return {void}
         */
        registerConditionsField: function ($field) {
            const fieldId = this.getFieldId($field);
            if (!fieldId) {
                return;
            }

            const conditions = $field.data('conditions');
            if (!conditions || !Array.isArray(conditions) || !conditions.length) {
                return;
            }

            this.dependencies[fieldId] = {
                element: $field,
                conditions: conditions
            };

            if (window.WPFlyout.debug) {
                console.log('Registered conditional field (conditions):', fieldId, conditions);
            }
        },

        /**
         * Register a field using legacy data-depends format.
         *
         * Converts to the normalized conditions format internally.
         * Supports:
         * - String: "field_name" (truthy check)
         * - Object with value: {field: "name", value: "x"}
         * - Object with contains: {field: "name", contains: "x"}
         *
         * @since 1.0.0
         * @param {jQuery} $field Field wrapper element
         * @return {void}
         */
        registerDependsField: function ($field) {
            const fieldId = this.getFieldId($field);
            if (!fieldId) {
                return;
            }

            const dependsData = $field.data('depends');
            if (!dependsData) {
                return;
            }

            // Convert legacy format to conditions array
            const conditions = this.convertDependsToConditions(dependsData);
            if (!conditions || !conditions.length) {
                return;
            }

            this.dependencies[fieldId] = {
                element: $field,
                conditions: conditions
            };

            if (window.WPFlyout.debug) {
                console.log('Registered conditional field (legacy depends):', fieldId, conditions);
            }
        },

        /**
         * Get a unique identifier for a field element.
         *
         * @since 2.0.0
         * @param {jQuery} $field Field wrapper element
         * @return {string|null} Field identifier or null
         */
        getFieldId: function ($field) {
            return $field.attr('id') ||
                $field.find('input, select, textarea').first().attr('name') ||
                null;
        },

        /**
         * Convert legacy data-depends format to normalized conditions array.
         *
         * @since 2.0.0
         * @param {string|Object} data Legacy dependency data
         * @return {Array} Normalized conditions array
         */
        convertDependsToConditions: function (data) {
            // String format: "field_name" → truthy check
            if (typeof data === 'string') {
                return [{
                    field: data,
                    value: '',
                    operator: 'not_empty'
                }];
            }

            if (typeof data === 'object' && data !== null) {
                // Contains format
                if (data.contains !== undefined) {
                    return [{
                        field: data.field,
                        value: data.contains,
                        operator: 'contains'
                    }];
                }

                // Value format
                if (data.value !== undefined) {
                    return [{
                        field: data.field,
                        value: data.value,
                        operator: '='
                    }];
                }
            }

            return [];
        },

        // =====================================================================
        // CHANGE HANDLING
        // =====================================================================

        /**
         * Handle field change event
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
                const referencesField = dep.conditions.some(function (c) {
                    return c.field === fieldName;
                });

                if (referencesField) {
                    self.evaluateField(key);
                }
            });
        },

        // =====================================================================
        // EVALUATION
        // =====================================================================

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

                if ($context && !$context.find(dep.element).length && !$context.is(dep.element)) {
                    return;
                }

                self.evaluateField(key);
            });
        },

        /**
         * Evaluate a single field's visibility
         *
         * All conditions must be met (AND logic).
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

            const self = this;
            let allMet = true;

            dep.conditions.forEach(function (condition) {
                const currentValue = self.getFieldValue(condition.field);

                if (!self.checkCondition(currentValue, condition.value, condition.operator)) {
                    allMet = false;
                }
            });

            this.toggleField(dep.element, allMet);

            if (window.WPFlyout.debug) {
                console.log('Evaluated field:', fieldId, 'Show:', allMet);
            }
        },

        // =====================================================================
        // CONDITION CHECKING
        // =====================================================================

        /**
         * Check if a condition is met between current and expected values.
         *
         * Supports operators: =, ==, ===, !=, !==, >, >=, <, <=,
         * in, not_in, contains, not_contains, empty, not_empty.
         *
         * @since 2.0.0
         * @param {*}      current  The current field value.
         * @param {*}      expected The expected value from the condition.
         * @param {string} operator The comparison operator.
         * @return {boolean} Whether the condition is met.
         */
        checkCondition: function (current, expected, operator) {
            switch (operator) {
                case '=':
                case '==':
                    return current == expected;

                case '===':
                    return current === expected;

                case '!=':
                case '!==':
                    return current != expected;

                case '>':
                    return parseFloat(current) > parseFloat(expected);

                case '>=':
                    return parseFloat(current) >= parseFloat(expected);

                case '<':
                    return parseFloat(current) < parseFloat(expected);

                case '<=':
                    return parseFloat(current) <= parseFloat(expected);

                case 'in':
                    expected = Array.isArray(expected) ? expected : [expected];
                    return expected.indexOf(current) !== -1 ||
                        expected.indexOf(String(current)) !== -1;

                case 'not_in':
                    expected = Array.isArray(expected) ? expected : [expected];
                    return expected.indexOf(current) === -1 &&
                        expected.indexOf(String(current)) === -1;

                case 'contains':
                    if (Array.isArray(current)) {
                        return current.indexOf(expected) !== -1 ||
                            current.indexOf(String(expected)) !== -1;
                    }
                    return String(current).indexOf(String(expected)) !== -1;

                case 'not_contains':
                    if (Array.isArray(current)) {
                        return current.indexOf(expected) === -1 &&
                            current.indexOf(String(expected)) === -1;
                    }
                    return String(current).indexOf(String(expected)) === -1;

                case 'empty':
                    return !current ||
                        current === '' ||
                        current === '0' ||
                        (Array.isArray(current) && current.length === 0);

                case 'not_empty':
                    return current &&
                        current !== '' &&
                        current !== '0' &&
                        (!Array.isArray(current) || current.length > 0);

                default:
                    return current == expected;
            }
        },

        // =====================================================================
        // FIELD VALUE RESOLUTION
        // =====================================================================

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
            let $field = $('[name="' + fieldName + '"]');

            if (!$field.length) {
                $field = $('[name="' + fieldName + '[]"]');
            }

            if (!$field.length) {
                $field = $('#' + fieldName);
            }

            if (!$field.length) {
                return null;
            }

            const type = $field.attr('type') || $field.prop('tagName').toLowerCase();

            if (type === 'checkbox') {
                if ($field.length > 1) {
                    const values = [];
                    $field.filter(':checked').each(function () {
                        values.push($(this).val());
                    });
                    return values;
                } else {
                    return $field.is(':checked');
                }
            } else if (type === 'radio') {
                return $field.filter(':checked').val();
            } else {
                return $field.val();
            }
        },

        // =====================================================================
        // VISIBILITY TOGGLING
        // =====================================================================

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
                    $field.find('input, select, textarea')
                        .prop('disabled', false)
                        .removeClass('conditional-disabled');

                    $field.trigger('conditional:shown');
                    $(document).trigger('wpflyout:conditional:shown', [$field]);
                });
            } else if (!show && isVisible) {
                $field.slideUp(200, function () {
                    $field.find('input, select, textarea')
                        .prop('disabled', true)
                        .addClass('conditional-disabled');

                    $field.find('input[type="text"], input[type="email"], input[type="url"], textarea')
                        .val('');
                    $field.find('input[type="checkbox"], input[type="radio"]')
                        .prop('checked', false);

                    $field.trigger('conditional:hidden');
                    $(document).trigger('wpflyout:conditional:hidden', [$field]);
                });
            }
        },

        // =====================================================================
        // PUBLIC API
        // =====================================================================

        /**
         * Manually trigger evaluation of a specific field
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