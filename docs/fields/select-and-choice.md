# Select & Choice Fields

## Select & Multi-Select

```php
'status' => [
    'type'        => 'select',
    'label'       => 'Status',
    'placeholder' => 'Select status...',
    'options'     => [
        'draft'     => 'Draft',
        'published' => 'Published',
        'archived'  => 'Archived',
    ],
],

'categories' => [
    'type'     => 'select',
    'label'    => 'Categories',
    'multiple' => true,
    'options'  => [
        'electronics' => 'Electronics',
        'clothing'    => 'Clothing',
        'home'        => 'Home & Garden',
    ],
],
```

## Toggle (Checkbox)

```php
'enabled' => [
    'type'  => 'toggle',
    'label' => 'Enable Feature',
],
```

The toggle renders as a styled switch. The value is `'1'` when checked, `'0'` when not.

## Radio Buttons

```php
'shipping' => [
    'type'    => 'radio',
    'label'   => 'Shipping Method',
    'options' => [
        'standard'  => 'Standard (5-7 days)',
        'express'   => 'Express (2-3 days)',
        'overnight' => 'Overnight',
    ],
],
```

## Date & Color

```php
'start_date' => [
    'type'  => 'date',
    'label' => 'Start Date',
],

'brand_color' => [
    'type'    => 'color',
    'label'   => 'Brand Color',
    'default' => '#3498db',
],
```
