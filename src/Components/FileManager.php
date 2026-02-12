<?php
/**
 * File Manager Component
 *
 * File management with drag-and-drop sorting and media library integration.
 * Supports both media library uploads and external URLs (Canva, Google Drive, etc).
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;

/**
 * Class FileManager
 *
 * Manages file attachments with media library integration and drag-drop sorting.
 */
class FileManager implements Renderable {

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
            $this->config['id'] = 'file-manager-' . wp_generate_uuid4();
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
                'id'          => '',
                'name'        => 'files',
                'items'       => [],
                'max_files'   => 0,
                'sortable'    => true,
                'add_text'    => __( 'Add File', 'wp-flyout' ),
                'empty_text'  => __( 'No files attached yet.', 'wp-flyout' ),
                'class'       => '',
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-file-manager' ];

        if ( $this->config['sortable'] ) {
            $classes[] = 'is-sortable';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $count     = count( $this->config['items'] );
        $max_files = (int) $this->config['max_files'];
        $at_limit  = $max_files > 0 && $count >= $max_files;

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-max-files="<?php echo esc_attr( (string) $max_files ); ?>">

            <div class="file-manager-list <?php echo $count === 0 ? 'is-empty' : ''; ?>">

                <div class="file-manager-empty">
                    <span class="dashicons dashicons-media-document"></span>
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>

                <div class="file-manager-items">
                    <?php foreach ( $this->config['items'] as $index => $file ) : ?>
                        <?php $this->render_item( $file, $index ); ?>
                    <?php endforeach; ?>
                </div>

            </div>

            <div class="file-manager-footer">
                <button type="button"
                        class="button button-small file-manager-add"
                        data-action="add"
                        <?php echo $at_limit ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>

                <?php if ( $max_files > 0 ) : ?>
                    <span class="file-manager-count">
						<span class="current-count"><?php echo $count; ?></span>/<?php echo $max_files; ?>
					</span>
                <?php endif; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single file item
     *
     * @param array $file  File data
     * @param int   $index Item index
     *
     * @return void
     */
    private function render_item( array $file, int $index ): void {
        $name          = $this->config['name'];
        $file_name     = $file['name'] ?? '';
        $file_url      = $file['url'] ?? '';
        $attachment_id = $file['attachment_id'] ?? $file['id'] ?? '';
        $lookup_key    = $file['lookup_key'] ?? '';
        ?>
        <div class="file-manager-item" data-index="<?php echo $index; ?>">

            <?php if ( $this->config['sortable'] ) : ?>
                <span class="file-handle" title="<?php esc_attr_e( 'Drag to reorder', 'wp-flyout' ); ?>">
					<span class="dashicons dashicons-menu"></span>
				</span>
            <?php endif; ?>

            <div class="file-fields">
                <input type="text"
                       name="<?php echo esc_attr( "{$name}[{$index}][name]" ); ?>"
                       value="<?php echo esc_attr( $file_name ); ?>"
                       placeholder="<?php esc_attr_e( 'File name', 'wp-flyout' ); ?>"
                       class="file-name-input">

                <input type="url"
                       name="<?php echo esc_attr( "{$name}[{$index}][url]" ); ?>"
                       value="<?php echo esc_attr( $file_url ); ?>"
                       placeholder="<?php esc_attr_e( 'URL or browse media library', 'wp-flyout' ); ?>"
                       class="file-url-input">

                <input type="hidden"
                       name="<?php echo esc_attr( "{$name}[{$index}][attachment_id]" ); ?>"
                       value="<?php echo esc_attr( (string) $attachment_id ); ?>"
                       class="file-attachment-id">

                <input type="hidden"
                       name="<?php echo esc_attr( "{$name}[{$index}][lookup_key]" ); ?>"
                       value="<?php echo esc_attr( $lookup_key ); ?>"
                       class="file-lookup-key">
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

}