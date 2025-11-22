# WordPress Flyout Registration System

A declarative flyout panel system for WordPress admin interfaces. Build slide-out forms with 20+ components, automatic data binding, and AJAX handling.

## Features

* **Simple Registration API**: Register flyouts with fields, panels, and actions
* **20+ Components**: From simple inputs to complex interactive elements
* **Automatic Data Binding**: Smart resolution from objects/arrays
* **AJAX Everything**: Built-in save, load, delete with security
* **Tabbed Interfaces**: Organize complex forms into panels

## Installation
```bash
composer require arraypress/wp-register-flyouts
```

## Quick Start

### 1. Register a Flyout
```php
register_flyout( 'shop_edit_product', [
    'title'    => 'Edit Product',
    'size'     => 'medium',  // small, medium, large, full
    'fields'   => [
        'name' => [
            'type'  => 'text',
            'label' => 'Product Name'
        ],
        'price' => [
            'type'  => 'number',
            'label' => 'Price'
        ],
        'description' => [
            'type'  => 'textarea',
            'label' => 'Description'
        ]
    ],
    'load'     => fn( $id ) => get_product( $id ),
    'save'     => fn( $id, $data ) => update_product( $id, $data ),
    'delete'   => fn( $id ) => delete_product( $id )
] );
```

### 2. Add Trigger Button
```php
// Simple button
render_flyout_button( 'shop_edit_product', [
    'id' => $product_id
] );

// Custom button
echo get_flyout_button( 'shop_edit_product', 
    [ 'id' => $product_id ],
    [ 'text' => 'Edit', 'icon' => 'edit', 'class' => 'button-primary' ]
);

// Link trigger
echo get_flyout_link( 'shop_edit_product', 'Edit Product', [
    'id' => $product_id
] );
```

## Available Components

### Form Fields
```php
'fields' => [
    // Basic inputs
    'email'    => [ 'type' => 'email' ],
    'url'      => [ 'type' => 'url' ],
    'password' => [ 'type' => 'password' ],
    'date'     => [ 'type' => 'date' ],
    
    // Selection
    'status' => [
        'type'    => 'select',
        'options' => [ 'active' => 'Active', 'draft' => 'Draft' ]
    ],
    
    // Toggle switch
    'featured' => [ 'type' => 'toggle', 'label' => 'Featured Product' ],
    
    // Tags input
    'tags' => [ 'type' => 'tags', 'placeholder' => 'Add tags...' ],
    
    // AJAX select with search
    'customer' => [
        'type'            => 'ajax_select',
        'search_callback' => fn( $term ) => search_customers( $term ),
        'placeholder'     => 'Search customers...'
    ]
]
```

### Interactive Components
```php
// Line items with pricing
'items' => [
    'type'              => 'line_items',
    'currency'          => 'USD',
    'editable_price'    => true,
    'search_callback'   => fn( $term ) => search_products( $term ),
    'details_callback'  => fn( $id ) => get_product_details( $id )
],

// File manager
'attachments' => [
    'type'       => 'files',
    'max_files'  => 5,
    'reorderable' => true
],

// Image gallery
'gallery' => [
    'type'       => 'image_gallery',
    'max_images' => 10,
    'columns'    => 4
],

// Notes/comments
'notes' => [
    'type'          => 'notes',
    'add_callback'  => fn( $note ) => add_note( $note ),
    'delete_callback' => fn( $id ) => delete_note( $id )
],

// Key-value metadata
'metadata' => [
    'type'     => 'key_value_list',
    'sortable' => true
],

// Feature list
'features' => [
    'type'       => 'feature_list',
    'max_items'  => 10,
    'sortable'   => true
]
```

### Display Components
```php
// Entity header
'header' => [
    'type'     => 'header',
    'title'    => $order->number,
    'subtitle' => $order->customer_name,
    'badges'   => [ 
        [ 'text' => 'Paid', 'type' => 'success' ] 
    ]
],

// Stats grid
'metrics' => [
    'type'  => 'stats',
    'items' => [
        [ 'label' => 'Revenue', 'value' => '$1,234', 'trend' => 'up' ],
        [ 'label' => 'Orders', 'value' => '56', 'change' => '+12%' ]
    ]
],

// Price breakdown
'pricing' => [
    'type'     => 'price_summary',
    'items'    => $line_items,
    'subtotal' => 10000,
    'tax'      => 1000,
    'total'    => 11000,
    'currency' => 'USD'
],

// Progress steps
'steps' => [
    'type'    => 'progress_steps',
    'steps'   => [ 'Order Placed', 'Processing', 'Shipped', 'Delivered' ],
    'current' => 2
],

// Timeline
'history' => [
    'type'  => 'timeline',
    'items' => [
        [ 'title' => 'Order placed', 'date' => '2 hours ago' ],
        [ 'title' => 'Payment received', 'date' => '1 hour ago' ]
    ]
]
```

