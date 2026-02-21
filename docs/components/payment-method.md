# Payment Method

Display payment information with card brand icons.

```php
'payment' => [
    'type'              => 'payment_method',
    'payment_method'    => 'card',
    'payment_brand'     => 'visa',           // visa, mastercard, amex, discover, diners, jcb, unionpay
    'payment_last4'     => '4242',
    'stripe_risk_level' => 'normal',         // normal, elevated, highest
    'stripe_risk_score' => 42,
],
```
