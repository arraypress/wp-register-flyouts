# WP Flyout Library

A WordPress library for creating slide-out panels with forms, data displays, and interactive components. Perfect for
admin interfaces, edit screens, and anywhere you need contextual editing without page reloads.

## Installation

Install via Composer:

```bash
composer require arraypress/wp-register-flyouts
```

## Quick Start

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

## Core Concepts

### Flyout ID Format

Flyout IDs use a `prefix_name` format. The prefix groups related flyouts and is used for asset loading and namespacing:

```php
register_flyout( 'shop_edit_product', [...] );  // prefix: "shop", name: "edit_product"
register_flyout( 'shop_view_order', [...] );    // prefix: "shop", name: "view_order"
```

If a single-word ID is provided (no underscore), the prefix equals the ID and the flyout name becomes `'default'`.

### Data Flow

1. User clicks a trigger button/link with an ID
2. Flyout opens and calls your `load` callback with that ID
3. Data is automatically mapped to fields via the data resolution system
4. On save, your `save` callback receives the ID and sanitized form data

### Data Resolution Order

When populating fields from the object returned by `load`, values are resolved in this order:

1. **`{field}_data()` method** — Most explicit, returns complete data for a field
2. **Array key access** — `$data['field']`
3. **`get_{field}()` getter** — `$data->get_field()`
4. **Direct property** — `$data->field`
5. **`{field}()` method** — `$data->field()`
6. **CamelCase method** — For underscore names: `user_name` → `$data->userName()`

**Important:** When a field has a `name` attribute that differs from its array key, the `name` is used for data
resolution. For example, a field registered as `order_items` with `'name' => 'items'` will look up `$data['items']`,
not `$data['order_items']`.

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

    // Method 3: Direct method
    public function formatted_price() {
        return '$' . number_format( $this->price / 100, 2 );
    }
}

register_flyout( 'shop_edit_product', [
    'fields' => [
        'name'            => [ 'type' => 'text', 'label' => 'Name' ],
        'description'     => [ 'type' => 'textarea', 'label' => 'Description' ],
        'price'           => [ 'type' => 'number', 'label' => 'Price' ],
        'formatted_price' => [ 'type' => 'text', 'label' => 'Display Price', 'readonly' => true ],
    ],
    'load' => fn( $id ) => new Product( $id ),
] );
```

## Registration Options

```php
register_flyout( 'prefix_name', [
    // Basic
    'title'      => 'Flyout Title',
    'subtitle'   => 'Optional subtitle',
    'size'       => 'medium',                    // 'small', 'medium', 'large', 'full'
    'capability' => 'manage_options',            // Required user capability

    // Admin pages where assets should load
    'admin_pages' => [
        'edit.php',
        'toplevel_page_my-plugin',
    ],

    // Fields (see Field Types section)
    'fields' => [...],

    // Callbacks
    'load' => function ( $id ) {
        // Return object/array with data to populate fields
        return get_post( $id );
    },

    'validate' => function ( $data ) {
        // Return true or WP_Error
        if ( empty( $data['name'] ) ) {
            return new WP_Error( 'missing_name', 'Name is required' );
        }
        return true;
    },

    'save' => function ( $id, $data ) {
        // Save and return result
        return update_post_meta( $id, '_data', $data );
    },

    'delete' => function ( $id ) {
        // Optional delete handler
        return wp_delete_post( $id );
    },

    // Footer action buttons (auto-generated if omitted)
    // If 'save' callback exists, a Save button is auto-added
    // If 'delete' callback exists, a Delete button is auto-added
    'actions' => [
        [
            'text'  => 'Save Changes',
            'style' => 'primary',              // primary, secondary, link-delete
            'class' => 'wp-flyout-save',       // Required for save functionality
            'icon'  => '',                     // Optional dashicon name
        ],
        [
            'text'  => 'Delete',
            'style' => 'link-delete',
            'class' => 'wp-flyout-delete',     // Required for delete functionality
        ],
    ],
] );
```

## Tabbed Interface

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

## Trigger Buttons & Links

### Button

```php
// Render directly
render_flyout_button( 'shop_edit_product', [
    'id'       => $product_id,
    'text'     => 'Edit Product',
    'icon'     => 'edit',                    // Dashicon (without 'dashicons-' prefix)
    'class'    => 'button button-primary',
    'title'    => 'Dynamic Title',           // Overrides registered title
    'subtitle' => 'Dynamic Subtitle',        // Overrides registered subtitle
] );

// Or get HTML string
$html = get_flyout_button( 'shop_edit_product', [
    'id'   => $product_id,
    'text' => 'Edit',
] );
```

### Link

```php
// Render directly
render_flyout_link( 'shop_edit_product', [
    'id'    => $product_id,
    'text'  => 'Edit',
    'class' => 'row-action',
] );

