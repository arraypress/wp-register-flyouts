# Card Choice

Visual card selection (radio or checkbox).

```php
'shipping_method' => [
    'type'    => 'card_choice',
    'name'    => 'shipping',
    'mode'    => 'radio',                    // 'radio' (single) or 'checkbox' (multiple)
    'columns' => 2,
    'value'   => 'standard',                 // Pre-selected value
    'options' => [
        'standard' => [
            'title'       => 'Standard Shipping',
            'description' => '5-7 business days',
            'icon'        => 'car',
        ],
        'express' => [
            'title'       => 'Express Shipping',
            'description' => '2-3 business days',
            'icon'        => 'airplane',
        ],
    ],
],
```

For checkbox mode, `value` should be an array.
