# File Manager

Upload and manage files via WordPress Media Library.

```php
'attachments' => [
    'type'        => 'files',
    'name'        => 'attachments',
    'max_files'   => 10,                     // 0 = unlimited
    'sortable'    => true,
    'add_text'    => 'Add Attachment',
    'empty_text'  => 'No files attached',
],
```

## Data Format

The `items` array (populated from load data) should be an array of file objects:

```php
[
    [
        'name'          => 'Document.pdf',
        'url'           => 'https://example.com/uploads/doc.pdf',
        'attachment_id' => 123,
        'lookup_key'    => 'optional_key',   // Optional identifier for your system
    ],
]
```
