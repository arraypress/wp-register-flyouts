# Text & Number Fields

## Text Fields

```php
'name' => [
    'type'        => 'text',
    'label'       => 'Name',
    'placeholder' => 'Enter name...',
    'required'    => true,
],

'email' => [
    'type'        => 'email',
    'label'       => 'Email Address',
    'placeholder' => 'user@example.com',
],

'website' => [
    'type'        => 'url',
    'label'       => 'Website',
    'placeholder' => 'https://',
],

'phone' => [
    'type'  => 'tel',
    'label' => 'Phone Number',
],

'api_key' => [
    'type'  => 'password',
    'label' => 'API Key',
],
```

## Number Fields

```php
'quantity' => [
    'type'  => 'number',
    'label' => 'Quantity',
    'min'   => 0,
    'max'   => 100,
    'step'  => 1,
],

'price' => [
    'type'  => 'number',
    'label' => 'Price',
    'min'   => 0,
    'step'  => 0.01,
],
```

## Textarea

```php
'description' => [
    'type'        => 'textarea',
    'label'       => 'Description',
    'rows'        => 5,
    'cols'        => 50,
    'placeholder' => 'Enter description...',
],
```
