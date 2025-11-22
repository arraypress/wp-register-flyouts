<?php
/**
 * DataTable Component
 *
 * Renders structured data in a clean table format.
 *
 * @package     ArrayPress\RegisterFlyouts\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Components;

use ArrayPress\RegisterFlyouts\Interfaces\Renderable;
use ArrayPress\RegisterFlyouts\Traits\Formatter;

class DataTable implements Renderable {
    use Formatter;

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
            $this->config['id'] = 'datatable-' . wp_generate_uuid4();
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
                'class'       => '',
                'columns'     => [],
                'data'        => [],
                'empty_text'  => __( 'No data found.', 'wp-flyout' ),
                'empty_value' => '—'
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['columns'] ) ) {
            return '';
        }

        $classes = [ 'wp-flyout-data-table' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div class="datatable-wrapper">
            <table id="<?php echo esc_attr( $this->config['id'] ); ?>"
                   class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

                <thead>
                <tr>
                    <?php foreach ( $this->config['columns'] as $key => $column ) : ?>
                        <?php $this->render_header_cell( $key, $column ); ?>
                    <?php endforeach; ?>
                </tr>
                </thead>

                <tbody>
                <?php if ( ! empty( $this->config['data'] ) ) : ?>
                    <?php foreach ( $this->config['data'] as $row ) : ?>
                        <tr>
                            <?php foreach ( $this->config['columns'] as $key => $column ) : ?>
                                <?php $this->render_body_cell( $key, $column, $row ); ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count( $this->config['columns'] ); ?>" class="empty-row">
                            <?php echo esc_html( $this->config['empty_text'] ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render header cell
     *
     * @param string $key    Column key
     * @param mixed  $column Column config
     */
    private function render_header_cell( string $key, $column ): void {
        $label = is_array( $column ) ? ( $column['label'] ?? $key ) : $column;
        $class = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
        $width = is_array( $column ) ? ( $column['width'] ?? '' ) : '';

        $attrs = [];
        if ( $class ) {
            $attrs[] = 'class="' . esc_attr( $class ) . '"';
        }
        if ( $width ) {
            $attrs[] = 'style="width: ' . esc_attr( $width ) . '"';
        }
        ?>
        <th <?php echo implode( ' ', $attrs ); ?>>
            <?php echo esc_html( $label ); ?>
        </th>
        <?php
    }

    /**
     * Render body cell
     *
     * @param string $key    Column key
     * @param mixed  $column Column config
     * @param array  $row    Row data
     */
    private function render_body_cell( string $key, $column, array $row ): void {
        $value    = $row[ $key ] ?? '';
        $class    = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
        $callback = is_array( $column ) ? ( $column['callback'] ?? null ) : null;

        if ( is_callable( $callback ) ) {
            $value = call_user_func( $callback, $value, $row );
        } elseif ( empty( $value ) ) {
            $value = $this->format_value( $this->config['empty_value'] );
        } else {
            $value = esc_html( $value );
        }
        ?>
        <td <?php echo $class ? 'class="' . esc_attr( $class ) . '"' : ''; ?>>
            <?php echo $value; ?>
        </td>
        <?php
    }

}