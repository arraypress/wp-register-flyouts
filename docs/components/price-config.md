# Price Config

Stripe-compatible pricing configuration with one-time or recurring billing. Displays a type toggle, amount input, compare-at price, currency selector, and conditional recurring interval fields.

```php
'pricing' => [
    'type'        => 'price_config',
    'name'        => 'pricing',
    'label'       => 'Price',
    'description' => 'Set the price and billing period.',

    // Values (populated from load data or set directly)
    'amount'                    => 1999,     // In cents — displayed as 19.99
    'compare_at_amount'         => 2999,     // In cents — displayed as 29.99 (must be higher than amount, or 0)
    'currency'                  => 'USD',
    'recurring_interval'        => 'month',  // null for one-time
    'recurring_interval_count'  => 1,        // null for one-time
],
```

## Type Toggle

Switches between "One off" and "Recurring". When "One off" is selected, the interval fields are hidden and saved as `null`.

## Amount

Entered as a decimal (19.99) and automatically converted to cents (1999) during sanitization using the `to_currency_cents()` helper if available.

## Compare At

Optional strikethrough price shown to customers (e.g. "was $29.99, now $19.99"). Must be higher than the amount to save — if equal or lower, it's automatically zeroed out during sanitization.

## Currency

Populated from the `arraypress/currencies` library via `Currency::get_options()` if installed, returning options in "Name (symbol) — CODE" format (e.g. "US Dollar ($) — USD"). Falls back to common currencies (USD, EUR, GBP, CAD, AUD, JPY) if the library is not available.

## Recurring Intervals

Stripe-supported values: `day`, `week`, `month`, `year`. Billing period presets are available (Daily, Weekly, Monthly, Every 3 months, Every 6 months, Yearly, Custom) with a custom option for arbitrary intervals like "every 3 months" or "every 2 weeks".

## Saved Data Shape
```php
[
    'pricing' => [
        'amount'                   => 1999,    // In cents
        'compare_at_amount'        => 2999,    // In cents (0 if not set or <= amount)
        'currency'                 => 'USD',
        'recurring_interval'       => 'month', // null if one-time
        'recurring_interval_count' => 1,       // null if one-time
    ],
]
```

Maps directly to Stripe Price fields: `unit_amount`, `currency`, `recurring.interval`, `recurring.interval_count`.