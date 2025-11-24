<?php
/**
 * Plugin Name: WP Register Flyouts Demo
 * Description: Comprehensive demonstration of the WP Register Flyouts library
 * Version: 1.0.0
 * Author: ArrayPress
 * License: GPL-2.0-or-later
 *
 * @package ArrayPress\RegisterFlyoutsDemo
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load vendor autoloader
require_once __DIR__ . '/vendor/autoload.php';

/**
 * WP Register Flyouts Demo Plugin
 *
 * Demonstrates all components and features of the WP Register Flyouts library
 * in a concise, well-organized manner.
 *
 * @since 1.0.0
 */
class WP_Register_Flyouts_Demo {

    /**
     * Option key for demo data storage
     *
     * @var string
     */
    private const OPTION_KEY = 'wp_register_flyouts_demo';

    /**
     * Initialize the demo plugin
     *
     * @return void
     * @since 1.0.0
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_flyouts' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
    }

    /**
     * Register all demo flyouts
     *
     * @return void
     * @since 1.0.0
     */
    public static function register_flyouts(): void {
        self::register_complete_form_demo();
        self::register_interactive_components_demo();
        self::register_display_components_demo();
        self::register_ecommerce_demo();
        self::register_conditional_fields_demo();
    }

    /**
     * Register complete form fields demonstration
     *
     * Shows all available form field types in a multi-panel layout
     *
     * @return void
     * @since 1.0.0
     */
    private static function register_complete_form_demo(): void {
        register_flyout( 'demo_complete_form', [
                'title'       => 'Complete Form Fields',
                'subtitle'    => 'All field types with validation and conditional logic',
                'size'        => 'large',
                'panels'      => [
                        'basic'     => 'Basic Fields',
                        'selection' => 'Selection Fields',
                        'advanced'  => 'Advanced Fields'
                ],
                'fields'      => [
                    // Basic Fields Panel
                        'text'         => [
                                'type'        => 'text',
                                'panel'       => 'basic',
                                'label'       => 'Text Field',
                                'required'    => true,
                                'placeholder' => 'Enter text...'
                        ],
                        'email'        => [
                                'type'     => 'email',
                                'panel'    => 'basic',
                                'label'    => 'Email Address',
                                'required' => true
                        ],
                        'url'          => [
                                'type'  => 'url',
                                'panel' => 'basic',
                                'label' => 'Website URL'
                        ],
                        'password'     => [
                                'type'  => 'password',
                                'panel' => 'basic',
                                'label' => 'Password'
                        ],
                        'number'       => [
                                'type'  => 'number',
                                'panel' => 'basic',
                                'label' => 'Quantity',
                                'min'   => 1,
                                'max'   => 100
                        ],
                        'date'         => [
                                'type'  => 'date',
                                'panel' => 'basic',
                                'label' => 'Date'
                        ],
                        'textarea'     => [
                                'type'  => 'textarea',
                                'panel' => 'basic',
                                'label' => 'Description',
                                'rows'  => 4
                        ],
                        'color'        => [
                                'type'    => 'color',
                                'panel'   => 'basic',
                                'label'   => 'Theme Color',
                                'default' => '#2271b1'
                        ],

                    // Selection Fields Panel
                        'select'       => [
                                'type'    => 'select',
                                'panel'   => 'selection',
                                'label'   => 'Country',
                                'options' => [
                                        'us' => 'United States',
                                        'ca' => 'Canada',
                                        'uk' => 'United Kingdom'
                                ]
                        ],
                        'ajax_select'  => [
                                'type'             => 'ajax_select',
                                'panel'            => 'selection',
                                'label'            => 'Search Products',
                                'placeholder'      => 'Type to search...',
                                'search_callback'  => [ __CLASS__, 'search_products' ],
                                'options_callback' => [ __CLASS__, 'get_product_options' ]
                        ],
                        'radio'        => [
                                'type'    => 'radio',
                                'panel'   => 'selection',
                                'label'   => 'Subscription Plan',
                                'options' => [
                                        'basic'      => 'Basic ($9/mo)',
                                        'pro'        => 'Pro ($29/mo)',
                                        'enterprise' => 'Enterprise ($99/mo)'
                                ]
                        ],
                        'toggle'       => [
                                'type'  => 'toggle',
                                'panel' => 'selection',
                                'label' => 'Enable Notifications'
                        ],
                        'card_choice'  => [
                                'type'    => 'card_choice',
                                'panel'   => 'selection',
                                'depends' => 'toggle',
                                'mode'    => 'checkbox',
                                'columns' => 2,
                                'options' => [
                                        'email' => [
                                                'title'       => 'Email Updates',
                                                'description' => 'Receive email notifications',
                                                'icon'        => 'email'
                                        ],
                                        'sms'   => [
                                                'title'       => 'SMS Alerts',
                                                'description' => 'Get text messages',
                                                'icon'        => 'smartphone'
                                        ]
                                ]
                        ],

                    // Advanced Fields Panel
                        'tags'         => [
                                'type'        => 'tags',
                                'panel'       => 'advanced',
                                'label'       => 'Skills',
                                'placeholder' => 'Add skills...'
                        ],
                        'key_value'    => [
                                'type'        => 'key_value_list',
                                'panel'       => 'advanced',
                                'label'       => 'Custom Properties',
                                'key_label'   => 'Property',
                                'value_label' => 'Value',
                                'max_items'   => 10,
                                'sortable'    => true
                        ],
                        'feature_list' => [
                                'type'        => 'feature_list',
                                'panel'       => 'advanced',
                                'label'       => 'Product Features',
                                'placeholder' => 'Add feature...',
                                'max_items'   => 5,
                                'sortable'    => true
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-register-flyouts-demo' ],
                'load'        => [ __CLASS__, 'load_form_data' ],
                'save'        => [ __CLASS__, 'save_form_data' ],
                'validate'    => [ __CLASS__, 'validate_form_data' ]
        ] );
    }

    /**
     * Register interactive components demonstration
     *
     * @return void
     * @since 1.0.0
     */
    private static function register_interactive_components_demo(): void {
        register_flyout( 'demo_interactive', [
                'title'       => 'Interactive Components',
                'size'        => 'large',
                'fields'      => [
                        'line_items'  => [
                                'type'             => 'line_items',
                                'currency'         => 'USD',
                                'editable_price'   => true,
                                'search_callback'  => [ __CLASS__, 'search_products' ],
                                'details_callback' => [ __CLASS__, 'get_product_details' ]
                        ],
                        'sep1'        => [ 'type' => 'separator', 'text' => 'Activity' ],
                        'notes'       => [
                                'type'            => 'notes',
                                'placeholder'     => 'Add a note...',
                                'add_callback'    => [ __CLASS__, 'add_note' ],
                                'delete_callback' => [ __CLASS__, 'delete_note' ]
                        ],
                        'files'       => [
                                'type'        => 'files',
                                'max_files'   => 5,
                                'reorderable' => true
                        ],
                        'gallery'     => [
                                'type'         => 'image_gallery',
                                'max_images'   => 6,
                                'columns'      => 3,
                                'show_caption' => true
                        ],
                        'sep2'        => [ 'type' => 'separator', 'text' => 'Quick Actions' ],
                        'actions'     => [
                                'type'    => 'action_buttons',
                                'buttons' => [
                                        [
                                                'text'     => 'Process Order',
                                                'action'   => 'process',
                                                'style'    => 'primary',
                                                'icon'     => 'yes',
                                                'callback' => [ __CLASS__, 'process_action' ]
                                        ],
                                        [
                                                'text'     => 'Cancel',
                                                'action'   => 'cancel',
                                                'style'    => 'danger',
                                                'icon'     => 'no',
                                                'confirm'  => 'Are you sure?',
                                                'callback' => [ __CLASS__, 'cancel_action' ]
                                        ]
                                ]
                        ],
                        'action_menu' => [
                                'type'        => 'action_menu',
                                'button_text' => 'More Actions',
                                'button_icon' => 'menu-alt',
                                'items'       => [
                                        [
                                                'text'     => 'Export',
                                                'icon'     => 'download',
                                                'action'   => 'export',
                                                'callback' => [ __CLASS__, 'export_action' ]
                                        ],
                                        [ 'type' => 'separator' ],
                                        [
                                                'text'     => 'Delete',
                                                'icon'     => 'trash',
                                                'action'   => 'delete',
                                                'danger'   => true,
                                                'callback' => [ __CLASS__, 'delete_action' ]
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-register-flyouts-demo' ],
                'load'        => [ __CLASS__, 'load_interactive_data' ],
                'save'        => [ __CLASS__, 'save_interactive_data' ]
        ] );
    }

    /**
     * Register display components demonstration
     *
     * @return void
     * @since 1.0.0
     */
    private static function register_display_components_demo(): void {
        register_flyout( 'demo_display', [
                'title'       => 'Display Components',
                'size'        => 'large',
                'fields'      => [
                        'header'    => [
                                'type'     => 'header',
                                'title'    => 'John Smith',
                                'subtitle' => 'Premium Customer',
                                'image'    => 'https://i.pravatar.cc/60?img=12',
                                'badges'   => [
                                        [ 'text' => 'VIP', 'type' => 'success' ],
                                        [ 'text' => 'Verified', 'type' => 'info' ]
                                ],
                                'meta'     => [
                                        [ 'label' => 'Since', 'value' => '2020', 'icon' => 'calendar' ],
                                        [ 'label' => 'Spent', 'value' => '$4,582', 'icon' => 'money-alt' ]
                                ]
                        ],
                        'alert'     => [
                                'type'        => 'alert',
                                'style'       => 'warning',
                                'title'       => 'Important',
                                'message'     => 'Account requires attention',
                                'dismissible' => true
                        ],
                        'stats'     => [
                                'type'    => 'stats',
                                'columns' => 3,
                                'items'   => [
                                        [
                                                'label'  => 'Revenue',
                                                'value'  => '$45K',
                                                'change' => '+12%',
                                                'trend'  => 'up'
                                        ],
                                        [
                                                'label'  => 'Orders',
                                                'value'  => '1,426',
                                                'change' => '-3%',
                                                'trend'  => 'down'
                                        ],
                                        [ 'label' => 'Users', 'value' => '2,543', 'change' => '+8%', 'trend' => 'up' ]
                                ]
                        ],
                        'progress'  => [
                                'type'    => 'progress_steps',
                                'steps'   => [ 'Cart', 'Shipping', 'Payment', 'Complete' ],
                                'current' => 2,
                                'style'   => 'numbers'
                        ],
                        'timeline'  => [
                                'type'  => 'timeline',
                                'items' => [
                                        [ 'title' => 'Order Placed', 'date' => '2 hours ago', 'type' => 'success' ],
                                        [ 'title' => 'Processing', 'date' => '1 hour ago', 'type' => 'info' ],
                                        [ 'title' => 'Shipped', 'date' => 'Now', 'type' => 'default' ]
                                ]
                        ],
                        'accordion' => [
                                'type'  => 'accordion',
                                'items' => [
                                        [
                                                'title'   => 'Description',
                                                'content' => 'Product details here...',
                                                'icon'    => 'info'
                                        ],
                                        [ 'title' => 'Shipping', 'content' => 'Ships in 24 hours', 'icon' => 'cart' ],
                                        [ 'title' => 'Returns', 'content' => '30-day return policy', 'icon' => 'undo' ]
                                ]
                        ],
                        'articles'  => [
                                'type'  => 'articles',
                                'items' => [
                                        [
                                                'title'       => 'New Features Released',
                                                'date'        => '2 days ago',
                                                'excerpt'     => 'Check out the latest updates...',
                                                'url'         => '#',
                                                'action_text' => 'Read more'
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-register-flyouts-demo' ]
        ] );
    }

    /**
     * Register e-commerce components demonstration
     *
     * @return void
     * @since 1.0.0
     */
    private static function register_ecommerce_demo(): void {
        register_flyout( 'demo_ecommerce', [
                'title'       => 'E-commerce Components',
                'size'        => 'medium',
                'fields'      => [
                        'payment_method' => [
                                'type'              => 'payment_method',
                                'payment_method'    => 'card',
                                'payment_brand'     => 'visa',
                                'payment_last4'     => '4242',
                                'stripe_risk_score' => 73,
                                'stripe_risk_level' => 'elevated'
                        ],
                        'sep'            => [ 'type' => 'separator', 'text' => 'Order Summary' ],
                        'price_summary'  => [
                                'type'     => 'price_summary',
                                'currency' => 'USD',
                                'items'    => [
                                        [ 'label' => 'Premium Widget', 'amount' => 19999, 'quantity' => 2 ],
                                        [ 'label' => 'Support', 'amount' => 29999, 'quantity' => 1 ]
                                ],
                                'subtotal' => 69997,
                                'discount' => 5000,
                                'tax'      => 5320,
                                'total'    => 70317
                        ],
                        'info_grid'      => [
                                'type'    => 'info_grid',
                                'columns' => 2,
                                'items'   => [
                                        [ 'label' => 'Order #', 'value' => '12345' ],
                                        [ 'label' => 'Status', 'value' => 'Processing' ],
                                        [ 'label' => 'Customer', 'value' => 'John Smith' ],
                                        [ 'label' => 'Shipping', 'value' => 'Express' ]
                                ]
                        ],
                        'data_table'     => [
                                'type'    => 'data_table',
                                'columns' => [
                                        'sku'     => 'SKU',
                                        'product' => 'Product',
                                        'stock'   => 'Stock'
                                ],
                                'data'    => [
                                        [ 'sku' => 'WDG-001', 'product' => 'Premium Widget', 'stock' => '15' ],
                                        [ 'sku' => 'WDG-002', 'product' => 'Basic Widget', 'stock' => '42' ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-register-flyouts-demo' ]
        ] );
    }

    /**
     * Register conditional fields demonstration
     *
     * @return void
     * @since 1.0.0
     */
    private static function register_conditional_fields_demo(): void {
        register_flyout( 'demo_conditional', [
                'title'       => 'Conditional Fields',
                'subtitle'    => 'Fields that show/hide based on other field values',
                'size'        => 'medium',
                'fields'      => [
                        'enable_shipping'  => [
                                'type'  => 'toggle',
                                'label' => 'Different Shipping Address'
                        ],
                        'shipping_address' => [
                                'type'    => 'textarea',
                                'label'   => 'Shipping Address',
                                'depends' => 'enable_shipping'
                        ],
                        'account_type'     => [
                                'type'    => 'select',
                                'label'   => 'Account Type',
                                'options' => [
                                        'personal' => 'Personal',
                                        'business' => 'Business'
                                ]
                        ],
                        'company_name'     => [
                                'type'    => 'text',
                                'label'   => 'Company Name',
                                'depends' => [
                                        'field' => 'account_type',
                                        'value' => 'business'
                                ]
                        ],
                        'features'         => [
                                'type'    => 'card_choice',
                                'mode'    => 'checkbox',
                                'label'   => 'Features',
                                'options' => [
                                        'api'     => 'API Access',
                                        'support' => 'Priority Support'
                                ]
                        ],
                        'api_key'          => [
                                'type'    => 'text',
                                'label'   => 'API Key',
                                'depends' => [
                                        'field'    => 'features',
                                        'contains' => 'api'
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-register-flyouts-demo' ],
                'load'        => [ __CLASS__, 'load_conditional_data' ],
                'save'        => [ __CLASS__, 'save_conditional_data' ]
        ] );
    }

    /**
     * Add admin menu page
     *
     * @return void
     * @since 1.0.0
     */
    public static function add_admin_menu(): void {
        add_menu_page(
                'WP Register Flyouts Demo',
                'Flyouts Demo',
                'manage_options',
                'wp-register-flyouts-demo',
                [ __CLASS__, 'render_admin_page' ],
                'dashicons-slides',
                30
        );
    }

    /**
     * Render the admin page
     *
     * @return void
     * @since 1.0.0
     */
    public static function render_admin_page(): void {
        ?>
        <div class="wrap">
            <h1>WP Register Flyouts Demo</h1>
            <p>Comprehensive demonstration of all flyout components and features.</p>

            <div class="card" style="max-width: 800px;">
                <h2>Available Demos</h2>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th>Demo</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><strong>Complete Form</strong></td>
                        <td>All field types organized in panels with validation</td>
                        <td><?php render_flyout_button( 'demo_complete_form', [
                                    'text'  => 'Open Demo',
                                    'class' => 'button button-primary'
                            ] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Interactive Components</strong></td>
                        <td>Line items, notes, files, galleries, and action buttons</td>
                        <td><?php render_flyout_button( 'demo_interactive', [
                                    'text'  => 'Open Demo',
                                    'class' => 'button button-primary'
                            ] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Display Components</strong></td>
                        <td>Headers, stats, timelines, accordions, and more</td>
                        <td><?php render_flyout_button( 'demo_display', [
                                    'text'  => 'Open Demo',
                                    'class' => 'button button-primary'
                            ] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>E-commerce</strong></td>
                        <td>Payment methods, pricing, and order components</td>
                        <td><?php render_flyout_button( 'demo_ecommerce', [
                                    'title'    => 'Order #2234',
                                    'subtitle' => 'John Smith - Processing',
                                    'text'     => 'Open Demo',
                                    'class'    => 'button button-primary'
                            ] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Conditional Fields</strong></td>
                        <td>Dynamic field visibility based on values</td>
                        <td><?php render_flyout_button( 'demo_conditional', [
                                    'text'  => 'Open Demo',
                                    'class' => 'button button-primary'
                            ] ); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    // Callback Methods

    /**
     * Search products callback
     *
     * @param array $request Request data
     *
     * @return array Search results
     * @since 1.0.0
     */
    public static function search_products( array $request ): array {
        $search   = sanitize_text_field( $request['search'] ?? '' );
        $products = [
                [ 'value' => '1', 'text' => 'Premium Widget' ],
                [ 'value' => '2', 'text' => 'Standard Widget' ],
                [ 'value' => '3', 'text' => 'Basic Widget' ]
        ];

        if ( $search ) {
            $products = array_filter( $products, fn( $p ) => stripos( $p['text'], $search ) !== false
            );
        }

        return array_values( $products );
    }

    /**
     * Get product options callback
     *
     * @param mixed $value Selected value
     *
     * @return array Product options
     * @since 1.0.0
     */
    public static function get_product_options( $value ): array {
        $products = [
                '1' => 'Premium Widget',
                '2' => 'Standard Widget',
                '3' => 'Basic Widget'
        ];

        return isset( $products[ $value ] ) ? [ $value => $products[ $value ] ] : [];
    }

    /**
     * Get product details callback
     *
     * @param array $request Request data
     *
     * @return array|WP_Error Product details or error
     * @since 1.0.0
     */
    public static function get_product_details( array $request ) {
        $products = [
                '1' => [ 'id' => '1', 'name' => 'Premium Widget', 'price' => 19999 ],
                '2' => [ 'id' => '2', 'name' => 'Standard Widget', 'price' => 9999 ],
                '3' => [ 'id' => '3', 'name' => 'Basic Widget', 'price' => 4999 ]
        ];

        $id = $request['id'] ?? '';

        return $products[ $id ] ?? new WP_Error( 'not_found', 'Product not found' );
    }

    /**
     * Add note callback
     *
     * @param array $request Request data
     *
     * @return array|WP_Error Added note or error
     * @since 1.0.0
     */
    public static function add_note( array $request ) {
        $content = sanitize_textarea_field( $request['content'] ?? '' );
        if ( empty( $content ) ) {
            return new WP_Error( 'empty', 'Note content required' );
        }

        $notes = get_option( self::OPTION_KEY . '_notes', [] );
        $note  = [
                'id'             => uniqid(),
                'content'        => $content,
                'author'         => wp_get_current_user()->display_name,
                'formatted_date' => current_time( 'M j, Y g:i a' ),
                'can_delete'     => true
        ];

        array_unshift( $notes, $note );
        update_option( self::OPTION_KEY . '_notes', $notes );

        return [ 'note' => $note ];
    }

    /**
     * Delete note callback
     *
     * @param array $request Request data
     *
     * @return array|WP_Error Success or error
     * @since 1.0.0
     */
    public static function delete_note( array $request ) {
        $note_id = sanitize_text_field( $request['note_id'] ?? '' );
        if ( empty( $note_id ) ) {
            return new WP_Error( 'invalid', 'Note ID required' );
        }

        $notes = get_option( self::OPTION_KEY . '_notes', [] );
        $notes = array_filter( $notes, fn( $note ) => $note['id'] !== $note_id );
        update_option( self::OPTION_KEY . '_notes', array_values( $notes ) );

        return [ 'success' => true ];
    }

    /**
     * Process action callback
     *
     * @param array $request Request data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function process_action( array $request ): array {
        sleep( 1 ); // Simulate processing

        return [ 'message' => 'Order processed successfully' ];
    }

    /**
     * Cancel action callback
     *
     * @param array $request Request data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function cancel_action( array $request ): array {
        sleep( 1 );

        return [ 'message' => 'Order cancelled' ];
    }

    /**
     * Export action callback
     *
     * @param array $request Request data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function export_action( array $request ): array {
        return [ 'message' => 'Export completed' ];
    }

    /**
     * Delete action callback
     *
     * @param array $request Request data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function delete_action( array $request ): array {
        return [ 'message' => 'Item deleted' ];
    }

    // Data Load/Save Methods

    /**
     * Load form data
     *
     * @param mixed $id Record ID
     *
     * @return array Form data
     * @since 1.0.0
     */
    public static function load_form_data( $id ): array {
        return get_option( self::OPTION_KEY . '_form', [
                'text'   => 'John Doe',
                'email'  => 'john@example.com',
                'tags'   => [ 'PHP', 'JavaScript' ],
                'toggle' => '1'
        ] );
    }

    /**
     * Save form data
     *
     * @param mixed $id   Record ID
     * @param array $data Form data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function save_form_data( $id, array $data ): array {
        update_option( self::OPTION_KEY . '_form', $data );

        return [
                'success' => true,
                'message' => 'Form saved successfully'
        ];
    }

    /**
     * Validate form data
     *
     * @param array $data Form data
     *
     * @return true|WP_Error Validation result
     * @since 1.0.0
     */
    public static function validate_form_data( array $data ) {
        if ( empty( $data['email'] ) ) {
            return new WP_Error( 'missing_email', 'Email is required' );
        }

        return true;
    }

    /**
     * Load interactive data
     *
     * @param mixed $id Record ID
     *
     * @return array Interactive data
     * @since 1.0.0
     */
    public static function load_interactive_data( $id ): array {
        return get_option( self::OPTION_KEY . '_interactive', [] );
//		return [
//			'notes' => get_option( self::OPTION_KEY . '_notes', [] ),
//			'files' => get_option( self::OPTION_KEY . '_files', [] )
//		];
    }

    /**
     * Save interactive data
     *
     * @param mixed $id   Record ID
     * @param array $data Interactive data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function save_interactive_data( $id, array $data ): array {
        update_option( self::OPTION_KEY . '_interactive', $data );

        return [ 'success' => true, 'message' => 'Data saved' ];
    }

    /**
     * Load conditional data
     *
     * @param mixed $id Record ID
     *
     * @return array Conditional data
     * @since 1.0.0
     */
    public static function load_conditional_data( $id ): array {
        return get_option( self::OPTION_KEY . '_conditional', [
                'enable_shipping' => false,
                'account_type'    => 'personal',
                'features'        => []
        ] );
    }

    /**
     * Save conditional data
     *
     * @param mixed $id   Record ID
     * @param array $data Conditional data
     *
     * @return array Response
     * @since 1.0.0
     */
    public static function save_conditional_data( $id, array $data ): array {
        update_option( self::OPTION_KEY . '_conditional', $data );

        return [ 'success' => true, 'message' => 'Settings saved' ];
    }
}

// Initialize the demo
add_action( 'plugins_loaded', [ 'WP_Register_Flyouts_Demo', 'init' ] );