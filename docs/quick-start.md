# Quick Start

Register a flyout, then render a button to open it:

```php
// Register a flyout
register_flyout( 'shop_edit_product', [
    'title'  => 'Edit Product',
    'fields' => [
        'name' => [
            'type'  => 'text',
            'label' => 'Product Name',
        ],
        'price' => [
            'type'  => 'number',
            'label' => 'Price',
            'min'   => 0,
            'step'  => 0.01,
        ],
    ],
    'load' => function ( $id ) {
        return get_post( $id );
    },
    'save' => function ( $id, $data ) {
        return wp_update_post( [
            'ID'         => $id,
            'post_title' => $data['name'],
        ] );
    },
] );

// Render a button to open it
render_flyout_button( 'shop_edit_product', [
    'id'   => $product_id,
    'text' => 'Edit',
] );
```
