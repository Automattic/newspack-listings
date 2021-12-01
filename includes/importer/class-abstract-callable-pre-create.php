<?php

namespace Newspack_Listings\Importer;

abstract class Abstract_Callable_Pre_Create {

	final public function __construct() {
		// Prevent instantiation.
	}

	abstract protected function get_callable(): callable;

	public function __invoke( array $csv_row ) {
		$this->get_callable()($csv_row);
	}
}