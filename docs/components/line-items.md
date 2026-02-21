# Line Items

Add/remove line items with AJAX product search. Uses Select2 for the search dropdown with two separate callbacks: one for searching products and one for fetching full product details when selected.
```php
'order_items' => [
    'type'            => 'line_items',
    'name'            => 'items',
    'currency'        => 'USD',
    'show_quantity'   => true,
    'placeholder'     => 'Search products...',
    'empty_text'      => 'No items added',
    'add_text'        => 'Add Item',

    // Called when user searches for products — receives $_POST array
    // Must return [ { value, text }, ... ] format for the dropdown
    'search_callback' => function ( $post_data ) {
        $term = sanitize_text_field( $post_data['search'] ?? '' );
        $products = search_products( $term );
        return array_map( fn( $p ) => [
            'value' => $p->id,
            'text'  => $p->name,
        ], $products );
    },

    // Called when a product is selected, to get full details for display
    // Must return { id, name, price, thumbnail } — price in cents
    'details_callback' => function ( $post_data ) {
        $product = get_product( absint( $post_data['id'] ?? 0 ) );
        if ( ! $product ) {
            return new WP_Error( 'not_found', 'Product not found' );
        }
        return [
            'id'        => $product->id,
            'name'      => $product->name,
            'price'     => $product->price,          // In cents
            'thumbnail' => $product->image_url,
        ];
    },
],
```

## Data Format

The `items` array (populated from load data) should contain:
```php
[
    [
        'id'        => 1,
        'name'      => 'Widget',
        'price'     => 1000,                 // In cents
        'quantity'  => 2,
        'thumbnail' => 'https://...',        // Optional
    ],
]
```

## Saved Data Shape

Only `id` and `quantity` are persisted. The save callback should look up the
authoritative price from the database rather than trusting client-submitted values.
```php
[
    'items' => [
        [ 'id' => 1, 'quantity' => 2 ],
        [ 'id' => 5, 'quantity' => 1 ],
    ],
]
```

> **Note:** The `name` attribute (`'items'` in this example) is used as the data key for both saving and loading. The field's array key (`order_items`) can be different — data resolution uses `name` for lookup.

> **Note:** This component is intended for order creation only (e.g. manual admin orders). For displaying existing order items, use the Price Summary component instead.
