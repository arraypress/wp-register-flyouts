# Articles

Display article cards.

```php
'recent_posts' => [
    'type'       => 'articles',
    'columns'    => 1,                       // 1 or 2
    'empty_text' => 'No articles found',
    'items'      => [
        [
            'title'       => 'Getting Started Guide',
            'date'        => 'Jan 15, 2025',
            'image'       => 'https://example.com/thumb.jpg',
            'excerpt'     => 'Learn how to get started with our platform...',
            'url'         => 'https://example.com/guide',
            'action_text' => 'Read More',
        ],
    ],
],
```
