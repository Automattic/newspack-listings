<?php
/**
 * New class to handle the importing of Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Importer;

use Exception;
use Iterator;
use Newspack_Listings\Contracts\Importer_Mode as Importer_Mode_Interface;
use Newspack_Listings\Contracts\Listings_Type_Mapper as Listings_Type_Mapper_Interface;
use Newspack_Listings\File_Import_Factory;
use Newspack_Listings\Importer_Mode;
use Newspack_Listings\Listing_Type;
use Newspack_Listings\Listings_Type_Mapper;
use Newspack_Listings\Marketplace_Type;
use Newspack_Listings\Core;
use WP_CLI;
use WP_Error;
use WP_Post;
use WP_Query;

/**
 * The main benefit of this class is the ability to inject callbacks to
 * modify CSV row data before and after insertion.
 *
 * @class Newspack_Listings_Callable_Importer
 */
class Newspack_Listings_Callable_Importer {

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
	 * Object representing the import mode to be referenced.
	 *
	 * @var Importer_Mode_Interface $importer_mode
	 */
	protected Importer_Mode_Interface $importer_mode;

	/**
	 * This interface will facilitate the mapping of Newspack Listings Type to custom listing types.
	 *
	 * @var Listings_Type_Mapper_Interface $listings_mapper Custom mapper for Newspack Listings Type and user defined types.
	 */
	protected Listings_Type_Mapper_Interface $listings_mapper;

	/**
	 * This will be the category ID that all listings will be imported under.
	 *
	 * @var ?int $category_id Main category for imported listings.
	 */
	protected ?int $category_id = null;

	/**
	 * If a listing type is not found, will default to the below. Can be set during execution.
	 *
	 * @var string $default_listing_type Default Listing Post Type.
	 */
	protected string $default_listing_type = Listing_Type::GENERIC;

	/**
	 * List of Newspack Listing Type params.
	 *
	 * @var array $listing_params_map
	 */
	protected array $listing_params_map = [
		Listing_Type::GENERIC     => [
			'template' => [
				'handle'      => 'generic-listing-template',
				'description' => 'HTML template used for Newspack Listing Type: ' . Listing_Type::GENERIC,
			],
			'mapper'   => [
				'handle'      => 'generic-listing-types',
				'description' => 'Custom post types that should be mapped to the Generic Listing Type.',
			],
		],
		Listing_Type::EVENT       => [
			'template' => [
				'handle'      => 'event-listing-template',
				'description' => 'HTML template used for Newspack Listing Type: ' . Listing_Type::EVENT,
			],
			'mapper'   => [
				'handle'      => 'event-listing-types',
				'description' => 'Custom post types that should be mapped to the Event Listing Type',
			],
		],
		Listing_Type::MARKETPLACE => [
			'template' => [
				'handle'      => 'marketplace-listing-template',
				'description' => 'HTML template used for Newspack Listing Type: ' . Listing_Type::MARKETPLACE,
			],
			'mapper'   => [
				'handle'      => 'marketplace-listing-types',
				'description' => 'Custom post types that should be mapped to the Marketplace Listing Type.',
			],
		],
		Listing_Type::PLACE       => [
			'template' => [
				'handle'      => 'place-listing-template',
				'description' => 'HTML template used for Newspack Listing Type: ' . Listing_Type::PLACE,
			],
			'mapper'   => [
				'handle'      => 'place-listing-types',
				'description' => 'Custom post types that should be mapped to the Place Listing Type.',
			],
		],
	];

	/**
	 * This array should contain Newspack Listing Type => HTML Template pair.
	 *
	 * @var array $template_override
	 */
	protected array $template_override = [];