### Layout Components
```php
// Accordion sections
'details' => [
    'type'  => 'accordion',
    'items' => [
        [ 'title' => 'Billing', 'content' => '...' ],
        [ 'title' => 'Shipping', 'content' => '...' ]
    ]
],

// Visual separator
'divider' => [
    'type'  => 'separator',
    'text'  => 'Additional Options',
    'style' => 'line'
]
```

## Multi-Panel Forms
```php
register_flyout( 'user_edit_profile', [
    'title'  => 'Edit Profile',
    'panels' => [
        'general'  => 'General',
        'billing'  => 'Billing',
        'settings' => 'Settings'
    ],
    'fields' => [
        // General panel
        'name'  => [ 'type' => 'text', 'panel' => 'general' ],
        'email' => [ 'type' => 'email', 'panel' => 'general' ],
        
        // Billing panel
        'address' => [ 'type' => 'textarea', 'panel' => 'billing' ],
        'country' => [ 'type' => 'select', 'panel' => 'billing' ],
        
        // Settings panel
        'notifications' => [ 'type' => 'toggle', 'panel' => 'settings' ]
    ]
] );
```

## Advanced Features

### Custom Actions
```php
'actions' => [
    [
        'text'  => 'Save & Continue',
        'style' => 'primary',
        'class' => 'wp-flyout-save'
    ],
    [
        'text'  => 'Delete',
        'style' => 'link-delete',
        'class' => 'wp-flyout-delete'
    ]
]

### Action Components
// Action buttons with AJAX callbacks
'order_actions' => [
    'type' => 'action_buttons',
    'buttons' => [
        [
            'text'     => 'Process Refund',
            'action'   => 'refund',
            'style'    => 'secondary',
            'icon'     => 'money',
            'callback' => fn($data) => process_refund($data['order_id']),
            'confirm'  => 'Are you sure you want to issue a refund?'
        ],
        [
            'text'     => 'Send Invoice',
            'action'   => 'send_invoice',
            'style'    => 'primary',
            'callback' => fn($data) => send_invoice_email($data['order_id'])
        ]
    ]
],

// Action dropdown menu
'bulk_actions' => [
    'type' => 'action_menu',
    'button_text' => 'Actions',
    'button_icon' => 'menu-alt',
    'items' => [
        [
            'text'     => 'Export to CSV',
            'icon'     => 'download',
            'action'   => 'export',
            'callback' => fn($data) => export_order($data['id'])
        ],
        ['type' => 'separator'],
        [
            'text'     => 'Delete',
            'icon'     => 'trash',
            'action'   => 'delete',
            'danger'   => true,
            'callback' => fn($data) => delete_order($data['id']),
            'confirm'  => 'This cannot be undone. Continue?'
        ]
    ]
],

// Card-style radio/checkbox selection
'shipping_method' => [
    'type'    => 'card_choice',
    'mode'    => 'radio',  // or 'checkbox' for multiple
    'columns' => 2,
    'options' => [
        'standard' => [
            'title'       => 'Standard Shipping',
            'description' => '5-7 business days',
            'icon'        => 'car'
        ],
        'express' => [
            'title'       => 'Express Shipping', 
            'description' => '2-3 business days',
            'icon'        => 'airplane'
        ]
    ]
]
```

### Conditional Fields
```php
'fields' => [
    'has_shipping' => [
        'type'  => 'toggle',
        'label' => 'Different shipping address?'
    ],
    'shipping_address' => [
        'type'    => 'textarea',
        'depends' => 'has_shipping'  // Shows when has_shipping is true
    ]
]
```

### Field Validation
```php
'validate' => function( $data ) {
    if ( empty( $data['email'] ) ) {
        return new WP_Error( 'missing_email', 'Email is required' );
    }
    return true;
}
```

### Data Resolution

The system automatically resolves data from objects:
```php
class Product {
    public $name = 'Widget';
    
    public function get_price() {
        return 9900; // cents
    }
    
    public function description_data() {
        // Explicit data method (highest priority)
        return 'Premium widget description';
    }
}

// Field 'name' -> resolves to $product->name
// Field 'price' -> calls $product->get_price()
// Field 'description' -> calls $product->description_data()
```

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later

## License

GPL-2.0-or-later

## Credits

Created by [David Sherlock](https://davidsherlock.com) at [ArrayPress](https://arraypress.com)