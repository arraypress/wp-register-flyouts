# Common Field Options

All field types share these common options:

```php
'field_key' => [
    'type'              => 'text',           // Field type (required)
    'name'              => 'field_key',      // Auto-set from array key if omitted
    'label'             => 'Field Label',
    'value'             => '',               // Default value (auto-populated from load data)
    'description'       => 'Help text',      // Displayed below the field
    'placeholder'       => '',
    'required'          => false,
    'disabled'          => false,
    'readonly'          => false,
    'class'             => '',               // CSS class on the input element
    'wrapper_class'     => '',               // CSS class on the wrapping div
    'wrapper_attrs'     => [],               // Additional HTML attributes on the wrapper div
    'tab'               => '',               // Tab ID for tabbed interfaces
    'depends'           => null,             // Conditional display (see Conditional Fields)
    'sanitize_callback' => null,             // Custom sanitization function
    'data_callback'     => null,             // Custom function to provide field value
],
```
