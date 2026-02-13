<?php
/**
 * Notes Component
 *
 * Displays notes with optional add/delete functionality via REST API.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

class Notes implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'notes-' . wp_generate_uuid4();
        }

        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'            => '',
                'name'          => 'notes',
                'label'         => '',
                'items'         => [],
                'editable'      => true,
                'placeholder'   => __( 'Add a note... (Shift+Enter to submit)', 'arraypress' ),
                'empty_text'    => __( 'No notes yet.', 'arraypress' ),
                'object_type'   => '',
                'add_action'    => 'add',
                'delete_action' => 'delete',
                'class'         => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-notes' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-object-type="<?php echo esc_attr( $this->config['object_type'] ); ?>"
             data-add-action="<?php echo esc_attr( $this->config['add_action'] ); ?>"
             data-delete-action="<?php echo esc_attr( $this->config['delete_action'] ); ?>">

            <?php if ( ! empty( $this->config['label'] ) ) : ?>
                <label class="wp-flyout-component-label"><?php echo esc_html( $this->config['label'] ); ?></label>
            <?php endif; ?>

            <div class="notes-list">
                <?php if ( empty( $this->config['items'] ) ) : ?>
                    <p class="no-notes"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                <?php else : ?>
                    <?php foreach ( $this->config['items'] as $note ) : ?>
                        <?php $this->render_note( $note ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['editable'] && $this->config['add_action'] ) : ?>
                <div class="note-add-form">
                    <textarea placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                              rows="3"></textarea>
                    <p>
                        <button type="button" class="button button-primary" data-action="add-note">
                            Add Note
                        </button>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single note
     *
     * @param array $note Note data
     */
    private function render_note( array $note ): void {
        ?>
        <div class="note-item" data-note-id="<?php echo esc_attr( $note['id'] ?? '' ); ?>">
            <div class="note-header">
                <?php if ( ! empty( $note['author'] ) ) : ?>
                    <span class="note-author"><?php echo esc_html( $note['author'] ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $note['formatted_date'] ) ) : ?>
                    <span class="note-date"><?php echo esc_html( $note['formatted_date'] ); ?></span>
                <?php endif; ?>

                <?php if ( $this->config['editable'] && $this->config['delete_action'] && ! empty( $note['can_delete'] ) ) : ?>
                    <button type="button" class="button-link" data-action="delete-note">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="note-content">
                <?php echo nl2br( esc_html( $note['content'] ?? '' ) ); ?>
            </div>
        </div>
        <?php
    }

}