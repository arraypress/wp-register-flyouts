# Discount Config

Stripe-compatible discount/coupon configuration with percentage or fixed amount, duration controls, and optional redemption limits. The rate type selector is embedded within the amount input field for a compact layout.

```php
'discount' => [
    'type'            => 'discount_config',
    'name'            => 'discount',
    'label'           => 'Discount',
    'description'     => 'Configure the discount amount and duration.',

    // Values (populated from load data or set directly)
    'rate_type'          => 'percent',       // 'percent' or 'fixed'
    'amount'             => 2500,            // Basis points for percent (2500 = 25.00%), cents for fixed
    'currency'           => 'USD',
    'currency_symbol'    => '$',
    'duration'           => 'once',          // 'once', 'forever', 'repeating'
    'duration_in_months' => null,            // Required when duration is 'repeating'

    // Optional
    'show_duration'      => true,
    'show_redemptions'   => false,
    'max_redemptions'    => null,            // null = unlimited
],
```

## Rate Type

Integrated dropdown within the amount input — shows `%` for percentage or the currency symbol for fixed amounts.

## Amount

Entered as a decimal (25.00) and automatically converted during sanitization. Percentages are stored as basis points (25.00% → 2500). Fixed amounts are stored as currency cents (25.00 → 2500). Percentages are capped at 100% (10000 basis points).

## Duration

Matches Stripe's coupon model — `once` (single use), `forever` (applies indefinitely), or `repeating` (applies for a set number of months).

## Saved Data Shape

```php
[
    'discount' => [
        'rate_type'          => 'percent',   // 'percent' or 'fixed'
        'amount'             => 2500,        // Basis points or cents
        'currency'           => 'USD',
        'duration'           => 'once',      // 'once', 'forever', 'repeating'
        'duration_in_months' => null,        // int when duration is 'repeating'
        'max_redemptions'    => null,        // int or null
    ],
]
```
