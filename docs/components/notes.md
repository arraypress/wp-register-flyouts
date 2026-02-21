# Notes

Threaded notes/comments with AJAX add/delete.

```php
'notes' => [
    'type'          => 'notes',
    'name'          => 'notes',
    'editable'      => true,
    'placeholder'   => 'Add a note... (Shift+Enter to submit)',
    'empty_text'    => 'No notes yet.',
    'object_type'   => 'order',              // Passed to callbacks for context

    // Called when user adds a new note
    'add_callback' => function ( $post_data ) {
        return add_note( [
            'content'     => sanitize_textarea_field( $post_data['content'] ?? '' ),
            'object_type' => $post_data['object_type'] ?? '',
        ] );
    },

    // Called when user deletes a note
    'delete_callback' => function ( $post_data ) {
        return delete_note( absint( $post_data['note_id'] ?? 0 ) );
    },
],
```

## Data Format

The `items` array (populated from load data) should contain:

```php
[
    [
        'id'             => 1,
        'content'        => 'Note text here',
        'author'         => 'John Doe',
        'formatted_date' => '2 hours ago',
        'can_delete'     => true,            // Show delete button
    ],
]
```
