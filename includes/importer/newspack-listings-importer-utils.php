<?php
/**
 * Utility functions for Newspack Listings Importer class.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Importer_Utils;

/**
 * Format the data from CSV.
 *
 * @since 1.0
 * @param string $data Data to be formatted.
 * @param string $file_encoding File encoding of the data.
 */
function format_data( $data, $file_encoding = 'UTF-8' ) {
	// Check if the function utf8_encode exists. The function is not found if the php-xml extension is not installed on the server.
	$return = function_exists( 'utf8_encode' ) ? utf8_encode( $data ) : $data;
	return ( 'UTF-8' == $file_encoding ) ? $data : $return;
}
