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
 * @param array $array Array of text or float values to be sanitized.
 * @return array Sanitized array.
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
 * @param string $block_name Block name to match.
 * @param array  $blocks Array of block objects to search.
 *
 * @return array Array of matching blocks.
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
 * @param array $blocks Array of block objects to get data from.
 * @param array $source Info for the block to source the data from.
 *              ['blockName'] Name of the block to search for.
 *              ['attrs']     (Optional) Specific block attributes to get.
 *                            If not provided, all attributes will be returned.
 *
 * @return array|Boolean Array of block data, or false if there are no blocks matching the given source (or no data to return).
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
			$block_data = false;

			// If we have a source `attr` key, sync only that attribute, otherwise sync all attributes.
			if ( ! empty( $source['attr'] ) ) {
				if ( ! empty( $matching_block['attrs'][ $source['attr'] ] ) ) {
					$block_data = $matching_block['attrs'][ $source['attr'] ];
				}
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

/**
 * Checks whether the current view is served in AMP context.
 *
 * @return bool True if AMP, false otherwise.
 */
function is_amp() {
	return ! is_admin() && function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
}


/**
 * Use AMP Plugin functions to render markup as valid AMP.
 *
 * @param string $html Markup to convert to AMP.
 * @return string
 */
function generate_amp_partial( $html ) {
	$dom = \AMP_DOM_Utils::get_dom_from_content( $html );

	\AMP_Content_Sanitizer::sanitize_document(
		$dom,
		amp_get_content_sanitizers(),
		[
			'use_document_element' => false,
		]
	);
	$xpath = new \DOMXPath( $dom );
	foreach ( iterator_to_array( $xpath->query( '//noscript | //comment()' ) ) as $node ) {
		$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
	return \AMP_DOM_Utils::get_content_from_dom( $dom );
}
