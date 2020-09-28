<?php
/**
 * Utility functions for Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Utils;

/**
 * On plugin activation, flush permalinks.
 */
function activate() {
	flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
}

/**
 * Sanitize an array of text values.
 *
 * @param Array $array Array of text or float values to be sanitized.
 * @return Array Sanitized array.
 */
function sanitize_array( $array ) {
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$value = sanitize_array( $value );
		} else {
			if ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				$value = floatval( $value );
			}
		}
	}

	return $array;
}

/**
 * Given an array of blocks, get all blocks (and recursively, all inner blocks) matching the given type.
 *
 * @param String $block_name Name of the block to match.
 * @param Array  $blocks Array of block objects to search.
 *
 * @return Array Array of matching blocks.
 */
function get_blocks_by_type( $block_name, $blocks ) {
	$matching_blocks = [];

	if ( empty( $block_name ) ) {
		return $matching_blocks;
	}

	foreach ( $blocks as $block ) {
		if ( $block['blockName'] === $block_name ) {
			$matching_blocks[] = $block;
		}

		// Recursively check inner blocks, too.
		if ( 0 < count( $block['innerBlocks'] ) ) {
			$matching_blocks = array_merge( $matching_blocks, get_blocks_by_type( $block_name, $block['innerBlocks'] ) );
		}
	}

	return $matching_blocks;
}
/**
 * Get all Mapbox locations associated with the given $post_id.
 * Searches the content of the post for instances of the jetpack/map block,
 * then returns all location points for all map block instances.
 *
 * @param Boolean|Int $post_id ID of the post, or false if the post lacks location data.
 *
 * @return Array Array of map locations with labels and coordinates.
 */
function get_location_data( $post_id ) {
	$location_data = false;

	$has_location_data = has_block( 'jetpack/map', $post_id );

	if ( $has_location_data ) {
		$location_data = [];

		$blocks = parse_blocks( get_the_content( null, false, $post_id ) );

		$map_blocks = get_blocks_by_type( 'jetpack/map', $blocks );

		foreach ( $map_blocks as $map_block ) {
			if ( ! empty( $map_block['attrs']['points'] ) ) {
				$location_data = array_merge( $location_data, $map_block['attrs']['points'] );
			}
		}
	}

	return $location_data;
}
