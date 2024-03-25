<?php
/**
 * Interface for interacting with CSV Files.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Contracts;

interface Csv_File extends Iterable_File {
	/**
	 * Get the CSV Header Row as an associative array.
	 *
	 * @return array
	 */
	public function get_header(): array;

	/**
	 * Sets the separator to be used for CSV parsing.
	 *
	 * @param string $separator Separator character to use for CSV parsing.
	 *
	 * @return void|Csv_File
	 */
	public function set_separator( string $separator );

	/**
	 * Returns the separator character to be used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_separator(): string;

	/**
	 * Sets the enclosure character to be used for CSV parsing.
	 *
	 * @param string $enclosure Enclosure character to use for CSV parsing.
	 *
	 * @return void|Csv_File
	 */
	public function set_enclosure( string $enclosure );

	/**
	 * Returns the enclosure character to be used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_enclosure(): string;

	/**
	 * Sets the escape character to be used for CSV parsing.
	 *
	 * @param string $escape Escape character to use for CSV parsing.
	 *
	 * @return void|Csv_File
	 */
	public function set_escape( string $escape );

	/**
	 * Returns the escape character to be used for CSV parsing.
	 *
	 * @return string
	 */
	public function get_escape(): string;
}
