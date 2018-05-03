<?php

namespace wpscholar\WordPress;

/**
 * Get a collection of related post IDs.
 *
 * @param int $count
 * @param \WP_Post $post
 *
 * @return int[] Returns an array of related post IDs.
 */
function relatedPosts( $count = 5, \WP_Post $post = null ) {

	$related_posts = [];

	$post = get_post( $post );

	if ( $post && is_object( $post ) ) {

		$related_posts = wp_cache_get( 'wpscholar_related_posts', $post->ID );

		if ( ! $related_posts ) {

			$related_posts = [];

			$query_args = [
				'post_status'    => 'publish',
				'post_type'      => $post->post_type,
				'posts_per_page' => min( $count, 100 ),
				'post__not_in'   => [ $post->ID ],
				'tax_query'      => [],
				'orderby'        => 'rand',
				'fields'         => 'ids',
			];

			$taxonomies = get_object_taxonomies( $post );

			foreach ( $taxonomies as $taxonomy ) {

				// Get terms for this taxonomy related to the current post.
				$terms = wp_get_object_terms( $post->ID, $taxonomy );

				// If there are no terms, don't add a tax_query clause.
				if ( $terms ) {
					$query_args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => wp_list_pluck( $terms, 'slug' ),
					];
				}

			}

			if ( empty( $query_args['tax_query'] ) ) {

				// If we have no tax_query clauses, ensure the query returns no results.
				$query_args['post__in'] = [ 0 ];
				unset( $query_args['post__not_in'] );

			} else {

				// If we do have tax_query clauses, make sure we have an 'OR' relationship.
				$query_args['tax_query']['relation'] = 'OR';

			}

			$query = new \WP_Query( $query_args );

			if ( $query->have_posts() ) {
				$related_posts = (array) $query->posts;
			}

			wp_cache_set( 'wpscholar_related_posts', $related_posts, $post->ID, MINUTE_IN_SECONDS );

		}

	}

	return $related_posts;
}