// Or get HTML string
$html = get_flyout_link( 'shop_edit_product', [
    'id'   => $product_id,
    'text' => 'Edit',
] );
```

Data attributes: Any key besides `text`, `class`, `icon`, and `target` is passed as a `data-*` attribute on the trigger
element. The `id` attribute is the record identifier passed to the `load` callback. The `title` and `subtitle`
attributes override the registered flyout title/subtitle for that instance.

## WP_List_Table Integration

The most common use case is adding flyout actions to admin list tables:

```php
class Products_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'product',
            'plural'   => 'products',
        ] );

        $this->register_flyouts();
    }

    private function register_flyouts() {
        register_flyout( 'shop_edit_product', [
            'title'       => 'Edit Product',
            'admin_pages' => [ 'toplevel_page_my-products' ],
            'fields'      => [
                'name' => [
                    'type'  => 'text',
                    'label' => 'Product Name',
                ],
                'price' => [
                    'type'  => 'number',
                    'label' => 'Price',
                ],
                'status' => [
                    'type'    => 'select',
                    'label'   => 'Status',
                    'options' => [
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ],
                ],
            ],
            'load' => fn( $id ) => $this->get_product( $id ),
            'save' => fn( $id, $data ) => $this->save_product( $id, $data ),
        ] );

        register_flyout( 'shop_view_product', [
            'title'       => 'Product Details',
            'admin_pages' => [ 'toplevel_page_my-products' ],
            'actions'     => [],                   // No footer buttons (read-only)
            'fields'      => [
                'header' => [
                    'type' => 'header',
                ],
                'details' => [
                    'type'    => 'info_grid',
                    'columns' => 2,
                ],
            ],
            'load' => fn( $id ) => $this->get_product_display_data( $id ),
        ] );
    }

    // Add flyout buttons to row actions
    public function column_name( $item ) {
        $actions = [
            'edit' => get_flyout_link( 'shop_edit_product', [
                'id'   => $item->id,
                'text' => 'Edit',
            ] ),
            'view' => get_flyout_link( 'shop_view_product', [
                'id'   => $item->id,
                'text' => 'View',
            ] ),
        ];

        return sprintf( '%s %s', $item->name, $this->row_actions( $actions ) );
    }

    // Or add a dedicated actions column
    public function column_actions( $item ) {
        return get_flyout_button( 'shop_edit_product', [
            'id'    => $item->id,
            'text'  => 'Edit',
            'icon'  => 'edit',
            'class' => 'button button-small',
        ] );
    }
}
```

---

## Field Types

All field types share these common options:

```php
'field_key' => [
    'type'              => 'text',           // Field type (required)
    'name'              => 'field_key',      // Auto-set from array key if omitted
    'label'             => 'Field Label',
    'value'             => '',               // Default value (auto-populated from load data)
    'description'       => 'Help text',      // Displayed below the field
    'placeholder'       => '',
    'required'          => false,
    'disabled'          => false,
    'readonly'          => false,
    'class'             => '',               // CSS class on the input element
    'wrapper_class'     => '',               // CSS class on the wrapping div
    'wrapper_attrs'     => [],               // Additional HTML attributes on the wrapper div
    'tab'               => '',               // Tab ID for tabbed interfaces
    'depends'           => null,             // Conditional display (see Conditional Fields)
    'sanitize_callback' => null,             // Custom sanitization function
    'data_callback'     => null,             // Custom function to provide field value
],
```

### Text Fields

```php
'name' => [
    'type'        => 'text',
    'label'       => 'Name',
    'placeholder' => 'Enter name...',
    'required'    => true,
],

'email' => [
    'type'        => 'email',
    'label'       => 'Email Address',
    'placeholder' => 'user@example.com',
],

'website' => [
    'type'        => 'url',
    'label'       => 'Website',
    'placeholder' => 'https://',
],

'phone' => [
    'type'  => 'tel',
    'label' => 'Phone Number',
],

'api_key' => [
    'type'  => 'password',
    'label' => 'API Key',
],
```

### Number Fields

```php
'quantity' => [
    'type'  => 'number',
    'label' => 'Quantity',
    'min'   => 0,
    'max'   => 100,
    'step'  => 1,
],

