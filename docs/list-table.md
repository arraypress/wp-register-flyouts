# WP_List_Table Integration

The most common use case is adding flyout actions to admin list tables:

```php
class Products_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'product',
            'plural'   => 'products',
        ] );

        $this->register_flyouts();
    }

    private function register_flyouts() {
        register_flyout( 'shop_edit_product', [
            'title'       => 'Edit Product',
            'admin_pages' => [ 'toplevel_page_my-products' ],
            'fields'      => [
                'name' => [
                    'type'  => 'text',
                    'label' => 'Product Name',
                ],
                'price' => [
                    'type'  => 'number',
                    'label' => 'Price',
                ],
                'status' => [
                    'type'    => 'select',
                    'label'   => 'Status',
                    'options' => [
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ],
                ],
            ],
            'load' => fn( $id ) => $this->get_product( $id ),
            'save' => fn( $id, $data ) => $this->save_product( $id, $data ),
        ] );

        register_flyout( 'shop_view_product', [
            'title'       => 'Product Details',
            'admin_pages' => [ 'toplevel_page_my-products' ],
            'actions'     => [],                   // No footer buttons (read-only)
            'fields'      => [
                'header' => [
                    'type' => 'header',
                ],
                'details' => [
                    'type'    => 'info_grid',
                    'columns' => 2,
                ],
            ],
            'load' => fn( $id ) => $this->get_product_display_data( $id ),
        ] );
    }

    // Add flyout buttons to row actions
    public function column_name( $item ) {
        $actions = [
            'edit' => get_flyout_link( 'shop_edit_product', [
                'id'   => $item->id,
                'text' => 'Edit',
            ] ),
            'view' => get_flyout_link( 'shop_view_product', [
                'id'   => $item->id,
                'text' => 'View',
            ] ),
        ];

        return sprintf( '%s %s', $item->name, $this->row_actions( $actions ) );
    }

    // Or add a dedicated actions column
    public function column_actions( $item ) {
        return get_flyout_button( 'shop_edit_product', [
            'id'    => $item->id,
            'text'  => 'Edit',
            'icon'  => 'edit',
            'class' => 'button button-small',
        ] );
    }
}
```
