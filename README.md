# WordPress Flyout Library

A WordPress library for creating slide-out panels with forms, data displays, and interactive components. Perfect for
admin interfaces, edit screens, and anywhere you need contextual editing without page reloads.

## Installation

```bash
composer require arraypress/wp-register-flyouts
```

## Quick Start

```php
register_flyout( 'shop_edit_product', [
    'title'  => 'Edit Product',
    'fields' => [
        'name'  => [ 'type' => 'text', 'label' => 'Product Name' ],
        'price' => [ 'type' => 'number', 'label' => 'Price', 'min' => 0, 'step' => 0.01 ],
    ],
    'load' => fn( $id ) => get_post( $id ),
    'save' => fn( $id, $data ) => wp_update_post( [ 'ID' => $id, 'post_title' => $data['name'] ] ),
] );

render_flyout_button( 'shop_edit_product', [ 'id' => $product_id, 'text' => 'Edit' ] );
```

## Documentation

Full documentation is available at **[https://arraypress.github.io/wp-register-flyouts](https://arraypress.github.io/wp-register-flyouts)**

## Requirements

- PHP 7.4+
- WordPress 5.8+

## License

GPL-2.0-or-later