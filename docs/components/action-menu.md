# Action Menu

Dropdown action menu (cleaner alternative to multiple buttons).

```php
'actions' => [
    'type'         => 'action_menu',
    'button_text'  => 'Actions',
    'button_icon'  => 'menu-alt',
    'button_style' => 'secondary',
    'position'     => 'left',                // left or right
    'items'        => [
        [
            'text'     => 'Duplicate',
            'icon'     => 'admin-page',
            'action'   => 'duplicate',
            'callback' => fn( $post_data ) => duplicate_item( absint( $post_data['id'] ?? 0 ) ),
        ],
        [ 'type' => 'separator' ],           // Visual divider
        [
            'text'     => 'Delete',
            'icon'     => 'trash',
            'action'   => 'delete',
            'danger'   => true,              // Red styling
            'confirm'  => 'Are you sure you want to delete this?',
            'callback' => fn( $post_data ) => delete_item( absint( $post_data['id'] ?? 0 ) ),
        ],
    ],
],
```

The library automatically registers REST API endpoints for each menu item that has a `callback`, generates nonces, and handles the frontend wiring.
