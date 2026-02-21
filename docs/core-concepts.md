# Core Concepts

## Flyout ID Format

Flyout IDs use a `prefix_name` format. The prefix groups related flyouts and is used for asset loading and namespacing:

```php
register_flyout( 'shop_edit_product', [...] );  // prefix: "shop", name: "edit_product"
register_flyout( 'shop_view_order', [...] );    // prefix: "shop", name: "view_order"
```

If a single-word ID is provided (no underscore), the prefix equals the ID and the flyout name becomes `'default'`.

## Data Flow

1. User clicks a trigger button/link with an ID
2. Flyout opens and calls your `load` callback with that ID
3. Data is automatically mapped to fields via the data resolution system
4. On save, your `save` callback receives the ID and sanitized form data

## Data Resolution Order

When populating fields from the object returned by `load`, values are resolved in this order:

1. **`{field}_data()` method** — Most explicit, returns complete data for a field
2. **Array key access** — `$data['field']`
3. **`get_{field}()` getter** — `$data->get_field()`

**Important:** When a field has a `name` attribute that differs from its array key, the `name` is used for data resolution. For example, a field registered as `order_items` with `'name' => 'items'` will look up `$data['items']`, not `$data['order_items']`.

Example:

```php
class Product {
    public $name = 'Widget';
    private $price = 9900;

    // Method 1: Explicit data method (highest priority)
    public function description_data() {
        return 'Product description here';
    }

    // Method 2: Getter
    public function get_price() {
        return $this->price / 100;
    }
}

register_flyout( 'shop_edit_product', [
    'fields' => [
        'name'        => [ 'type' => 'text', 'label' => 'Name' ],
        'description' => [ 'type' => 'textarea', 'label' => 'Description' ],
        'price'       => [ 'type' => 'number', 'label' => 'Price' ],
    ],
    'load' => fn( $id ) => new Product( $id ),
] );
```
