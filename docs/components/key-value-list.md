# Key-Value List

Manage key-value pairs (similar to Stripe metadata).

```php
'metadata' => [
    'type'            => 'key_value_list',
    'name'            => 'meta',
    'sortable'        => true,
    'max_items'       => 20,                 // 0 = unlimited
    'key_label'       => 'Key',
    'value_label'     => 'Value',
    'key_placeholder' => 'meta_key',
    'val_placeholder' => 'meta_value',
    'required_key'    => false,              // If true, key is required
    'add_text'        => 'Add Metadata',
    'empty_text'      => 'No metadata',
],
```

The `items` array should contain: `[['key' => 'color', 'value' => 'red'], ...]`. Empty rows are automatically removed on save.
