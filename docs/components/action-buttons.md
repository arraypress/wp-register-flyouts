# Action Buttons

Inline action buttons with REST API callbacks.

```php
'actions' => [
    'type'    => 'action_buttons',
    'layout'  => 'inline',                   // inline, stacked, grid
    'align'   => 'left',                     // left, center, right, justify
    'buttons' => [
        [
            'text'    => 'Export PDF',
            'icon'    => 'download',
            'style'   => 'secondary',        // primary, secondary, link, danger
            'action'  => 'export_pdf',       // Unique action identifier
            'enabled' => true,
            'data'    => [],                 // Extra data attributes
            'callback' => function ( $post_data ) {
                $id = absint( $post_data['id'] ?? 0 );
                return generate_pdf( $id );
            },
        ],
        [
            'text'    => 'Resend Email',
            'icon'    => 'email',
            'style'   => 'secondary',
            'action'  => 'resend_email',
            'confirm' => 'Send confirmation email again?',   // Browser confirm dialog
            'callback' => function ( $post_data ) {
                $id = absint( $post_data['id'] ?? 0 );
                return send_confirmation( $id );
            },
        ],
    ],
],
```

The library automatically registers REST API endpoints for each button that has a `callback`, generates nonces, and handles the frontend wiring.
