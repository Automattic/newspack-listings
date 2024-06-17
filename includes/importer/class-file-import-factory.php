<?php

namespace Newspack_Listings;

use Exception;
use IteratorAggregate;
use Newspack_Listings\Contracts\Iterable_File;
use Newspack_Listings\Importer\Csv_File;

class File_Import_Factory {
	/**
	 * @throws Exception
	 */
	public function get_file( string $file_path ): Iterable_File {
		if ( str_ends_with( $file_path, 'csv' ) ) {
			return $this->make_csv( $file_path );
		}

		if ( str_ends_with( $file_path, 'json' ) ) {
			return $this->make_json( $file_path );
		}

		throw new Exception( 'Unsupported File Type.' );
	}

	/**
	 * @param string $file_path
	 *
	 * @return Csv_File
	 * @throws Exception
	 */
	protected function make_csv( string $file_path ): Csv_File {
		return new Csv_File( $file_path );
	}

	/**
	 * @param string $file_path
	 *
	 * @return Iterable_File
	 * @throws Exception
	 */
	protected function make_json( string $file_path ): Iterable_File {
		return new Json_File( $file_path );
	}
}