<?php
/**
 * Class to encapsulate Marketplace Type values.
 *
 * @package Newspack_Listings;
 */

namespace Newspack_Listings;

/**
 * Pseudo Enum class.
 */
final class Marketplace_Type {
	const CLASSIFIED = 'classified';
	const REAL_ESTATE = 'real_estate';

	/**
	 * Constructor to prevent initialization.
	 */
	public function __construct() {
		// Prevent initialization.
	}
}
