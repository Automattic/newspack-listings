<?php
/**
 * Newspack Listings CSV Importer.
 *
 * CSV Importer for Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \WP_CLI as WP_CLI;
use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Importer_Utils as Importer_Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 * Sets up CLI-based importer for listings.
 */
final class Newspack_Listings_Importer {
	/**
	 * The current row number of the CSV being processed.
	 *
	 * @var Newspack_Listings_Importer
	 */
	public static $row_number;

	/**
	 * The directory containing the CSV file to be imported.
	 *
	 * @var Newspack_Listings_Importer
	 */
	public static $import_dir;

	/**
	 * Whether the script is running as a dry-run.
	 *
	 * @var Newspack_Listings_Importer
	 */
	public static $is_dry_run = false;

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Importer
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings_Importer instance.
	 * Ensures only one instance of Newspack_Listings_Importer is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Importer - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ __CLASS__, 'add_cli_command' ] );
	}

	/**
	 * Register the 'newspack-listings import' WP CLI command.
	 */
	public static function add_cli_command() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command(
			'newspack-listings import',
			[ __CLASS__, 'run_cli_command' ],
			[
				'shortdesc' => 'Import listings data from a CSV file.',
				'synopsis'  => [
					[
						'type'        => 'assoc',
						'name'        => 'file',
						'description' => 'Path of the CSV file to import, relative to the plugin’s root directory.',
						'optional'    => false,
						'repeating'   => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'config',
						'description' => 'Path of the config file to use for mapping CSV fields to WP data, relative to the plugin’s root directory.',
						'optional'    => false,
						'repeating'   => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'start',
						'description' => 'Row number to start at.',
						'optional'    => true,
						'repeating'   => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'max-rows',
						'description' => 'Max number of rows to process.',
						'optional'    => true,
						'repeating'   => false,
					],
					[
						'type'        => 'flag',
						'name'        => 'dry-run',
						'description' => 'Whether to do a dry run.',
						'optional'    => true,
						'repeating'   => false,
					],
				],
			]
		);
	}

	/**
	 * Run the 'newspack-listings import' WP CLI command.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public static function run_cli_command( $args, $assoc_args ) {
		$file_arg   = isset( $assoc_args['file'] ) ? $assoc_args['file'] : false;
		$config_arg = isset( $assoc_args['config'] ) ? $assoc_args['config'] : false;
		$start_row  = isset( $assoc_args['start'] ) ? intval( $assoc_args['start'] ) : 0;
		$max_rows   = isset( $assoc_args['max-rows'] ) ? intval( $assoc_args['max-rows'] ) : false;

		// If a dry run, we won't persist any data.
		self::$is_dry_run = isset( $assoc_args['dry-run'] ) ? true : false;

		// Look for config file at the given path.
		$config_path = self::load_config( $config_arg );
		if ( ! $config_path ) {
			WP_CLI::error( 'Could not find config file at ' . $config_arg );
		}

		$file_path = self::load_file( $file_arg );
		if ( ! $file_path ) {
			WP_CLI::error( 'Could not find file at ' . $file_arg );
		}

		if ( self::$is_dry_run ) {
			WP_CLI::line( "\n===================\n=     Dry Run     =\n===================\n" );
		}

		if ( 0 < $start_row ) {
			WP_CLI::line( 'Starting CSV import at row ' . $start_row . '...' );
		} else {
			WP_CLI::line( 'Starting CSV import...' );
		}
		self::import_data( $file_path, $start_row, $max_rows );
		WP_CLI::success( 'Completed! Processed ' . ( self::$row_number - $start_row ) . ' records.' );
	}

	/**
	 * Load up the config file.
	 *
	 * @param string $config_path File path for the config file containing field mappings, relative to the plugin root.
	 * @return string Full absolute file path of the CSV.
	 */
	public static function load_config( $config_path = false ) {
		if ( empty( $config_path ) ) {
			return false;
		}

		$config_path = NEWSPACK_LISTINGS_PLUGIN_FILE . $config_path;

		if ( ! file_exists( $config_path ) ) {
			return false;
		}

		include_once $config_path;
		return $config_path;
	}

	/**
	 * Load up the CSV file.
	 *
	 * @param string $file_path File path for the CSV to process, relative to the plugin root.
	 * @return string Full absolute file path of the CSV.
	 */
	public static function load_file( $file_path = false ) {
		if ( empty( $file_path ) ) {
			return false;
		}

		$file_path = NEWSPACK_LISTINGS_PLUGIN_FILE . $file_path;

		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		self::$import_dir = dirname( $file_path );

		return $file_path;
	}

	/**
	 * Setup data for importing.
	 *
	 * @param string   $file_path Full absolute file path for the CSV to process.
	 * @param int      $start_row Number of the row to start with, for batching.
	 * @param int|bool $max_rows Maximum number of rows to process, for batching.
	 *
	 * @return void
	 */
	public static function import_data( $file_path, $start_row, $max_rows ) {
		$file_path        = addslashes( $file_path );
		$end_row          = ! empty( $max_rows ) ? $start_row + $max_rows : false;
		self::$row_number = 0;

		self::import_start( $file_path, $start_row, $end_row );
	}

	/**
	 * Load the CSV file contents and start the import.
	 *
	 * @param string   $file_path Full absolute file path for the CSV to process.
	 * @param int      $start_row Number of the row to start with, for batching.
	 * @param int|bool $end_row Number of the row to end with, for batching.
	 *
	 * @return void
	 */
	public static function import_start( $file_path, $start_row, $end_row ) {

		// Check if the function mb_detect_encoding exists. The mbstring extension must be installed on the server.
		$file_encoding = function_exists( 'mb_detect_encoding' ) ? mb_detect_encoding( $file_path, 'UTF-8, ISO-8859-1', true ) : false;

		if ( $file_encoding ) {
			setlocale( LC_ALL, 'en_US.' . $file_encoding );
		}

		@ini_set( 'auto_detect_line_endings', true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $file_path && ( $file_handle = fopen( $file_path, 'r' ) ) !== false ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, WordPress.WP.AlternativeFunctions.file_system_read_fopen
			$data           = [];
			$column_headers = fgetcsv( $file_handle, 0 );

			while ( ( $csv_row = fgetcsv( $file_handle, 0 ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition

				// If given a start row arg, skip to that row before beginning to import.
				if ( 0 !== $start_row && $start_row > self::$row_number + 1 ) {
					self::$row_number++;
					continue;
				}

				foreach ( $column_headers as $key => $header ) {
					if ( ! $header ) {
						continue;
					}
					$data[ $header ] = ( isset( $csv_row[ $key ] ) ) ? trim( Importer_Utils\format_data( $csv_row[ $key ], $file_encoding ) ) : '';
				}


				self::$row_number++;
				self::import_listing( $data );

				if ( $end_row && self::$row_number >= $end_row ) {
					break;
				}
			}
			fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}
	}

	/**
	 * Given CSV row data, create a listing post.
	 *
	 * @param object $data Row data in associative array format, keyed by column header name.
	 *
	 * @return void
	 */
	public static function import_listing( $data ) {
		WP_CLI::line( 'Importing data for ' . $data['post_title'] . '...' );

		$field_map           = NEWSPACK_LISTINGS_IMPORT_MAPPING; // Defined in config file.
		$separator           = NEWSPACK_LISTINGS_IMPORT_SEPARATOR; // Defined in config file.
		$post_type_to_create = Core::NEWSPACK_LISTINGS_POST_TYPES['place'];
		$existing_post       = function_exists( 'wpcom_vip_get_page_by_title' ) ?
			wpcom_vip_get_page_by_title( $data['post_title'], OBJECT, $post_type_to_create ) :
			get_page_by_title( $data['post_title'], OBJECT, $post_type_to_create ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_title_get_page_by_title

		// Post data to be inserted in WP.
		$post = [
			'post_author' => 1, // Default user in case author isn't defined.
			'post_date'   => ! empty( $data[ $field_map['post_date'] ] ) ? gmdate( 'Y-m-d H:i:s', $data[ $field_map['post_date'] ] ) : gmdate( 'Y-m-d H:i:s', time() ),
			'post_status' => 'publish',
			'post_title'  => ! empty( $data[ $field_map['post_title'] ] ) ? $data[ $field_map['post_title'] ] : __( '(no title)', 'newspack-listings' ),
			'post_type'   => $post_type_to_create,
		];

		// If a post already exists, update it.
		if ( $existing_post ) {
			$post['ID'] = $existing_post->ID;
		}

		// Handle post author.
		if ( ! empty( $data[ $field_map['post_author'] ] ) ) {
			$post_author = get_user_by( 'slug', $data[ $field_map['post_author'] ] );

			if ( $post_author ) {
				$post_author_id = $post_author->ID;
			} else {
				$post_author_id = wp_create_user( $data[ $field_map['post_author'] ], wp_generate_password() );
			}

			$post['post_author'] = $post_author_id;
		}

		// Handle featured image.
		if ( ! self::$is_dry_run && ! empty( $data[ $field_map['_thumbnail_id'] ] ) ) {
			$featured_image_id = self::process_images( explode( $separator, $data[ $field_map['_thumbnail_id'] ] ) );

			if ( ! empty( $featured_image_id ) ) {
				$post['_thumbnail_id'] = $featured_image_id;
			}
		}

		// Handle categories.
		if ( ! self::$is_dry_run && ! empty( $data[ $field_map['post_category'] ] ) ) {
			$category_names        = explode( $separator, $data[ $field_map['post_category'] ] );
			$category_ids          = self::handle_terms( $category_names, 'category' );
			$post['post_category'] = $category_ids;
		}

		// Handle tags.
		if ( ! self::$is_dry_run && ! empty( $data[ $field_map['tags_input'] ] ) ) {
			$tag_names          = explode( $separator, $data[ $field_map['tags_input'] ] );
			$tag_ids            = self::handle_terms( $tag_names, 'post_tag' );
			$post['tags_input'] = $tag_ids;
		}

		// If doing a dry run, don't create the post.
		if ( self::$is_dry_run ) {
			WP_CLI::success( $post['post_title'] . ' imported successfully.' );
		} else {
			$post_id = wp_insert_post( $post );
			WP_CLI::success( $post['post_title'] . ' imported successfully as post ID ' . $post_id . '.' );
		}
	}

	/**
	 * Given an array of term names, find existing term IDs or create the term if non-existent.
	 *
	 * @param array  $term_names Array of term names to look up.
	 * @param string $taxonomy Name of the taxonomy to look up or create.
	 *
	 * @return array Array of term IDs.
	 */
	public static function handle_terms( $term_names, $taxonomy ) {
		$term_ids = [];

		foreach ( $term_names as $term_name ) {
			$term = get_term_by( 'name', $term_name, $taxonomy );

			if ( ! $term ) {
				$term = wp_insert_term( $term_name, $taxonomy );
			}

			$term_id    = $term->term_id;
			$term_ids[] = $term_id;
		}

		return $term_ids;
	}

	/**
	 * Given an array of image filenames, create attachments and return the first image ID to be used as a featured image.
	 *
	 * @param array $images Array of image filenames. The importer will look for images in an /images
	 *                      directory in the same location as the CSV file being imported.
	 *
	 * @return int The attachment ID for the first image in the array.
	 */
	public static function process_images( $images ) {
		$attachment_ids = [];
		$upload_dir     = wp_upload_dir();

		foreach ( $images as $image ) {
			$image_path     = self::$import_dir . '/images/' . $image;
			$image_name     = basename( $image_path );
			$existing_image = function_exists( 'wpcom_vip_get_page_by_title' ) ?
				wpcom_vip_get_page_by_title( $image_name, OBJECT, 'attachment' ) :
				get_page_by_title( $image_name, OBJECT, 'attachment' ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_title_get_page_by_title

			// If an attachment for this image already exists in the Media Library, use that.
			if ( $existing_image ) {
				$attachment_ids[] = $existing_image->ID;
				continue;
			}

			// Otherwise, download the image as a new attachment.
			if ( file_exists( $image_path ) ) {
				$image_data = file_get_contents( $image_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				$image_type = wp_check_filetype( $image_name, null );

				if ( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $image_name;
				} else {
					$file = $upload_dir['basedir'] . '/' . $image_name;
				}

				file_put_contents( $file, $image_data ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents

				$attachment = [
					'post_mime_type' => $image_type['type'],
					'post_title'     => sanitize_file_name( $image_name ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				];

				$attachment_id = wp_insert_attachment( $attachment, $file );
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				$attachment_ids[] = $attachment_id;
			}
		}

		return reset( $attachment_ids );
	}
}

Newspack_Listings_Importer::instance();
