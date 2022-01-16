<?php
/**
 * Class to encapsulate Post Type values.
 *
 * @package Newspack_Listings;
 */

namespace Newspack_Listings;

/**
 * Pseudo Enum class.
 */
final class Listing_Type {
	const EVENT = 'newspack_lst_event';
	const GENERIC = 'newspack_lst_generic';
	const MARKETPLACE = 'newspack_lst_mktplce';
	const PLACE = 'newspack_lst_place';

	/**
	 * Constructor to prevent initialization.
	 */
	public function __construct() {
		// Prevent initialization.
	}
}
