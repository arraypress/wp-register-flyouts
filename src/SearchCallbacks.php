<?php
/**
 * Built-in Search Callbacks
 *
 * Pre-built callbacks for common WordPress search patterns.
 * Each method returns a closure matching the unified callback signature:
 * callback( string $search, ?array $ids ): array
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

class SearchCallbacks {

	/**
	 * Create a post search callback.
	 *
	 * @param string|array $post_type  Post type(s) to search. Default 'post'.
	 * @param array        $query_args Additional WP_Query args to merge.
	 *
	 * @return callable Callback matching signature: callback( string $search, ?array $ids ): array
	 */
	public static function posts( $post_type = 'post', array $query_args = [] ): callable {
		return function ( string $search, ?array $ids = null ) use ( $post_type, $query_args ): array {
			$args = array_merge( [
				'post_type'      => $post_type,
				'posts_per_page' => 20,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			], $query_args );

			if ( ! empty( $ids ) ) {
				$args['post__in']       = array_map( 'absint', $ids );
				$args['posts_per_page'] = count( $ids );
				$args['orderby']        = 'post__in';
			} elseif ( $search !== '' ) {
				$args['s'] = $search;
			}

			$results = [];

			foreach ( get_posts( $args ) as $post ) {
				$results[ $post->ID ] = $post->post_title;
			}

			return $results;
		};
	}

	/**
	 * Create a taxonomy term search callback.
	 *
	 * @param string $taxonomy   Taxonomy to search. Default 'category'.
	 * @param array  $query_args Additional get_terms args to merge.
	 *
	 * @return callable Callback matching signature: callback( string $search, ?array $ids ): array
	 */
	public static function taxonomy( string $taxonomy = 'category', array $query_args = [] ): callable {
		return function ( string $search, ?array $ids = null ) use ( $taxonomy, $query_args ): array {
			$args = array_merge( [
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 20,
				'orderby'    => 'name',
				'order'      => 'ASC',
			], $query_args );

			if ( ! empty( $ids ) ) {
				$args['include'] = array_map( 'absint', $ids );
				$args['number']  = count( $ids );
			} elseif ( $search !== '' ) {
				$args['search'] = $search;
			}

			$terms   = get_terms( $args );
			$results = [];

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$results[ $term->term_id ] = $term->name;
				}
			}

			return $results;
		};
	}

	/**
	 * Create a user search callback.
	 *
	 * @param string|array $role       Role(s) to filter by. Empty for all roles.
	 * @param array        $query_args Additional WP_User_Query args to merge.
	 *
	 * @return callable Callback matching signature: callback( string $search, ?array $ids ): array
	 */
	public static function users( $role = '', array $query_args = [] ): callable {
		return function ( string $search, ?array $ids = null ) use ( $role, $query_args ): array {
			$args = array_merge( [
				'number'  => 20,
				'orderby' => 'display_name',
				'order'   => 'ASC',
			], $query_args );

			if ( ! empty( $role ) ) {
				$roles            = is_array( $role ) ? $role : array_map( 'trim', explode( ',', $role ) );
				$args['role__in'] = array_filter( $roles );
			}

			if ( ! empty( $ids ) ) {
				$args['include'] = array_map( 'absint', $ids );
				$args['number']  = count( $ids );
			} elseif ( $search !== '' ) {
				$args['search']         = '*' . $search . '*';
				$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
			}

			$user_query = new \WP_User_Query( $args );
			$results    = [];

			foreach ( $user_query->get_results() as $user ) {
				$results[ $user->ID ] = $user->display_name;
			}

			return $results;
		};
	}

}