<?php
/**
 * Front-end render functions for the Listing block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Listing_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;

/**
 * Dynamic block registration.
 */
function register_blocks() {
	foreach ( Core::NEWSPACK_LISTINGS_POST_TYPES as $label => $post_type ) {
		if ( 'curated_list' !== $label ) {
			register_block_type(
				'newspack-listings/' . $label,
				[
					'render_callback' => __NAMESPACE__ . '\render_block',
				]
			);
		}
	}
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

	// Bail if there's no listing post ID for this block.
	if ( empty( $attributes['listing'] ) ) {
		return;
	}

	$post = get_post( intval( $attributes['listing'] ) );

	// Bail if there's no post with the saved ID.
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
				<?php echo wp_kses_post( wpautop( get_the_excerpt( $post->ID ) ) ); ?>
			</article>
		</a>
	</li>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_blocks();