'price' => [
    'type'  => 'number',
    'label' => 'Price',
    'min'   => 0,
    'step'  => 0.01,
],
```

### Textarea

```php
'description' => [
    'type'        => 'textarea',
    'label'       => 'Description',
    'rows'        => 5,
    'cols'        => 50,
    'placeholder' => 'Enter description...',
],
```

### Select & Multi-Select

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

### Toggle (Checkbox)

```php
'enabled' => [
    'type'  => 'toggle',
    'label' => 'Enable Feature',
],
```

The toggle renders as a styled switch. The value is `'1'` when checked, `'0'` when not.

### Radio Buttons

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

### Date & Color

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

### AJAX Select (Select2-Powered Dynamic Search)

Searchable dropdowns powered by Select2 and a unified server-side callback. Supports single select, multi-select, and
free-text tags mode.

The **unified callback** pattern uses a single `callback` function that handles both searching (user types) and
hydration (reloading saved values). The callback signature is:

```php
function ( string $search = '', ?array $ids = null ): array
```

- When `$search` is provided (user typing): return matching results
- When `$ids` is provided (hydration on load): return labels for those IDs
- Return format: `[ id => label, ... ]` — automatically converted to Select2 format

#### Single Select

```php
'customer_id' => [
    'type'        => 'ajax_select',
    'label'       => 'Customer',
    'placeholder' => 'Search customers...',
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        $args = [ 'number' => 20 ];
        if ( $ids ) {
            $args['include'] = $ids;
        } else {
            $args['search'] = '*' . $search . '*';
        }
        $result = [];
        foreach ( get_users( $args ) as $user ) {
            $result[ $user->ID ] = $user->display_name;
        }
        return $result;
    },
],
```

#### Multi-Select

```php
'post_ids' => [
    'type'        => 'ajax_select',
    'label'       => 'Select Posts',
    'placeholder' => 'Search posts...',
    'multiple'    => true,
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
        ];
        if ( $ids ) {
            $args['post__in'] = $ids;
        } else {
            $args['s'] = $search;
        }
        $result = [];
        foreach ( get_posts( $args ) as $id ) {
            $result[ $id ] = get_the_title( $id );
        }
        return $result;
    },
],
```

#### Tags Mode (Free-Text)

Allows users to type and create new tags that don't exist in the search results:

```php
'custom_tags' => [
    'type'        => 'ajax_select',
    'label'       => 'Tags',
    'placeholder' => 'Type and press enter...',
    'multiple'    => true,
    'tags'        => true,
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        if ( $ids ) {
            // For free-text tags, the ID is the tag itself
            return array_combine( $ids, $ids );
        }
        $result = [];
        foreach ( get_tags( [ 'search' => $search, 'number' => 20, 'hide_empty' => false ] ) as $tag ) {
            $result[ $tag->slug ] = $tag->name;
        }
        return $result;
    },
],
```

The library automatically registers AJAX endpoints, generates nonces, initializes Select2, and handles hydration
on reload. You only provide the callback.

**Saved data:** Single select saves a string value. Multi-select and tags save arrays of values.

### Hidden Fields

```php
'record_id' => [
    'type'  => 'hidden',
    'value' => $record_id,
],
```

### Field Groups

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

### Separator

Visual divider between fields:

```php
'divider' => [
    'type'   => 'separator',
    'text'   => 'Advanced Options',          // Optional label
    'icon'   => 'admin-settings',            // Optional dashicon name
    'style'  => 'line',                      // line, dotted, dashed, double
    'align'  => 'center',                    // left, center, right
    'margin' => '20px',
],
```

## Conditional Fields

Show/hide fields based on other field values. The JavaScript handles real-time visibility toggling.

### Simple Value Match

```php
'fields' => [
    'has_discount' => [
        'type'  => 'toggle',
        'label' => 'Apply Discount',
    ],
    'discount_type' => [
        'type'    => 'select',
        'label'   => 'Discount Type',
        'options' => [
            'percentage' => 'Percentage',
            'fixed'      => 'Fixed Amount',
        ],
        'depends' => [
            'field' => 'has_discount',
            'value' => true,
        ],
    ],
    'discount_percent' => [
        'type'  => 'number',
        'label' => 'Discount %',
        'min'   => 0,
        'max'   => 100,
        'depends' => [
            'field' => 'discount_type',
            'value' => 'percentage',
        ],
    ],
    'discount_amount' => [
        'type'  => 'number',
        'label' => 'Discount Amount',
        'min'   => 0,
        'depends' => [
            'field' => 'discount_type',
            'value' => 'fixed',
        ],
    ],
],
```

### Simple Truthy Check

```php
'extra_field' => [
    'type'    => 'text',
    'label'   => 'Extra Field',
    'depends' => 'has_discount',             // Show when 'has_discount' is truthy
],
```

### Contains Check

```php
'premium_options' => [
    'type'    => 'text',
    'label'   => 'Premium Options',
    'depends' => [
        'field'    => 'features',
        'contains' => 'premium',             // Show when 'features' contains 'premium'
    ],
],
```

Fields with dependencies start hidden and are shown/hidden by JavaScript based on the current values of their dependent
fields.

---

## Display Components

Display components are read-only and used to show information. They do not submit form data. Their content is populated
from the data returned by the `load` callback.

### Header

Display entity headers with image/icon, title, badges, and metadata:

```php
'header' => [
    'type' => 'header',
    // All values below can be set directly OR resolved from load data
    'title'       => 'Order #12345',
    'subtitle'    => 'John Doe',
    'image'       => 'https://example.com/avatar.jpg',  // OR use icon
    'icon'        => 'cart',                            // Dashicon name
    'description' => 'Placed on January 15, 2025',
    'badges'      => [
        [ 'text' => 'Paid', 'type' => 'success' ],
        [ 'text' => 'Processing', 'type' => 'warning' ],
        'Simple badge',                                  // String format works too
    ],
    'meta' => [
        [ 'label' => 'Date', 'value' => '2025-01-15', 'icon' => 'calendar' ],
        [ 'label' => 'Total', 'value' => '$99.00', 'icon' => 'money' ],
    ],
],
```

Badge types: `default`, `success`, `warning`, `error`, `info`

When used with the data resolution system, your `load` callback can return an object with a `header_data()` method or a
`header` property that returns the full array.

### Stats

Display metrics in a grid:

```php
'metrics' => [
    'type'    => 'stats',
    'columns' => 3,                          // 2, 3, or 4
    'items'   => [
        [
            'label'       => 'Revenue',
            'value'       => '$12,345',
            'icon'        => 'chart-line',
            'change'      => '+15%',
            'trend'       => 'up',           // 'up', 'down', or empty
            'description' => 'vs last month',
        ],
        [
            'label'  => 'Orders',
            'value'  => '156',
            'change' => '-3%',
            'trend'  => 'down',
        ],
        [
            'label' => 'Customers',
            'value' => '1,234',
        ],
    ],
],
```

### Info Grid

Display label/value pairs in a grid:

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

### Data Table

Display tabular data:

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

### Timeline

Display chronological events:

```php
'history' => [
    'type'    => 'timeline',
    'compact' => false,
    'items'   => [
        [
            'title'       => 'Order placed',
            'description' => 'Customer completed checkout',
            'date'        => '2 hours ago',
            'type'        => 'success',          // default, success, warning, error
            'icon'        => 'cart',
        ],
        [
            'title'       => 'Payment received',
            'description' => 'Visa ending in 4242',
            'date'        => '2 hours ago',
            'type'        => 'success',
            'icon'        => 'money',
        ],
        [
            'title' => 'Awaiting shipment',
            'date'  => 'Now',
            'type'  => 'warning',
        ],
    ],
],
```

### Progress Steps

Display step-by-step progress:

```php
'order_status' => [
    'type'      => 'progress_steps',
    'steps'     => [ 'Order Placed', 'Processing', 'Shipped', 'Delivered' ],
    'current'   => 2,                        // 1-based index
    'style'     => 'numbers',                // 'numbers', 'icons', 'simple'
    'clickable' => false,
],
```

### Price Summary

Display pricing breakdown (amounts in cents):

```php
'pricing' => [
    'type'     => 'price_summary',
    'currency' => 'USD',
    'items'    => [
        [
            'label'    => 'Widget × 2',
            'amount'   => 2000,              // In cents
            'quantity' => 2,
        ],
        [
            'label'  => 'Gadget',
            'amount' => 2500,
        ],
    ],
    'subtotal' => 4500,                      // In cents, null to hide
    'discount' => 500,                       // In cents, shown as negative
    'tax'      => 320,                       // In cents, null to hide
    'total'    => 4320,                      // In cents
],
```

### Payment Method

Display payment information with card brand icons:

```php
'payment' => [
    'type'              => 'payment_method',
    'payment_method'    => 'card',
    'payment_brand'     => 'visa',           // visa, mastercard, amex, discover, diners, jcb, unionpay
    'payment_last4'     => '4242',
    'stripe_risk_level' => 'normal',         // normal, elevated, highest
    'stripe_risk_score' => 42,
],
```

### Alert

Display alert messages:

```php
'notice' => [
    'type'        => 'alert',
    'style'       => 'warning',              // success, error, warning, info
    'title'       => 'Warning',
    'message'     => 'This order has issues that need attention.',
    'dismissible' => true,
],
```

### Empty State

Display when no data is available:

```php
'empty' => [
    'type'         => 'empty_state',
    'icon'         => 'format-gallery',
    'title'        => 'No images yet',
    'description'  => 'Upload your first image to get started.',
    'action_text'  => 'Upload Image',
    'action_url'   => '#',
    'action_class' => 'button button-primary',
    'action_attrs' => [],                    // Additional HTML attributes on the action element
],
```

### Articles

Display article cards:

```php
'recent_posts' => [
    'type'       => 'articles',
    'columns'    => 1,                       // 1 or 2
    'empty_text' => 'No articles found',
    'items'      => [
        [
            'title'       => 'Getting Started Guide',
            'date'        => 'Jan 15, 2025',
            'image'       => 'https://example.com/thumb.jpg',
            'excerpt'     => 'Learn how to get started with our platform...',
            'url'         => 'https://example.com/guide',
            'action_text' => 'Read More',
        ],
    ],
],
```

---

## Interactive Components

Interactive components allow user interaction and data manipulation. They submit data with the form and have their own
sanitizers.

### Image Gallery

Upload and manage multiple images via WordPress Media Library. Stores attachment IDs only:

```php
'gallery' => [
    'type'       => 'image_gallery',
    'name'       => 'gallery',
    'max_images' => 20,                      // 0 = unlimited
    'columns'    => 4,                       // 2-6
    'size'       => 'thumbnail',             // WordPress image size for preview
    'sortable'   => true,
    'multiple'   => true,                    // Allow selecting multiple images at once
    'add_text'   => 'Add Images',
    'empty_text' => 'No images',
    'empty_icon' => 'format-gallery',
],
```

The `items` array (populated from load data) should be an array of attachment IDs: `[123, 456, 789]`.

### File Manager

Upload and manage files via WordPress Media Library:

```php
'attachments' => [
    'type'        => 'files',
    'name'        => 'attachments',
    'max_files'   => 10,                     // 0 = unlimited
    'reorderable' => true,
    'add_text'    => 'Add Attachment',
    'empty_text'  => 'No files attached',
],
```

The `items` array (populated from load data) should be an array of file objects:

```php
[
    [
        'name'          => 'Document.pdf',
        'url'           => 'https://example.com/uploads/doc.pdf',
        'attachment_id' => 123,
        'lookup_key'    => 'optional_key',   // Optional identifier for your system
    ],
]
```

### Line Items

Add/remove line items with AJAX product search. Uses Select2 for the search dropdown with two separate callbacks:
one for searching products and one for fetching full product details when selected.

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

**Note:** The `name` attribute (`'items'` in this example) is used as the data key for both saving and loading. The
field's array key (`order_items`) can be different — data resolution uses `name` for lookup.

### Notes

Threaded notes/comments with AJAX add/delete:

```php
'notes' => [
    'type'          => 'notes',
    'name'          => 'notes',
    'editable'      => true,
    'placeholder'   => 'Add a note... (Shift+Enter to submit)',
    'empty_text'    => 'No notes yet.',
    'object_type'   => 'order',              // Passed to callbacks for context

    // Called when user adds a new note
    'add_callback' => function ( $post_data ) {
        return add_note( [
            'content'     => sanitize_textarea_field( $post_data['content'] ?? '' ),
            'object_type' => $post_data['object_type'] ?? '',
        ] );
    },

    // Called when user deletes a note
    'delete_callback' => function ( $post_data ) {
        return delete_note( absint( $post_data['note_id'] ?? 0 ) );
    },
],
```

The `items` array (populated from load data) should contain:

```php
[
    [
        'id'             => 1,
        'content'        => 'Note text here',
        'author'         => 'John Doe',
        'formatted_date' => '2 hours ago',
        'can_delete'     => true,            // Show delete button
    ],
]
```

### Feature List

Manage a list of text items:

```php
'features' => [
    'type'        => 'feature_list',
    'name'        => 'features',
    'label'       => 'Product Features',
    'max_items'   => 10,                     // 0 = unlimited
    'sortable'    => true,
    'placeholder' => 'Enter feature',
    'add_text'    => 'Add Feature',
    'empty_text'  => 'No features added',
],
```

The `items` array should be a simple array of strings: `['Feature 1', 'Feature 2']`. Empty rows are automatically
removed on save.

### Key-Value List

Manage key-value pairs (similar to Stripe metadata):

```php
'metadata' => [
    'type'            => 'key_value_list',
    'name'            => 'meta',
    'sortable'        => true,
    'max_items'       => 20,                 // 0 = unlimited
    'key_label'       => 'Key',
    'value_label'     => 'Value',
    'key_placeholder' => 'meta_key',
    'val_placeholder' => 'meta_value',
    'required_key'    => false,              // If true, key is required
    'add_text'        => 'Add Metadata',
    'empty_text'      => 'No metadata',
],
```

The `items` array should contain: `[['key' => 'color', 'value' => 'red'], ...]`. Empty rows are automatically removed on
save.

### Tags

Tag input field:

```php
'tags' => [
    'type'        => 'tags',
    'name'        => 'tags',
    'label'       => 'Tags',
    'placeholder' => 'Add tag...',
],
```

The value should be an array of strings: `['tag1', 'tag2', 'tag3']`.

### Card Choice

Visual card selection (radio or checkbox):

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

### Price Config

Stripe-compatible pricing configuration with one-time or recurring billing. Displays a type toggle, amount input,
currency selector, and conditional recurring interval fields.

```php
'pricing' => [
    'type'        => 'price_config',
    'name'        => 'pricing',
    'label'       => 'Price',
    'description' => 'Set the price and billing period.',

    // Values (populated from load data or set directly)
    'amount'                    => 1999,     // In cents — displayed as 19.99
    'currency'                  => 'USD',
    'recurring_interval'        => 'month',  // null for one-time
    'recurring_interval_count'  => 1,        // null for one-time
],
```

**Type toggle:** Switches between "One-time" and "Recurring". When "One-time" is selected, the interval fields are
hidden and saved as `null`.

**Amount:** Entered as a decimal (19.99) and automatically converted to cents (1999) during sanitization using the
`to_currency_cents()` helper if available.

**Currency:** Populated from the `arraypress/currencies` library if installed (`get_currency_options()`), otherwise
falls back to common currencies (USD, EUR, GBP, CAD, AUD, JPY).

**Recurring intervals:** Stripe-supported values: `day`, `week`, `month`, `year`. The interval count allows expressions
like "every 3 months" or "every 2 weeks".

**Saved data shape:**

```php
[
    'pricing' => [
        'amount'                   => 1999,    // In cents
        'currency'                 => 'USD',
        'recurring_interval'       => 'month', // null if one-time
        'recurring_interval_count' => 1,       // null if one-time
    ],
]
```

Maps directly to Stripe Price fields: `unit_amount`, `currency`, `recurring.interval`, `recurring.interval_count`.

### Accordion

Collapsible sections:

```php
'faq' => [
    'type'         => 'accordion',
    'multiple'     => false,                 // Allow multiple sections open simultaneously
    'default_open' => 0,                     // Index or array of indices to start open
    'items'        => [
        [
            'title'   => 'How do I get started?',
            'content' => 'Follow our quick start guide...',    // Supports HTML (wp_kses_post)
            'icon'    => 'editor-help',
        ],
        [
            'title'   => 'What payment methods do you accept?',
            'content' => 'We accept all major credit cards...',
        ],
    ],
],
```

---

## Action Components

Action components provide buttons with AJAX callback functionality, typically used for operations like refunds, exports,
or status changes.

### Action Buttons

Inline action buttons:

```php
'actions' => [
    'type'    => 'action_buttons',
    'layout'  => 'inline',                   // inline, stacked, grid
    'align'   => 'left',                     // left, center, right, justify
    'buttons' => [
        [
            'text'    => 'Export PDF',
            'icon'    => 'download',
            'style'   => 'secondary',        // primary, secondary, link, danger
            'action'  => 'export_pdf',       // Unique action identifier
            'enabled' => true,
            'data'    => [],                 // Extra data attributes
            'callback' => function ( $post_data ) {
                $id = absint( $post_data['id'] ?? 0 );
                return generate_pdf( $id );
            },
        ],
        [
            'text'    => 'Resend Email',
            'icon'    => 'email',
            'style'   => 'secondary',
            'action'  => 'resend_email',
            'confirm' => 'Send confirmation email again?',   // Browser confirm dialog
            'callback' => function ( $post_data ) {
                $id = absint( $post_data['id'] ?? 0 );
                return send_confirmation( $id );
            },
        ],
    ],
],
```

### Action Menu

Dropdown action menu (cleaner alternative to multiple buttons):

```php
'actions' => [
    'type'         => 'action_menu',
    'button_text'  => 'Actions',
    'button_icon'  => 'menu-alt',
    'button_style' => 'secondary',
    'position'     => 'left',                // left or right
    'items'        => [
        [
            'text'     => 'Duplicate',
            'icon'     => 'admin-page',
            'action'   => 'duplicate',
            'callback' => fn( $post_data ) => duplicate_item( absint( $post_data['id'] ?? 0 ) ),
        ],
        [ 'type' => 'separator' ],           // Visual divider
        [
            'text'     => 'Delete',
            'icon'     => 'trash',
            'action'   => 'delete',
            'danger'   => true,              // Red styling
            'confirm'  => 'Are you sure you want to delete this?',
            'callback' => fn( $post_data ) => delete_item( absint( $post_data['id'] ?? 0 ) ),
        ],
    ],
],
```

The library automatically registers AJAX endpoints for each button/menu item that has a `callback`, generates nonces,
and handles the frontend wiring.

---

## Custom Field Sanitization

### Per-Field Sanitization

Override the default sanitizer for any field:

```php
'slug' => [
    'type'              => 'text',
    'label'             => 'URL Slug',
    'sanitize_callback' => function ( $value ) {
        return sanitize_title( $value );
    },
],
```

### Register Global Sanitizer

Register a sanitizer for a custom field type or override an existing one:

```php
use ArrayPress\RegisterFlyouts\Sanitizer;

