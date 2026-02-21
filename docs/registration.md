# Registration Options

```php
register_flyout( 'prefix_name', [
    // Basic
    'title'      => 'Flyout Title',
    'subtitle'   => 'Optional subtitle',
    'size'       => 'medium',                    // 'small', 'medium', 'large', 'full'
    'capability' => 'manage_options',            // Required user capability

    // Admin pages where assets should load
    'admin_pages' => [
        'edit.php',
        'toplevel_page_my-plugin',
    ],

    // Fields (see Field Types section)
    'fields' => [...],

    // Callbacks
    'load' => function ( $id ) {
        // Return object/array with data to populate fields
        return get_post( $id );
    },

    'validate' => function ( $data ) {
        // Return true or WP_Error
        if ( empty( $data['name'] ) ) {
            return new WP_Error( 'missing_name', 'Name is required' );
        }
        return true;
    },

    'save' => function ( $id, $data ) {
        // Save and return result
        return update_post_meta( $id, '_data', $data );
    },

    'delete' => function ( $id ) {
        // Optional delete handler
        return wp_delete_post( $id );
    },

    // Footer action buttons (auto-generated if omitted)
    // If 'save' callback exists, a Save button is auto-added
    // If 'delete' callback exists, a Delete button is auto-added
    'actions' => [
        [
            'text'  => 'Save Changes',
            'style' => 'primary',              // primary, secondary, link-delete
            'class' => 'wp-flyout-save',       // Required for save functionality
            'icon'  => '',                     // Optional dashicon name
        ],
        [
            'text'  => 'Delete',
            'style' => 'link-delete',
            'class' => 'wp-flyout-delete',     // Required for delete functionality
        ],
    ],
] );
```
