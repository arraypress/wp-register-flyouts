# Unit Input

Numeric input with a unit indicator displayed as a prefix or suffix. Supports fixed units (static text) or selectable units (dropdown). Useful for currency amounts, percentages, weights, dimensions, durations, and more.

## Fixed Unit

```php
'weight' => [
    'type'          => 'unit_input',
    'name'          => 'weight',
    'label'         => 'Weight',
    'description'   => 'Enter the product weight.',
    'value'         => '2.5',
    'placeholder'   => '0.00',
    'inputmode'     => 'decimal',

    // Fixed unit (static text)
    'unit'          => 'kg',
    'unit_position' => 'suffix',             // 'prefix' or 'suffix'
],
```

## Selectable Units

```php
'dimension' => [
    'type'          => 'unit_input',
    'name'          => 'dimension',
    'label'         => 'Length',
    'value'         => '10',
    'units'         => [                     // Multiple units = dropdown
        'cm' => 'cm',
        'in' => 'in',
        'mm' => 'mm',
    ],
    'unit_value'    => 'cm',                 // Currently selected unit
    'unit_position' => 'suffix',
],
```

## Currency Prefix

```php
'fee' => [
    'type'          => 'unit_input',
    'name'          => 'fee',
    'label'         => 'Fee',
    'value'         => '9.99',
    'unit'          => '$',
    'unit_position' => 'prefix',
],
```

## Behavior

**Fixed vs selectable:** When `unit` is a string or `units` has exactly one entry, a static label is rendered. When `units` has multiple entries, a `<select>` dropdown is rendered.

**Unit form name:** The selected unit value is submitted as a separate field. By default the name is `{name}_unit` (e.g. `dimension_unit`), configurable via `unit_name`.

**Saved data:** The numeric value is saved under the field name. The unit selection is saved as a separate field (`{name}_unit`).
