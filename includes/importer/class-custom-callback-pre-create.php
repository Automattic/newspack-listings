<?php

namespace Newspack_Listings\Importer;

use Newspack_Listings\Contracts\Importer_Mode;

class Custom_Callback_Pre_Create extends Abstract_Callable_Pre_Create {

	protected function get_callable(): callable {
		return function ( array $row, Importer_Mode $importer_mode ) {
			$dry_run = $importer_mode->is_dry_run() ? 'Dry Run: Yes' : 'Dry Run: No';
			$skip = $importer_mode->is_skip() ? 'Skip: Yes' : 'Skip: No';
			$update = $importer_mode->is_update() ? 'Update: Yes' : 'Update: No';
			var_dump("THE CUSTOM PRE CREATE HAS BEEN CALLED --- Import Mode => $dry_run | $skip | $update");
		};
	}
}
