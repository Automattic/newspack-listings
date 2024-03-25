<?php
/**
 * Concrete CSV File Implementation.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Importer;

use Exception;
use Iterator;
use Newspack_Listings\Abstract_Iterable_File;
use Newspack_Listings\Contracts\Csv_File as Csv_File_Interface;
use Newspack_Listings\Contracts\Iterable_File;
use function Newspack_Listings\Importer_Utils\format_data;

/**
 * Short description.
 */
class Csv_File extends Abstract_Iterable_File implements Csv_File_Interface {

	/**
	 * CSV start row.
	 *
	 * @var int $start
	 */
	protected int $start = 1;

	/**
	 * The separator character to be used for CSV parsing.
	 *
	 * @var string $separator
	 */
	protected string $separator = ',';

	/**
	 * The enclosure character to be used for CSV parsing.
	 *
	 * @var string $enclosure
	 */
	protected string $enclosure = '"';

	/**
	 * The escape character to be used for CSV parsing.
	 *
	 * @var string $escape
	 */
	protected string $escape = '\\';

	/**
	 * Defines the encoding to be used for decoding the CSV file.
	 *
	 * @var string|bool $encoding
	 */
	protected $encoding = false;

	/**
	 * Constructor.
	 *
	 * @param string $path Full path to CSV File.
	 * @param string $separator CSV Separator to use.
	 * @param string $enclosure CSV Enclosure Char to use.
	 * @param string $escape CSV Escape Char to use.
	 *
	 * @throws Exception Throws Exception if file is not found.
	 */
	public function __construct( string $path, string $separator = ',', string $enclosure = '"', string $escape = '\\' ) {
		parent::__construct( $path );
		$this->set_separator( $separator );
		$this->set_enclosure( $enclosure );
		$this->set_escape( $escape );

		$this->encoding = function_exists( 'mb_detect_encoding' ) ?
			mb_detect_encoding( $path, 'UTF-8, ISO-8859-1', true ) :
			false;
	}

	/**
	 * Returns the very first row in a CSV, the header.
	 *
	 * @inheritDoc
	 * @throws Exception Exception thrown if file does not exist on system.
	 */
	public function get_header(): array {
		return $this->get_row( rewind( $this->get_handle() ) );
	}

	/**
	 * The iterator that provides the CSV rows.
	 *
	 * @return Iterator
	 * @throws Exception Exception thrown if file does not exist on system.
	 */
	public function getIterator(): Iterator {
		$handle = $this->get_handle();
		$header = $this->get_row( $handle );
		$header = array_map( fn( $column) => trim( $column ), $header );

		$row_count = 1;
		while ( $row_count < $this->get_start() ) {
			fgets( $handle ); // Move the file handle to the desired starting position.

			if ( feof( $handle ) ) {
				yield [];
				return;
			}

			$row_count++;
		}

		while ( $row_count <= $this->get_end() && ! feof( $handle ) ) {
			$row = array_combine( $header, $this->get_row( $handle ) );

			array_map( fn( $value ) => format_data( $value, $this->get_encoding() ), $row );

			yield $row;
			$row_count++;
		}
	}


	/**
	 * Overriding the default behavior of set_start to ensure we always skip the header CSV row.
	 *
	 * @param int $start Starting row position.
	 *
	 * @return Iterable_File
	 * @throws Exception Will throw exception if $start is greater than $max.
	 */
	public function set_start( int $start = 1 ): Iterable_File {
		if ( 1 > $start ) {
			$start = 1;
		}
		return parent::set_start( $start );
	}

	/**
	 * Set the separator character to be used for CSV parsing.
	 *
	 * @param string $separator Separator character to be used for CSV parsing.
	 *
	 * @return Csv_File_Interface
	 */
	public function set_separator( string $separator ): Csv_File_Interface {
		$this->separator = $separator;

		return $this;
	}

	/**
	 * Get the separator used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_separator(): string {
		return $this->separator;
	}

	/**
	 * Set the enclosure character to be used for CSV parsing.
	 *
	 * @param string $enclosure The enclosure character used for CSV parsing.
	 *
	 * @return Csv_File_Interface
	 */
	public function set_enclosure( string $enclosure ): Csv_File_Interface {
		$this->enclosure = $enclosure;

		return $this;
	}

	/**
	 * Returns the enclosure character used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_enclosure(): string {
		return $this->enclosure;
	}

	/**
	 * Set the escape character to be used for CSV Parsing.
	 *
	 * @param string $escape The escape character used for CSV parsing.
	 *
	 * @return Csv_File_Interface
	 */
	public function set_escape( string $escape ): Csv_File_Interface {
		$this->escape = $escape;

		return $this;
	}

	/**
	 * Returns the escape character to be used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_escape(): string {
		return $this->escape;
	}

	/**
	 * Convenience function to facilitate obtaining a CSV row.
	 *
	 * @param resource $handle The open file handle for the CSV file.
	 * @throws Exception Exception thrown if $handle is not a resource.
	 */
	protected function get_row( $handle ): array {
		if ( ! is_resource( $handle ) ) {
			throw new Exception( '$handle is not a resource.' );
		}

		@ini_set( 'auto_detect_line_endings', true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return fgetcsv(
			$handle,
			0,
			$this->get_separator(),
			$this->get_enclosure(),
			$this->get_escape()
		);
	}

	/**
	 * Returns the CSV File encoding. False if unable to detect the encoding.
	 *
	 * @return bool|string
	 */
	private function get_encoding() {
		return $this->encoding;
	}
}
