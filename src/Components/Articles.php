<?php
/**
 * Articles Component
 *
 * Displays a list of article cards with images, titles, excerpts and links.
 * Useful for news feeds, blog posts, announcements or any content listing.
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

/**
 * Class Articles
 *
 * Renders a collection of article cards in a flyout.
 *
 * @since 1.0.0
 */
class Articles implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     * @since 1.0.0
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     *
     * @since 1.0.0
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'articles-' . wp_generate_uuid4();
        }

        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Get default configuration
     *
     * @return array Default configuration values
     * @since 1.0.0
     */
    private static function get_defaults(): array {
        return [
                'id'         => '',
                'items'      => [],
                'empty_text' => __( 'No articles found.', 'wp-flyout' ),
                'columns'    => 1, // 1 or 2 column layout
                'class'      => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     */
    public function render(): string {
        $classes = [ 'wp-flyout-articles' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }
        if ( $this->config['columns'] > 1 ) {
            $classes[] = 'columns-' . $this->config['columns'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <?php if ( empty( $this->config['items'] ) ) : ?>
                <p class="articles-empty"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
            <?php else : ?>
                <div class="articles-list">
                    <?php foreach ( $this->config['items'] as $article ) : ?>
                        <?php $this->render_article( $article ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single article card
     *
     * @param array $article Article data with keys: title, date, image, excerpt, url, action_text
     *
     * @return void
     * @since 1.0.0
     */
    private function render_article( array $article ): void {
        $has_link = ! empty( $article['url'] );
        ?>
        <article class="article-item">
            <?php if ( ! empty( $article['image'] ) ) : ?>
                <div class="article-image">
                    <?php if ( $has_link ) : ?>
                    <a href="<?php echo esc_url( $article['url'] ); ?>">
                        <?php endif; ?>
                        <img src="<?php echo esc_url( $article['image'] ); ?>"
                             alt="<?php echo esc_attr( $article['title'] ?? '' ); ?>">
                        <?php if ( $has_link ) : ?>
                    </a>
                <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="article-content">
                <?php if ( ! empty( $article['date'] ) ) : ?>
                    <time class="article-date"><?php echo esc_html( $article['date'] ); ?></time>
                <?php endif; ?>

                <?php if ( ! empty( $article['title'] ) ) : ?>
                    <h3 class="article-title">
                        <?php if ( $has_link ) : ?>
                        <a href="<?php echo esc_url( $article['url'] ); ?>">
                            <?php endif; ?>
                            <?php echo esc_html( $article['title'] ); ?>
                            <?php if ( $has_link ) : ?>
                        </a>
                    <?php endif; ?>
                    </h3>
                <?php endif; ?>

                <?php if ( ! empty( $article['excerpt'] ) ) : ?>
                    <p class="article-excerpt"><?php echo esc_html( $article['excerpt'] ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $article['action_text'] ) && $has_link ) : ?>
                    <a href="<?php echo esc_url( $article['url'] ); ?>" class="article-link">
                        <?php echo esc_html( $article['action_text'] ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

}