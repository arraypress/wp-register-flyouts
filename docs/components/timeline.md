# Timeline

Display chronological events.

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
