<?php
/**
 * Front-end render functions for the Listing block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Listing_Block;

/**
 * Dynamic block registration.
 */
function register_block() {
	register_block_type(
		'newspack-listings/listing',
		[
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Block render callback.
 *
 * @param Array $attributes Block attributes.
 */
function render_block( $attributes ) {
	// Don't output the block inside RSS feeds.
	if ( is_feed() ) {
		return;
	}

	if ( empty( $attributes['listing'] ) ) {
		return;
	}

	$post = get_post( intval( $attributes['listing'] ) );

	if ( empty( $post ) ) {
		return;
	}
	// This will let the FSE plugin know we need CSS/JS now.
	do_action( 'newspack_listings_render_listing_block' );

	ob_start();

	?>
	<li class="newspack-listings__list-item">
		<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
			<article class="newspack-listings__listing-article">
				<h3><?php echo wp_kses_post( $post->post_title ); ?></h3>
				<?php echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) ); ?>
			</article>
		</a>
	</li>
	<?php

	$content = ob_get_clean();

	return $content;
}

/**
 * Query listings posts with the given attributes.
 *
 * @param Array $attributes Map of query attributes.
 * @return WP_Query Results of query.
 */
function get_listings( $attributes ) {
	$listing_type    = $attributes['listingType'];
	$number_of_posts = $attributes['postsToShow'];
	$specific_mode   = $attributes['specificMode'];
	$specific_posts  = $attributes['specificPosts'];
	$args            = [
		'post_type' => $listing_type,
	];

	// If fetching specific posts by IDs.
	if ( true === $specific_mode && ! empty( $specific_posts ) ) {
		$args['post__in'] = $specific_posts;
		$args['orderby']  = 'post__in';
	}

	return new \WP_Query( $args );
}

register_block();