// Register sanitizer for custom field type
Sanitizer::register_field_sanitizer( 'my_custom_type', function ( $value ) {
    return my_custom_sanitize( $value );
} );

// Register sanitizer for custom component type
Sanitizer::register_component_sanitizer( 'my_component', function ( $value ) {
    return my_component_sanitize( $value );
} );
```

### Built-in Sanitizers

Field type sanitizers:

| Type                    | Sanitizer                                                        |
|-------------------------|------------------------------------------------------------------|
| `text`, `tel`, `hidden` | `sanitize_text_field`                                            |
| `textarea`              | `sanitize_textarea_field`                                        |
| `email`                 | `sanitize_email`                                                 |
| `url`                   | `esc_url_raw`                                                    |
| `password`              | `trim()`                                                         |
| `number`                | `intval()` or `floatval()` (auto-detected)                       |
| `date`                  | Validates `Y-m-d` format                                         |
| `select`, `radio`       | `sanitize_text_field`                                            |
| `ajax_select`           | `sanitize_text_field` or array of sanitized strings (multi/tags) |
| `toggle`                | Returns `'1'` or `'0'`                                           |
| `color`                 | `sanitize_hex_color`                                             |

Component sanitizers:

| Type             | Behavior                                                           |
|------------------|--------------------------------------------------------------------|
| `tags`           | Array of `sanitize_text_field` values                              |
| `card_choice`    | `sanitize_text_field` (or array for checkbox mode)                 |
| `feature_list`   | Removes empty items, sanitizes text                                |
| `key_value_list` | Removes rows with empty keys, `sanitize_key` on keys               |
| `line_items`     | Validates IDs, sanitizes names, enforces min quantity of 1         |
| `files`          | Validates URLs and attachment IDs, removes invalid entries         |
| `image_gallery`  | Validates attachment IDs, verifies they are images                 |
| `price_config`   | Converts decimal to cents, validates interval, uppercases currency |

---

## Hooks & Filters

### Configuration Filters

```php
// Filter any flyout configuration before registration
add_filter( 'wp_flyout_register_config', function ( $config, $id, $prefix ) {
    // Add field to all flyouts
    $config['fields']['_modified'] = [
        'type'     => 'text',
        'label'    => 'Last Modified',
        'readonly' => true,
    ];
    return $config;
}, 10, 3 );

