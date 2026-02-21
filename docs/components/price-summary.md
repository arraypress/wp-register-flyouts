# Price Summary

Read-only display of pricing breakdown with line items, totals, and refund
information. Amounts are in the smallest currency unit (e.g. cents).
```php
'pricing' => [
    'type'     => 'price_summary',
    'currency' => 'USD',
    'items'    => [
        [
            'label'       => 'Widget',
            'amount'      => 2000,
            'quantity'    => 2,
            'description' => 'License: XXXX-XXXX-XXXX',  // Optional
        ],
        [
            'label'  => 'Gadget',
            'amount' => 2500,
        ],
    ],
    'subtotal' => 4500,                      // null to hide
    'discount' => 500,                       // Shown as negative, null to hide
    'tax'      => 320,                       // null to hide
    'total'    => 4320,
    'refunded' => 4320,                      // null to hide
],
```

## Display Behavior

- **Subtotal, discount, tax** only render when not `null`
- **Discount** is automatically displayed as a negative amount
- **Refunded** shows below the total with a "Net" row when present
- **Description** renders as a secondary line under the item label (useful for license keys, expiry dates, or other metadata)

## Typical Usage with an Order Object
```php
'pricing' => [
    'type'     => 'price_summary',
    'currency' => $order->get_currency(),
    'items'    => array_map( fn( $item ) => [
        'label'    => $item->get_product_name(),
        'amount'   => $item->get_total(),
        'quantity' => $item->get_quantity(),
    ], $order->get_items() ),
    'subtotal' => $order->get_subtotal(),
    'discount' => $order->has_discount() ? $order->get_discount() : null,
    'tax'      => $order->has_tax() ? $order->get_tax() : null,
    'total'    => $order->get_total(),
    'refunded' => $order->is_refunded() ? $order->get_refunded() : null,
],
```