# Refund Form

Inline expandable refund panel for issuing full or partial refunds. Designed for use within flyouts displaying order/payment details. Submits via the REST action endpoint.

```php
'refund' => [
    'type'            => 'refund_form',
    'name'            => 'refund',
    'action'          => 'issue_refund',     // Action key for the REST callback
    'label'           => 'Refund',           // Button label
    'allow_custom'    => true,               // Allow custom reason text

    // Values (populated from load data or set directly)
    'amount_paid'     => 5000,               // In cents — total amount paid
    'amount_refunded' => 0,                  // In cents — already refunded
    'currency'        => 'USD',

    // Optional: custom reason options (defaults to Stripe reasons)
    'reasons' => [
        'requested_by_customer' => 'Requested by customer',
        'duplicate'             => 'Duplicate',
        'fraudulent'            => 'Fraudulent',
    ],
],
```

## Behavior

The component displays a trigger button that expands to show a refund panel with a summary (paid/refunded/available), amount input (pre-filled with maximum refundable amount), reason selector, and submit/cancel buttons.

When `amount_paid - amount_refunded <= 0`, the component renders a "Fully refunded" state instead.

The refund action is dispatched via the REST `/action` endpoint using the `action` key. Your action callback receives the refund amount, reason, and custom reason (if applicable) in `$post_data`.
