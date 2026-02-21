# Trigger Buttons & Links

## Button

```php
// Render directly
render_flyout_button( 'shop_edit_product', [
    'id'       => $product_id,
    'text'     => 'Edit Product',
    'icon'     => 'edit',                    // Dashicon (without 'dashicons-' prefix)
    'class'    => 'button button-primary',
    'title'    => 'Dynamic Title',           // Overrides registered title
    'subtitle' => 'Dynamic Subtitle',        // Overrides registered subtitle
] );

// Or get HTML string
$html = get_flyout_button( 'shop_edit_product', [
    'id'   => $product_id,
    'text' => 'Edit',
] );
```

## Link

```php
// Render directly
render_flyout_link( 'shop_edit_product', [
    'id'    => $product_id,
    'text'  => 'Edit',
    'class' => 'row-action',
] );

// Or get HTML string
$html = get_flyout_link( 'shop_edit_product', [
    'id'   => $product_id,
    'text' => 'Edit',
] );
```

## Data Attributes

Any key besides `text`, `class`, `icon`, and `target` is passed as a `data-*` attribute on the trigger element. The `id` attribute is the record identifier passed to the `load` callback. The `title` and `subtitle` attributes override the registered flyout title/subtitle for that instance.
