<?php
/**
 * Contract establishing Newspack Listings import mode.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings\Contracts;

interface Importer_Mode {

	/**
	 * Set the mode the Importer should run under.
	 *
	 * @param string $mode @see \Newspack_Listings\Import_Mode.
	 *
	 * @return void
	 */
	public function set_mode( string $mode );

	/**
	 * This mode indicates that no DB updates should be made.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool;

	/**
	 * This mode indicates that existing content should not be updated. It should be skipped.
	 *
	 * @return bool
	 */
	public function is_skip(): bool;

	/**
	 * This mode indicates that all DB operations are permitted.
	 *
	 * @return bool
	 */
	public function is_update(): bool;
}
