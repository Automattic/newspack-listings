<?php
/**
 * Front-end render functions for the Curated List block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Curated_List_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Blocks as Blocks;
use \Newspack_Listings\Newspack_Listings_Api as Api;
use \Newspack_Listings\Utils as Utils;

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
	if ( $attributes['showImage'] ) {
		$classes[] = 'show-image';
		$classes[] = 'media-position-' . $attributes['mediaPosition'];
		$classes[] = 'media-size-' . $attributes['imageScale'];
	}
	$classes[] = 'type-scale-' . $attributes['typeScale'];

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

	// Default: we can't have more pages unless a.) the block is in query mode, and b.) the number of queried posts exceeds max_num_pages.
	$has_more_pages = false;

	// If in query mode, dynamically build the list based on query terms.
	if ( $attributes['queryMode'] && ! empty( $attributes['queryOptions'] ) ) {
		$args           = Api::build_listings_query( $attributes['queryOptions'] );
		$listings       = new \WP_Query( $args );
		$page           = $listings->paged ?? 1;
		$has_more_pages = $attributes['showLoadMore'] && ( ++$page ) <= $listings->max_num_pages;

		// Only include the attributes we care about for individual listings.
		$listing_attributes = [ 'textColor', 'showImage', 'showCaption', 'showCategory', 'showAuthor', 'showExcerpt' ];
		$request_attributes = array_map(
			function( $attribute ) {
				return false === $attribute ? '0' : str_replace( '#', '%23', $attribute );
			},
			array_intersect_key( $attributes, array_flip( $listing_attributes ) )
		);

		// REST API URL to fetch more listings.
		$listings_rest_url = add_query_arg(
			[
				'attributes' => $request_attributes,
				'query'      => $attributes['queryOptions'],
				'page'       => 2,
				'amp'        => Utils\is_amp(),
				'_fields'    => 'html',
			],
			rest_url( '/newspack-listings/v1/listings' )
		);

		if ( $has_more_pages ) {
			$classes[] = 'has-more-button';
		}

		if ( $listings->have_posts() ) {
			$inner_content .= '<ol class="newspack-listings__list-container newspack-listings__query-mode">';

			while ( $listings->have_posts() ) {
				$listings->the_post();
				$listing_content = Blocks::template_include(
					'listing',
					[
						'attributes' => $attributes,
						'post'       => get_post(),
					]
				);

				$inner_content .= $listing_content;
			}

			$inner_content .= '</ol>';
		}
	}

	// Begin front-end output.
	ob_start();

	?>
	<?php if ( $has_more_pages && Utils\is_amp() ) : ?>
		<amp-script layout="container" src="<?php echo esc_url( NEWSPACK_LISTINGS_URL . 'src/assets/amp/curated-list.js' ); ?>">
	<?php endif; ?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php echo wp_kses( $inner_content, $allowed_elements ); ?>
		<?php if ( $attributes['queryMode'] && $has_more_pages ) : ?>
			<button type="button" data-next="<?php echo esc_url( $listings_rest_url ); ?>">
			<?php
			if ( ! empty( $attributes['loadMoreText'] ) ) {
				echo esc_html( $attributes['loadMoreText'] );
			} else {
				esc_html_e( 'Load more listings', 'newspack-listings' );
			}
			?>
			</button>
			<p class="loading">
				<?php esc_html_e( 'Loading...', 'newspack-listings' ); ?>
			</p>
			<p class="error">
				<?php esc_html_e( 'Something went wrong. Please refresh the page and/or try again.', 'newspack-listings' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php if ( $has_more_pages && Utils\is_amp() ) : ?>
		</amp-script>
	<?php endif; ?>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_block();
