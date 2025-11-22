<?php
/**
 * Form Field Component
 *
 * Unified form field rendering with support for all input types.
 * Replaces individual field components with a single configurable class.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     5.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Components\Separator;
use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

/**
 * Class FormField
 *
 * Renders form fields based on configuration array.
 *
 * @since 5.0.0
 */
class FormField implements Renderable {

    /**
     * Field configuration
     *
     * @since 5.0.0
     * @var array
     */
    private array $config = [];

    /**
     * Field type renderers
     *
     * @since 5.0.0
     * @var array
     */
    private static array $type_renderers = [
            'text'        => 'render_input',
            'email'       => 'render_input',
            'url'         => 'render_input',
            'tel'         => 'render_input',
            'number'      => 'render_input',
            'password'    => 'render_input',
            'date'        => 'render_input',
            'hidden'      => 'render_hidden',
            'textarea'    => 'render_textarea',
            'select'      => 'render_select',
            'ajax_select' => 'render_ajax_select',
            'toggle'      => 'render_toggle',
            'radio'       => 'render_radio',
            'tags'        => 'render_tags',
            'color'       => 'render_color',
            'group'       => 'render_group',
            'separator'   => 'render_separator',
    ];

    /**
     * Constructor
     *
     * @param array $config Field configuration array.
     *
     * @since 5.0.0
     *
     */
    public function __construct( array $config ) {
        $this->config = $this->normalize_config( $config );
    }

    /**
     * Normalize field configuration
     *
     * Ensures all required keys exist with appropriate defaults.
     *
     * @param array $config Raw configuration array.
     *
     * @return array Normalized configuration.
     * @since 5.0.0
     *
     */
    private function normalize_config( array $config ): array {
        $defaults = [
                'type'          => 'text',
                'name'          => '',
                'id'            => '',
                'label'         => '',
                'value'         => '',
                'description'   => '',
                'placeholder'   => '',
                'required'      => false,
                'disabled'      => false,
                'readonly'      => false,
                'class'         => '',
                'wrapper_class' => '',
                'data_callback' => null,
                'condition'     => null,
        ];

        // Type-specific defaults
        $type_defaults = $this->get_type_defaults( $config['type'] ?? 'text' );

        // Merge all defaults
        $config = array_merge( $defaults, $type_defaults, $config );

        // Auto-generate ID from name if not provided
        if ( empty( $config['id'] ) && ! empty( $config['name'] ) ) {
            $config['id'] = sanitize_key( $config['name'] );
        }

        // Load value from callback if provided
        if ( is_callable( $config['data_callback'] ) ) {
            $config['value'] = call_user_func( $config['data_callback'] );
        }

        // Set appropriate class based on type
        if ( empty( $config['class'] ) ) {
            $config['class'] = $this->get_default_class( (string) $config['type'] );
        }

        return $config;
    }

    /**
     * Get type-specific default configuration
     *
     * @param string $type Field type.
     *
     * @return array Type-specific defaults.
     * @since 5.0.0
     */
    private function get_type_defaults( string $type ): array {
        $defaults = [
                'textarea'    => [
                        'rows' => 5,
                        'cols' => 50,
                ],
                'select'      => [
                        'options'  => [],
                        'multiple' => false,
                ],
                'ajax_select' => [
                        'ajax'        => '',  // The AJAX action to call
                        'options'     => [],  // Pre-loaded options if available
                        'placeholder' => __( 'Type to search...', 'wp-flyout' ),
                ],
                'number'      => [
                        'min'  => null,
                        'max'  => null,
                        'step' => 1,
                ],
                'toggle'      => [
                        'checked' => false,
                        'value'   => '1',
                ],
                'tags'        => [
                        'placeholder' => 'Add tags...'
                ],
                'radio'       => [
                        'options' => [],
                ],
                'color'       => [
                        'default' => '#000000',
                ],
                'separator'   => [
                        'text'   => '',
                        'icon'   => '',
                        'margin' => '20px',
                        'style'  => 'line',
                        'align'  => 'center'
                ],
        ];

        return $defaults[ $type ] ?? [];
    }

