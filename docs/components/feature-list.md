# Feature List

Manage a list of text items.

```php
'features' => [
    'type'        => 'feature_list',
    'name'        => 'features',
    'label'       => 'Product Features',
    'max_items'   => 10,                     // 0 = unlimited
    'sortable'    => true,
    'placeholder' => 'Enter feature',
    'add_text'    => 'Add Feature',
    'empty_text'  => 'No features added',
],
```

The `items` array should be a simple array of strings: `['Feature 1', 'Feature 2']`. Empty rows are automatically removed on save.
