# Conditional Fields

Show/hide fields based on other field values. The JavaScript handles real-time visibility toggling.

## Simple Value Match

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
            'field' => 'has_discount',
            'value' => true,
        ],
    ],
    'discount_percent' => [
        'type'  => 'number',
        'label' => 'Discount %',
        'min'   => 0,
        'max'   => 100,
        'depends' => [
            'field' => 'discount_type',
            'value' => 'percentage',
        ],
    ],
    'discount_amount' => [
        'type'  => 'number',
        'label' => 'Discount Amount',
        'min'   => 0,
        'depends' => [
            'field' => 'discount_type',
            'value' => 'fixed',
        ],
    ],
],
```

## Simple Truthy Check

```php
'extra_field' => [
    'type'    => 'text',
    'label'   => 'Extra Field',
    'depends' => 'has_discount',             // Show when 'has_discount' is truthy
],
```

## Contains Check

```php
'premium_options' => [
    'type'    => 'text',
    'label'   => 'Premium Options',
    'depends' => [
        'field'    => 'features',
        'contains' => 'premium',             // Show when 'features' contains 'premium'
    ],
],
```

Fields with dependencies start hidden and are shown/hidden by JavaScript based on the current values of their dependent fields.
