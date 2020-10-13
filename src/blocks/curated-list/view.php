<?php
/**
 * Front-end render functions for the Curated List block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Curated_List_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Blocks as Blocks;

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

	// Conditional class names based on attributes.
	$classes = [ 'newspack-listings__curated-list' ];

	if ( $attributes['showNumbers'] ) {
		$classes[] = 'show-numbers';
	}
	if ( $attributes['showMap'] ) {
		$classes[] = 'show-map';
	}
	if ( $attributes['mobileStack'] ) {
		$classes[] = 'mobile-stack';
	}
	if ( $attributes['showImage'] ) {
		$classes[] = 'show-image';
		$classes[] = 'media-position-' . $attributes['mediaPosition'];
		$classes[] = 'media-size-' . $attributes['imageScale'];
	}
	$classes[] = 'type-scale-' . $attributes['typeScale'];

	// Begin front-end output.
	// TODO: Templatize this output; integrate more variations based on attributes.

	// Extend wp_kses_post to allow jetpack/map required elements and attributes.
	$allowed_elements = wp_kses_allowed_html( 'post' );

	// Allow amp-iframe with jetpack/map attributes.
	if ( empty( $allowed_elements['amp-iframe'] ) ) {
		$allowed_elements['amp-iframe'] = [
			'allowfullscreen' => true,
			'frameborder'     => true,
			'height'          => true,
			'layout'          => true,
			'sandbox'         => true,
			'src'             => true,
			'width'           => true,
		];
	}

	// Allow placeholder attribute on divs.
	if ( empty( $allowed_elements['div']['placeholder'] ) ) {
		$allowed_elements['div']['placeholder'] = true;
	}

	// If in query mode, dynamically build the list based on query terms.
	if ( $attributes['queryMode'] && ! empty( $attributes['queriedListings'] ) ) {
		$inner_content .= '<ol class="newspack-listings__list-container newspack-listings__query-mode">';

		foreach ( $attributes['queriedListings']  as $listing ) {
			$post            = get_post( intval( $listing['id'] ) );
			$listing_content = Blocks::template_include(
				'listing',
				[
					'attributes' => $attributes,
					'post'       => $post,
				]
			);

			$inner_content .= $listing_content;
		}

		$inner_content .= '</ol>';
	}

	ob_start();

	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php echo wp_kses( $inner_content, $allowed_elements ); ?>
	</div>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_block();
