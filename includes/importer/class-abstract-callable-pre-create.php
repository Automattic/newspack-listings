<?php
/**
 * Custom implementation which provides a mechanism for users to tie into the Newspack Listings Import process.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings\Importer;

use Newspack_Listings\Contracts\Importer_Mode;

/**
 * Abstract Class which establishes that a callable must be defined and returned.
 */
abstract class Abstract_Callable_Pre_Create {

	/**
	 * Constructor.
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
	 * Automatically execute Abstract_Callable_Pre_Create class when instantiated.
	 *
	 * @param array         $row Row of data from import file.
	 * @param Importer_Mode $importer_mode Newspack Listing Import Mode.
	 */
	public function __invoke( array &$row, Importer_Mode $importer_mode ) {
		$this->get_callable()( $row, $importer_mode );
	}
}
