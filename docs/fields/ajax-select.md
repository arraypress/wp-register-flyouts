# AJAX Select

A searchable dropdown backed by a callback of your own. Single select, multi-select, and free text.

Reach for it when what you are choosing from is not a post, a term or a user — a customer, a licence, a row in your own table. For those three there are [shortcut types](post.md) that need no callback at all.

The control is the field kit's combobox and the search goes through the kit's endpoint, so it behaves the same here as it does on a settings page: type to filter, arrow keys to move, Escape to close, and a clear button on a single select that has something in it.

## The callback

One function answers both questions the field asks — what matches what I typed, and what are the names of the values already saved:

```php
function ( string $search = '', ?array $ids = null ): array
```

- `$search` is what the user typed. Return what matches.
- `$ids` is a set of saved values. Return their labels, and ignore `$search`.
- Either way, return `[ id => label, ... ]`.

The second half is what makes a saved value show its name rather than a bare id when the flyout opens.

## Single select

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

## Multi-select

```php
'post_ids' => [
    'type'        => 'ajax_select',
    'label'       => 'Related posts',
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

Each choice becomes a removable chip above the input.

## Free text

`creatable` lets someone keep a value that matched nothing — a tag that does not exist yet:

```php
'custom_tags' => [
    'type'        => 'ajax_select',
    'label'       => 'Tags',
    'placeholder' => 'Type and press enter...',
    'multiple'    => true,
    'creatable'   => true,
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

## Options

| Key | What it does |
| --- | --- |
| `callback` | The search. Required — without it the field has nothing to search. |
| `multiple` | Several values rather than one. |
| `creatable` | Keep a typed value that matched nothing. |
| `min_chars` | Characters before the first search fires. Defaults to 1. |
| `placeholder` | Text in the empty input. |

## What is saved

A single select saves one value. `multiple` saves an array. With `creatable`, a value may be text rather than an id, so do not assume integers.

## Registration

Nothing to register. The callback is registered as a search source when the flyout builds its fields, and the field emits the name it was registered under — so the endpoint answering the request is the same one every other field in the admin uses.
