<?php

namespace Newspack_Listings\Importer;

abstract class Abstract_Callable_Post_Create {

	final public function __construct() {
		// Prevent instantiation.
	}

	abstract protected function get_callable(): callable;

	public function __invoke( WP_Post $listing, array $csv_row = array() ) {
		$this->get_callable()($listing, $csv_row);
	}
}