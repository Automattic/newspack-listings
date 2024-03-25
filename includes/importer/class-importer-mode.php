<?php
/**
 * Concrete implementation of @see \Newspack_Listings\Contracts\Importer_Mode.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings;

use Newspack_Listings\Contracts\Importer_Mode as Importer_Mode_Interface;

/**
 * Concrete implementation of @see Importer_Mode_Interface.
 */
class Importer_Mode implements Importer_Mode_Interface {

	/**
	 * This boolean flag tracks whether records should be created in the database.
	 *
	 * @var bool $dry_run Boolean flag used to determine whether records should be created.
	 */
	protected bool $dry_run = false;

	/**
	 * This boolean flag tracks whether records should be updated in the database.
	 *
	 * @var bool $update Boolean flag used to determine whether records should be updated.
	 */
	protected bool $update = true;

	/**
	 * This boolean flag tracks whether records found in the database should disregard any updates.
	 *
	 * @var bool $skip Boolean flag used to determine whether existing records should be skipped.
	 */
	protected bool $skip = false;

	/**
	 * Set the mode the Importer should run under.
	 *
	 * @param string $mode @see \Newspack_Listings\Import_Mode.
	 *
	 * @return void
	 */
	public function set_mode( string $mode ) {
		switch ( strtolower( $mode ) ) {
			case Import_Mode::DRY_RUN:
				$this->set_dry_run();
				break;
			case Import_Mode::SKIP:
				$this->set_skip();
				break;
			case Import_Mode::UPDATE:
			default:
				$this->set_update();
				break;
		}
	}

	/**
	 * Sets the $mode_dry_run flag. No records should be created or updated.
	 *
	 * @param bool $dry_run Boolean flag.
	 */
	public function set_dry_run( bool $dry_run = true ) {
		$this->dry_run = $dry_run;

		if ( $this->dry_run ) {
			$this->set_update( false );
			$this->set_skip( false );
		}
	}

	/**
	 * Set the $mode_update flag. Determines whether existing records should be updated.
	 *
	 * @param bool $update Boolean flag.
	 */
	public function set_update( bool $update = true ) {
		$this->update = $update;

		if ( $this->update ) {
			$this->set_dry_run( false );
			$this->set_skip( false );
		}
	}

	/**
	 * Set the $mode_skip flag. Determines whether existing records should disregard updates.
	 *
	 * @param bool $skip Boolean flag.
	 */
	public function set_skip( bool $skip = true ) {
		$this->skip = $skip;

		if ( $this->skip ) {
			$this->set_dry_run( false );
			$this->set_update( false );
		}
	}

	/**
	 * Flag asserting dry-run mode. No database inserts/updates should be performed.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool {
		return $this->dry_run;
	}

	/**
	 * Flag asserting skip mode. Only database inserts should be performed.
	 *
	 * @return bool
	 */
	public function is_skip(): bool {
		return $this->skip;
	}

	/**
	 * Flag asserting update mode. All database operations are permitted.
	 *
	 * @return bool
	 */
	public function is_update(): bool {
		return $this->update;
	}
}
