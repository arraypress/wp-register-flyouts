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

Flyout IDs use a `prefix_name` format:

```php
register_flyout( 'shop_edit_product', [...] );  // prefix: "shop", name: "edit_product"
register_flyout( 'shop_view_order', [...] );    // prefix: "shop", name: "view_order"
```

The prefix groups related flyouts together and is used for asset loading and namespacing.

### Data Flow

1. User clicks a trigger button/link with an ID
2. Flyout opens and calls your `load` callback with that ID
3. Data is automatically mapped to fields
4. On save, your `save` callback receives the ID and form data

## Registration Options

```php
register_flyout( 'prefix_name', [
    // Basic
    'title'       => 'Flyout Title',
    'subtitle'    => 'Optional subtitle',
    'icon'        => 'dashicons-edit',           // Dashicon name
    'position'    => 'right',                    // 'right' or 'left'
    'width'       => '400px',                    // CSS width
    
    // Admin pages where assets should load
    'admin_pages' => [
        'edit.php',
        'toplevel_page_my-plugin',
    ],
    
    // Fields (see Field Types section)
    'fields' => [...],
    
    // Or use panels for tabbed interface
    'panels' => [
        'general' => [
            'label'  => 'General',
            'icon'   => 'admin-settings',
            'fields' => [...],
        ],
        'advanced' => [
            'label'  => 'Advanced',
            'icon'   => 'admin-tools',
            'fields' => [...],
        ],
    ],
    
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
    
    // Footer buttons
    'show_save_button'   => true,
    'show_delete_button' => false,
    'save_button_text'   => 'Save Changes',
    'delete_button_text' => 'Delete',
] );
```

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

## WP_List_Table Integration

The most common use case is adding flyout actions to admin list tables:

