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
 * @param Array $array Array of text values to be sanitized.
 * @return Array Sanitized array.
 */
function sanitize_array( $array ) {
	foreach ( $array as $key => $value ) {
		$value = sanitize_text_field( $value );
	}

	return $array;
}
