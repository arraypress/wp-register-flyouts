# WordPress Register Flyouts

Slide-out panels for the WordPress admin. Edit a record without leaving the list it is in.

A flyout is a form, and the form is a field configuration — the same one a settings page or a metabox takes, because
they all go through [wp-field-kit](https://github.com/arraypress/wp-field-kit). What this library adds is the panel
around it: the overlay, the tabs, the footer, the save and delete round trips, and a set of components for the parts of
a record that are read rather than edited.

## Features

- Every field type the kit has, with no separate vocabulary to learn
- Tabs, conditional fields, and a delete action when you want one
- Fourteen components for the read-only half: line items, a price summary, a timeline, stats, notes
- Load, validate, save and delete as four callbacks
- Search and action handlers registered for you, over the kit's own REST endpoints
- No jQuery UI, no bundled select library, no colour that ignores the admin scheme

## Requirements

- PHP 7.4+
- WordPress 5.8+

## Installation

```bash
composer require arraypress/wp-register-flyouts
```

## Quick start

```php
register_flyout( 'shop_edit_product', [
    'title'  => __( 'Edit product', 'my-plugin' ),
    'fields' => [
        'name'  => [ 'type' => 'text', 'label' => __( 'Name', 'my-plugin' ) ],
        'price' => [ 'type' => 'number', 'label' => __( 'Price', 'my-plugin' ), 'min' => 0, 'step' => 0.01 ],
    ],
    'load' => fn( $id ) => get_post( $id ),
    'save' => fn( $id, $data ) => wp_update_post( [ 'ID' => $id, 'post_title' => $data['name'] ] ),
] );

render_flyout_button( 'shop_edit_product', [ 'id' => $product_id, 'text' => __( 'Edit', 'my-plugin' ) ] );
```

`load` gets the id and returns whatever holds the values — a `WP_Post`, a BerlinDB row, an array, a plain object. Each
field is looked for on it as `{key}_data()`, then an array key, then `get_{key}()`, then a property, so most objects
work without being told anything.

## Registration

```php
register_flyout( 'shop_edit_product', [
    'title'       => __( 'Edit product', 'my-plugin' ),
    'subtitle'    => __( 'Everything about this one', 'my-plugin' ),
    'size'        => 'medium',        // small, medium, large, full
    'capability'  => 'manage_options',
    'admin_pages' => [ 'shop_page_products' ],
    'fields'      => [ /* ... */ ],
    'tabs'        => [ /* ... */ ],
    'load'        => fn( $id ) => get_product( $id ),
    'validate'    => fn( $data ) => '' !== $data['name'],
    'save'        => fn( $id, $data ) => save_product( $id, $data ),
    'delete'      => fn( $id ) => delete_product( $id ),
] );
```

| Key | What it does |
| --- | --- |
| `title` | The panel's heading. |
| `subtitle` | A line under it. |
| `size` | `small`, `medium`, `large` or `full`. |
| `capability` | Who may open it. Defaults to `manage_options`. |
| `admin_pages` | Screens to load the assets on. Everywhere when empty. |
| `fields` | The field configuration. |
| `tabs` | Slug to label. A field says which one it is in with `tab`. |
| `load` | `fn( $id )` — what holds the values. |
| `validate` | `fn( $data )` — return false or a `WP_Error` to refuse the save. |
| `save` | `fn( $id, $data )` — the id first, always. |
| `delete` | `fn( $id )` — adds a delete button when present. |

A callback that throws is caught and reported in the panel rather than taking the page down. Under `WP_DEBUG` the
message comes with it.

## Opening one

```php
// A button.
render_flyout_button( 'shop_edit_product', [ 'id' => 42, 'text' => __( 'Edit', 'my-plugin' ) ] );

// A link, for a list-table row action.
render_flyout_link( 'shop_edit_product', [ 'id' => 42, 'text' => __( 'Edit', 'my-plugin' ) ] );

// Or the markup, to place yourself.
$html = get_flyout_button( 'shop_edit_product', [ 'id' => 42 ] );
```

Omit `id` for a new record: `load` is called with `0` and every field takes its default.

## Tabs

```php
register_flyout( 'shop_edit_product', [
    'tabs'   => [
        'general' => __( 'General', 'my-plugin' ),
        'pricing' => __( 'Pricing', 'my-plugin' ),
    ],
    'fields' => [
        'name'  => [ 'type' => 'text', 'label' => __( 'Name', 'my-plugin' ), 'tab' => 'general' ],
        'price' => [ 'type' => 'number', 'label' => __( 'Price', 'my-plugin' ), 'tab' => 'pricing' ],
    ],
] );
```

## Conditional fields

A field can name what it depends on. The condition is evaluated in the browser and the field is properly hidden —
removed from the tab order, not just made invisible.

```php
'fields' => [
    'is_sale'    => [ 'type' => 'toggle', 'label' => __( 'On sale', 'my-plugin' ) ],
    'sale_price' => [
        'type'    => 'number',
        'label'   => __( 'Sale price', 'my-plugin' ),
        'depends' => 'is_sale',
    ],
    'reason'     => [
        'type'    => 'text',
        'label'   => __( 'Reason', 'my-plugin' ),
        'depends' => [ 'status' => 'refunded' ],
    ],
],
```

## Searching

Any field backed by a callback gets a searchable combobox, and the callback answers both questions the field asks —
what matches what was typed, and what the saved values are called.

```php
'customer' => [
    'type'     => 'ajax_select',
    'label'    => __( 'Customer', 'my-plugin' ),
    'callback' => function ( string $search = '', ?array $ids = null ): array {
        $args = $ids ? [ 'include' => $ids ] : [ 'search' => '*' . $search . '*' ];

        $found = [];

        foreach ( get_users( $args + [ 'number' => 20 ] ) as $user ) {
            $found[ $user->ID ] = $user->display_name;
        }

        return $found;
    },
],
```

For posts, terms and users there is nothing to write: `post`, `taxonomy` and `user` are field types that search
themselves.

## Components

The read-only half of a record — what it cost, what happened to it, what is attached to it. A component takes its data
from the same `load` result as the fields.

| Component | What it shows |
| --- | --- |
| `header` | Title, subtitle, image and badges for the record. |
| `alert` | A message, in core's notice styles. |
| `empty_state` | An icon, a line, and something to do about it. |
| `stats` | Metric cards with trends. |
| `timeline` | What happened, in order. |
| `accordion` | Sections that open one at a time. |
| `data_table` | A table of rows. |
| `info_grid` | Label and value pairs. |
| `notes` | Notes, with adding and deleting. |
| `line_items` | Order lines, with a product search and running totals. |
| `price_summary` | Subtotal, tax, discount, total. |
| `payment_method` | Card brand, last four, risk. |
| `refund_form` | A full or partial refund. |
| `action_buttons` | Buttons that run a handler over REST. |

```php
'fields' => [
    'summary' => [
        'type'     => 'info_grid',
        'items'    => [
            [ 'label' => __( 'Placed', 'my-plugin' ), 'value' => $order->date ],
            [ 'label' => __( 'Status', 'my-plugin' ), 'value' => $order->status ],
        ],
    ],
    'actions' => [
        'type'    => 'action_buttons',
        'buttons' => [
            [
                'text'     => __( 'Resend receipt', 'my-plugin' ),
                'action'   => 'resend',
                'icon'     => 'email',
                'callback' => fn( $request ) => resend_receipt( $request['item_id'] ),
            ],
            [
                'text'     => __( 'Refund', 'my-plugin' ),
                'action'   => 'refund',
                'style'    => 'destructive',
                'confirm'  => __( 'Refund this order?', 'my-plugin' ),
                'callback' => fn( $request ) => refund( $request['item_id'] ),
            ],
        ],
    ],
],
```

Button styles are core's, and there are three: `primary`, `secondary` and `destructive`. Anything else falls back to
secondary rather than emitting a class nothing styles.

## Registering a component of your own

```php
use ArrayPress\RegisterFlyouts\Components;
use ArrayPress\RegisterFlyouts\Renderable;

final class Shipping_Label implements Renderable {

    public function __construct( private array $config ) {}

    public function render(): string {
        return sprintf( '<p>%s</p>', esc_html( $this->config['tracking'] ?? '' ) );
    }
}

Components::register( 'shipping_label', [
    'class' => Shipping_Label::class,
    'data'  => [ 'tracking', 'carrier' ],
    'asset' => null,
] );
```

`data` names the keys the component wants. They are looked for on the `load` result the same way a field's value is.

## Hooks

Every one carries a scope: `{prefix}` is the string you passed to
`register_flyouts()`, `{id}` is a flyout's, `{type}` is a component's. That
scope is what keeps two plugins bundling this library from filtering each
other — the names themselves are literal, so they survive Strauss prefixing
and are actually reachable from outside the copy that fires them.

| Hook | When |
| --- | --- |
| `wp_flyout_register_config_{prefix}` | A flyout is registered. Also `wp_flyout_{prefix}_{id}_config` for one in particular. |
| `wp_flyout_before_normalize_fields_{prefix}` / `wp_flyout_after_normalize_fields_{prefix}` | Around the field configuration being normalized. |
| `wp_flyout_before_render_fields_{prefix}` | The whole field set, before any of it renders. |
| `wp_flyout_render_field_{prefix}` | Each field, before it renders. Also `wp_flyout_render_field_{prefix}_{key}`. |
| `wp_flyout_after_render_fields_{prefix}` | The rendered markup. |
| `wp_flyout_build_flyout_{prefix}` | The panel object, before its content is added. |
| `wp_flyout_before_save_{prefix}` / `wp_flyout_after_save_{prefix}` | Around `save`, with the sanitized values. |
| `wp_flyout_before_delete_{prefix}` / `wp_flyout_after_delete_{prefix}` | Around `delete`. |
| `wp_flyout_component_{prefix}_{type}_config` | A component's configuration. |
| `wp_flyout_rest_capability_{prefix}` | The capability a REST request is checked against. |
| `wp_flyout_classes_{id}` | A panel's own classes. |
| `wp_flyout_before_header_{id}` / `wp_flyout_after_header_{id}` | Around a panel's header. |

Most of the time you will not need any of them: the field configuration is an
array, so the extension point you probably want is your own filter over it.

```php
register_flyout( 'shop_edit_product', [
    'fields' => apply_filters( 'my_plugin_product_fields', [ /* ... */ ] ),
] );
```

## License

GPL-2.0-or-later
