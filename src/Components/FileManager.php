<?php
/**
 * File Manager Component
 *
 * Enhanced file management with drag-and-drop sorting and media library integration
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;
use ArrayPress\RegisterFlyouts\Traits\FileUtilities;

/**
 * Class FileManager
 *
 * Manages file attachments with media library integration and drag-drop sorting.
 *
 * @since 6.0.0
 */
class FileManager implements Renderable {
    use FileUtilities;

    /**
     * Component configuration
     *
     * @since 6.0.0
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @since 6.0.0
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'file-manager-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Get default configuration
     *
     * @since 6.0.0
     *
     * @return array Default configuration values
     */
    private static function get_defaults(): array {
        return [
                'id'          => '',
                'name'        => 'files',
                'items'       => [],
                'max_files'   => 0,  // 0 = unlimited
                'reorderable' => true,
                'add_text'    => __( 'Add File', 'wp-flyout' ),
                'empty_text'  => __( 'No files attached yet', 'wp-flyout' ),
                'class'       => ''
        ];
    }

    /**
     * Render the component
     *
     * @since 6.0.0
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $classes = [ 'wp-flyout-file-manager' ];

        if ( $this->config['reorderable'] ) {
            $classes[] = 'is-sortable';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-prefix="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-max-files="<?php echo esc_attr( $this->config['max_files'] ); ?>"
             data-template='<?php echo $this->get_template(); ?>'>

            <div class="file-manager-header">
				<span class="file-manager-title">
					<span class="dashicons dashicons-paperclip"></span>
					<?php esc_html_e( 'Attachments', 'wp-flyout' ); ?>
                    <?php if ( $this->config['max_files'] > 0 ) : ?>
                        <span class="file-count">
							(<span class="current-count"><?php echo count( $this->config['items'] ); ?></span>/<?php echo $this->config['max_files']; ?>)
						</span>
                    <?php endif; ?>
				</span>

                <button type="button"
                        class="button button-small file-manager-add"
                        data-action="add"
                        <?php if ( $this->config['max_files'] > 0 && count( $this->config['items'] ) >= $this->config['max_files'] ) : ?>
                            disabled
                        <?php endif; ?>>
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>
            </div>

            <div class="file-manager-list <?php echo empty( $this->config['items'] ) ? 'is-empty' : ''; ?>">
                <div class="file-manager-empty">
                    <span class="dashicons dashicons-media-document"></span>
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>

                <div class="file-manager-items">
                    <?php foreach ( $this->config['items'] as $index => $file ) : ?>
                        <?php $this->render_file_item( $file, $index ); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single file item
     *
     * @since 6.0.0
     * @access private
     *
     * @param array $file  File data
     * @param int   $index Item index
     *
     * @return void
     */
    private function render_file_item( array $file, int $index ): void {
        $file_extension = $this->get_file_extension( $file['url'] ?? '' );
        $file_icon      = $this->get_file_icon( $file_extension );
        $lookup_key     = $file['lookup_key'] ?? '';
        ?>
        <div class="file-manager-item" data-index="<?php echo $index; ?>">
            <?php if ( $this->config['reorderable'] ) : ?>
                <span class="file-handle" title="<?php esc_attr_e( 'Drag to reorder', 'wp-flyout' ); ?>">
				<span class="dashicons dashicons-menu"></span>
			</span>
            <?php endif; ?>

            <div class="file-icon" data-extension="<?php echo esc_attr( $file_extension ); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr( $file_icon ); ?>"></span>
                <?php if ( $file_extension ) : ?>
                    <span class="file-extension"><?php echo esc_html( strtoupper( $file_extension ) ); ?></span>
                <?php endif; ?>
            </div>

            <div class="file-details">
                <input type="text"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $file['name'] ?? '' ); ?>"
                       placeholder="<?php esc_attr_e( 'File name', 'wp-flyout' ); ?>"
                       data-field="name"
                       class="file-name-input">

                <input type="url"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][url]"
                       value="<?php echo esc_attr( $file['url'] ?? '' ); ?>"
                       placeholder="<?php esc_attr_e( 'File URL', 'wp-flyout' ); ?>"
                       data-field="url"
                       class="file-url-input">

                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][attachment_id]"
                       value="<?php echo esc_attr( $file['attachment_id'] ?? $file['id'] ?? '' ); ?>"
                       data-field="attachment_id">

                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][lookup_key]"
                       value="<?php echo esc_attr( $lookup_key ); ?>"
                       data-field="lookup_key">
            </div>

            <div class="file-actions">
                <button type="button"
                        class="file-action-btn"
                        data-action="browse"
                        title="<?php esc_attr_e( 'Browse media library', 'wp-flyout' ); ?>">
                    <span class="dashicons dashicons-admin-media"></span>
                </button>

                <button type="button"
                        class="file-action-btn file-remove"
                        data-action="remove"
                        title="<?php esc_attr_e( 'Remove file', 'wp-flyout' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Get item template for JavaScript
     *
     * @since 6.0.0
     * @access private
     *
     * @return string JavaScript template HTML
     */
    private function get_template(): string {
        ob_start();
        ?>
        <div class="file-manager-item" data-index="{{index}}">
        <?php if ( $this->config['reorderable'] ) : ?>
            <span class="file-handle" title="Drag to reorder"><span class="dashicons dashicons-menu"></span></span>
        <?php endif; ?>
        <div class="file-icon" data-extension="{{extension}}"><span class="dashicons dashicons-{{icon}}"></span><span
                    class="file-extension" style="{{extension_display}}">{{extension_upper}}</span></div>
        <div class="file-details">
            <input type="text" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]" value="{{name}}"
                   placeholder="File name" data-field="name" class="file-name-input">
            <input type="url" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][url]" value="{{url}}"
                   placeholder="File URL" data-field="url" class="file-url-input">
            <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][attachment_id]"
                   value="{{attachment_id}}"
                   data-field="attachment_id">
            <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][lookup_key]"
                   value="{{lookup_key}}"
                   data-field="lookup_key">
        </div>
        <div class="file-actions">
            <button type="button" class="file-action-btn" data-action="browse" title="Browse media library"><span
                        class="dashicons dashicons-admin-media"></span></button>
            <button type="button" class="file-action-btn file-remove" data-action="remove" title="Remove file"><span
                        class="dashicons dashicons-trash"></span></button>
        </div>
        </div><?php
        $html = ob_get_clean();

        return str_replace( array( "\r", "\n", "\t" ), '', trim( $html ) );
    }

}