// Filter specific flyout by full ID
add_filter( 'wp_flyout_shop_edit_product_config', function ( $config ) {
    $config['fields']['extra'] = [
        'type'  => 'text',
        'label' => 'Extra Field',
    ];
    return $config;
} );
```

### Field Rendering Filters

```php
// Filter all fields before rendering
add_filter( 'wp_flyout_before_render_fields', function ( $fields, $data, $prefix ) {
    if ( $data->status === 'locked' ) {
        foreach ( $fields as $key => &$field ) {
            $field['readonly'] = true;
        }
    }
    return $fields;
}, 10, 3 );

// Filter individual field
add_filter( 'wp_flyout_render_field', function ( $field, $key, $data, $prefix ) {
    return $field;
}, 10, 4 );

// Filter specific field by key
add_filter( 'wp_flyout_render_field_price', function ( $field, $data, $prefix ) {
    return $field;
}, 10, 3 );
```

### Field Normalization Filters

```php
// Filter fields before normalization
add_filter( 'wp_flyout_before_normalize_fields', function ( $fields, $prefix ) {
    return $fields;
}, 10, 2 );

// Filter fields after normalization
add_filter( 'wp_flyout_after_normalize_fields', function ( $fields, $prefix ) {
    return $fields;
}, 10, 2 );
```

### Sanitization Filters

```php
// Filter before sanitization
add_filter( 'wp_flyout_before_sanitize', function ( $raw_data, $fields ) {
    return $raw_data;
}, 10, 2 );

