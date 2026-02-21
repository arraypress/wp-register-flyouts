# AJAX Select

Searchable dropdowns powered by Select2 and a unified server-side callback. Supports single select, multi-select, and free-text tags mode.

The **unified callback** pattern uses a single `callback` function that handles both searching (user types) and hydration (reloading saved values). The callback signature is:

```php
function ( string $search = '', ?array $ids = null ): array
```

- When `$search` is provided (user typing): return matching results
- When `$ids` is provided (hydration on load): return labels for those IDs
- Return format: `[ id => label, ... ]` — automatically converted to Select2 format

## Single Select

```php
'customer_id' => [
    'type'        => 'ajax_select',
    'label'       => 'Customer',
    'placeholder' => 'Search customers...',
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        $args = [ 'number' => 20 ];
        if ( $ids ) {
            $args['include'] = $ids;
        } else {
            $args['search'] = '*' . $search . '*';
        }
        $result = [];
        foreach ( get_users( $args ) as $user ) {
            $result[ $user->ID ] = $user->display_name;
        }
        return $result;
    },
],
```

## Multi-Select

```php
'post_ids' => [
    'type'        => 'ajax_select',
    'label'       => 'Select Posts',
    'placeholder' => 'Search posts...',
    'multiple'    => true,
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
        ];
        if ( $ids ) {
            $args['post__in'] = $ids;
        } else {
            $args['s'] = $search;
        }
        $result = [];
        foreach ( get_posts( $args ) as $id ) {
            $result[ $id ] = get_the_title( $id );
        }
        return $result;
    },
],
```

## Tags Mode (Free-Text)

Allows users to type and create new tags that don't exist in the search results:

```php
'custom_tags' => [
    'type'        => 'ajax_select',
    'label'       => 'Tags',
    'placeholder' => 'Type and press enter...',
    'multiple'    => true,
    'tags'        => true,
    'callback'    => function ( string $search = '', ?array $ids = null ): array {
        if ( $ids ) {
            return array_combine( $ids, $ids );
        }
        $result = [];
        foreach ( get_tags( [ 'search' => $search, 'number' => 20, 'hide_empty' => false ] ) as $tag ) {
            $result[ $tag->slug ] = $tag->name;
        }
        return $result;
    },
],
```

The library automatically registers REST API endpoints, generates nonces, initializes Select2, and handles hydration on reload. You only provide the callback.

**Saved data:** Single select saves a string value. Multi-select and tags save arrays of values.
