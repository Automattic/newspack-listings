<?php

namespace Newspack_Listings\Importer;

class Custom_Callback_Pre_Create extends Abstract_Callable_Pre_Create {

	protected function get_callable(): callable {
		return function ( $csv_row ) {
			var_dump("THE CUSTOM PRE CREATE HAS BEEN CALLED");
		};
	}
}
