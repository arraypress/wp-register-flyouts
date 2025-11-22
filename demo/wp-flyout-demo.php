<?php
/**
 * Plugin Name: WP Flyout Simple Demo (Callback Version)
 * Description: Simple demonstration of WP Flyout library functionality using callbacks
 * Version: 2.0.0
 * Author: ArrayPress
 * License: GPL v2 or later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the vendor autoloader
require_once __DIR__ . '/vendor/autoload.php';

/**
 * WP Flyout Demo Plugin
 */
class WP_Flyout_Demo {

    /**
     * Option key for storing demo data
     */
    private const OPTION_KEY = 'wp_flyout_demo_data';

    /**
     * Initialize the demo
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_flyouts' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
    }

    /**
     * Register all demo flyouts
     */
    public static function register_flyouts() {
        // 1. Form Fields Demo - Shows all field types
        register_flyout( 'demo_form_fields', [
                'title'       => 'All Form Fields Demo',
                'subtitle'    => 'kasjhdkajshdkashdkajshdkajshdk aksjdh aksjdh akjdsh kasjdh kajsh d',
                'size'        => 'large',
                'fields'      => [
                    // Text inputs
                        'name'         => [
                                'type'        => 'text',
                                'name'        => 'name',
                                'label'       => 'Name',
                                'required'    => true,
                                'placeholder' => 'Enter your name'
                        ],
                        'email'        => [
                                'type'     => 'email',
                                'name'     => 'email',
                                'label'    => 'Email',
                                'required' => true
                        ],
                        'website'      => [
                                'type'  => 'url',
                                'name'  => 'website',
                                'label' => 'Website'
                        ],
                        'phone'        => [
                                'type'  => 'tel',
                                'name'  => 'phone',
                                'label' => 'Phone'
                        ],
                        'password'     => [
                                'type'  => 'password',
                                'name'  => 'password',
                                'label' => 'Password'
                        ],
                        'age'          => [
                                'type'  => 'number',
                                'name'  => 'age',
                                'label' => 'Age',
                                'min'   => 18,
                                'max'   => 120
                        ],
                        'birthday'     => [
                                'type'  => 'date',
                                'name'  => 'birthday',
                                'label' => 'Birthday'
                        ],
                        'bio'          => [
                                'type'  => 'textarea',
                                'name'  => 'bio',
                                'label' => 'Biography',
                                'rows'  => 5
                        ],

                    // Selection fields
                        'country'      => [
                                'type'    => 'select',
                                'name'    => 'country',
                                'label'   => 'Country',
                                'options' => [
                                        'us' => 'United States',
                                        'ca' => 'Canada',
                                        'uk' => 'United Kingdom'
                                ]
                        ],
                        'ajax_country' => [
                                'type'             => 'ajax_select',
                                'name'             => 'ajax_country',
                                'label'            => 'Country (AJAX)',
                                'placeholder'      => 'Type to search countries...',
                                'search_callback'  => function ( $request ) {
                                    $search = sanitize_text_field( $request['search'] ?? '' );

                                    $countries = [
                                            'US' => 'United States',
                                            'CA' => 'Canada',
                                            'GB' => 'United Kingdom',
                                            'AU' => 'Australia',
                                            'DE' => 'Germany',
                                            'FR' => 'France'
                                    ];

                                    $results = [];
                                    foreach ( $countries as $code => $name ) {
                                        if ( ! $search || stripos( $name, $search ) !== false ) {
                                            $results[] = [ 'value' => $code, 'text' => $name ];
                                        }
                                    }

                                    return $results;
                                },
                                'options_callback' => function ( $value ) {
                                    $countries = [
                                            'US' => 'United States',
                                            'CA' => 'Canada',
                                            'GB' => 'United Kingdom'
                                    ];

                                    return isset( $countries[ $value ] ) ? [ $value => $countries[ $value ] ] : [];
                                }
                        ],
                        'newsletter'   => [
                                'type'  => 'toggle',
                                'name'  => 'newsletter',
                                'label' => 'Subscribe to newsletter'
                        ],
                        'gender'       => [
                                'type'    => 'radio',
                                'name'    => 'gender',
                                'label'   => 'Gender',
                                'options' => [
                                        'male'   => 'Male',
                                        'female' => 'Female',
                                        'other'  => 'Other'
                                ]
                        ],
                        'skills'       => [
                                'type'        => 'tags',
                                'name'        => 'skills',
                                'label'       => 'Skills',
                                'placeholder' => 'Add skills...'
                        ],
                        'theme_color'  => [
                                'type'    => 'color',
                                'name'    => 'theme_color',
                                'label'   => 'Theme Color',
                                'default' => '#2271b1'
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_form_data' ],
//                'save'        => [ __CLASS__, 'save_form_data' ],
//                'delete'      => [ __CLASS__, 'delete_form_data' ]
        ] );

        // 2. Display Components Demo
        register_flyout( 'demo_display', [
                'title'       => 'Display Components Demo',
                'size'        => 'large',
                'fields'      => [
                    // Entity Header
                        'customer_header' => [
                                'type'     => 'header',
                                'title'    => 'John Smith',
                                'subtitle' => 'Premium Customer',
                                'image'    => 'https://i.pravatar.cc/60?img=12',
                                'badges'   => [
                                        [ 'text' => 'VIP', 'type' => 'success' ],
                                        [ 'text' => 'Verified', 'type' => 'info' ]
                                ],
                                'meta'     => [
                                        [ 'label' => 'Member Since', 'value' => 'Jan 2020', 'icon' => 'calendar' ],
                                        [ 'label' => 'Total Spent', 'value' => '$4,582', 'icon' => 'money-alt' ]
                                ]
                        ],

                    // Separator
                        'sep1'            => [
                                'type' => 'separator',
                                'text' => 'Alerts Section'
                        ],

                    // Alert
                        'alert1'          => [
                                'type'        => 'alert',
                                'style'       => 'warning',
                                'title'       => 'Important Notice',
                                'message'     => 'This customer has an outstanding balance.',
                                'dismissible' => true
                        ],

                    // Data Table
                        'data_table'      => [
                                'type'    => 'data_table',
                                'columns' => [
                                        'id'     => 'ID',
                                        'name'   => 'Name',
                                        'status' => 'Status'
                                ],
                                'data'    => [
                                        [ 'id' => '1', 'name' => 'Item 1', 'status' => 'Active' ],
                                        [ 'id' => '2', 'name' => 'Item 2', 'status' => 'Pending' ],
                                        [ 'id' => '3', 'name' => 'Item 3', 'status' => 'Inactive' ]
                                ]
                        ],

                    // Info Grid
                        'info_grid'       => [
                                'type'    => 'info_grid',
                                'columns' => 3,
                                'items'   => [
                                        [ 'label' => 'Status', 'value' => 'Active' ],
                                        [ 'label' => 'Plan', 'value' => 'Professional' ],
                                        [ 'label' => 'Users', 'value' => '25' ],
                                        [ 'label' => 'Storage', 'value' => '50 GB' ],
                                        [ 'label' => 'Bandwidth', 'value' => 'Unlimited' ],
                                        [ 'label' => 'Support', 'value' => '24/7' ]
                                ]
                        ],

                    // Empty State
                        'empty_state'     => [
                                'type'        => 'empty_state',
                                'icon'        => 'admin-comments',
                                'title'       => 'No Reviews Yet',
                                'description' => 'This customer hasn\'t left any reviews.',
                                'action_text' => 'Request Review',
                                'action_url'  => '#'
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_display_data' ],
                'save'        => [ __CLASS__, 'save_display_data' ]
        ] );

        // 3. Interactive Components Demo
        register_flyout( 'demo_interactive', [
                'title'       => 'Interactive Components Demo',
                'size'        => 'large',
                'fields'      => [
                    // Line Items
                        'line_items' => [
                                'type'             => 'line_items',
                                'currency'         => 'USD',
                                'editable_price'   => true,
                                'placeholder'      => 'Search for products...',
                                'search_callback'  => function ( $request ) {
                                    $search = sanitize_text_field( $request['search'] ?? '' );

                                    $products = [
                                            [ 'value' => '1', 'text' => 'Premium Widget' ],
                                            [ 'value' => '2', 'text' => 'Standard Widget' ],
                                            [ 'value' => '3', 'text' => 'Basic Widget' ],
                                            [ 'value' => '4', 'text' => 'Support Package' ]
                                    ];

                                    if ( $search ) {
                                        $products = array_filter( $products, function ( $p ) use ( $search ) {
                                            return stripos( $p['text'], $search ) !== false;
                                        } );
                                    }

                                    return array_values( $products );
                                },
                                'details_callback' => function ( $request ) {
                                    $item_id = $request['item_id'] ?? '';

                                    $products = [
                                            '1' => [
                                                    'id'        => '1',
                                                    'name'      => 'Premium Widget',
                                                    'price'     => 19999,
                                                    'thumbnail' => ''
                                            ],
                                            '2' => [
                                                    'id'        => '2',
                                                    'name'      => 'Standard Widget',
                                                    'price'     => 9999,
                                                    'thumbnail' => ''
                                            ],
                                            '3' => [
                                                    'id'        => '3',
                                                    'name'      => 'Basic Widget',
                                                    'price'     => 4999,
                                                    'thumbnail' => ''
                                            ],
                                            '4' => [
                                                    'id'        => '4',
                                                    'name'      => 'Support Package',
                                                    'price'     => 29999,
                                                    'thumbnail' => ''
                                            ]
                                    ];

                                    if ( isset( $products[ $item_id ] ) ) {
                                        return $products[ $item_id ];
                                    }

                                    return new WP_Error( 'not_found', 'Product not found' );
                                }
                        ],

                        'sep1'  => [
                                'type' => 'separator',
                                'text' => 'Notes'
                        ],

                    // Notes
                        'notes' => [
                                'type'            => 'notes',
                                'placeholder'     => 'Add a note...',
                                'object_type'     => 'demo',
                                'add_callback'    => function ( $request ) {
                                    $content = sanitize_textarea_field( $request['content'] ?? '' );
                                    if ( empty( $content ) ) {
                                        return new WP_Error( 'empty', 'Note content required' );
                                    }

                                    $notes = get_option( WP_Flyout_Demo::OPTION_KEY . '_notes', [] );
                                    $note  = [
                                            'id'             => uniqid(),
                                            'content'        => $content,
                                            'author'         => wp_get_current_user()->display_name,
                                            'formatted_date' => date( 'M j, Y g:i a' ),
                                            'can_delete'     => true
                                    ];
                                    array_unshift( $notes, $note );
                                    update_option( WP_Flyout_Demo::OPTION_KEY . '_notes', $notes );

                                    return [ 'note' => $note ];
                                },
                                'delete_callback' => function ( $request ) {
                                    $note_id = sanitize_text_field( $request['note_id'] ?? '' );
                                    if ( empty( $note_id ) ) {
                                        return new WP_Error( 'invalid', 'Note ID required' );
                                    }

                                    $notes = get_option( WP_Flyout_Demo::OPTION_KEY . '_notes', [] );
                                    $notes = array_filter( $notes, function ( $note ) use ( $note_id ) {
                                        return $note['id'] !== $note_id;
                                    } );
                                    update_option( WP_Flyout_Demo::OPTION_KEY . '_notes', array_values( $notes ) );

                                    return [ 'success' => true ];
                                }
                        ],

                        'sep2'        => [
                                'type' => 'separator',
                                'text' => 'File Attachments'
                        ],

                    // Files
                        'attachments' => [
                                'type'       => 'files',
                                'max_files'  => 5,
                                'add_text'   => 'Attach File',
                                'empty_text' => 'No files attached'
                        ],

                        'features'       => [
                                'type'        => 'feature_list',
                                'label'       => 'Product Features',
                                'placeholder' => 'Enter a feature',
                                'add_text'    => 'Add Feature',
                                'icon'        => 'yes-alt', // Checkmark icon for each item
                                'max_items'   => 10,
                                'sortable'    => true
                        ],

                    // Product metadata using MetaKeyValue
                        'metadata'       => [
                                'type'            => 'key_value_list',
                                'key_label'       => 'Property',
                                'value_label'     => 'Value',
                                'add_text'        => 'Add Custom Field',
                                'key_placeholder' => 'e.g., color',
                                'val_placeholder' => 'e.g., blue',
                                'max_items'       => 20,
                                'sortable'        => true
                        ],

                    // Specifications using MetaKeyValue (different configuration)
                        'specifications' => [
                                'type'            => 'key_value_list',
                                'key_label'       => 'Specification',
                                'value_label'     => 'Details',
                                'add_text'        => 'Add Specification',
                                'key_placeholder' => 'e.g., weight',
                                'val_placeholder' => 'e.g., 2.5 kg',
                                'empty_text'      => 'No specifications added',
                                'sortable'        => false // No sorting for this instance
                        ],
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_interactive_data' ],
                'save'        => [ __CLASS__, 'save_interactive_data' ]
        ] );

        // 4. Layout Components Demo
        register_flyout( 'demo_layout', [
                'title'       => 'Layout Components Demo',
                'size'        => 'large',
                'panels'      => [
                        'choices' => 'Card Choices',
                        'content' => 'Content Layout'
                ],
                'fields'      => [
                    // Card Choice - Radio
                        'subscription_plan' => [
                                'type'    => 'card_choice',
                                'panel'   => 'choices',
                                'mode'    => 'radio',
                                'columns' => 3,
                                'options' => [
                                        'basic'      => [
                                                'title'       => 'Basic',
                                                'description' => 'Essential features',
                                                'icon'        => 'admin-users'
                                        ],
                                        'pro'        => [
                                                'title'       => 'Professional',
                                                'description' => 'Advanced features',
                                                'icon'        => 'star-filled'
                                        ],
                                        'enterprise' => [
                                                'title'       => 'Enterprise',
                                                'description' => 'All features',
                                                'icon'        => 'building'
                                        ]
                                ]
                        ],

                    // Card Choice - Checkbox
                        'features'          => [
                                'type'    => 'card_choice',
                                'panel'   => 'choices',
                                'mode'    => 'checkbox',
                                'columns' => 2,
                                'options' => [
                                        'email'   => [
                                                'title'       => 'Email Notifications',
                                                'description' => 'Get email updates',
                                                'icon'        => 'email'
                                        ],
                                        'sms'     => [
                                                'title'       => 'SMS Alerts',
                                                'description' => 'Text messages',
                                                'icon'        => 'smartphone'
                                        ],
                                        'webhook' => [
                                                'title'       => 'Webhooks',
                                                'description' => 'API integration',
                                                'icon'        => 'admin-links'
                                        ],
                                        'reports' => [
                                                'title'       => 'Reports',
                                                'description' => 'Analytics',
                                                'icon'        => 'chart-bar'
                                        ]
                                ]
                        ],

                    // Timeline
                        'timeline'          => [
                                'type'  => 'timeline',
                                'panel' => 'content',
                                'items' => [
                                        [
                                                'title'       => 'Account Created',
                                                'description' => 'User signed up',
                                                'date'        => '2024-01-01 10:00',
                                                'type'        => 'success',
                                                'icon'        => 'admin-users'
                                        ],
                                        [
                                                'title'       => 'First Purchase',
                                                'description' => 'Made initial purchase',
                                                'date'        => '2024-01-02 14:30',
                                                'type'        => 'info',
                                                'icon'        => 'cart'
                                        ],
                                        [
                                                'title'       => 'Support Ticket',
                                                'description' => 'Opened support request',
                                                'date'        => '2024-01-05 09:15',
                                                'type'        => 'warning',
                                                'icon'        => 'sos'
                                        ]
                                ]
                        ],

                    // Accordion
                        'faq'               => [
                                'type'  => 'accordion',
                                'panel' => 'content',
                                'items' => [
                                        [
                                                'title'   => 'How does billing work?',
                                                'content' => 'We bill monthly or annually based on your preference.',
                                                'icon'    => 'money-alt'
                                        ],
                                        [
                                                'title'   => 'Can I change my plan?',
                                                'content' => 'Yes, you can upgrade or downgrade at any time.',
                                                'icon'    => 'update'
                                        ],
                                        [
                                                'title'   => 'What payment methods are accepted?',
                                                'content' => 'We accept all major credit cards and PayPal.',
                                                'icon'    => 'cart'
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_layout_data' ],
                'save'        => [ __CLASS__, 'save_layout_data' ]
        ] );

        // 5. Domain Components Demo (Payment & Pricing)
        register_flyout( 'demo_domain', [
                'title'       => 'Domain Components Demo',
                'size'        => 'medium',
                'fields'      => [
                    // Payment Method
                        'payment_info' => [
                                'type'              => 'payment_method',
                                'payment_method'    => 'card',
                                'payment_brand'     => 'visa',
                                'payment_last4'     => '4242',
                                'stripe_risk_score' => 73,
                                'stripe_risk_level' => 'elevated'
                        ],

                        'sep'     => [
                                'type' => 'separator',
                                'text' => 'Order Summary'
                        ],

                    // Price Summary
                        'pricing' => [
                                'type'     => 'price_summary',
                                'currency' => 'USD',
                                'items'    => [
                                        [ 'label' => 'Premium Widget', 'amount' => 19999, 'quantity' => 2 ],
                                        [ 'label' => 'Standard Widget', 'amount' => 9999, 'quantity' => 1 ],
                                        [ 'label' => 'Support Package', 'amount' => 29999, 'quantity' => 1 ]
                                ],
                                'subtotal' => 69997,
                                'discount' => 5000,
                                'shipping' => 1500,
                                'tax'      => 5320,
                                'total'    => 71817
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_domain_data' ],
                'save'        => [ __CLASS__, 'save_domain_data' ]
        ] );

        // 6. Action Buttons Demo
        register_flyout( 'demo_actions', [
                'title'       => 'Action Buttons Demo',
                'size'        => 'medium',
                'fields'      => [
                    // Entity to demonstrate actions on
                        'customer_info' => [
                                'type'     => 'header',
                                'title'    => 'John Smith',
                                'subtitle' => 'Premium Customer',
                                'image'    => 'https://i.pravatar.cc/60?img=12',
                                'badges'   => [
                                        [ 'text' => 'Active', 'type' => 'success' ]
                                ],
                                'meta'     => [
                                        [ 'label' => 'ID', 'value' => '#12345', 'icon' => 'id' ],
                                        [ 'label' => 'Balance', 'value' => '$1,250', 'icon' => 'money-alt' ]
                                ]
                        ],

                        'sep1'          => [
                                'type' => 'separator',
                                'text' => 'Quick Actions'
                        ],

                    // Action buttons component
                        'quick_actions' => [
                                'type'    => 'action_buttons',
                                'layout'  => 'grid',
                                'buttons' => [
                                        [
                                                'text'     => 'Send Invoice',
                                                'action'   => 'send_invoice',
                                                'style'    => 'primary',
                                                'icon'     => 'email-alt',
                                                'data'     => [ 'customer_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    $customer_id = sanitize_text_field( $request['customer_id'] ?? '' );
                                                    sleep( 1 ); // Simulate processing

                                                    return [ 'message' => sprintf( 'Invoice sent to customer #%s', $customer_id ) ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Apply Credit',
                                                'action'   => 'apply_credit',
                                                'style'    => 'secondary',
                                                'icon'     => 'plus-alt',
                                                'confirm'  => 'Apply $50 credit to this account?',
                                                'data'     => [ 'customer_id' => '12345', 'amount' => '50' ],
                                                'callback' => function ( $request ) {
                                                    $customer_id = sanitize_text_field( $request['customer_id'] ?? '' );
                                                    $amount      = sanitize_text_field( $request['amount'] ?? '0' );
                                                    sleep( 1 );

                                                    return [ 'message' => sprintf( '$%s credit applied to customer #%s', $amount, $customer_id ) ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Reset Password',
                                                'action'   => 'reset_password',
                                                'style'    => 'secondary',
                                                'icon'     => 'admin-network',
                                                'confirm'  => 'Send password reset email?',
                                                'data'     => [ 'customer_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    $customer_id = sanitize_text_field( $request['customer_id'] ?? '' );
                                                    sleep( 1 );

                                                    return [ 'message' => sprintf( 'Password reset email sent to customer #%s', $customer_id ) ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Suspend Account',
                                                'action'   => 'suspend_account',
                                                'style'    => 'danger',
                                                'icon'     => 'dismiss',
                                                'confirm'  => 'Are you sure you want to suspend this account?',
                                                'data'     => [ 'customer_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    $customer_id = sanitize_text_field( $request['customer_id'] ?? '' );
                                                    sleep( 2 );

                                                    return [ 'message' => sprintf( 'Account #%s has been suspended', $customer_id ) ];
                                                }
                                        ]
                                ]
                        ],

                        'sep2' => [
                                'type' => 'separator',
                                'text' => 'Status'
                        ],

                        'status_display' => [
                                'type'    => 'info_grid',
                                'columns' => 2,
                                'items'   => [
                                        [ 'label' => 'Last Invoice', 'value' => 'Not sent' ],
                                        [ 'label' => 'Credits Applied', 'value' => '0' ],
                                        [ 'label' => 'Password Resets', 'value' => '0' ],
                                        [ 'label' => 'Account Status', 'value' => 'Active' ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_actions_data' ]
        ] );

        // 7. Action Menu Demo
        register_flyout( 'demo_action_menu', [
                'title'       => 'Action Menu Demo',
                'size'        => 'medium',
                'fields'      => [
                    // Entity header for context
                        'order_info' => [
                                'type'     => 'header',
                                'title'    => 'Order #12345',
                                'subtitle' => 'Processing',
                                'icon'     => 'cart',
                                'badges'   => [
                                        [ 'text' => 'Paid', 'type' => 'success' ],
                                        [ 'text' => 'Priority', 'type' => 'warning' ]
                                ],
                                'meta'     => [
                                        [ 'label' => 'Date', 'value' => date( 'M j, Y' ), 'icon' => 'calendar' ],
                                        [ 'label' => 'Total', 'value' => '$499.99', 'icon' => 'money-alt' ]
                                ]
                        ],

                        'sep1'          => [
                                'type' => 'separator',
                                'text' => 'Order Actions'
                        ],

                    // Action menu component
                        'order_actions' => [
                                'type'         => 'action_menu',
                                'button_text'  => 'Order Actions',
                                'button_icon'  => 'menu-alt',
                                'button_style' => 'primary',
                                'position'     => 'left',
                                'items'        => [
                                        [
                                                'text'     => 'View Details',
                                                'action'   => 'view_order',
                                                'icon'     => 'visibility',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 1 );

                                                    return [ 'message' => 'Opening order details...' ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Print Invoice',
                                                'action'   => 'print_invoice',
                                                'icon'     => 'media-document',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 1 );

                                                    return [ 'message' => 'Preparing invoice for printing...' ];
                                                }
                                        ],
                                        [ 'type' => 'separator' ],
                                        [
                                                'text'     => 'Send Email',
                                                'action'   => 'send_email',
                                                'icon'     => 'email',
                                                'confirm'  => 'Send order confirmation email?',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 1 );

                                                    return [ 'message' => 'Order confirmation email sent successfully' ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Add Note',
                                                'action'   => 'add_order_note',
                                                'icon'     => 'admin-comments',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 1 );

                                                    return [ 'message' => 'Note added to order' ];
                                                }
                                        ],
                                        [ 'type' => 'separator' ],
                                        [
                                                'text'     => 'Issue Refund',
                                                'action'   => 'issue_refund',
                                                'icon'     => 'undo',
                                                'danger'   => true,
                                                'confirm'  => 'Are you sure you want to issue a refund?',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 2 );

                                                    return [ 'message' => 'Refund of $499.99 has been processed' ];
                                                }
                                        ],
                                        [
                                                'text'     => 'Cancel Order',
                                                'action'   => 'cancel_order',
                                                'icon'     => 'dismiss',
                                                'danger'   => true,
                                                'confirm'  => 'This action cannot be undone. Cancel this order?',
                                                'data'     => [ 'order_id' => '12345' ],
                                                'callback' => function ( $request ) {
                                                    sleep( 2 );

                                                    return [ 'message' => 'Order #12345 has been cancelled' ];
                                                }
                                        ]
                                ]
                        ],

                        'sep3' => [
                                'type' => 'separator',
                                'text' => 'Activity Log'
                        ],

                        'activity' => [
                                'type'    => 'timeline',
                                'compact' => false,
                                'items'   => [
                                        [
                                                'title' => 'Order placed',
                                                'date'  => '10:00 AM',
                                                'type'  => 'success'
                                        ],
                                        [
                                                'title' => 'Payment confirmed',
                                                'date'  => '10:05 AM',
                                                'type'  => 'info'
                                        ],
                                        [
                                                'title' => 'Processing started',
                                                'date'  => '10:15 AM',
                                                'type'  => 'default'
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_action_menu_data' ]
        ] );

        // 8. Articles Demo
        register_flyout( 'demo_articles', [
                'title'       => 'Articles & Updates',
                'size'        => 'medium',
                'fields'      => [
                        'recent_articles' => [
                                'type'  => 'articles',
                                'items' => [
                                        [
                                                'title'       => 'New Features Released in Version 2.0',
                                                'date'        => '2 days ago',
                                                'image'       => 'https://via.placeholder.com/400x225/2271b1/ffffff?text=Version+2.0',
                                                'excerpt'     => 'Major update includes improved performance, new UI components, and enhanced security features. Update now to access these improvements.',
                                                'url'         => '#',
                                                'action_text' => 'View changelog'
                                        ],
                                        [
                                                'title'       => 'Black Friday Sale: 40% Off All Plans',
                                                'date'        => '5 days ago',
                                                'image'       => 'https://via.placeholder.com/400x225/d63638/ffffff?text=40%25+OFF',
                                                'excerpt'     => 'Limited time offer! Upgrade your plan and save big. Sale ends November 30th.',
                                                'url'         => '#',
                                                'action_text' => 'Claim discount'
                                        ],
                                        [
                                                'title'       => 'Security Update: Action Required',
                                                'date'        => '1 week ago',
                                                'excerpt'     => 'Important security patch available. All users should update immediately to ensure data protection.',
                                                'url'         => '#',
                                                'action_text' => 'Update now'
                                        ],
                                        [
                                                'title'       => 'New Integration: Stripe Payment Gateway',
                                                'date'        => '2 weeks ago',
                                                'image'       => 'https://via.placeholder.com/400x225/635bff/ffffff?text=Stripe',
                                                'excerpt'     => 'Accept payments seamlessly with our new Stripe integration. Setup takes less than 5 minutes.',
                                                'url'         => '#',
                                                'action_text' => 'Learn more'
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ]
        ] );

        // 9. Stats Demo
        register_flyout( 'demo_stats', [
                'title'       => 'Performance Dashboard',
                'size'        => 'medium',
                'fields'      => [
                        'monthly_stats' => [
                                'type'    => 'stats',
                                'columns' => 3,
                                'items'   => [
                                        [
                                                'label'  => 'Revenue',
                                                'value'  => '$45,231',
                                                'change' => '+12.5%',
                                                'trend'  => 'up',
                                                'icon'   => 'chart-area'
                                        ],
                                        [
                                                'label'  => 'Orders',
                                                'value'  => '1,426',
                                                'change' => '-3.2%',
                                                'trend'  => 'down',
                                                'icon'   => 'cart'
                                        ],
                                        [
                                                'label'  => 'Customers',
                                                'value'  => '2,543',
                                                'change' => '+8.7%',
                                                'trend'  => 'up',
                                                'icon'   => 'admin-users'
                                        ]
                                ]
                        ],

                        'sep' => [
                                'type' => 'separator',
                                'text' => 'Website Stats'
                        ],

                        'site_stats' => [
                                'type'    => 'stats',
                                'columns' => 2,
                                'items'   => [
                                        [
                                                'label'       => 'Page Views',
                                                'value'       => '89.2K',
                                                'description' => 'Last 30 days'
                                        ],
                                        [
                                                'label'  => 'Bounce Rate',
                                                'value'  => '42%',
                                                'change' => '-5%',
                                                'trend'  => 'up'
                                        ],
                                        [
                                                'label' => 'Avg Session',
                                                'value' => '3:42'
                                        ],
                                        [
                                                'label'  => 'Conversion',
                                                'value'  => '4.8%',
                                                'change' => '+0.3%',
                                                'trend'  => 'up'
                                        ]
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ]
        ] );

        // 10. Progress Steps Demo
        register_flyout( 'demo_progress', [
                'title'       => 'Multi-Step Process Demo',
                'subtitle'    => 'Shows progress indicators for multi-step workflows',
                'size'        => 'large',
                'panels'      => [
                        'wizard'   => 'Setup Wizard',
                        'checkout' => 'Checkout Process'
                ],
                'fields'      => [
                    // Setup Wizard Progress
                        'wizard_progress' => [
                                'type'    => 'progress_steps',
                                'panel'   => 'wizard',
                                'steps'   => [
                                        'Account Details',
                                        'Business Information',
                                        'Payment Setup',
                                        'Integrations',
                                        'Review & Launch'
                                ],
                                'current' => 3, // Currently on step 3 (Payment Setup)
                                'style'   => 'numbers'
                        ],

                        'wizard_sep'     => [
                                'type'  => 'separator',
                                'panel' => 'wizard',
                                'text'  => 'Step 3: Payment Setup'
                        ],

                    // Sample fields for current step
                        'payment_method' => [
                                'type'    => 'select',
                                'panel'   => 'wizard',
                                'label'   => 'Payment Gateway',
                                'options' => [
                                        'stripe'    => 'Stripe',
                                        'paypal'    => 'PayPal',
                                        'square'    => 'Square',
                                        'authorize' => 'Authorize.net'
                                ]
                        ],

                        'api_key' => [
                                'type'        => 'password',
                                'panel'       => 'wizard',
                                'label'       => 'API Key',
                                'placeholder' => 'Enter your payment gateway API key'
                        ],

                        'test_mode'         => [
                                'type'  => 'toggle',
                                'panel' => 'wizard',
                                'label' => 'Enable Test Mode'
                        ],

                    // Checkout Process Progress
                        'checkout_progress' => [
                                'type'    => 'progress_steps',
                                'panel'   => 'checkout',
                                'steps'   => [
                                        'Cart',
                                        'Shipping',
                                        'Payment',
                                        'Confirmation'
                                ],
                                'show_if' => [
                                        'test_mode' => 'enabled'
                                ],
                                'current' => 2, // Currently on Shipping
                                'style'   => 'numbers'
                        ],

                        'checkout_sep'     => [
                                'type'  => 'separator',
                                'panel' => 'checkout',
                                'text'  => 'Step 2: Shipping Information'
                        ],

                    // Shipping fields
                        'shipping_address' => [
                                'type'        => 'text',
                                'panel'       => 'checkout',
                                'label'       => 'Street Address',
                                'placeholder' => '123 Main St'
                        ],

                        'shipping_city' => [
                                'type'        => 'text',
                                'panel'       => 'checkout',
                                'label'       => 'City',
                                'placeholder' => 'New York'
                        ],

                        'shipping_zip' => [
                                'type'        => 'text',
                                'panel'       => 'checkout',
                                'label'       => 'ZIP Code',
                                'placeholder' => '10001'
                        ],

                        'shipping_method' => [
                                'type'    => 'radio',
                                'panel'   => 'checkout',
                                'label'   => 'Shipping Method',
                                'options' => [
                                        'standard' => 'Standard (5-7 days) - $5.99',
                                        'express'  => 'Express (2-3 days) - $14.99',
                                        'next_day' => 'Next Day - $29.99'
                                ]
                        ],

                    // Alternative style - Simple progress
                        'simple_sep'      => [
                                'type'  => 'separator',
                                'panel' => 'checkout',
                                'text'  => 'Alternative Styles'
                        ],

                        'simple_progress'    => [
                                'type'    => 'progress_steps',
                                'panel'   => 'checkout',
                                'steps'   => [
                                        'Start',
                                        'Processing',
                                        'Complete'
                                ],
                                'current' => 2,
                                'style'   => 'simple'
                        ],

                    // Completed progress example
                        'completed_progress' => [
                                'type'    => 'progress_steps',
                                'panel'   => 'checkout',
                                'steps'   => [
                                        'Order Placed',
                                        'Payment Confirmed',
                                        'Shipped',
                                        'Delivered'
                                ],
                                'current' => 5, // Beyond the last step means all complete
                                'style'   => 'numbers'
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'load'        => [ __CLASS__, 'load_progress_data' ],
                'save'        => [ __CLASS__, 'save_progress_data' ]
        ] );

        /**
         * Demo 11: Simple Conditional Fields Test
         *
         * One simple test for each pattern
         */

// Register simple conditional test
        register_flyout( 'demo_conditional', [
                'title'       => 'Simple Conditional Test',
                'subtitle'    => 'Testing each dependency type',
                'size'        => 'medium',
                'fields'      => [

                    // TEST 1: Toggle dependency
                        'separator1' => [
                                'type' => 'separator',
                                'text' => 'Test 1: Toggle Dependency'
                        ],

                        'enable_feature' => [
                                'type'  => 'toggle',
                                'label' => 'Enable Feature'
                        ],

                        'feature_name' => [
                                'type'    => 'text',
                                'label'   => 'Feature Name (shows when toggle is on)',
                                'depends' => 'enable_feature'
                        ],

                    // TEST 2: Select dependency
                        'separator2'   => [
                                'type' => 'separator',
                                'text' => 'Test 2: Select Dependency'
                        ],

                        'user_type' => [
                                'type'    => 'select',
                                'label'   => 'User Type',
                                'options' => [
                                        ''        => '-- Select --',
                                        'basic'   => 'Basic User',
                                        'premium' => 'Premium User'
                                ]
                        ],

                        'premium_key' => [
                                'type'    => 'text',
                                'label'   => 'Premium Key (shows for premium only)',
                                'depends' => [
                                        'field' => 'user_type',
                                        'value' => 'premium'
                                ]
                        ],

                    // TEST 3: Checkbox dependency
                        'separator3'  => [
                                'type' => 'separator',
                                'text' => 'Test 3: Checkbox Contains'
                        ],

                        'addons' => [
                                'type'    => 'card_choice',
                                'mode'    => 'checkbox',
                                'label'   => 'Select Addons',
                                'options' => [
                                        'api'   => 'API Access',
                                        'email' => 'Email Support'
                                ]
                        ],

                        'api_key' => [
                                'type'    => 'text',
                                'label'   => 'API Key (shows when API selected)',
                                'depends' => [
                                        'field'    => 'addons',
                                        'contains' => 'api'
                                ]
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'save'        => function ( $id, $data ) {
                    update_option( 'flyout_conditional_test', $data );
                    error_log( 'Saved fields: ' . print_r( array_keys( $data ), true ) );

                    return true;
                },
                'load'        => function ( $id ) {
                    return get_option( 'flyout_conditional_test', [] );
                }
        ] );

        /**
         * Demo 12: Image Gallery
         *
         * Simple test of the image gallery component
         */
// Register image gallery demo
        register_flyout( 'demo_image_gallery', [
                'title'       => 'Image Gallery Demo',
                'subtitle'    => 'Upload and manage images with drag-drop reordering',
                'size'        => 'large',
                'fields'      => [

                    // Basic gallery
                        'separator1' => [
                                'type' => 'separator',
                                'text' => 'Product Images'
                        ],

                        'product_images' => [
                                'type'         => 'image_gallery',
                                'name'         => 'product_images',
                                'max_images'   => 6,
                                'columns'      => 3,
                                'show_caption' => true,
                                'show_alt'     => true,
                                'items'        => null //[1150, 1146] // Will be populated on load
                        ],

                    // Gallery without limits
                        'separator2'     => [
                                'type' => 'separator',
                                'text' => 'Portfolio Gallery'
                        ],

                        'portfolio'  => [
                                'type'         => 'image_gallery',
                                'name'         => 'portfolio',
                                'max_images'   => 0, // Unlimited
                                'columns'      => 4,
                                'show_caption' => true,
                                'show_alt'     => false,
                                'items'        => []
                        ],

                    // Minimal gallery (no captions/alt)
                        'separator3' => [
                                'type' => 'separator',
                                'text' => 'Simple Image List'
                        ],

                        'simple_images' => [
                                'type'         => 'image_gallery',
                                'name'         => 'simple_images',
                                'max_images'   => 3,
                                'columns'      => 6,
                                'show_caption' => false,
                                'show_alt'     => false,
                                'size'         => 'medium',
                                'items'        => []
                        ]
                ],
                'admin_pages' => [ 'toplevel_page_wp-flyout-demo' ],
                'save'        => function ( $id, $data ) {
                    // Save each gallery separately for easier debugging
                    update_option( 'flyout_product_images', $data['product_images'] ?? [] );
                    update_option( 'flyout_portfolio', $data['portfolio'] ?? [] );
                    update_option( 'flyout_simple_images', $data['simple_images'] ?? [] );

                    error_log( 'Saved galleries: ' . print_r( [
                                    'product_images' => count( $data['product_images'] ?? [] ),
                                    'portfolio'      => count( $data['portfolio'] ?? [] ),
                                    'simple_images'  => count( $data['simple_images'] ?? [] )
                            ], true ) );

                    return true;
                },
                'load'        => function ( $id ) {
                    return [
                            'product_images' => get_option( 'flyout_product_images', [] ),
                            'portfolio'      => get_option( 'flyout_portfolio', [] ),
                            'simple_images'  => get_option( 'flyout_simple_images', [] )
                    ];
                }
        ] );
    }

    /**
     * Add admin menu page
     */
    public static function add_admin_menu() {
        add_menu_page(
                'WP Flyout Demo',
                'Flyout Demo',
                'manage_options',
                'wp-flyout-demo',
                [ __CLASS__, 'render_admin_page' ],
                'dashicons-slides',
                30
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Flyout Demo (Callback Version)</h1>
            <p>Simple demonstration of all WP Flyout components using the new callback-based approach.</p>

            <div class="card">
                <h2>Demo Flyouts</h2>

                <h3>1. Form Fields</h3>
                <p>Demonstrates all form field types with AJAX callbacks for select fields.</p>
                <p>
                    <?php render_flyout_button( 'demo_form_fields', [], [
                            'text'  => 'Open Form Fields Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'forms'
                    ] ); ?>
                </p>

                <h3>2. Display Components</h3>
                <p>Shows entity headers, alerts, data tables, info grids, empty states, and separators.</p>
                <p>
                    <?php render_flyout_button( 'demo_display', [], [
                            'text'  => 'Open Display Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'visibility'
                    ] ); ?>
                </p>

                <h3>3. Interactive Components</h3>
                <p>Line items with callbacks for search and details, notes with add/delete callbacks, and file
                    manager.</p>
                <p>
                    <?php render_flyout_button( 'demo_interactive', [], [
                            'text'  => 'Open Interactive Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'admin-generic'
                    ] ); ?>
                </p>

                <h3>4. Layout Components</h3>
                <p>Card choices, timeline, accordion, and multi-panel layout.</p>
                <p>
                    <?php render_flyout_button( 'demo_layout', [], [
                            'text'  => 'Open Layout Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'layout'
                    ] ); ?>
                </p>

                <h3>5. Domain Components</h3>
                <p>Payment methods and price summaries.</p>
                <p>
                    <?php render_flyout_button( 'demo_domain', [], [
                            'text'  => 'Open Domain Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'cart'
                    ] ); ?>
                </p>

                <h3>6. Action Buttons</h3>
                <p>Action buttons with inline callbacks, confirmations, and loading states.</p>
                <p>
                    <?php render_flyout_button( 'demo_actions', [], [
                            'text'  => 'Open Actions Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'admin-generic'
                    ] ); ?>
                </p>

                <h3>7. Action Menu</h3>
                <p>Dropdown action menu with inline callbacks, separators, and danger states.</p>
                <p>
                    <?php render_flyout_button( 'demo_action_menu', [], [
                            'text'  => 'Open Action Menu Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'menu-alt'
                    ] ); ?>
                </p>

                <h3>8. Articles</h3>
                <p>Display article cards with images, dates, and excerpts.</p>
                <p>
                    <?php render_flyout_button( 'demo_articles', [], [
                            'text'  => 'Open Articles Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'admin-post'
                    ] ); ?>
                </p>

                <h3>9. Stats Dashboard</h3>
                <p>Display metric cards with values and trends.</p>
                <p>
                    <?php render_flyout_button( 'demo_stats', [], [
                            'text'  => 'Open Stats Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'chart-bar'
                    ] ); ?>
                </p>

                <h3>10. Progress Steps</h3>
                <p>Multi-step process indicators for wizards and workflows.</p>
                <p>
                    <?php render_flyout_button( 'demo_progress', [], [
                            'text'  => 'Open Progress Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'controls-forward'
                    ] ); ?>
                </p>

                <h3>Demo 11: Conditional Fields Test</h3>
                <p>Test all three conditional patterns: toggle dependencies, select value matching, and checkbox
                    contains checks.</p>
                <p>
                    <?php render_flyout_button( 'demo_conditional', [], [
                            'text'  => 'Open Conditional Fields Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'visibility'
                    ] ); ?>
                </p>

                <h3>Demo 12: Image Gallery</h3>
                <p>Visual image management with drag-drop reordering, media library integration, and optional
                    captions/alt text.</p>
                <p>
                    <?php render_flyout_button( 'demo_image_gallery', [], [
                            'text'  => 'Open Image Gallery Demo',
                            'class' => 'button button-primary',
                            'icon'  => 'format-gallery'
                    ] ); ?>
                </p>
            </div>

            <div class="card">
                <h2>Key Changes in Callback Version</h2>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>All AJAX operations now use inline callbacks instead of separate registered actions</li>
                    <li>Security (nonces and capability checks) handled automatically</li>
                    <li>Cleaner API - all related logic stays in the flyout configuration</li>
                    <li>No need to manually register wp_ajax_* handlers</li>
                </ul>
            </div>
        </div>
        <?php
    }

    // Load/Save/Delete handlers for each demo

    public static function load_form_data( $id ) {
        $data = get_option( self::OPTION_KEY . '_form', [] );

        return wp_parse_args( $data, [
                'name'         => 'John Doe',
                'email'        => 'john@example.com',
                'skills'       => [ 'PHP', 'JavaScript', 'CSS' ],
                'newsletter'   => '1',
                'country'      => 'us',
                'ajax_country' => 'US'
        ] );
    }

    public static function save_form_data( $id, $data ) {
        update_option( self::OPTION_KEY . '_form', $data );

        return [
                'success' => true,
                'message' => 'Saved ' . count( array_keys( $data ) ) . ' fields successfully!',
                'reload'  => false
        ];
    }

    public static function delete_form_data( $id ) {
        delete_option( self::OPTION_KEY . '_form' );

        return [
                'success' => true,
                'message' => 'Form data deleted!',
                'reload'  => true
        ];
    }

    public static function load_display_data( $id ) {
        return [];
    }

    public static function save_display_data( $id, $data ) {
        return [ 'success' => true, 'message' => 'Display demo completed!' ];
    }

    public static function load_interactive_data( $id ) {
        $data = get_option( self::OPTION_KEY . '_interactive', [] );

        // Load stored notes
        $data['notes'] = get_option( self::OPTION_KEY . '_notes', [] );

        // If no notes exist, add a sample
        if ( empty( $data['notes'] ) ) {
            $data['notes'] = [
                    [
                            'id'             => '1',
                            'content'        => 'This is a sample note',
                            'author'         => 'Admin',
                            'formatted_date' => date( 'M j, Y g:i a' ),
                            'can_delete'     => true
                    ]
            ];
        }

        return $data;
    }

    public static function save_interactive_data( $id, $data ) {
        update_option( self::OPTION_KEY . '_interactive', $data );

        return [ 'success' => true, 'message' => 'Interactive data saved!' ];
    }

    public static function load_layout_data( $id ) {
        $data = get_option( self::OPTION_KEY . '_layout', [] );

        return wp_parse_args( $data, [
                'subscription_plan' => 'pro',
                'features'          => [ 'email', 'webhook' ]
        ] );
    }

    public static function save_layout_data( $id, $data ) {
        update_option( self::OPTION_KEY . '_layout', $data );

        return [ 'success' => true, 'message' => 'Layout preferences saved!' ];
    }

    public static function load_domain_data( $id ) {
        return [];
    }

    public static function save_domain_data( $id, $data ) {
        return [ 'success' => true, 'message' => 'Domain demo completed!' ];
    }

    public static function load_actions_data( $id ) {
        return [];
    }

    public static function load_action_menu_data( $id ) {
        return [];
    }

    public static function load_progress_data( $id ) {
        $data = get_option( self::OPTION_KEY . '_progress', [] );

        return wp_parse_args( $data, [
                'payment_method'  => 'stripe',
                'test_mode'       => '1',
                'shipping_method' => 'standard'
        ] );
    }

    public static function save_progress_data( $id, $data ) {
        update_option( self::OPTION_KEY . '_progress', $data );

        // You could update the progress step here based on what's completed
        return [
                'success' => true,
                'message' => 'Progress saved! Moving to next step...',
                'reload'  => false
        ];
    }

    /**
     * Load data for conditional demo
     */
    function load_conditional_data( $id ) {
        // Load from options or return defaults
        $data = get_option( 'flyout_conditional_demo', [] );

        // Set some defaults for testing
        if ( empty( $data ) ) {
            $data = [
                    'enable_shipping'      => false,
                    'account_type'         => 'personal',
                    'features'             => [],
                    'environment'          => 'development',
                    'payment_gateway'      => '',
                    'enable_notifications' => false,
                    'notification_types'   => []
            ];
        }

        return $data;
    }

    /**
     * Save conditional demo data
     */
    function save_conditional_data( $id, $data ) {
        // Log what fields were submitted (visible vs hidden)
        error_log( 'Conditional Demo - Submitted fields: ' . print_r( array_keys( $data ), true ) );

        // Save to options
        update_option( 'flyout_conditional_demo', $data );

        // Log specific conditional field states
        $log_message = "Conditional Field States:\n";
        $log_message .= "- Shipping enabled: " . ( ! empty( $data['enable_shipping'] ) ? 'Yes' : 'No' ) . "\n";

        if ( ! empty( $data['enable_shipping'] ) ) {
            $log_message .= "  - Address: " . ( $data['shipping_address'] ?? 'Not provided' ) . "\n";
            $log_message .= "  - City: " . ( $data['shipping_city'] ?? 'Not provided' ) . "\n";
            $log_message .= "  - ZIP: " . ( $data['shipping_zip'] ?? 'Not provided' ) . "\n";
        }

        $log_message .= "- Account type: " . ( $data['account_type'] ?? 'Not set' ) . "\n";

        if ( $data['account_type'] === 'business' ) {
            $log_message .= "  - Company: " . ( $data['company_name'] ?? 'Not provided' ) . "\n";
            $log_message .= "  - Tax ID: " . ( $data['tax_id'] ?? 'Not provided' ) . "\n";
        }

        $log_message .= "- Features selected: " . implode( ', ', (array) ( $data['features'] ?? [] ) ) . "\n";

        if ( ! empty( $data['features'] ) && in_array( 'api', $data['features'] ) ) {
            $log_message .= "  - API Key: " . ( ! empty( $data['api_key'] ) ? 'Set' : 'Not set' ) . "\n";
        }

        error_log( $log_message );

        return true;
    }

}

// Initialize the demo
add_action( 'plugins_loaded', [ 'WP_Flyout_Demo', 'init' ] );