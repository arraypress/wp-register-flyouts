# Custom Components

## Creating a Component

```php
use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class My_Custom_Component implements Renderable {

    private array $config;

    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, [
            'id'    => '',
            'items' => [],
            'class' => '',
        ] );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'my-custom-' . wp_generate_uuid4();
        }
    }

    public function render(): string {
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="wp-flyout-my-custom <?php echo esc_attr( $this->config['class'] ); ?>">
            <!-- Your custom HTML -->
        </div>
        <?php
        return ob_get_clean();
    }
}
```

## Registering

```php
use ArrayPress\RegisterFlyouts\Components;

Components::register( 'my_custom', [
    'class'       => My_Custom_Component::class,
    'data_fields' => 'items',              // or array of field names
    'asset'       => 'my-custom',          // Component asset handle, or null
    'category'    => 'interactive',        // display, interactive, form, layout, data
    'description' => 'My custom component',
] );
```

## Registering a Sanitizer

If your custom component submits form data, register a sanitizer:

```php
use ArrayPress\RegisterFlyouts\Sanitizer;

Sanitizer::register( 'my_custom', function ( $value ) {
    if ( ! is_array( $value ) ) {
        return [];
    }
    return array_map( 'sanitize_text_field', $value );
} );
```
