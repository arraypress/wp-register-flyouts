# Sanitization

## Per-Field Sanitization

Override the default sanitizer for any field:

```php
'slug' => [
    'type'              => 'text',
    'label'             => 'URL Slug',
    'sanitize_callback' => function ( $value ) {
        return sanitize_title( $value );
    },
],
```

## Register Global Sanitizer

Register a sanitizer for a custom field type or override an existing one. Field types and component types share a single unified registry:

```php
use ArrayPress\RegisterFlyouts\Sanitizer;

// Register sanitizer for any type (field or component)
Sanitizer::register( 'my_custom_type', function ( $value ) {
    return my_custom_sanitize( $value );
} );
```

## Built-in Sanitizers

### Field Types

| Type                       | Sanitizer                                                        |
|----------------------------|------------------------------------------------------------------|
| `text`, `tel`, `hidden`    | `sanitize_text_field`                                            |
| `textarea`                 | `sanitize_textarea_field`                                        |
| `email`                    | `sanitize_email`                                                 |
| `url`                      | `esc_url_raw`                                                    |
| `password`                 | `trim()`                                                         |
| `number`                   | `intval()` or `floatval()` (auto-detected)                       |
| `date`                     | Validates `Y-m-d` format                                         |
| `select`, `radio`          | `sanitize_text_field`                                            |
| `ajax_select`              | `sanitize_text_field` or array of sanitized strings (multi/tags) |
| `post`, `taxonomy`, `user` | Same as `ajax_select` (derivative types)                         |
| `toggle`                   | Returns `'1'` or `'0'`                                           |
| `color`                    | `sanitize_hex_color`                                             |
| `image`, `header`          | Validates attachment ID is a valid image (returns `int`)         |

### Component Types

| Type              | Behavior                                                                                  |
|-------------------|-------------------------------------------------------------------------------------------|
| `tags`            | Array of `sanitize_text_field` values                                                     |
| `card_choice`     | `sanitize_text_field` (or array for checkbox mode)                                        |
| `feature_list`    | Removes empty items, sanitizes text                                                       |
| `key_value_list`  | Removes rows with empty keys, `sanitize_key` on keys                                      |
| `line_items`      | Validates IDs, sanitizes names, enforces min quantity of 1                                |
| `files`           | Validates URLs and attachment IDs, removes invalid entries                                |
| `gallery`         | Validates attachment IDs, verifies they are images                                        |
| `price_config`    | Converts decimal to cents, validates compare-at, validates interval, uppercases currency  |
| `discount_config` | Converts decimal to basis points/cents, validates rate type, validates duration            |
| `unit_input`      | `sanitize_text_field` on the numeric value                                                |
| `code_generator`  | `sanitize_text_field`                                                                     |
