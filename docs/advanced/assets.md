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

`file-manager`, `gallery`, `image-picker`, `notes`, `line-items`, `feature-list`, `key-value-list`, `ajax-select`, `accordion`, `card-choice`, `timeline`, `price-summary`, `payment-method`, `action-buttons`, `action-menu`, `articles`, `stats`, `price-config`, `discount-config`, `refund-form`, `unit-input`, `code-generator`.
