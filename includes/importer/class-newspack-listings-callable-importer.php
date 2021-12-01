<?php
/**
 * New class to handle the importing of Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Importer;

use Exception;
use Iterator;
use Newspack_Listings\File_Import_Factory;
use WP_CLI;
use WP_Post;

/**
 * The main benefit of this class is the ability to inject callbacks to
 * modify CSV row data before and after insertion.
 *
 * @class Newspack_Listings_Callable_Importer
 */
class Newspack_Listings_Callable_Importer {

	private const MODE_DRY_RUN = 'dry-run';
	private const MODE_UPDATE = 'update';
	private const MODE_SKIP = 'skip';

	/**
	 * Singleton instance property.
	 *
	 * @var Newspack_Listings_Callable_Importer|null $instance
	 */
	protected static ?Newspack_Listings_Callable_Importer $instance = null;

	/**
	 * Abstract Callable instance property.
	 *
	 * @var Abstract_Callable_Pre_Create|null $callable_pre_create
	 */
	protected ?Abstract_Callable_Pre_Create $callable_pre_create = null;

	/**
	 * Need to implement Abstract Callable instance property.
	 *
	 * @var callable $callable_post_create
	 */
	protected $callable_post_create;

	/**
	 * This boolean flag tracks whether records should be created in the database.
	 *
	 * @var bool $mode_dry_run Boolean flag used to determine whether records should be created.
	 */
	protected bool $mode_dry_run = false;

	/**
	 * This boolean flag tracks whether records should be updated in the database.
	 *
	 * @var bool $mode_update Boolean flag used to determine whether records should be updated.
	 */
	protected bool $mode_update = true;

	/**
	 * This boolean flag tracks whether records found in the database should disregard any updates.
	 *
	 * @var bool $mode_skip Boolean flag used to determine whether existing records should be skipped.
	 */
	protected bool $mode_skip = false;

