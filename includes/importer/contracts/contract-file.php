<?php
/**
 * Interface for interacting with files.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Contracts;

interface File {
	/**
	 * Get the file's full path.
	 *
	 * @return string
	 */
	public function get_path(): string;

	/**
	 * Should return the file resource.
	 *
	 * @return resource
	 */
	public function get_handle();

	/**
	 * Get the file's name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get the file's size.
	 *
	 * @return int
	 */
	public function get_size(): int;
}
