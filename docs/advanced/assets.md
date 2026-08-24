# Manual Asset Enqueuing

```php
use ArrayPress\RegisterFlyouts\Assets;

// Enqueue all core flyout assets
Assets::enqueue();

// Enqueue specific component assets (also loads core)
Assets::enqueue_component( 'line-items' );
Assets::enqueue_component( 'gallery' );
Assets::enqueue_component( 'ajax-select' );
Assets::enqueue_component( 'price-config' );
Assets::enqueue_component( 'discount-config' );
Assets::enqueue_component( 'image-picker' );
Assets::enqueue_component( 'refund-form' );
Assets::enqueue_component( 'unit-input' );
Assets::enqueue_component( 'code-generator' );
```

## Available Component Assets

`image-picker`, `notes`, `line-items`, `accordion`, `timeline`, `price-summary`, `payment-method`, `action-buttons`, `stats`, `refund-form`.
