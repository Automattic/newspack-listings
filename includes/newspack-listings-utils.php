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
 * Given a block name, get all blocks (and recursively, all inner blocks) matching the given type.
 *
 * @param String $block_name Block name to match.
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
 * Get data from content blocks within the given $post_id.
 * Searches the content of the post for instances of the source block,
 * then returns the given attributes for all block instances.
 *
 * @param Array $blocks Array of block objects to get data from.
 * @param Array $source Info for the block to source the data from.
 *              ['blockName'] Name of the block to search for.
 *              ['attrs']     (Optional) Specific block attributes to get.
 *                            If not provided, all attributes will be returned.
 *
 * @return Array|Boolean Array of block data, or false if there are no blocks matching the given source (or no data to return).
 */
function get_data_from_blocks( $blocks, $source ) {
	$data = [];

	if ( ! empty( $source ) && ! empty( $source['blockName'] ) ) {
		$matching_blocks = get_blocks_by_type( $source['blockName'], $blocks );

		// Return false if there are no matching blocks of the given source type.
		if ( empty( $matching_blocks ) ) {
			return false;
		}

		// Gather data from all matching block instances.
		foreach ( $matching_blocks as $matching_block ) {
			// If we have a source `attr` key, sync only that attribute, otherwise sync all attributes.
			if ( ! empty( $source['attr'] ) ) {
				$block_data = $matching_block['attrs'][ $source['attr'] ];
			} else {
				$block_data = [ $matching_block['attrs'] ];
			}

			if ( is_array( $block_data ) ) {
				$data = array_merge( $data, $block_data );
			} else {
				$data[] = $block_data;
			}
		}
	}

	// Return false instead of an empty array, if there's no data to return.
	if ( empty( $data ) ) {
		return false;
	}

	return $data;
}
