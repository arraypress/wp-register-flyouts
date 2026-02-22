# Conditional Fields

Show/hide fields based on other field values. Fields with dependencies start hidden and are shown/hidden by JavaScript
based on the current values of their dependent fields. All conditions use AND logic — every condition must be met for
the field to show.

## Simple Truthy Check

The simplest form — show a field when another field has any truthy value (checked, non-empty, non-zero).

```php
'extra_field' => [
    'type'    => 'text',
    'label'   => 'Extra Field',
    'depends' => 'has_discount',
],
```

## Simple Key → Value

The most common format. Pass one or more field/value pairs — all must match.

```php
// Single condition
'notification_email' => [
    'type'    => 'email',
    'label'   => 'Notification Email',
    'depends' => [
        'enable_notifications' => 1,
    ],
],

// Multiple conditions (AND logic)
'discount_percent' => [
    'type'  => 'number',
    'label' => 'Discount %',
    'depends' => [
        'enable_discounts' => 1,
        'discount_type'    => 'percentage',
    ],
],
```

## Value Match with Array (IN)

When the value is an array, the field shows if the current value matches any of the provided values.

```php
'priority_support_note' => [
    'type'    => 'message',
    'content' => 'Priority support is included with your plan.',
    'depends' => [
        'license_type' => [ 'business', 'enterprise' ],
    ],
],
```

## Single Condition with Operator

For comparisons beyond simple equality, use the explicit format with an operator.

```php
'discount_amount' => [
    'type'  => 'number',
    'label' => 'Discount Amount',
    'depends' => [
        'field'    => 'discount_type',
        'value'    => 'fixed',
        'operator' => '=',
    ],
],

'free_shipping_notice' => [
    'type'    => 'message',
    'content' => 'Free shipping applies to this order.',
    'depends' => [
        'field'    => 'order_total',
        'value'    => 50,
        'operator' => '>=',
    ],
],
```

## Multiple Conditions with Operators (AND)

Pass an array of condition arrays — all must be met.

```php
'refund_message' => [
    'type'  => 'textarea',
    'label' => 'Refund Message',
    'depends' => [
        [
            'field'    => 'enable_refunds',
            'value'    => 1,
            'operator' => '=',
        ],
        [
            'field'    => 'order_total',
            'value'    => 100,
            'operator' => '>=',
        ],
        [
            'field'    => 'order_status',
            'value'    => [ 'completed', 'processing' ],
            'operator' => 'in',
        ],
    ],
],
```

## Contains Check

Show a field when a multi-value field (e.g. checkbox group) contains a specific value.

```php
'api_key' => [
    'type'  => 'text',
    'label' => 'API Key',
    'depends' => [
        'field'    => 'features',
        'value'    => 'api',
        'operator' => 'contains',
    ],
],

// Legacy shorthand (still supported)
'premium_options' => [
    'type'  => 'text',
    'label' => 'Premium Options',
    'depends' => [
        'field'    => 'features',
        'contains' => 'premium',
    ],
],
```

## Empty / Not Empty

Show fields based on whether another field has any value.

```php
'scaling_warning' => [
    'type'    => 'message',
    'content' => 'Consider upgrading for better performance.',
    'depends' => [
        'field'    => 'max_users',
        'value'    => '',
        'operator' => 'not_empty',
    ],
],

'setup_prompt' => [
    'type'    => 'message',
    'content' => 'Please configure your API key above.',
    'depends' => [
        'field'    => 'api_key',
        'value'    => '',
        'operator' => 'empty',
    ],
],
```

## Available Operators

| Operator       | Description                                                        |
|----------------|--------------------------------------------------------------------|
| `=` / `==`     | Loose equality                                                     |
| `===`          | Strict equality                                                    |
| `!=` / `!==`   | Not equal                                                          |
| `>`            | Greater than                                                       |
| `>=`           | Greater than or equal                                              |
| `<`            | Less than                                                          |
| `<=`           | Less than or equal                                                 |
| `in`           | Value matches any in array                                         |
| `not_in`       | Value matches none in array                                        |
| `contains`     | Array contains value, or string contains substring                 |
| `not_contains` | Array does not contain value, or string does not contain substring |
| `empty`        | Value is empty, blank, or zero                                     |
| `not_empty`    | Value is not empty                                                 |

## Complete Example

```php
'fields' => [
    'has_discount' => [
        'type'  => 'toggle',
        'label' => 'Apply Discount',
    ],
    'discount_type' => [
        'type'    => 'select',
        'label'   => 'Discount Type',
        'options' => [
            'percentage' => 'Percentage',
            'fixed'      => 'Fixed Amount',
        ],
        'depends' => [
            'has_discount' => 1,
        ],
    ],
    'discount_percent' => [
        'type'  => 'number',
        'label' => 'Discount %',
        'min'   => 0,
        'max'   => 100,
        'depends' => [
            'has_discount'  => 1,
            'discount_type' => 'percentage',
        ],
    ],
    'discount_amount' => [
        'type'  => 'number',
        'label' => 'Discount Amount',
        'min'   => 0,
        'depends' => [
            'has_discount'  => 1,
            'discount_type' => 'fixed',
        ],
    ],
    'large_discount_warning' => [
        'type'    => 'message',
        'content' => 'Discounts over 50% require manager approval.',
        'depends' => [
            [
                'field'    => 'has_discount',
                'value'    => 1,
                'operator' => '=',
            ],
            [
                'field'    => 'discount_percent',
                'value'    => 50,
                'operator' => '>',
            ],
        ],
    ],
],
```