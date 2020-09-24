<?php
/**
 * Front-end render functions for the Curated List block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Curated_List_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Listings block attributes.
	$block_json = json_decode(
		file_get_contents( __DIR__ . '/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Register Curated List block.
	register_block_type(
		$block_json['name'],
		[
			'attributes'      => $block_json['attributes'],
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Block render callback.
 *
 * @param Array  $attributes Block attributes.
 * @param String $inner_content InnerBlock content.
 */
function render_block( $attributes, $inner_content ) {
	// Don't output the block inside RSS feeds.
	if ( is_feed() ) {
		return;
	}

	// Bail if there's no InnerBlock content to display.
	if ( empty( trim( $inner_content ) ) ) {
		return '';
	}

	// Conditional class names based on attributes.
	$classes = [ 'newspack-listings__curated-list' ];

	if ( true === $attributes['showNumbers'] ) {
		$classes[] = 'show-numbers';
	}
	if ( true === $attributes['showImage'] ) {
		$classes[] = 'show-image';
	}
	if ( true === $attributes['showMap'] ) {
		$classes[] = 'show-map';
	}

	// Begin front-end output.
	// TODO: Templatize this output; integrate more variations based on attributes.

	ob_start();

	?>
	<ol class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php echo wp_kses_post( $inner_content ); ?>
	</ol>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_block();