    /**
     * Get default CSS class for field type
     *
     * @param string $type Field type.
     *
     * @return string Default CSS class.
     * @since 5.0.0
     *
     */
    private function get_default_class( string $type ): string {
        $classes = [
                'text'     => 'regular-text',
                'email'    => 'regular-text',
                'url'      => 'large-text',
                'textarea' => 'large-text',
                'select'   => 'regular-text',
                'number'   => 'small-text',
                'color'    => 'small-text',
        ];

        return $classes[ $type ] ?? '';
    }

    /**
     * Check if field should be displayed based on conditions
     *
     * @return bool True if field should be displayed.
     * @since 5.0.0
     *
     */
    private function should_display(): bool {
        if ( empty( $this->config['condition'] ) ) {
            return true;
        }

        // TODO: Implement condition checking
        // This would check against other field values in the form
        return true;
    }

    /**
     * Render the form field
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    public function render(): string {
        if ( ! $this->should_display() ) {
            return '';
        }

        // Hidden fields don't need wrapper
        if ( $this->config['type'] === 'hidden' ) {
            return $this->render_hidden();
        }

        $wrapper_classes = [
                'wp-flyout-field',
                'field-type-' . $this->config['type'],
                $this->config['wrapper_class']
        ];

        // Build wrapper attributes from config
        $wrapper_attrs = $this->config['wrapper_attrs'] ?? [];

        // Handle classes
        if ( ! empty( $wrapper_attrs['class'] ) ) {
            $wrapper_classes[] = $wrapper_attrs['class'];
            unset( $wrapper_attrs['class'] );
        }

        // Build attribute string
        $attrs_html = '';

        // Add ID
        if ( ! empty( $wrapper_attrs['id'] ) ) {
            $attrs_html .= sprintf( ' id="%s"', esc_attr( $wrapper_attrs['id'] ) );
            unset( $wrapper_attrs['id'] );
        }

        // Add remaining attributes
        foreach ( $wrapper_attrs as $attr => $value ) {
            // Special handling for data-depends - it's already JSON encoded
            if ( $attr === 'data-depends' ) {
                $attrs_html .= sprintf( ' %s=\'%s\'', $attr, $value );
            } else {
                $attrs_html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
            }
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>"<?php echo $attrs_html; ?>>
            <?php if ( ! in_array( $this->config['type'], [ 'toggle', 'radio' ], true ) && $this->config['label'] ) : ?>
                <label for="<?php echo esc_attr( $this->config['id'] ); ?>">
                    <?php echo esc_html( $this->config['label'] ); ?>
                    <?php if ( $this->config['required'] ) : ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <?php echo $this->render_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( $this->config['description'] ) : ?>
                <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the field element
     *
     * @return string Generated field HTML.
     * @since 5.0.0
     *
     */
    private function render_field(): string {
        $type = $this->config['type'];

        // Get renderer method
        $renderer = self::$type_renderers[ $type ] ?? 'render_input';

        if ( ! method_exists( $this, $renderer ) ) {
            return '';
        }

        return $this->$renderer();
    }

