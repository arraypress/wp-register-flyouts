# Tabbed Interface

Use `tabs` to organize fields into tabbed sections. Fields are assigned to tabs via the `tab` key:

```php
register_flyout( 'shop_edit_product', [
    'title' => 'Edit Product',

    'tabs' => [
        'general'  => [ 'label' => 'General' ],
        'pricing'  => [ 'label' => 'Pricing' ],
        'advanced' => [ 'label' => 'Advanced' ],
    ],

    'fields' => [
        // General tab fields
        'name' => [
            'type'  => 'text',
            'label' => 'Product Name',
            'tab'   => 'general',
        ],
        'description' => [
            'type'  => 'textarea',
            'label' => 'Description',
            'tab'   => 'general',
        ],

        // Pricing tab fields
        'price' => [
            'type'  => 'number',
            'label' => 'Price',
            'tab'   => 'pricing',
        ],
        'sale_price' => [
            'type'  => 'number',
            'label' => 'Sale Price',
            'tab'   => 'pricing',
        ],

        // Advanced tab fields
        'slug' => [
            'type'  => 'text',
            'label' => 'URL Slug',
            'tab'   => 'advanced',
        ],
    ],

    'load' => fn( $id ) => get_product( $id ),
    'save' => fn( $id, $data ) => save_product( $id, $data ),
] );
```

The first tab is automatically set as active.
