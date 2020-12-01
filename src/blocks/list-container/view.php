<?php
/**
 * Front-end render functions for the Curated List Container block.
 * This is a blank wrapper block that contains listing items.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Curated_List_Container_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Register Curated List Container block.
	register_block_type(
		'newspack-listings/list-container',
		[
			'attributes'      => [],
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Block render callback.
 *
 * @param array  $attributes Block attributes.
 * @param string $inner_content InnerBlock content.
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

	// Begin front-end output.
	ob_start();

	?>
	<ol class="newspack-listings__list-container">
		<?php echo wp_kses_post( $inner_content ); ?>
	</ol>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_block();