// Filter after sanitization
add_filter( 'wp_flyout_after_sanitize', function ( $sanitized, $raw_data, $fields ) {
    $sanitized['processed_at'] = current_time( 'mysql' );
    return $sanitized;
}, 10, 3 );

// Filter sanitizer for specific field type
add_filter( 'wp_flyout_sanitize_field_text', function ( $value, $field_config ) {
    return $value;
}, 10, 2 );

// Override the default sanitizer
add_filter( 'wp_flyout_default_sanitizer', function ( $sanitizer, $value, $field_config ) {
    return $sanitizer;
}, 10, 3 );
```

### Save/Delete Actions

```php
// Before save (filter)
add_filter( 'wp_flyout_before_save', function ( $form_data, $config, $prefix ) {
    return $form_data;
}, 10, 3 );

// After save (action)
add_action( 'wp_flyout_after_save', function ( $result, $id, $data, $config, $prefix ) {
    do_action( 'my_plugin_log', 'Flyout saved', [
        'id'     => $id,
        'prefix' => $prefix,
    ] );
}, 10, 5 );

// Before delete (filter)
add_filter( 'wp_flyout_before_delete', function ( $id, $config, $prefix ) {
    return $id;
}, 10, 3 );

// After delete (action)
add_action( 'wp_flyout_after_delete', function ( $result, $id, $config, $prefix ) {
    // Cleanup
}, 10, 4 );
```

### Component Filters

```php
// Filter component configuration before instantiation
add_filter( 'wp_flyout_component_config', function ( $config, $type, $class ) {
    return $config;
}, 10, 3 );

