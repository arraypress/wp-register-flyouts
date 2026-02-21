# Gallery

Upload and manage multiple images via WordPress Media Library. Stores attachment IDs only.

```php
'gallery' => [
    'type'       => 'gallery',
    'name'       => 'gallery',
    'max_images' => 20,                      // 0 = unlimited
    'columns'    => 4,                       // 2-6
    'size'       => 'thumbnail',             // WordPress image size for preview
    'sortable'   => true,
    'multiple'   => true,                    // Allow selecting multiple images at once
    'add_text'   => 'Add Images',
    'empty_text' => 'No images',
    'empty_icon' => 'format-gallery',
],
```

The `items` array (populated from load data) should be an array of attachment IDs: `[123, 456, 789]`.
