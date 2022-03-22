<?php
/**
 * Front-end render functions for the Event Dates block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Event_Dates_Block;

use \Newspack_Listings\Core;
use \Newspack_Listings\Utils;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Block attributes.
	$block_json = json_decode(
		file_get_contents( __DIR__ . '/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Register Evend Dates block.
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
	// Bail if there's no start date to display.
	if ( empty( $attributes['startDate'] ) ) {
		return '';
	}

	// Begin front-end output.
	$content = Utils\template_include(
		'event-dates',
		[ 'attributes' => $attributes ]
	);

	return $content;
}

register_block();
