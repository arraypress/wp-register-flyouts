# Image

Standalone image picker field for selecting a single image via the WordPress media library. Displays a preview thumbnail with hover actions to select or remove the image. Stores the attachment ID.

```php
'product_image' => [
    'type'        => 'image',
    'label'       => 'Product Image',
    'description' => 'Select a product image',
    'image_size'  => 'medium',          // WordPress image size for preview
    'image_shape' => 'rounded',         // 'square', 'circle', 'rounded'
    'icon'        => 'format-image',    // Placeholder dashicon when no image
    'empty_text'  => 'No image set',    // Text shown when no image
],
```

The value (populated from load data) should be an attachment ID (integer). Sanitized to ensure valid image attachment.
