# Post, Taxonomy & User

These derivative types automatically convert to `ajax_select` with built-in search callbacks from `Callbacks\Search`. They provide convenient shortcuts for the most common WordPress search patterns.

## Post Select

```php
'related_post' => [
    'type'        => 'post',
    'label'       => 'Related Post',
    'placeholder' => 'Search posts...',
    'post_type'   => 'post',           // Default: 'post'. Accepts string or array.
    'query_args'  => [],               // Additional WP_Query args to merge.
],

// Multi-select variant
'related_pages' => [
    'type'        => 'post',
    'label'       => 'Related Pages',
    'multiple'    => true,
    'post_type'   => 'page',
],
```

## Taxonomy Select

```php
'category' => [
    'type'        => 'taxonomy',
    'label'       => 'Category',
    'placeholder' => 'Search categories...',
    'taxonomy'    => 'category',       // Default: 'category'
    'query_args'  => [],               // Additional get_terms args to merge.
],
```

## User Select

```php
'author' => [
    'type'        => 'user',
    'label'       => 'Author',
    'placeholder' => 'Search users...',
    'role'        => 'editor',         // Default: '' (all roles). Accepts string or array.
    'query_args'  => [],               // Additional WP_User_Query args to merge.
],
```

These types are sanitized the same as `ajax_select` and support all the same options (`multiple`, `tags`, etc.).
