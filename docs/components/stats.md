# Stats

Display metrics in a grid.

```php
'metrics' => [
    'type'    => 'stats',
    'columns' => 3,                          // 2, 3, or 4
    'items'   => [
        [
            'label'       => 'Revenue',
            'value'       => '$12,345',
            'icon'        => 'chart-line',
            'change'      => '+15%',
            'trend'       => 'up',           // 'up', 'down', or empty
            'description' => 'vs last month',
        ],
        [
            'label'  => 'Orders',
            'value'  => '156',
            'change' => '-3%',
            'trend'  => 'down',
        ],
        [
            'label' => 'Customers',
            'value' => '1,234',
        ],
    ],
],
```
