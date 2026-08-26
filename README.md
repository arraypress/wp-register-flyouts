# Register Flyouts

Edit a record in a panel that slides in, instead of navigating away to a form
and back.

## What it does

Editing one field on one row should not cost a page load, a form, and a
redirect that loses your place in the table. A flyout keeps the list on
screen and puts the record beside it.

Building one means a panel, a REST endpoint, loading, saving, validation and
a way to refresh the row underneath. This is that, from a description of the
fields — the same field types the rest of the admin uses.

## Features

* Edit a record in a panel, without leaving the list it came from
* Load and save through two callbacks, and nothing else
* Use any field type the field kit renders, including repeaters and media
* Add components a record screen needs — line items, notes, a refund form
* Split a long panel into tabs
* Open one from a button, a row action or a link
* Refresh the row underneath after a save, so the table stays true

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
		'price' => [ 'type' => 'number', 'label' => __( 'Price', 'my-plugin' ), 'min' => 0 ],
	],
	'load'   => fn( $id ) => get_post( $id ),
	'save'   => fn( $id, $data ) => wp_update_post( [
		'ID'         => $id,
		'post_title' => $data['name'],
	] ),
] );
```

Then wherever the record appears:

```php
render_flyout_button( 'shop_edit_product', [
	'id'   => $product_id,
	'text' => __( 'Edit', 'my-plugin' ),
] );
```

## Amounts are major units

Any component that shows money — line items, the price summary, the refund
form — takes 148.00 to mean one hundred and forty-eight, and reads the number
of decimal places from the currency.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
