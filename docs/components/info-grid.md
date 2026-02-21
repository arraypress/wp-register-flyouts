# Info Grid

Display label/value pairs in a grid.

```php
'customer_info' => [
    'type'    => 'info_grid',
    'columns' => 2,
    'items'   => [
        [ 'label' => 'Name', 'value' => 'John Doe' ],
        [ 'label' => 'Email', 'value' => 'john@example.com' ],
        [ 'label' => 'Phone', 'value' => '555-1234' ],
        [ 'label' => 'Company', 'value' => '' ],         // Shows '—' for empty
    ],
],
```