// Filter specific component type
add_filter( 'wp_flyout_component_timeline_config', function ( $config ) {
    return $config;
} );
```

### Flyout Build Filter

```php
// Modify the Flyout instance during build
add_filter( 'wp_flyout_build_flyout', function ( $flyout, $config, $data, $prefix ) {
    $flyout->add_class( 'my-custom-class' );
    return $flyout;
}, 10, 4 );

// Filter flyout CSS classes
add_filter( 'wp_flyout_classes', function ( $classes, $id, $config ) {
    return $classes;
}, 10, 3 );
```

---

## JavaScript Events

Listen for flyout events in JavaScript:

```javascript
// Flyout opened
document.addEventListener('flyout:opened', function (e) {
    console.log('Flyout opened:', e.detail.id);
});

// Flyout closed
document.addEventListener('flyout:closed', function (e) {
    console.log('Flyout closed:', e.detail.id);
});

// Data loaded
document.addEventListener('flyout:loaded', function (e) {
    console.log('Data loaded:', e.detail.data);
});

// Before save
document.addEventListener('flyout:before_save', function (e) {
    e.detail.data.extra = 'value';
});

// After save
document.addEventListener('flyout:saved', function (e) {
    console.log('Saved:', e.detail.result);
});

// Save error
document.addEventListener('flyout:save_error', function (e) {
    console.error('Save failed:', e.detail.error);
});
```

---

## Advanced Usage

### Manual Asset Enqueuing

```php
use ArrayPress\RegisterFlyouts\Assets;

// Enqueue all core flyout assets
Assets::enqueue();

// Enqueue specific component assets (also loads core)
Assets::enqueue_component( 'line-items' );
Assets::enqueue_component( 'image-gallery' );
Assets::enqueue_component( 'ajax-select' );
Assets::enqueue_component( 'price-config' );
```

Available component asset names: `file-manager`, `image-gallery`, `notes`, `line-items`, `feature-list`,
`key-value-list`, `ajax-select`, `tags`, `accordion`, `card-choice`, `timeline`, `price-summary`, `payment-method`,
`action-buttons`, `action-menu`, `articles`, `stats`, `progress-steps`, `price-config`.

### Using the Registry

For complex applications managing multiple flyout groups:

```php
use ArrayPress\RegisterFlyouts\Registry;

