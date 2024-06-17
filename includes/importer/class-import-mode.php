<?php
/**
 * Class to encapsulate Import Mode values.
 *
 * @package Newspack_Listings;
 */
namespace Newspack_Listings;

/**
 * Pseudo Enum Class.
 */
final class Import_Mode {
	const DRY_RUN = 'dry-run';
	const SKIP = 'skip';
	const UPDATE = 'UPDATE';

	/**
	 * Constructor to prevent initialization.
	 */
	public function __construct() {
		// Prevent initialization.
	}
}