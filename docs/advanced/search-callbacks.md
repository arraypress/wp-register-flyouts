# Built-in Search Callbacks

The `Callbacks\Search` class provides pre-built callbacks for common WordPress search patterns. These are used automatically by the `post`, `taxonomy`, and `user` shortcut field types, but can also be used directly:

```php
use ArrayPress\RegisterFlyouts\Callbacks\Search;

// Post search callback
'related' => [
    'type'     => 'ajax_select',
    'label'    => 'Related Post',
    'callback' => Search::posts( 'post', [ 'post_status' => 'publish' ] ),
],

// Taxonomy term search callback
'category' => [
    'type'     => 'ajax_select',
    'label'    => 'Category',
    'callback' => Search::taxonomy( 'category', [ 'hide_empty' => true ] ),
],

// User search callback
'author' => [
    'type'     => 'ajax_select',
    'label'    => 'Author',
    'callback' => Search::users( 'editor' ),
],
```
