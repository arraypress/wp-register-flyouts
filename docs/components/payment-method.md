# Payment Method

Display payment information with card brand icons.
```php
'payment' => [
    'type'              => 'payment_method',
    'payment_method'    => 'card',           // card, link, paypal, cashapp, klarna, etc.
    'payment_brand'     => 'visa',           // visa, mastercard, amex, discover, diners, jcb, unionpay
    'payment_last4'     => '4242',
    'stripe_risk_level' => 'normal',         // normal, elevated, highest
    'stripe_risk_score' => 42,
],
```

The `payment_method` defaults to `'card'` if not provided. For non-card methods
(e.g. PayPal, Klarna), `payment_brand` and `payment_last4` are typically empty
and the component displays the method type label instead.