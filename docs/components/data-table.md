# Data Table

Display tabular data.

```php
'order_items' => [
    'type'       => 'data_table',
    'columns'    => [
        'name'  => [ 'label' => 'Product', 'width' => '50%' ],
        'qty'   => 'Quantity',                           // Simple string label
        'price' => [
            'label'    => 'Price',
            'class'    => 'text-right',
            'callback' => function ( $value, $row ) {    // Custom cell renderer
                return '$' . number_format( $value, 2 );
            },
        ],
    ],
    'data' => [
        [ 'name' => 'Widget', 'qty' => 2, 'price' => 10.00 ],
        [ 'name' => 'Gadget', 'qty' => 1, 'price' => 25.00 ],
    ],
    'empty_text'  => 'No items found',
    'empty_value' => '—',                                // Shown for empty cell values
],
```