```php
class Products_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'product',
            'plural'   => 'products',
        ] );
        
        // Register flyouts
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
            'title'            => 'Product Details',
            'admin_pages'      => [ 'toplevel_page_my-products' ],
            'show_save_button' => false,
            'fields'           => [
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

## Field Types

### Text Fields

```php
'name' => [
    'type'        => 'text',
    'label'       => 'Name',
    'placeholder' => 'Enter name...',
    'default'     => '',
    'required'    => true,
    'readonly'    => false,
    'disabled'    => false,
    'description' => 'Help text below the field',
    'class'       => 'custom-class',
    'maxlength'   => 100,
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

### Checkbox & Toggle

```php
'featured' => [
    'type'  => 'checkbox',
    'label' => 'Featured Product',
],

'enabled' => [
    'type'  => 'toggle',
    'label' => 'Enable Feature',
],
```

### Radio Buttons

```php
'shipping' => [
    'type'    => 'radio',
    'label'   => 'Shipping Method',
    'options' => [
        'standard' => 'Standard (5-7 days)',
        'express'  => 'Express (2-3 days)',
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

### AJAX Select (Dynamic Search)

```php
'customer' => [
    'type'             => 'ajax_select',
    'label'            => 'Customer',
    'placeholder'      => 'Search customers...',
    'multiple'         => false,
    
    // Called when user types to search
    'search_callback' => function ( $search_term ) {
        $customers = search_customers( $search_term );
        return array_map( fn( $c ) => [
            'value' => $c->id,
            'text'  => $c->name,
        ], $customers );
    },
    
    // Called to load initial option(s) when editing existing record
    'options_callback' => function ( $value, $data ) {
        $customer = get_customer( $value );
        return [
            $value => $customer->name,
        ];
    },
],
```

### WordPress-Specific Fields

```php
'author' => [
    'type'        => 'user',
    'label'       => 'Author',
    'role'        => 'author',              // Filter by role
    'placeholder' => 'Select user...',
],

'category' => [
    'type'        => 'term',
    'label'       => 'Category',
    'taxonomy'    => 'category',
    'placeholder' => 'Select category...',
],

'related_post' => [
    'type'        => 'post',
    'label'       => 'Related Post',
    'post_type'   => 'post',
    'placeholder' => 'Search posts...',
],

'country' => [
    'type'        => 'country',
    'label'       => 'Country',
    'placeholder' => 'Select country...',
],
```

### Hidden Fields

```php
'record_id' => [
    'type'  => 'hidden',
    'value' => $record_id,
],
```

### Field Groups

```php
'address' => [
    'type'   => 'group',
    'label'  => 'Address',
    'fields' => [
        'street' => [
            'type'  => 'text',
            'label' => 'Street',
        ],
        'city' => [
            'type'  => 'text',
            'label' => 'City',
        ],
        'zip' => [
            'type'  => 'text',
            'label' => 'ZIP Code',
        ],
    ],
],
```

## Conditional Fields

Show/hide fields based on other field values:

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

### Multiple Conditions

```php
'special_field' => [
    'type'    => 'text',
    'label'   => 'Special Field',
    'depends' => [
        [
            'field' => 'type',
            'value' => 'premium',
        ],
        [
            'field' => 'status',
            'value' => 'active',
        ],
    ],
],
```

## Display Components

Display components are read-only and used to show information.

### Header

Display entity headers with image/icon, title, badges, and metadata:

```php
'header' => [
    'type'        => 'header',
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
        'price' => [ 'label' => 'Price', 'class' => 'text-right' ],
    ],
    'data' => [
        [ 'name' => 'Widget', 'qty' => 2, 'price' => '$10.00' ],
        [ 'name' => 'Gadget', 'qty' => 1, 'price' => '$25.00' ],
    ],
    'empty_text' => 'No items found',
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
            'type'        => 'success',
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

Display pricing breakdown:

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
    'subtotal' => 4500,
    'discount' => 500,                       // Shows as negative
    'tax'      => 320,
    'total'    => 4320,
],
```

### Payment Method

Display payment information with card icons:

```php
'payment' => [
    'type'              => 'payment_method',
    'payment_method'    => 'card',
    'payment_brand'     => 'visa',           // visa, mastercard, amex, discover, etc.
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

### Separator

Visual divider between sections:

```php
'divider' => [
    'type'   => 'separator',
    'text'   => 'Advanced Options',          // Optional label
    'icon'   => 'admin-settings',            // Optional icon
    'style'  => 'line',                      // line, dotted, dashed, double
    'align'  => 'center',                    // left, center, right
    'margin' => '20px',
],
```

## Interactive Components

### Image Gallery

Upload and manage multiple images:

```php
'gallery' => [
    'type'       => 'image_gallery',
    'name'       => 'gallery',
    'max_images' => 20,
    'columns'    => 4,                       // 2-6
    'size'       => 'thumbnail',             // WordPress image size
    'sortable'   => true,
    'multiple'   => true,
    'add_text'   => 'Add Images',
    'empty_text' => 'No images',
    'empty_icon' => 'format-gallery',
],
```

### File Manager

Upload and manage files:

```php
'attachments' => [
    'type'        => 'files',
    'name'        => 'attachments',
    'max_files'   => 10,
    'reorderable' => true,
    'add_text'    => 'Add Attachment',
    'empty_text'  => 'No files attached',
],
```

### Line Items

Add/remove line items with search:

```php
'order_items' => [
    'type'            => 'line_items',
    'name'            => 'items',
    'currency'        => 'USD',
    'show_quantity'   => true,
    'placeholder'     => 'Search products...',
    'empty_text'      => 'No items added',
    'add_text'        => 'Add Item',
    
    'search_callback' => function ( $term ) {
        $products = search_products( $term );
        return array_map( fn( $p ) => [
            'value' => $p->id,
            'text'  => $p->name,
        ], $products );
    },
    
    'details_callback' => function ( $data ) {
        $product = get_product( $data['id'] );
        return [
            'id'        => $product->id,
            'name'      => $product->name,
            'price'     => $product->price,          // In cents
            'thumbnail' => $product->image_url,
        ];
    },
],
```

### Notes

Threaded notes/comments:

```php
'notes' => [
    'type'          => 'notes',
    'name'          => 'notes',
    'editable'      => true,
    'placeholder'   => 'Add a note... (Shift+Enter to submit)',
    'empty_text'    => 'No notes yet.',
    'object_type'   => 'order',
    
    'add_callback' => function ( $data ) {
        return add_note( [
            'content'     => $data['content'],
            'object_type' => $data['object_type'],
        ] );
    },
    
    'delete_callback' => function ( $data ) {
        return delete_note( $data['note_id'] );
    },
],
```

### Feature List

Manage a list of text items:

```php
'features' => [
    'type'        => 'feature_list',
    'name'        => 'features',
    'label'       => 'Product Features',
    'max_items'   => 10,
    'sortable'    => true,
    'placeholder' => 'Enter feature',
    'add_text'    => 'Add Feature',
    'empty_text'  => 'No features added',
],
```

### Key-Value List

Manage key-value pairs:

```php
'metadata' => [
    'type'            => 'key_value_list',
    'name'            => 'meta',
    'sortable'        => true,
    'max_items'       => 20,
    'key_label'       => 'Key',
    'value_label'     => 'Value',
    'key_placeholder' => 'meta_key',
    'val_placeholder' => 'meta_value',
    'required_key'    => false,
    'add_text'        => 'Add Metadata',
    'empty_text'      => 'No metadata',
],
```

### Tags

Tag input field:

```php
'tags' => [
    'type'        => 'tags',
    'name'        => 'tags',
    'label'       => 'Tags',
    'placeholder' => 'Add tag...',
    'max_tags'    => 10,
],
```

### Card Choice

Visual card selection:

```php
'shipping_method' => [
    'type'    => 'card_choice',
    'name'    => 'shipping',
    'mode'    => 'radio',                    // 'radio' or 'checkbox'
    'columns' => 2,
    'value'   => 'standard',
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

### Accordion

Collapsible sections:

```php
'faq' => [
    'type'         => 'accordion',
    'multiple'     => false,                 // Allow multiple sections open
    'default_open' => 0,                     // Index or array of indices
    'items'        => [
        [
            'title'   => 'How do I get started?',
            'content' => 'Follow our quick start guide...',
            'icon'    => 'editor-help',
        ],
        [
            'title'   => 'What payment methods do you accept?',
            'content' => 'We accept all major credit cards...',
        ],
    ],
],
```

## Action Components

### Action Buttons

Footer action buttons:

```php
'actions' => [
    'type'    => 'action_buttons',
    'buttons' => [
        [
            'text'     => 'Export PDF',
            'icon'     => 'download',
            'style'    => 'secondary',
            'action'   => 'export_pdf',
            'callback' => function ( $data ) {
                return generate_pdf( $data['id'] );
            },
        ],
        [
            'text'    => 'Resend Email',
            'icon'    => 'email',
            'style'   => 'secondary',
            'action'  => 'resend_email',
            'confirm' => 'Send confirmation email again?',
            'callback' => function ( $data ) {
                return send_confirmation( $data['id'] );
            },
        ],
    ],
],
```

### Action Menu

Dropdown action menu:

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
            'callback' => fn( $data ) => duplicate_item( $data['id'] ),
        ],
        [ 'type' => 'separator' ],           // Visual divider
        [
            'text'     => 'Delete',
            'icon'     => 'trash',
            'action'   => 'delete',
            'danger'   => true,
            'confirm'  => 'Are you sure you want to delete this?',
            'callback' => fn( $data ) => delete_item( $data['id'] ),
        ],
    ],
],
```

## Custom Field Sanitization

### Per-Field Sanitization

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

```php
use ArrayPress\WP_Flyout\Sanitizer;

// Register sanitizer for custom field type
Sanitizer::register_field_sanitizer( 'my_custom_type', function ( $value ) {
    return my_custom_sanitize( $value );
} );
```

## Data Resolution

The library automatically resolves field values from the data returned by your `load` callback. It checks in this order:

1. **`{field}_data()` method** - Most explicit
2. **Array key access** - `$data['field']`
3. **`get_{field}()` getter** - `$data->get_field()`
4. **Direct property** - `$data->field`
5. **`{field}()` method** - `$data->field()`
6. **CamelCase method** - For underscore names: `user_name` → `userName()`

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
        return $this->price / 100;  // Convert cents to dollars
    }
    
    // Method 3: Direct method
    public function formatted_price() {
        return '$' . number_format( $this->price / 100, 2 );
    }
}

