<?php
/**
 * Front-end render functions for the Self-Serve Listing block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Self_Serve_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Products as Products;
use \Newspack_Listings\Utils as Utils;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Block attributes.
	$block_json = json_decode(
		file_get_contents( __DIR__ . '/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Register Price block.
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
 * @param array $attributes Block attributes.
 * @return string $content content.
 */
function render_block( $attributes ) {
	// Only render if enabled.
	if ( ! defined( 'NEWSPACK_LISTINGS_SELF_SERVE_ENABLED' ) || ! NEWSPACK_LISTINGS_SELF_SERVE_ENABLED ) {
		return '';
	}

	// Only render if WooCommerce is active.
	if ( ! Products::is_active() ) {
		return '';
	}

	$content = Utils\template_include(
		'self-serve-form',
		[
			'attributes' => $attributes,
		]
	);

	return $content;
}

register_block();