	/**
	 * Return's singleton instance.
	 *
	 * @return Newspack_Listings_Callable_Importer
	 */
	public static function get_instance(): Newspack_Listings_Callable_Importer {

		if ( is_null( self::$instance ) ) {
			self::$instance                  = new static();
			self::$instance->importer_mode   = new Importer_Mode();
			self::$instance->listings_mapper = new Listings_Type_Mapper();
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
		$synopsis = [
			[
				'type'        => 'positional',
				'name'        => 'file',
				'description' => 'Path to file',
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
				'name'        => 'end',
				'description' => 'Row number to end at.',
				'optional'    => true,
				'repeating'   => false,
			],
			[
				'type'        => 'assoc',
				'name'        => 'mode',
				'description' => 'Whether to perform a dry-run (no DB interaction), update, or skip if already imported.',
				'optional'    => true,
				'default'     => 'update',
				'options'     => [ 'dry-run', 'update', 'skip' ],
			],
			[
				'type'        => 'assoc',
				'name'        => 'pre-create-callback',
				'description' => 'Path to custom callback to be executed before Newspack Listing Post creation.',
				'optional'    => true,
				'repeating'   => false,
			],
			[
				'type'        => 'assoc',
				'name'        => 'post-create-callback',
				'description' => 'Path to custom callback to be executed after Newspack Listing Post creation.',
				'optional'    => true,
				'repeating'   => false,
			],
			[
				'type'        => 'flag',
				'name'        => 'use-parent-category',
				'description' => 'This flag determines whether the listings should be imported under a specific category.',
				'optional'    => true,
			],
			[
				'type'        => 'assoc',
				'name'        => 'parent-category',
				'description' => 'Specify the main category name to use.',
				'optional'    => true,
				'repeating'   => false,
			],
			[
				'type'        => 'assoc',
				'name'        => 'default-listing-type',
				'description' => 'The default listing type to use, if none found.',
				'optional'    => true,
				'default'     => Listing_Type::GENERIC,
				'options'     => array_keys( self::$instance->listing_params_map ),
				'repeating'   => false,
			],
		];

		// Add Newspack Listing Types params.
		foreach ( self::$instance->listing_params_map as $values ) {
			$synopsis[] = [
				'type'        => 'assoc',
				'name'        => $values['mapper']['handle'],
				'description' => $values['mapper']['description'],
				'optional'    => true,
				'repeating'   => true,
			];
		}

		foreach ( self::$instance->listing_params_map as $values ) {
			$synopsis[] = [
				'type'        => 'assoc',
				'name'        => $values['template']['handle'],
				'description' => $values['template']['description'],
				'optional'    => true,
				'repeating'   => true,
			];
		}

		WP_CLI::add_command(
			'newspack-listings import',
			[ self::$instance, 'handle_import_command' ],
			[
				'shortdesc' => 'Import listings data.',
				'synopsis'  => $synopsis,
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
			if ( str_contains( $callable_pre_create, '.php' ) ) {
				$callable_pre_create = $this->include_class( $callable_pre_create );
			}

			$callable_pre_create = new $callable_pre_create();
		}

		if ( ! ( $callable_pre_create instanceof Abstract_Callable_Pre_Create ) ) {
			throw new Exception( 'Please provide a valid class of type Abstract_Callable_Pre_Create.' );
		}

		$this->callable_pre_create = $callable_pre_create;
	}

	/**
	 * Get the instance of @return Abstract_Callable_Pre_Create|null
	 *
	 * @see Abstract_Callable_Pre_Create that was set.
	 */
	protected function get_callable_pre_create(): ?Abstract_Callable_Pre_Create {
		return $this->callable_pre_create;
	}

	/**
	 * Set a callable instance for execution.
	 *
	 * @param string|Abstract_Callable_Post_Create $callable_post_create TBD.
	 *
	 * @return void
	 * @throws Exception Will throw an exception is class is not found.
	 */
	protected function set_callable_post_create( $callable_post_create ) {
		if ( is_string( $callable_post_create ) ) {
			if ( str_contains( $callable_post_create, '.php' ) ) {
				$callable_post_create = $this->include_class( $callable_post_create );
			}

			$callable_post_create = new $callable_post_create();
		}

		if ( ! ( $callable_post_create instanceof Abstract_Callable_Post_Create ) ) {
			throw new Exception( 'Please provide a valid class of type Abstract_Callable_Post_Create.' );
		}

		$this->callable_post_create = $callable_post_create;
	}

	/**
	 * Get the instance of @return Abstract_Callable_Post_Create|null
	 *
	 * @see Abstract_Callable_Post_Create that was set
	 */
	protected function get_callable_post_create(): ?Abstract_Callable_Post_Create {
		return $this->callable_post_create;
	}

	/**
	 * Setting this variable will cause a category to be created and be associated with all imported listings.
	 *
	 * @param string $category Main category used for imported listings.
	 */
	protected function set_category_id( string $category ) {
		if ( category_exists( $category ) ) {
			WP_CLI::confirm( "Category: '$category' already exists. Are you sure you want to proceed?" );

			global $wpdb;

			$category_sql = "SELECT * FROM $wpdb->terms t
    			LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
				WHERE t.name = '$category'
				AND tt.taxonomy = 'category'";

			$result = $wpdb->get_results( $category_sql );
			$result = array_shift( $result );

			$this->category_id = $result->term_id;
		} else {
			if ( $this->get_importer_mode()->is_update() || $this->get_importer_mode()->is_skip() ) {
				$this->category_id = wp_create_category( $category );
			}
		}
	}

	/**
	 * Returns the Category ID set by the import.
	 *
	 * @return int|null
	 */
	protected function get_category_id(): ?int {
		return $this->category_id;
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
		if ( array_key_exists( 'pre-create-callback', $assoc_args ) ) {
			$this->set_callable_pre_create( $assoc_args['pre-create-callback'] );
		}

		if ( array_key_exists( 'post-create-callback', $assoc_args ) ) {
			$this->set_callable_post_create( $assoc_args['post-create-callback'] );
		}

		if ( array_key_exists( 'use-parent-category', $assoc_args ) ) {
			if ( $assoc_args['use-parent-category'] ) {
				if ( ! array_key_exists( 'parent-category', $assoc_args ) ) {
					WP_CLI::error( 'The `--use-parent-category` flag was set, but no parent category was provided.' );
				}

				$this->set_category_id( $assoc_args['parent-category'] );
			}
		}

		$this->get_importer_mode()->set_mode( $assoc_args['mode'] ?? '' );

		$this->set_default_listing_type( $assoc_args['default-listing-type'] ?? Listing_Type::GENERIC );

		$assoc_keys            = array_keys( $assoc_args );
		$custom_listings_types = array_intersect(
			array_map( fn( $listing_param ) => $listing_param['mapper']['handle'], $this->listing_params_map ),
			$assoc_keys
		);

		$listing_types_by_mapper_handle = [];

		foreach ( $this->listing_params_map as $key => $values ) {
			$listing_types_by_mapper_handle[ $values['mapper']['handle'] ] = $key;
		}

		if ( ! empty( $custom_listings_types ) ) {
			$listings_type_mapper = new Listings_Type_Mapper();

			foreach ( $custom_listings_types as $key ) {
				$listings_type_mapper->set_types(
					$listing_types_by_mapper_handle[ $key ],
					explode( ',', trim( $assoc_args[ $key ] ) )
				);

				$this->set_listings_type_mapper( $listings_type_mapper );
			}
		}

		foreach ( $this->listing_params_map as $key => $values ) {
			if ( array_key_exists( $values['template']['handle'], $assoc_args ) ) {
				$this->template_override[ $key ] = $assoc_args[ $values['template']['handle'] ];
			}
		}

		$this->import(
			( new File_Import_Factory() )
				->get_file( $args[0] )
				->set_start( $assoc_args['start'] ?? 0 )
				->set_end( $assoc_args['end'] ?? PHP_INT_MAX )
				->getIterator()
		);
	}

	/**
	 * Set the default listing type to be used when a listing type can't be found.
	 *
	 * @param string $type Listing type.
	 */
	protected function set_default_listing_type( string $type ) {
		// TODO does WP CLI throw an exception/error if a value not in the options list is given?
		if ( Core::is_listing( $type ) ) {
			$this->default_listing_type = $type;
		}
	}

	/**
	 * Returns the default listing type to use when no listing type can be found.
	 *
	 * @return string
	 */
	protected function get_default_listing_type(): string {
		return $this->default_listing_type;
	}

	/**
	 * Get the object representing the mode the importer is running under.
	 *
	 * @return Importer_Mode_Interface
	 */
	protected function get_importer_mode(): Importer_Mode_Interface {
		return $this->importer_mode;
	}

	/**
	 * Import data rows.
	 *
	 * @param Iterator $iterator Cycle through rows.
	 *
	 * @throws Exception Throws exception if data row cannot be imported properly.
	 */
	public function import( Iterator $iterator ) {
		do {
			$row = $iterator->current();

			if ( ! is_null( $this->get_callable_pre_create() ) ) {
				$this->get_callable_pre_create()( $row, $this->get_importer_mode() );
			}

			$listing = $this->create_or_update_listing( $row );

			if ( isset( $this->callable_post_create ) ) {
				$this->get_callable_post_create()( $listing, $this->get_importer_mode(), $row );
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
	 *
	 * @throws Exception Throws exception if a data row cannot be processed.
	 */
	protected function create_or_update_listing( array $row ): WP_Post {
		$post_type     = $this->get_listing_type( $row );
		$existing_post = function_exists( 'wpcom_vip_get_page_by_title' ) ?
			wpcom_vip_get_page_by_title( $row['wp_post.post_title'], OBJECT, $post_type ) :
			new WP_Query( [ 'post_type' => $post_type, 'title' => $row['wp_post.post_title'] ] );

		if ( $existing_post instanceof WP_Query ) {
			if ( $existing_post->found_posts >= 1 ) {
				$existing_post = $existing_post->posts[0];
			}
		}

		$incoming_post_data = [
			'post_author' => 1, // Default user in case author isn't defined.
		];

		$other_data = [];

		foreach ( $row as $key => $value ) {
			if ( str_starts_with( $key, 'wp_post.' ) ) {
				$incoming_post_data[ str_replace( 'wp_post.', '', $key ) ] = $value;
			} else {
				$other_data[ $key ] = $value;
			}
		}

		$date_format                        = 'Y-m-d H:i:s';
		$incoming_post_data['post_type']    = $post_type;
		$incoming_post_data['post_date']    = isset( $incoming_post_data['post_date'] ) ?
			gmdate( $date_format, strtotime( $incoming_post_data['post_date'] ) ) :
			gmdate( $date_format, time() );
		$incoming_post_data['post_excerpt'] = $incoming_post_data['post_excerpt'] ?? '';
		$incoming_post_data['post_title']   = $incoming_post_data['post_title'] ?? __( '(no title)', 'newspack-listing' );
		$incoming_post_data['post_status']  = 'publish';
		$incoming_post_data['meta_input']['_wp_page_template'] = $incoming_post_data['meta_input']['_wp_page_template'] ?? 'single-wide.php';
		// Keep featured image position if set, otherwise, featured image is shown in listing content.
		$incoming_post_data['meta_input']['newspack_featured_image_position'] = $incoming_post_data['meta_input']['newspack_featured_image_position'] ?? 'hidden';

		WP_CLI::log( $incoming_post_data['post_title'] );

		if ( is_string( $incoming_post_data['post_author'] ) ) {
			$incoming_post_data['post_author'] = $this->handle_post_author( $incoming_post_data['post_author'] );
		}

		$images = [];

		if ( array_key_exists( 'images', $row ) ) {
			if ( is_string( $row['images'] ) ) {
				if ( str_contains( $row['images'], ',' ) ) {
					$paths          = explode( ',', $row['images'] );
					$array_of_paths = [];
					foreach ( $paths as $path ) {
						$array_of_paths[] = [ 'path' => $path ];
					}
					$row['images'] = $array_of_paths;
				} else {
					$row['images'] = [ [ 'path' => $row['images'] ] ];
				}
			}

			$images = $this->handle_post_images( $row['images'] );

			if ( array_key_exists( 'featured_image', $images ) ) {
				$incoming_post_data['meta_input']['_thumbnail_id'] = $images['featured_image']['id'];
			}
		}

		$incoming_post_data['post_content'] = $this->handle_post_content(
			$incoming_post_data['post_type'] ?? '',
			$other_data,
			$images
		);

		if ( $this->get_importer_mode()->is_dry_run() ) {
			if ( $existing_post ) {
				$diff = array_diff_assoc( $existing_post->to_array(), $incoming_post_data );

				/*var_dump(
					[
						'Existing Post' => $existing_post->to_array(),
						'Updates'       => $diff,
					]
				);*/

				return $this->handle_existing_post_properties( $existing_post, $incoming_post_data, $post_type );
			} else {
				/*var_dump(
					[
						'New Post' => $incoming_post_data,
					]
				);*/

				return new WP_Post( (object) $incoming_post_data );
			}
		}

		if ( $this->get_importer_mode()->is_skip() ) {
			$post_exists = $existing_post ? 'exists' : 'does not exist';
			WP_CLI::line( "Post '{$incoming_post_data['post_title']}' $post_exists" );

			if ( $existing_post ) {
				$this->handle_category( $other_data, $existing_post->ID );
				$this->handle_tag( $other_data, $existing_post->ID );
			}
		}

		if ( $this->get_importer_mode()->is_update() ) {
			if ( $existing_post ) {
				$post_id = wp_insert_post(
					$this->handle_existing_post_properties( $existing_post, $incoming_post_data, $post_type ),
					true
				);
			} else {
				$post_id = wp_insert_post( $incoming_post_data, true );
			}

			if ( $post_id instanceof WP_Error ) {
				WP_CLI::error( $post_id->get_error_message() );
			} else {
				$existing_post = get_post( $post_id );
			}

			$this->handle_category( $other_data, $post_id );
			$this->handle_tag( $other_data, $post_id );
		}

		return $existing_post ?? new WP_Post( (object) $incoming_post_data );
	}

	/**
	 * Setter for @see Listings_Type_Mapper.
	 *
	 * @param Listings_Type_Mapper_Interface $mapper Custom mapper to handle relationship between custom types and Newspack Listing Types.
	 */
	protected function set_listings_type_mapper( Listings_Type_Mapper_Interface $mapper ) {
		$this->listings_mapper = $mapper;
	}

	/**
	 * Getter for @return Listings_Type_Mapper_Interface
	 *
	 * @see Listings_Type_Mapper.
	 */
	protected function get_listings_type_mapper(): Listings_Type_Mapper_Interface {
		return $this->listings_mapper;
	}

	/**
	 * Convenience function to handle the proper retrieval of Newspack Listing Type.
	 *
	 * @param array $row Row of data from file.
	 *
	 * @return string
	 */
	protected function get_listing_type( array $row ): string {
		if ( $this->listings_mapper->has_mapped_types() ) {
			try {
				return $this->listings_mapper->get_listing_type( $row['wp_post.post_type'] );
			} catch ( Exception $e ) {
				return $this->get_default_listing_type();
			}
		}

		return $this->get_default_listing_type();
	}

	/**
	 * Get or create the Author's ID.
	 *
	 * @param string $author_slug URL representing the Author.
	 *
	 * @return int
	 */
	protected function handle_post_author( string $author_slug ): int {
		$post_author = get_user_by( 'slug', $author_slug );

		if ( $post_author ) {
			return $post_author->ID;
		} else {
			return wp_create_user( $author_slug, wp_generate_password() );
		}
	}

	/**
	 * Generates a Newspack Listing Block from a template.
	 *
	 * @param string $listing_type @see Listing_Type.
	 * @param array  $data Newspack Listing Post Data.
	 * @param array  $images Array of images to be inserted into post.
	 *
	 * @return string
	 */
	protected function handle_post_content( string $listing_type, array $data, array $images = [] ): string {
		$processed_featured_image = '';
        $processed_images = '';

        foreach ( $images as $key => $image ) {
            if ( 'featured_image' === $key ) {
                $featured_image_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/featured_image.html' );
                $processed_featured_image = strtr(
                    $featured_image_template,
                    [
                        '{id}'  => $image['id'],
                        '{url}' => $image['path'],
                    ]
                );
            } else {
                $image_template = file_get_contents(WP_PLUGIN_DIR . '/newspack-listings/includes/templates/image.html');
                $processed_images .= strtr(
                    $image_template,
                    [
                        '{id}' => $image['id'],
                        '{url}' => $image['path'],
                    ]
                );
            }
        }

		if ( array_key_exists( $listing_type, $this->template_override ) ) {
			$place_template = file_get_contents( $this->template_override[ $listing_type ] );

			foreach ( $data as $key => $value ) {
				if ( ! str_starts_with( $key, '{' ) && ! str_ends_with( $key, '}' ) ) {
					$data[ "{{$key}}" ] = $value;
					unset( $data[ $key ] );
				}
			}

			$data['{featured_image}'] = $processed_featured_image;
            $data['{processed_images}'] = $processed_images;

			return strtr( $place_template, $data );
		}

		switch ( $listing_type ) {
			case Listing_Type::PLACE:
				$place_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/place/place.html' );

				$content = strtr(
					$place_template,
					[
						'{featured_image}' => $processed_featured_image,
						'{description}'    => $data['description'] ?? '',
						'{email}'          => $data['email'] ?? '',
						'{phone}'          => $data['phone'] ?? '',
						'{phone_display}'  => $data['phone_display'] ?? '',
						'{address_street}' => $data['address_street'] ?? '',
						'{address_city}'   => $data['address_city'] ?? '',
						'{address_region}' => $data['address_region'] ?? '',
						'{address_postal}' => $data['address_postal'] ?? '',
					]
				);
				break;
			case Listing_Type::MARKETPLACE:
				if ( ! array_key_exists( 'marketplace_type', $data ) ) {
					WP_CLI::warning( 'Listing Type is ' . Listing_Type::MARKETPLACE . " but no 'marketplace_type' param found." );
					$content = '';
					break;
				}

				if ( Marketplace_Type::CLASSIFIED === strtolower( $data['marketplace_type'] ) ) {
					$classified_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/marketplace/classified.html' );

					$content = strtr(
						$classified_template,
						[
							'{featured_image}'  => $processed_featured_image,
							'{price}'           => $data['price'] ?? '',
							'{formatted_price}' => $data['formatted_price'] ?? '',
							'{description}'     => $data['description'] ?? '',
						]
					);
				} else {
					$marketplace_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/marketplace/real_estate.html' );

					$content = strtr(
						$marketplace_template,
						[
							'{featured_image}'   => $processed_featured_image,
							'{email}'            => $data['email'],
							'{phone}'            => $data['phone'],
							'{phone_display}'    => $data['phone_display'] ?? '',
							'{address_street}'   => $data['address_street'] ?? '',
							'{address_city}'     => $data['address_city'] ?? '',
							'{address_region}'   => $data['address_region'] ?? '',
							'{address_postal}'   => $data['address_postal'] ?? '',
							'{price}'            => $data['price'] ?? '',
							'{formatted_price}'  => $data['formatted_price'] ?? '',
							'{show_decimals}'    => $data['show_decimals'] ?? '',
							'{bedroom_count}'    => $data['bedroom_count'] ?? '',
							'{bathroom_count}'   => $data['bathroom_count'] ?? '',
							'{area}'             => $data['area'] ?? '',
							'{area_measurement}' => $data['area_measurement'] ?? '',
							'{description}'      => $data['description'] ?? '',
							'{property_details}' => $data['property_details'] ?? '',
							'{year_built}'       => $data['year_built'] ?? '',
							'{garage}'           => $data['garage'] ?? '',
							'{basement}'         => $data['basement'] ?? '',
							'{heating}'          => $data['heating'] ?? '',
							'{cooling}'          => $data['cooling'] ?? '',
							'{appliances}'       => $data['appliances'] ?? '',
						]
					);
				}
				break;
			case Listing_Type::EVENT:
				$event_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/event/event.html' );

				$content = strtr(
					$event_template,
					[
						'{featured_image}' => $processed_featured_image,
						'{start_date}'     => $data['start_date'] ?? '',
					]
				);
				break;
			case Listing_Type::GENERIC:
			default:
				$generic_template = file_get_contents( WP_PLUGIN_DIR . '/newspack-listings/includes/templates/generic.html' );

				$content = strtr(
					$generic_template,
					[
						'{featured_image}' => $processed_featured_image,
						'{html}'           => $data['html'] ?? '',
					]
				);
		}

		return $content;
	}

	/**
	 * Handles properly uploading images.
	 *
	 * @param string[]|array $images Array of image paths or URL's.
	 *
	 * @return string[]
	 *
	 * @throws Exception Random Int generation throws exception.
	 */
	protected function handle_post_images( array $images ): array {
		$uploaded_images  = [];
		$upload_directory = wp_upload_dir();

		foreach ( $images as $key => $image ) {
			$image_name   = basename( $image['path'] );
			$image_exists = function_exists( 'wpcom_vip_get_page_by_title' ) ?
				wpcom_vip_get_page_by_title( $image_name, OBJECT, 'attachment' ) :
				new WP_Query( [ 'post_type' => 'attachment', 'title' => $image_name] );

			if ( $image_exists instanceof WP_Query ) {
				if ( $image_exists->found_posts >= 1 ) {
					$image_exists = $image_exists->posts[0];
				}
			}

			if ( $image_exists ) {
                $uploads_folder_file_path = get_post_meta( $image_exists->ID, '_wp_attached_file', true );
                $id = $image_exists->ID;
                $path = $upload_directory['baseurl'] . '/' . $uploads_folder_file_path;

				if ( is_string( $key ) ) {
					$uploaded_images[ $key ] = [
						'id'   => $id,
						'path' => $path,
					];
				} else {
					if ( ! array_key_exists( 'featured_image', $uploaded_images ) ) {
						$uploaded_images['featured_image'] = [
                            'id' => $id,
                            'path' => $path,
                        ];
					} else {
						$uploaded_images[] = [
							'id'   => $id,
							'path' => $path,
						];
					}
				}

				continue;
			}

			if ( $this->get_importer_mode()->is_dry_run() ) {
				if ( is_string( $key ) ) {
					$uploaded_images[ $key ] = $image;
				} else {
					if ( ! array_key_exists( 'featured_image', $uploaded_images ) ) {
						$uploaded_images['featured_image'] = $image;
					} else {
						$uploaded_images[] = $image;
					}
				}
			} elseif ( file_exists( $image['path'] ) || filter_var( $image['path'], FILTER_VALIDATE_URL ) ) {
				$image_data = file_get_contents( $image['path'] );
				$image_type = wp_check_filetype( $image_name );

				$file_path = '/' . urldecode( $image_name );
				if ( wp_mkdir_p( $upload_directory['path'] ) ) {
					$file_path = "{$upload_directory['path']}$file_path";
				} else {
					$file_path = "{$upload_directory['basedir']}$file_path";
				}

				file_put_contents( $file_path, $image_data );

				$attachment = [
					'post_mime_type' => $image_type['type'],
					'post_title'     => sanitize_file_name( $image_name ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				];

				$attachment_id = random_int( 5, 5 );
				$path          = "https://example.com/$attachment_id";

				if ( $this->get_importer_mode()->is_update() ) {
					$attachment_id = wp_insert_attachment( $attachment, $file_path );
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
					$path = $upload_directory['baseurl'] . '/' . $attachment_data['file'];
				}

				$uploaded_image = [
					'id'   => $attachment_id,
					'path' => $path,
				];

				if ( is_string( $key ) ) {
					$uploaded_images[ $key ] = $uploaded_image;
				} else {
					if ( ! array_key_exists( 'featured_image', $uploaded_images ) ) {
						$uploaded_images['featured_image'] = $uploaded_image;
					} else {
						$uploaded_images[] = $uploaded_image;
					}
				}
			}
		}

		return $uploaded_images;
	}

	/**
	 * Convenience function to handle the import of categories or category. All categories will
	 * be imported under a parent category if it's provided.
	 *
	 * @param array $data Incoming data from import file.
	 * @param int   $post_id The ID of the post to associate with the category.
	 */
	protected function handle_category( array $data, int $post_id ) {
		if ( array_key_exists( 'categories', $data ) && ! array_key_exists( 'category', $data ) && ! empty( $data['categories'] ) ) {
			foreach ( $data['categories'] as $category ) {
				if ( ! is_array( $category ) ) {
					$category = [ 'category' => $category ];
				}

				$this->handle_category_creation_or_update( $category, $post_id );
			}
		} elseif ( array_key_exists( 'category', $data ) && ! array_key_exists( 'categories', $data ) ) {
			$this->handle_category_creation_or_update( $data, $post_id );
		} else {
			WP_CLI::warning( 'Either no categories provided, or something wrong with categories.' );
		}
	}

	/**
	 * This function will handle the creation of a category record if necessary or the update.
	 *
	 * @param array $data Incoming data from import file.
	 * @param int   $post_id The ID of the post to associate with the category.
	 */
	protected function handle_category_creation_or_update( array $data, int $post_id ) {
		$category = $data['category'];

		global $wpdb;

		$constraint = '';

		if ( is_string( $category ) ) {
			$constraint = "t.name = '$category'";
		} elseif ( is_int( $category ) ) {
			$constraint = "t.term_id = $category";
		}

		$category_sql = "SELECT t.term_id, t.name, tt.taxonomy, tt.parent as parent_term_id FROM $wpdb->terms as t
			LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
			WHERE $constraint AND tt.taxonomy = 'category'";
		$result       = $wpdb->get_results( $category_sql );

		if ( ! empty( $result ) ) {
			$category = array_shift( $result );

			if ( $this->get_category_id() !== $category->parent_term_id && $this->can_touch_database() ) {
				$wpdb->update(
					$wpdb->term_taxonomy,
					[
						'parent' => $this->get_category_id(),
					],
					[
						'term_id'  => $category->term_id,
						'taxonomy' => 'category',
					]
				);

				$this->add_category_to_post( $post_id, $category->term_id );
			}
		} else {
			$parent = $this->get_category_id() ?? 0;

			if ( $this->can_touch_database() ) {
				$category_id = wp_create_category( $category, $parent );

				$this->add_category_to_post( $post_id, $category_id );
			}
		}
	}

	/**
	 * Convenience function to handle the importation of a single or multiple tags.
	 *
	 * @param array $data Incoming data from import file.
	 * @param int   $post_id The ID of the post to associate with tag.
	 */
	protected function handle_tag( array $data, int $post_id ) {
		if ( array_key_exists( 'tags', $data ) && ! array_key_exists( 'tag', $data ) && ! empty( $data['tags'] ) ) {
			foreach ( $data['tags'] as $tag ) {
				if ( ! is_array( $tag ) ) {
					$tag = [ 'tag' => $tag ];
				}

				$this->handle_tag_creation_or_update( $tag, $post_id );
			}
		} elseif ( array_key_exists( 'tag', $data ) && ! array_key_exists( 'tags', $data ) ) {
			$this->handle_tag_creation_or_update( $data, $post_id );
		} else {
			WP_CLI::warning( 'Either no tags provided, or something wrong with tags.' );
		}
	}

	/**
	 * This function will handle the creation or update of tags.
	 *
	 * @param array $data Incoming data from import file.
	 * @param int   $post_id The ID of the post to associate with tag.
	 */
	protected function handle_tag_creation_or_update( array $data, int $post_id ) {
		if ( tag_exists( $data['tag'] ) ) {
			if ( $this->can_touch_database() ) {
				wp_add_post_tags( $post_id, $data['tag'] );
			}
		} else {
			if ( $this->can_touch_database() ) {
				$tag = wp_create_tag( $data['tag'] );

				wp_add_post_tags( $post_id, $tag );
			}
		}
	}

	/**
	 * Includes the file at the given path, and returns the Class name.
	 *
	 * @param string $path Path to class/file.
	 *
	 * @return string
	 */
	private function include_class( string $path ): string {
		[ $file_path, $class ] = explode( ',', $path );

		require_once $file_path;

		return $class;
	}

	/**
	 * This function will sync the $new_params into an existing $post.
	 *
	 * @param WP_Post $post Existing post.
	 * @param array   $new_params New data from file.
	 * @param string  $post_type Newspack Listing Type.
	 *
	 * @return WP_Post
	 */
	private function handle_existing_post_properties( WP_Post $post, array $new_params, string $post_type ): WP_Post {
        unset( $new_params['ID'] );
        unset( $new_params['id'] );
		foreach ( $new_params as $key => $value ) {
			$post->$key = $value;
		}

		$post->post_type = $post_type;

		return $post;
	}

	/**
	 * Convenience function to easily associate a category and a post.
	 *
	 * @param int $post_id The post to associate the category to.
	 * @param int $category_id AKA term_id. The category to be associated with a post.
	 */
	private function add_category_to_post( int $post_id, int $category_id ) {
		global $wpdb;

		$associated_check_sql = "SELECT * FROM $wpdb->term_relationships tr
			INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tt.term_id = $category_id AND tt.taxonomy = 'category' AND tr.object_id = $post_id";
		$result               = $wpdb->get_results( $associated_check_sql );

		if ( empty( $result ) && $this->can_touch_database() ) {
			$term_taxonomy_sql = "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_id = $category_id AND taxonomy = 'category'";
			$result            = $wpdb->get_results( $term_taxonomy_sql );
			$result            = array_shift( $result );
			$result            = $wpdb->insert(
				$wpdb->term_relationships,
				[
					'object_id'        => $post_id,
					'term_taxonomy_id' => $result->term_taxonomy_id,
				]
			);
		}
	}

	/**
	 * Determines whether the import script is running in a mode that allows DB insertion or update.
	 *
	 * @return bool
	 */
	private function can_touch_database(): bool {
		return $this->get_importer_mode()->is_update() || $this->get_importer_mode()->is_skip();
	}
}

Newspack_Listings_Callable_Importer::get_instance();
