# Code Generator

Text input with an attached "Generate" button that produces random codes client-side. Useful for discount codes, API keys, license keys, referral codes, and similar identifiers.

```php
'coupon_code' => [
    'type'           => 'code_generator',
    'name'           => 'code',
    'label'          => 'Coupon Code',
    'description'    => 'Enter a code or generate one automatically.',
    'placeholder'    => 'e.g. SUMMER25',

    // Generator settings
    'length'         => 8,                   // Number of characters to generate
    'format'         => 'alphanumeric_upper', // Character set (see below)
    'prefix'         => '',                  // e.g. 'PROMO-' prepended to generated code
    'separator'      => '-',                 // Separator between segments
    'segment_length' => 4,                   // Characters per segment (e.g. 4 → XXXX-XXXX)
    'button_text'    => 'Generate',
],
```

## Format Options

| Format               | Characters     |
|----------------------|----------------|
| `alphanumeric_upper` | A-Z, 0-9       |
| `alphanumeric`       | A-Z, a-z, 0-9  |
| `alpha_upper`        | A-Z             |
| `hex`                | 0-9, A-F       |
| `numeric`            | 0-9             |

## Segments

When `separator` and `segment_length` are set, the generated code is split into segments. For example, `length: 8`, `segment_length: 4`, `separator: '-'` produces codes like `ABCD-EF12`.

## Saved Data

A single string value, sanitized with `sanitize_text_field`.
