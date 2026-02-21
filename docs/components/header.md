# Header

Display entity headers with image/icon, title, badges, metadata, and optional interactive image picker.

```php
'header' => [
    'type' => 'header',

    // Text content (resolved from load data or set directly)
    'title'       => 'Order #12345',
    'subtitle'    => 'John Doe',
    'description' => 'Placed on January 15, 2025',

    // Image display — provide an image URL or attachment_id
    'image'           => 'https://example.com/avatar.jpg',
    'attachment_id'   => 0,              // If set and no 'image', resolves URL from attachment
    'image_size'      => 'thumbnail',    // WordPress image size for resolution
    'image_shape'     => 'square',       // square, circle, rounded
    'thumbnail_width' => 60,             // Display size in pixels (32-200, always square)

    // Fallback when no image is set
    'fallback_image'         => 'https://example.com/default.jpg',
    'fallback_attachment_id' => 0,

    // Icon (used when no image at all)
    'icon' => 'cart',                    // Dashicon name

    // Interactive image picker (enables selecting/replacing image via media library)
    'editable' => false,                 // Set to true to enable image picker
    'name'     => 'header_image',        // Form field name for the attachment ID

    // Badges
    'badges' => [
        [ 'text' => 'Paid', 'type' => 'success' ],
        [ 'text' => 'Processing', 'type' => 'warning' ],
        'Simple badge',                  // String format works too
    ],

    // Meta items
    'meta' => [
        [ 'label' => 'Date', 'value' => '2025-01-15', 'icon' => 'calendar' ],
        [ 'label' => 'Total', 'value' => '$99.00', 'icon' => 'money' ],
    ],
],
```

## Badge Types

`default`, `success`, `warning`, `error`, `info`

## Editable Image

When `editable` is `true`, the header image becomes clickable and opens the WordPress media library for selecting/replacing the image. The selected attachment ID is stored in a hidden input using the `name` field and submitted with the form. The `image-picker` component asset is automatically loaded when `editable` is enabled.

## Data Resolution

When used with the data resolution system, your `load` callback can return an object with a `header_data()` method or a `header` property that returns the full array.
