# Registry

For complex applications managing multiple flyout groups:

```php
use ArrayPress\RegisterFlyouts\Registry;

// Get the registry instance
$registry = Registry::instance();

// Get a specific manager by prefix
$manager = $registry->get( 'shop' );

// Check if a manager exists
if ( $registry->has( 'shop' ) ) {
    // ...
}

// Resolve a compound flyout ID to manager + flyout_id
$resolved = Registry::resolve( 'shop_edit_product' );
// Returns: [ 'manager' => Manager, 'flyout_id' => 'edit_product' ]

// Parse a compound ID without resolving
$parts = Registry::parse_id( 'shop_edit_product' );
// Returns: [ 'prefix' => 'shop', 'flyout_id' => 'edit_product' ]

// Get all registered managers
$managers = $registry->all();

// Unregister a manager
$registry->unregister( 'shop' );
```
