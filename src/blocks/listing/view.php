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
	// Listings block attributes.
	$block_json = json_decode(
		file_get_contents( __DIR__ . '/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Parent Curated List block attributes.
	$parent_block_json = json_decode(
		file_get_contents( dirname( __DIR__ ) . '/curated-list/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Combine attributes with parent attributes, so parent can pass data to InnerBlocks.
	$attributes = array_merge( $block_json['attributes'], $parent_block_json['attributes'] );

	// Register a block for each listing type.
	foreach ( Core::NEWSPACK_LISTINGS_POST_TYPES as $label => $post_type ) {
		if ( 'curated_list' !== $label ) {
			register_block_type(
				'newspack-listings/' . $label,
				[
					'attributes'      => $attributes,
					'render_callback' => __NAMESPACE__ . '\render_block',
				]
			);
		}
	}
}

/**
 * Block render callback.
 *
 * @param Array $attributes Block attributes (including parent attributes inherited from Curated List container block).
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

	// Get the listing post by post ID.
	$post = get_post( intval( $attributes['listing'] ) );

	// Bail if there's no post with the saved ID.
	if ( empty( $post ) ) {
		return;
	}

	// Begin front-end output.
	// TODO: Templatize this output; integrate more variations based on attributes.
	ob_start();

	?>
	<li class="newspack-listings__listing">
		<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
			<article class="newspack-listings__listing-article">
				<h3><?php echo wp_kses_post( $post->post_title ); ?></h3>

				<?php if ( true === $attributes['showImage'] ) : ?>
				<figure class="newspack-listings__listing-featured-media">
					<?php echo wp_kses_post( get_the_post_thumbnail( $post->ID, 'large' ) ); ?>
					<?php if ( true === $attributes['showCaption'] ) : ?>
					<figcaption>
						<?php echo wp_kses_post( get_the_post_thumbnail_caption( $post->ID ) ); ?>
					</figcaption>
					<?php endif; ?>
				</figure>
				<?php endif; ?>

				<?php
				if ( true === $attributes['showExcerpt'] ) {
					echo wp_kses_post( wpautop( get_the_excerpt( $post->ID ) ) );
				}
				?>
			</article>
		</a>
	</li>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_blocks();
