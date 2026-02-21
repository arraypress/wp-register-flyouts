# Hooks & Filters

## Configuration Filters

```php
// Filter any flyout configuration before registration
add_filter( 'wp_flyout_register_config', function ( $config, $id, $prefix ) {
    // Add field to all flyouts
    $config['fields']['_modified'] = [
        'type'     => 'text',
        'label'    => 'Last Modified',
        'readonly' => true,
    ];
    return $config;
}, 10, 3 );

// Filter specific flyout by full ID
add_filter( 'wp_flyout_shop_edit_product_config', function ( $config ) {
    $config['fields']['extra'] = [
        'type'  => 'text',
        'label' => 'Extra Field',
    ];
    return $config;
} );
```

## Field Rendering Filters

```php
// Filter all fields before rendering
add_filter( 'wp_flyout_before_render_fields', function ( $fields, $data, $prefix ) {
    if ( $data->status === 'locked' ) {
        foreach ( $fields as $key => &$field ) {
            $field['readonly'] = true;
        }
    }
    return $fields;
}, 10, 3 );

// Filter individual field
add_filter( 'wp_flyout_render_field', function ( $field, $key, $data, $prefix ) {
    return $field;
}, 10, 4 );

// Filter specific field by key
add_filter( 'wp_flyout_render_field_price', function ( $field, $data, $prefix ) {
    return $field;
}, 10, 3 );
```

## Field Normalization Filters

```php
// Filter fields before normalization
add_filter( 'wp_flyout_before_normalize_fields', function ( $fields, $prefix ) {
    return $fields;
}, 10, 2 );

// Filter fields after normalization
add_filter( 'wp_flyout_after_normalize_fields', function ( $fields, $prefix ) {
    return $fields;
}, 10, 2 );
```

## Sanitization Filters

```php
// Filter before sanitization
add_filter( 'wp_flyout_before_sanitize', function ( $raw_data, $fields ) {
    return $raw_data;
}, 10, 2 );

// Filter after sanitization
add_filter( 'wp_flyout_after_sanitize', function ( $sanitized, $raw_data, $fields ) {
    $sanitized['processed_at'] = current_time( 'mysql' );
    return $sanitized;
}, 10, 3 );

// Filter sanitizer for specific field type
add_filter( 'wp_flyout_sanitize_field_text', function ( $value, $field_config ) {
    return $value;
}, 10, 2 );

// Override the default sanitizer
add_filter( 'wp_flyout_default_sanitizer', function ( $sanitizer, $value, $field_config ) {
    return $sanitizer;
}, 10, 3 );
```

## Save/Delete Actions

```php
// Before save (filter)
add_filter( 'wp_flyout_before_save', function ( $form_data, $config, $prefix ) {
    return $form_data;
}, 10, 3 );

// After save (action)
add_action( 'wp_flyout_after_save', function ( $result, $id, $data, $config, $prefix ) {
    do_action( 'my_plugin_log', 'Flyout saved', [
        'id'     => $id,
        'prefix' => $prefix,
    ] );
}, 10, 5 );

// Before delete (filter)
add_filter( 'wp_flyout_before_delete', function ( $id, $config, $prefix ) {
    return $id;
}, 10, 3 );

// After delete (action)
add_action( 'wp_flyout_after_delete', function ( $result, $id, $config, $prefix ) {
    // Cleanup
}, 10, 4 );
```

## Component Filters

```php
// Filter component configuration before instantiation
add_filter( 'wp_flyout_component_config', function ( $config, $type, $class ) {
    return $config;
}, 10, 3 );

// Filter specific component type
add_filter( 'wp_flyout_component_timeline_config', function ( $config ) {
    return $config;
} );
```

## Flyout Build Filter

```php
// Modify the Flyout instance during build
add_filter( 'wp_flyout_build_flyout', function ( $flyout, $config, $data, $prefix ) {
    $flyout->add_class( 'my-custom-class' );
    return $flyout;
}, 10, 4 );

// Filter flyout CSS classes
add_filter( 'wp_flyout_classes', function ( $classes, $id, $config ) {
    return $classes;
}, 10, 3 );
```
