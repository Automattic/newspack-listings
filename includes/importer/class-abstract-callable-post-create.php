<?php
/**
 * Custom implementation which provides a mechanism for users to tie into the Newspack Listings Import process.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings\Importer;

use Newspack_Listings\Contracts\Importer_Mode;
use WP_Post;

/**
 * Abstract Class which establishes that a callable must be defined and returned.
 */
abstract class Abstract_Callable_Post_Create {

	/**
	 * Constructor to prevent initialization.
	 */
	final public function __construct() {
		// Prevent instantiation.
	}

	/**
	 * Abstract function, forcing the child class to create a user-defined function.
	 *
	 * @return callable
	 */
	abstract protected function get_callable(): callable;

	/**
	 * Automatically execute Abstract_Callable_Post_create class when instantiated.
	 *
	 * @param WP_Post       $listing Newspack Listing Post.
	 * @param Importer_Mode $importer_mode Newspack Listing Import Mode.
	 * @param array         $row Row of data from import file.
	 */
	public function __invoke( WP_Post $listing, Importer_Mode $importer_mode, array $row = [] ) {
		$this->get_callable()( $listing, $importer_mode, $row );
	}
}