// Register flyout
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

// Filter specific flyout by ID
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
// Filter fields before rendering
add_filter( 'wp_flyout_before_render_fields', function ( $fields, $data, $prefix ) {
    // Modify fields based on data
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
```

### Sanitization Filters

```php
// Filter before sanitization
add_filter( 'wp_flyout_before_sanitize', function ( $raw_data, $fields ) {
    // Pre-process data
    return $raw_data;
}, 10, 2 );

// Filter after sanitization
add_filter( 'wp_flyout_after_sanitize', function ( $sanitized, $raw_data, $fields ) {
    // Post-process data
    $sanitized['processed_at'] = current_time( 'mysql' );
    return $sanitized;
}, 10, 3 );
```

### Save/Delete Actions

```php
// After save
add_action( 'wp_flyout_after_save', function ( $result, $id, $data, $config, $prefix ) {
    // Log the save
    do_action( 'my_plugin_log', 'Flyout saved', [
        'id'     => $id,
        'prefix' => $prefix,
    ] );
}, 10, 5 );

// After delete
add_action( 'wp_flyout_after_delete', function ( $result, $id, $config, $prefix ) {
    // Cleanup
}, 10, 4 );
```

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
    // Modify data before save
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

## Advanced Usage

### Manual Asset Enqueuing

```php
use ArrayPress\WP_Flyout\Assets;

// Enqueue all flyout assets
Assets::enqueue();

// Enqueue specific component
Assets::enqueue_component( 'line-items' );
Assets::enqueue_component( 'image-gallery' );
```

### Using the Registry

For complex applications managing multiple flyout groups:

```php
use ArrayPress\WP_Flyout\Registry;

// Get the registry instance
$registry = Registry::get_instance();

// Get a specific manager by prefix
$manager = $registry->get_manager( 'shop' );

// Check if a flyout exists
if ( $manager->has( 'edit_product' ) ) {
    // ...
}

// Get flyout configuration
$config = $manager->get( 'edit_product' );
```

### Creating Custom Components

```php
use ArrayPress\WP_Flyout\Components\Base_Component;

class My_Custom_Component extends Base_Component {
    
    public function get_type(): string {
        return 'my_custom';
    }
    
    public function render( array $config, $data ): string {
        $value = $this->resolve_value( $config, $data );
        
        return sprintf(
            '<div class="flyout-my-custom">%s</div>',
            esc_html( $value )
        );
    }
}

// Register the component
add_filter( 'wp_flyout_components', function ( $components ) {
    $components['my_custom'] = new My_Custom_Component();
    return $components;
} );
```

## Requirements

- PHP 7.4+
- WordPress 5.8+

## License

GPL-2.0-or-later