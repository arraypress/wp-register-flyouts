# Other Fields

## Hidden Fields

```php
'record_id' => [
    'type'  => 'hidden',
    'value' => $record_id,
],
```

## Field Groups

Group related fields together with optional layout control:

```php
'address' => [
    'type'   => 'group',
    'label'  => 'Address',
    'layout' => 'horizontal',          // 'horizontal' or 'block' (default)
    'gap'    => '10px',                // Gap between fields (horizontal layout)
    'fields' => [
        'street' => [
            'type'  => 'text',
            'label' => 'Street',
            'flex'  => 2,              // Relative width in horizontal layout
        ],
        'city' => [
            'type'  => 'text',
            'label' => 'City',
            'flex'  => 1,
        ],
        'zip' => [
            'type'  => 'text',
            'label' => 'ZIP Code',
            'flex'  => 1,
        ],
    ],
],
```