	/**
	 * Return's singleton instance.
	 *
	 * @return Newspack_Listings_Callable_Importer
	 */
	public static function get_instance(): Newspack_Listings_Callable_Importer {

		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}
	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		if ( class_exists( 'WP_CLI' ) ) {
			add_action( 'init', [ __CLASS__, 'add_cli_command' ] );
		}
	}

	/**
	 * Register's CLI command.
	 */
	public static function add_cli_command() {
		WP_CLI::add_command(
			'newspack-listings new-import',
			[ self::$instance, 'handle_import_command' ],
			[
				'shortdesc' => 'Import listings data.',
				'synopsis'  => [
					[
						'type'          => 'positional',
						'name'          => 'file',
						'description'   => 'Path to file',
						'optional'      => false,
						'repeating'     => false,
					],
					[
						'type'          => 'assoc',
						'name'          => 'start',
						'description'   => 'Row number to start at.',
						'optional'      => true,
						'repeating'     => false,
					],
					[
						'type'          => 'assoc',
						'name'          => 'end',
						'description'   => 'Row number to start at.',
						'optional'      => true,
						'repeating'     => false,
					],
					[
						'type'          => 'assoc',
						'name'          => 'mode',
						'description'   => 'Whether to perform a dry-run (no DB interaction), update, or skip if already imported.',
						'optional'      => true,
						'default'       => 'update',
						'options'       => [ 'dry-run', 'update', 'skip' ],
					],
				],
			],
		);
	}

	/**
	 * Set a callable instance for execution.
	 *
	 * @param string|Abstract_Callable_Pre_Create $callable_pre_create TBD.
	 *
	 * @return void
	 * @throws Exception Will throw an exception is class is not found.
	 */
	protected function set_callable_pre_create( $callable_pre_create ) {
		if ( is_string( $callable_pre_create ) ) {
			$callable_pre_create = new $callable_pre_create;
		}

		if ( ! ( $callable_pre_create instanceof Abstract_Callable_Pre_Create ) ) {
			throw new Exception( 'Please provide a valid class of type Abstract_Callable_Pre_Create.' );
		}

		$this->callable_pre_create = $callable_pre_create;
	}

	/**
	 * Get the instance of Abstract Callable Pre Create that was set.
	 *
	 * @return Abstract_Callable_Pre_Create|null
	 */
	protected function get_callable_pre_create(): ?Abstract_Callable_Pre_Create {
		return $this->callable_pre_create;
	}

	/**
	 * Handler for WP CLI execution.
	 *
	 * @param array $args Positional CLI arguments.
	 * @param array $assoc_args Associative CLI arguments.
	 *
	 * @throws Exception Thrown if file is not on system.
	 */
	public function handle_import_command( $args, $assoc_args ) {
//		WP_CLI::log('Got here');
//		var_dump($assoc_args);
		$this->set_callable_pre_create( new Custom_Callback_Pre_Create() );

		$this->set_mode( $assoc_args['dry-run'] ?? '' );

		$this->import(
			( new File_Import_Factory() )
				->get_file( $args[0] )
				->set_start( $assoc_args['start'] ?? 0)
				->set_end( $assoc_args['end'] ?? PHP_INT_MAX )
				->getIterator()
		);
	}

	/**
	 * Sets the mode under which to run the import.
	 *
	 * @param string $mode Should a mode from the options list.
	 */
	protected function set_mode( string $mode ) {
		switch ( strtolower( $mode ) ) {
			case self::MODE_DRY_RUN:
				$this->set_mode_dry_run();
				break;
			case self::MODE_SKIP:
				$this->set_mode_skip();
				break;
			case self::MODE_UPDATE:
			default:
				$this->set_mode_update();
				break;
		}
	}

	/**
	 * Sets the $mode_dry_run flag. No records should be created or updated.
	 *
	 * @param bool $mode_dry_run False by default.
	 */
	protected function set_mode_dry_run( bool $mode_dry_run = true ) {
		$this->mode_dry_run = $mode_dry_run;

		if ( $this->mode_dry_run ) {
			$this->set_mode_update( false );
			$this->set_mode_skip( false );
		}
	}

	/**
	 * Returns the $mode_dry_run flag.
	 *
	 * @return bool
	 */
	protected function is_mode_dry_run(): bool {
		return $this->mode_dry_run;
	}

	/**
	 * Set the $mode_update flag. Determines whether existing records should be updated.
	 *
	 * @param bool $mode_update Boolean flag.
	 */
	protected function set_mode_update( bool $mode_update = true ) {
		$this->mode_update = $mode_update;

		if ( $this->mode_update ) {
			$this->set_mode_dry_run( false );
			$this->set_mode_skip( false );
		}
	}

	/**
	 * Returns $mode_update flag, determining whether records should be updated.
	 *
	 * @return bool
	 */
	protected function is_mode_update(): bool {
		return $this->mode_update;
	}

	/**
	 * Set the $mode_skip flag. Determines whether existing records should disregard updates.
	 *
	 * @param bool $mode_skip Boolean flag.
	 */
	protected function set_mode_skip( bool $mode_skip = true ) {
		$this->mode_skip = $mode_skip;

		if ( $this->mode_skip ) {
			$this->set_mode_dry_run( false );
			$this->set_mode_update( false );
		}
	}

	/**
	 * Returns $mode_skip flag, determining whether existing records should desregard updates.
	 *
	 * @return bool
	 */
	protected function is_mode_skip(): bool {
		return $this->mode_skip;
	}

	/**
	 * Import data rows.
	 *
	 * @param Iterator $iterator Cycle through rows.
	 */
	protected function import( Iterator $iterator ) {
		do {
			if ( ! is_null( $this->get_callable_pre_create() ) ) {
				$this->get_callable_pre_create()( $iterator->current() );
			}

			$listing = $this->create_or_update_listing( $iterator->current() );

			if ( isset( $this->callable_post_create ) ) {
				$this->callable_post_create( $listing );
			}

			$iterator->next();
		} while ( $iterator->valid() );
	}

	/**
	 * Will take a row of data and attempt to create or update the corresponding row in the database.
	 *
	 * @param array $row Row representing data to be inserted.
	 *
	 * @return WP_Post
	 */
	protected function create_or_update_listing( array $row ): WP_Post {
		// TODO need to implement Newspack_Listings_Importer::get_post_type_mapping function.
		$existing_post = function_exists( 'wpcom_vip_get_page_by_title' ) ?
			wpcom_vip_get_page_by_title( $row['wp_post.post_title'], OBJECT, 'NEWSPACK_LISTING_TYPE' ) :
			get_page_by_title( $row['wp_post.post_title'], OBJECT, 'NEWSPACK_LISTING_TYPE' );

		// TODO flesh out the rest of this function.
		if ( $this->is_mode_dry_run() || $this->is_mode_skip() ) {
			return new WP_Post();
		}

		return new WP_Post();
	}
}

Newspack_Listings_Callable_Importer::get_instance();
