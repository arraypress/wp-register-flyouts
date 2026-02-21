# Price Summary

Display pricing breakdown (amounts in cents).

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
