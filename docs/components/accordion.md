# Accordion

Collapsible sections.

```php
'faq' => [
    'type'         => 'accordion',
    'multiple'     => false,                 // Allow multiple sections open simultaneously
    'default_open' => 0,                     // Index or array of indices to start open
    'items'        => [
        [
            'title'   => 'How do I get started?',
            'content' => 'Follow our quick start guide...',    // Supports HTML (wp_kses_post)
            'icon'    => 'editor-help',
        ],
        [
            'title'   => 'What payment methods do you accept?',
            'content' => 'We accept all major credit cards...',
        ],
    ],
],
```