    /**
     * Render standard input fields
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_input(): string {
        $valid_types = [ 'text', 'email', 'url', 'number', 'tel', 'password', 'date', 'search' ];
        $type        = in_array( $this->config['type'], $valid_types, true ) ? $this->config['type'] : 'text';

        $attrs = [
                'type'        => $type,
                'id'          => $this->config['id'],
                'name'        => $this->config['name'],
                'value'       => $this->config['value'],
                'class'       => $this->config['class'],
                'placeholder' => $this->config['placeholder'],
        ];

        // Add type-specific attributes
        if ( $type === 'number' ) {
            if ( $this->config['min'] !== null ) {
                $attrs['min'] = $this->config['min'];
            }
            if ( $this->config['max'] !== null ) {
                $attrs['max'] = $this->config['max'];
            }
            if ( $this->config['step'] !== null ) {
                $attrs['step'] = $this->config['step'];
            }
        }

        // Build HTML
        $html = '<input';
        foreach ( $attrs as $key => $value ) {
            if ( $value !== '' && $value !== null ) {
                $html .= sprintf( ' %s="%s"', $key, esc_attr( (string) $value ) );
            }
        }

        // Add boolean attributes
        if ( $this->config['required'] ) {
            $html .= ' required';
        }
        if ( $this->config['disabled'] ) {
            $html .= ' disabled';
        }
        if ( $this->config['readonly'] ) {
            $html .= ' readonly';
        }

        $html .= '>';

        return $html;
    }

    /**
     * Render hidden input
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_hidden(): string {
        return sprintf(
                '<input type="hidden" name="%s" value="%s">',
                esc_attr( $this->config['name'] ),
                esc_attr( (string) $this->config['value'] )
        );
    }

    /**
     * Render textarea field
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_textarea(): string {
        return sprintf(
                '<textarea id="%s" name="%s" class="%s" rows="%d" cols="%d" placeholder="%s" %s %s %s>%s</textarea>',
                esc_attr( $this->config['id'] ),
                esc_attr( $this->config['name'] ),
                esc_attr( $this->config['class'] ),
                absint( $this->config['rows'] ),
                absint( $this->config['cols'] ),
                esc_attr( $this->config['placeholder'] ),
                $this->config['required'] ? 'required' : '',
                $this->config['disabled'] ? 'disabled' : '',
                $this->config['readonly'] ? 'readonly' : '',
                esc_textarea( $this->config['value'] )
        );
    }

    /**
     * Render select field
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_select(): string {
        ob_start();
        ?>
        <select id="<?php echo esc_attr( $this->config['id'] ); ?>"
                name="<?php echo esc_attr( $this->config['name'] ); ?><?php echo $this->config['multiple'] ? '[]' : ''; ?>"
                class="<?php echo esc_attr( $this->config['class'] ); ?>"
                <?php echo $this->config['required'] ? 'required' : ''; ?>
                <?php echo $this->config['disabled'] ? 'disabled' : ''; ?>
                <?php echo $this->config['multiple'] ? 'multiple' : ''; ?>>
            <?php if ( $this->config['placeholder'] ) : ?>
                <option value=""><?php echo esc_html( $this->config['placeholder'] ); ?></option>
            <?php endif; ?>
            <?php foreach ( $this->config['options'] as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>"
                        <?php
                        if ( $this->config['multiple'] && is_array( $this->config['value'] ) ) {
                            selected( in_array( $value, $this->config['value'], true ) );
                        } else {
                            selected( $this->config['value'], $value );
                        }
                        ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Render toggle field
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_toggle(): string {
        $is_checked = ! empty( $this->config['value'] ) || ! empty( $this->config['checked'] );

        ob_start();
        ?>
        <label class="wp-flyout-toggle">
            <input type="checkbox"
                   name="<?php echo esc_attr( $this->config['name'] ); ?>"
                   id="<?php echo esc_attr( $this->config['id'] ); ?>"
                   value="<?php echo esc_attr( $this->config['value'] ?: '1' ); ?>"
                    <?php checked( $is_checked ); ?>
                    <?php disabled( $this->config['disabled'] ); ?>>
            <span class="toggle-slider"></span>
            <span class="toggle-label"><?php echo esc_html( $this->config['label'] ); ?></span>
        </label>
        <?php
        return ob_get_clean();
    }

    /**
     * Render AJAX select field
     *
     * @return string Generated HTML
     * @since 5.0.0
     */
    private function render_ajax_select(): string {
        // Remove nonce generation - Manager provides it
        $ajax_action = $this->config['ajax'] ?? '';
        $nonce       = $this->config['nonce'] ?? '';  // Use provided nonce

        ob_start();
        ?>
        <select id="<?php echo esc_attr( $this->config['id'] ); ?>"
                name="<?php echo esc_attr( $this->config['name'] ); ?>"
                class="<?php echo esc_attr( $this->config['class'] ); ?>"
                data-ajax="<?php echo esc_attr( $ajax_action ); ?>"
                data-nonce="<?php echo esc_attr( $nonce ); ?>"
                data-placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                <?php echo $this->config['required'] ? 'required' : ''; ?>
                <?php echo $this->config['disabled'] ? 'disabled' : ''; ?>>

            <?php
            // If we have options provided (value => label pairs), add them
            if ( ! empty( $this->config['options'] ) && is_array( $this->config['options'] ) ) :
                foreach ( $this->config['options'] as $value => $label ) :
                    $selected = ( $value == $this->config['value'] ) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php
                endforeach;
            // Otherwise, if we just have a value, create empty option
            elseif ( ! empty( $this->config['value'] ) ) :
                ?>
                <option value="<?php echo esc_attr( $this->config['value'] ); ?>" selected>
                    Loading...
                </option>
            <?php endif; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tags input field - Simplified
     *
     * @return string Generated HTML.
     * @since 5.0.0
     */
    private function render_tags(): string {
        $value = is_array( $this->config['value'] ) ? $this->config['value'] : [];
        ob_start();
        ?>
        <div class="wp-flyout-tag-input"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>">
            <div class="tag-input-container">
                <?php foreach ( $value as $tag ) : ?>
                    <span class="tag-item" data-tag="<?php echo esc_attr( $tag ); ?>">
                    <span class="tag-text"><?php echo esc_html( $tag ); ?></span>
                    <?php if ( ! $this->config['readonly'] ) : ?>
                        <button type="button" class="tag-remove" aria-label="Remove">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    <?php endif; ?>
                </span>
                <?php endforeach; ?>
                <?php if ( ! $this->config['readonly'] ) : ?>
                    <input type="text"
                           class="tag-input-field"
                           placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>">
                <?php endif; ?>
            </div>
            <?php foreach ( $value as $tag ) : ?>
                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[]"
                       value="<?php echo esc_attr( $tag ); ?>">
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render radio button group
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_radio(): string {
        ob_start();
        ?>
        <div class="wp-flyout-radio-group">
            <?php foreach ( $this->config['options'] as $value => $label ) : ?>
                <label class="wp-flyout-radio">
                    <input type="radio"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                            <?php checked( $this->config['value'], $value ); ?>
                            <?php echo $this->config['required'] ? 'required' : ''; ?>
                            <?php echo $this->config['disabled'] ? 'disabled' : ''; ?>>
                    <span class="radio-label"><?php echo esc_html( $label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render color picker field
     *
     * @return string Generated HTML.
     * @since 5.0.0
     *
     */
    private function render_color(): string {
        $value = $this->config['value'] ?: $this->config['default'];

        ob_start();
        ?>
        <div class="wp-flyout-color-wrapper">
            <input type="color"
                   id="<?php echo esc_attr( $this->config['id'] ); ?>"
                   name="<?php echo esc_attr( $this->config['name'] ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                   class="wp-flyout-color-input"
                    <?php echo $this->config['required'] ? 'required' : ''; ?>
                    <?php echo $this->config['disabled'] ? 'disabled' : ''; ?>>
            <input type="text"
                   value="<?php echo esc_attr( $value ); ?>"
                   class="wp-flyout-color-preview"
                   readonly>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render grouped fields
     *
     * @return string Generated HTML.
     */
    private function render_group(): string {
        if ( empty( $this->config['fields'] ) ) {
            return '';
        }

        $layout_class = $this->config['layout'] === 'horizontal' ? 'flex' : 'block';
        $gap_style    = $this->config['layout'] === 'horizontal' ? 'gap: ' . $this->config['gap'] : '';

        ob_start();
        ?>
        <div class="wp-flyout-field-group"
             style="display: <?php echo esc_attr( $layout_class ); ?>; <?php echo esc_attr( $gap_style ); ?>;">
            <?php foreach ( $this->config['fields'] as $field_config ) : ?>
                <?php
                $field = new self( $field_config );
                $flex  = $field_config['flex'] ?? 1;
                ?>
                <div style="flex: <?php echo esc_attr( $flex ); ?>;">
                    <?php echo $field->render(); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render separator
     *
     * @return string Generated HTML.
     */
    private function render_separator(): string {
        $separator = new Separator( [
                'text'   => $this->config['text'] ?? '',
                'icon'   => $this->config['icon'] ?? '',
                'margin' => $this->config['margin'] ?? '20px',
                'style'  => $this->config['style'] ?? 'line',
                'align'  => $this->config['align'] ?? 'center'
        ] );

        return $separator->render();
    }

}