// Get the registry instance
$registry = Registry::get_instance();

// Get a specific manager by prefix
$manager = $registry->get_manager( 'shop' );

// Check if a manager exists
if ( $registry->has_manager( 'shop' ) ) {
    // ...
}

// Get manager by full flyout ID
$manager = $registry->get_manager_by_flyout_id( 'shop_edit_product' );

// Check if a flyout exists within a manager
if ( $manager->has_flyout( 'edit_product' ) ) {
    // ...
}

// Get all registered prefixes
$prefixes = $registry->get_prefixes();

// Get all managers
$managers = $registry->get_all_managers();
```

### Creating Custom Components

```php
use ArrayPress\RegisterFlyouts\Components\Base_Component;
use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class My_Custom_Component implements Renderable {

    private array $config;

    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, [
            'id'    => '',
            'items' => [],
            'class' => '',
        ] );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'my-custom-' . wp_generate_uuid4();
        }
    }

    public function render(): string {
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="wp-flyout-my-custom <?php echo esc_attr( $this->config['class'] ); ?>">
            <!-- Your custom HTML -->
        </div>
        <?php
        return ob_get_clean();
    }
}

// Register the component
use ArrayPress\RegisterFlyouts\Components;

Components::register( 'my_custom', [
    'class'       => My_Custom_Component::class,
    'data_fields' => 'items',              // or array of field names
    'asset'       => 'my-custom',          // Component asset handle, or null
    'category'    => 'interactive',        // display, interactive, form, layout, data, utility
    'description' => 'My custom component',
] );
```

### Registering Custom Component Sanitizers

If your custom component submits form data, register a sanitizer:

```php
use ArrayPress\RegisterFlyouts\Sanitizer;

Sanitizer::register_component_sanitizer( 'my_custom', function ( $value ) {
    if ( ! is_array( $value ) ) {
        return [];
    }
    return array_map( 'sanitize_text_field', $value );
} );
```

---

## Component Reference Summary

### Display Components (read-only)

| Type             | Description                            | Data Fields                                                           |
|------------------|----------------------------------------|-----------------------------------------------------------------------|
| `header`         | Entity header with image, badges, meta | `title`, `subtitle`, `image`, `icon`, `badges`, `meta`, `description` |
| `alert`          | Alert messages with styles             | `type`, `message`, `title`                                            |
| `empty_state`    | Empty state with icon and action       | `icon`, `title`, `description`, `action_text`                         |
| `progress_steps` | Step progress indicator                | `steps`, `current`, `style`, `clickable`                              |
| `articles`       | Article card list                      | `items`                                                               |
| `timeline`       | Chronological event list               | `items`                                                               |
| `stats`          | Metric cards with trends               | `items`                                                               |

### Data Components (read-only, structured data)

| Type             | Description           | Data Fields                                                                                  |
|------------------|-----------------------|----------------------------------------------------------------------------------------------|
| `data_table`     | Tabular data display  | `columns`, `data`                                                                            |
| `info_grid`      | Label/value grid      | `items`                                                                                      |
| `payment_method` | Payment card display  | `payment_method`, `payment_brand`, `payment_last4`, `stripe_risk_score`, `stripe_risk_level` |
| `price_summary`  | Price breakdown table | `items`, `subtotal`, `tax`, `discount`, `total`, `currency`                                  |

### Interactive Components (user interaction, submit data)

| Type             | Description                     | Data Fields                       |
|------------------|---------------------------------|-----------------------------------|
| `image_gallery`  | Image upload with grid preview  | `items` (array of attachment IDs) |
| `files`          | File manager with media library | `items` (array of file objects)   |
| `line_items`     | Order line items with search    | `items` (array of item objects)   |
| `notes`          | Threaded notes with AJAX        | `items` (array of note objects)   |
| `feature_list`   | Text item list                  | `items` (array of strings)        |
| `key_value_list` | Key-value pair manager          | `items` (array of `{key, value}`) |
| `action_buttons` | AJAX action buttons             | `buttons`                         |
| `action_menu`    | Dropdown action menu            | `items`                           |

### Form Components (input fields)

| Type                                              | Description                                          |
|---------------------------------------------------|------------------------------------------------------|
| `text`, `email`, `url`, `tel`, `password`, `date` | Standard input fields                                |
| `number`                                          | Numeric input with min/max/step                      |
| `textarea`                                        | Multi-line text                                      |
| `select`                                          | Dropdown (single or multi)                           |
| `ajax_select`                                     | Select2 searchable dropdown (single, multi, or tags) |
| `toggle`                                          | Switch/checkbox                                      |
| `radio`                                           | Radio button group                                   |
| `color`                                           | Color picker                                         |
| `tags`                                            | Tag input                                            |
| `card_choice`                                     | Visual card selection                                |
| `price_config`                                    | Stripe pricing (one-time or recurring)               |
| `hidden`                                          | Hidden input                                         |
| `group`                                           | Nested field group with layout                       |

### Layout Components

| Type        | Description                        |
|-------------|------------------------------------|
| `separator` | Visual divider with optional label |
| `accordion` | Collapsible sections               |

---

## Requirements

- PHP 7.4+
- WordPress 5.8+

## License

GPL-2.0-or-later