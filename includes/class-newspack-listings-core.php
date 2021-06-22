<?php
/**
 * Newspack Listings Core.
 *
 * Registers custom post types and metadata.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Settings as Settings;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Main Core class.
 * Sets up CPTs for listings.
 */
final class Newspack_Listings_Core {

	/**
	 * Custom post type slugs for Newspack Listings.
	 */
	const NEWSPACK_LISTINGS_POST_TYPES = [
		'event'       => 'newspack_lst_event',
		'generic'     => 'newspack_lst_generic',
		'marketplace' => 'newspack_lst_mktplce',
		'place'       => 'newspack_lst_place',

	];

	/**
	 * Permalink slugs for Newspack Listings CPTs.
	 */
	const NEWSPACK_LISTINGS_PERMALINK_SLUGS = [
		'event'       => 'events',
		'generic'     => 'items',
		'marketplace' => 'marketplace',
		'place'       => 'places',

	];

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Core
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Core - Main instance.
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
		add_action( 'admin_menu', [ __CLASS__, 'add_plugin_page' ] );
		add_action( 'init', [ __CLASS__, 'register_post_types' ] );
		add_action( 'admin_init', [ __CLASS__, 'convert_legacy_taxonomies' ] );
		add_filter( 'body_class', [ __CLASS__, 'set_template_class' ] );
		add_action( 'save_post', [ __CLASS__, 'sync_post_meta' ], 10, 2 );
		add_filter( 'newspack_listings_hide_author', [ __CLASS__, 'hide_author' ] );
		add_filter( 'newspack_listings_hide_publish_date', [ __CLASS__, 'hide_publish_date' ] );
		add_filter( 'newspack_theme_featured_image_post_types', [ __CLASS__, 'support_featured_image_options' ] );
		add_filter( 'newspack_sponsors_post_types', [ __CLASS__, 'support_newspack_sponsors' ] );
		register_activation_hook( NEWSPACK_LISTINGS_FILE, [ __CLASS__, 'activation_hook' ] );
	}

	/**
	 * Add options page.
	 */
	public static function add_plugin_page() {
		// Top-level menu item.
		add_menu_page(
			'Newspack Listings',
			'Listings',
			'edit_posts',
			'newspack-listings',
			'',
			'data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjZmZmIiBoZWlnaHQ9IjI0IiB2aWV3Qm94PSIwIDAgMjQgMjQiIHdpZHRoPSIyNCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJtNS41IDcuNWgydjJoLTJ6bTIgNGgtMnYyaDJ6bTEtNGg3djJoLTd6bTcgNGgtN3YyaDd6Ii8+PHBhdGggY2xpcC1ydWxlPSJldmVub2RkIiBkPSJtNC42MjUgM2MtLjg5NyAwLTEuNjI1LjcyOC0xLjYyNSAxLjYyNXYxMS43NWMwIC44OTguNzI4IDEuNjI1IDEuNjI1IDEuNjI1aDExLjc1Yy44OTggMCAxLjYyNS0uNzI3IDEuNjI1LTEuNjI1di0xMS43NWMwLS44OTctLjcyNy0xLjYyNS0xLjYyNS0xLjYyNXptMTEuNzUgMS41aC0xMS43NWEuMTI1LjEyNSAwIDAgMCAtLjEyNS4xMjV2MTEuNzVjMCAuMDY5LjA1Ni4xMjUuMTI1LjEyNWgxMS43NWEuMTI1LjEyNSAwIDAgMCAuMTI1LS4xMjV2LTExLjc1YS4xMjUuMTI1IDAgMCAwIC0uMTI1LS4xMjV6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48cGF0aCBkPSJtMjEuNzUgOGgtMS41djExYzAgLjY5LS41NiAxLjI1LTEuMjQ5IDEuMjVoLTEzLjAwMXYxLjVoMTMuMDAxYTIuNzQ5IDIuNzQ5IDAgMCAwIDIuNzQ5LTIuNzV6Ii8+PC9zdmc+Cg==',
			35
		);

		// Custom taxonomy menu links.
		add_submenu_page(
			'newspack-listings',
			__( 'Newspack Listings: Categories', 'newspack-listings' ),
			__( 'Categories', 'newspack-listings' ),
			'manage_categories',
			'edit-tags.php?taxonomy=category'
		);
		add_submenu_page(
			'newspack-listings',
			__( 'Newspack Listings: Tags', 'newspack-listings' ),
			__( 'Tags', 'newspack-listings' ),
			'manage_categories',
			'edit-tags.php?taxonomy=post_tag'
		);

		// Settings menu link.
		add_submenu_page(
			'newspack-listings',
			__( 'Newspack Listings: Site-Wide Settings', 'newspack-listings' ),
			__( 'Settings', 'newspack-listings' ),
			'manage_options',
			'newspack-listings-settings-admin',
			[ '\Newspack_Listings\Newspack_Listings_Settings', 'create_admin_page' ]
		);
	}

	/**
	 * Is the current post a listings post type?
	 *
	 * @param string|null $post_type (Optional) Post type to check. If not given, will try to figure out the current post type.
	 *
	 * @returns Boolean Whether or not the current post type matches one of the listings CPTs.
	 */
	public static function is_listing( $post_type = null ) {
		if ( null === $post_type ) {
			$post_type = Utils\get_post_type();
		}

		if ( in_array( $post_type, self::NEWSPACK_LISTINGS_POST_TYPES ) ) {
			return true;
		}

		return false;
	}

	/**
	 * After WP init, register all the necessary post types and blocks.
	 */
	public static function register_post_types() {
		$settings          = Settings::get_settings();
		$prefix            = $settings['newspack_listings_permalink_prefix'];
		$default_config    = [
			'has_archive'  => false,
			'public'       => true,
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'editor', 'excerpt', 'title', 'author', 'custom-fields', 'thumbnail', 'newspack_blocks', 'revisions' ],
			'taxonomies'   => [ 'category', 'post_tag' ],
		];
		$post_types_config = [
			'event'       => [
				'labels'   => [
					'name'               => _x( 'Events', 'post type general name', 'newspack-listings' ),
					'singular_name'      => _x( 'Event', 'post type singular name', 'newspack-listings' ),
					'menu_name'          => _x( 'Events', 'admin menu', 'newspack-listings' ),
					'name_admin_bar'     => _x( 'Event', 'add new on admin bar', 'newspack-listings' ),
					'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
					'add_new_item'       => __( 'Add New Event', 'newspack-listings' ),
					'new_item'           => __( 'New Event', 'newspack-listings' ),
					'edit_item'          => __( 'Edit Event', 'newspack-listings' ),
					'view_item'          => __( 'View Event', 'newspack-listings' ),
					'all_items'          => __( 'Events', 'newspack-listings' ),
					'search_items'       => __( 'Search Events', 'newspack-listings' ),
					'parent_item_colon'  => __( 'Parent Event:', 'newspack-listings' ),
					'not_found'          => __( 'No events found.', 'newspack-listings' ),
					'not_found_in_trash' => __( 'No events found in Trash.', 'newspack-listings' ),
				],
				'rewrite'  => [ 'slug' => $prefix . '/' . $settings['newspack_listings_event_slug'] ],
				'template' => [ [ 'newspack-listings/event-dates' ] ],
			],
			'generic'     => [
				'labels'  => [
					'name'               => _x( 'Generic Listings', 'post type general name', 'newspack-listings' ),
					'singular_name'      => _x( 'Generic Listing', 'post type singular name', 'newspack-listings' ),
					'menu_name'          => _x( 'Generic Listings', 'admin menu', 'newspack-listings' ),
					'name_admin_bar'     => _x( 'Generic Listing', 'add new on admin bar', 'newspack-listings' ),
					'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
					'add_new_item'       => __( 'Add New Generic Listing', 'newspack-listings' ),
					'new_item'           => __( 'New Generic Listing', 'newspack-listings' ),
					'edit_item'          => __( 'Edit Generic Listing', 'newspack-listings' ),
					'view_item'          => __( 'View Generic Listing', 'newspack-listings' ),
					'all_items'          => __( 'Generic Listings', 'newspack-listings' ),
					'search_items'       => __( 'Search Generic Listings', 'newspack-listings' ),
					'parent_item_colon'  => __( 'Parent Generic Listing:', 'newspack-listings' ),
					'not_found'          => __( 'No generic listings found.', 'newspack-listings' ),
					'not_found_in_trash' => __( 'No generic listings found in Trash.', 'newspack-listings' ),
				],
				'rewrite' => [ 'slug' => $prefix . '/' . $settings['newspack_listings_generic_slug'] ],
			],
			'marketplace' => [
				'labels'  => [
					'name'               => _x( 'Marketplace', 'post type general name', 'newspack-listings' ),
					'singular_name'      => _x( 'Marketplace Listing', 'post type singular name', 'newspack-listings' ),
					'menu_name'          => _x( 'Marketplace Listings', 'admin menu', 'newspack-listings' ),
					'name_admin_bar'     => _x( 'Marketplace Listing', 'add new on admin bar', 'newspack-listings' ),
					'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
					'add_new_item'       => __( 'Add New Marketplace Listing', 'newspack-listings' ),
					'new_item'           => __( 'New Marketplace Listing', 'newspack-listings' ),
					'edit_item'          => __( 'Edit Marketplace Listing', 'newspack-listings' ),
					'view_item'          => __( 'View Marketplace Listing', 'newspack-listings' ),
					'all_items'          => __( 'Marketplace Listings', 'newspack-listings' ),
					'search_items'       => __( 'Search Marketplace Listings', 'newspack-listings' ),
					'parent_item_colon'  => __( 'Parent Marketplace Listing:', 'newspack-listings' ),
					'not_found'          => __( 'No Marketplace listings found.', 'newspack-listings' ),
					'not_found_in_trash' => __( 'No Marketplace listings found in Trash.', 'newspack-listings' ),
				],
				'rewrite' => [ 'slug' => $prefix . '/' . $settings['newspack_listings_marketplace_slug'] ],
			],
			'place'       => [
				'labels'  => [
					'name'               => _x( 'Places', 'post type general name', 'newspack-listings' ),
					'singular_name'      => _x( 'Place', 'post type singular name', 'newspack-listings' ),
					'menu_name'          => _x( 'Places', 'admin menu', 'newspack-listings' ),
					'name_admin_bar'     => _x( 'Place', 'add new on admin bar', 'newspack-listings' ),
					'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
					'add_new_item'       => __( 'Add New Place', 'newspack-listings' ),
					'new_item'           => __( 'New Place', 'newspack-listings' ),
					'edit_item'          => __( 'Edit Place', 'newspack-listings' ),
					'view_item'          => __( 'View Place', 'newspack-listings' ),
					'all_items'          => __( 'Places', 'newspack-listings' ),
					'search_items'       => __( 'Search Places', 'newspack-listings' ),
					'parent_item_colon'  => __( 'Parent Place:', 'newspack-listings' ),
					'not_found'          => __( 'No places found.', 'newspack-listings' ),
					'not_found_in_trash' => __( 'No places found in Trash.', 'newspack-listings' ),
				],
				'rewrite' => [ 'slug' => $prefix . '/' . $settings['newspack_listings_place_slug'] ],
			],
		];

		foreach ( $post_types_config as $post_type_slug => $post_type_config ) {
			$post_type = self::NEWSPACK_LISTINGS_POST_TYPES[ $post_type_slug ];
			$permalink = reset( $post_type_config['rewrite'] );

			// Register the post type.
			register_post_type( $post_type, wp_parse_args( $post_type_config, $default_config ) );

			// Register meta fields for this post type.
			$meta_fields = self::get_meta_fields( $post_type );
			foreach ( $meta_fields as $field_name => $meta_field ) {
				register_meta(
					'post',
					$field_name,
					$meta_field['settings']
				);
			}

			// Create a rewrite rule to handle the prefixed permalink.
			add_rewrite_rule( '^' . $permalink . '/([^/]+)/?$', 'index.php?name=$matches[1]&post_type=' . $post_type, 'top' );
		}
	}

	/**
	 * Define and return meta fields for any Newspack Listings CPTs.
	 *
	 * @param string  $post_type Post type to get corresponding meta fields.
	 * @param boolean $field_names_only (Optional) If true, return an array of just the field names without config.
	 * @return array Array of meta fields for the given $post_type.
	 */
	public static function get_meta_fields( $post_type = null, $field_names_only = false ) {
		if ( empty( $post_type ) ) {
			return [];
		}

		$all_meta_fields = [
			'_wp_page_template'                   => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Template', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => get_theme_mod( 'newspack_listing_default_template', 'single-wide.php' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_contact_email'     => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Contact email address', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'jetpack/email',
					'attr'      => 'email',
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Email address to contact for this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type' => 'string',
							],
						],
					],
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_contact_phone'     => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Contact phone number', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'jetpack/phone',
					'attr'      => 'phone',
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Phone number to contact for this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type' => 'string',
							],
						],
					],
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_contact_address'   => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Contact Address', 'newspack-listings' ),
				'source'     => [ 'blockName' => 'jetpack/address' ],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Contact address for this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'address'      => [
										'type' => 'string',
									],
									'addressLine2' => [
										'type' => 'string',
									],
									'addressLine3' => [
										'type' => 'string',
									],
									'city'         => [
										'type' => 'string',
									],
									'region'       => [
										'type' => 'string',
									],
									'postal'       => [
										'type' => 'string',
									],
									'country'      => [
										'type' => 'string',
									],
								],
							],
						],
					],
				],
			],
			'newspack_listings_business_hours'    => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Hours of Operation', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'jetpack/business-hours',
					'attr'      => 'days',
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Hours of operation for this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'name'  => [
										'type' => 'string',
									],
									'hours' => [
										'type'  => 'array',
										'items' => [
											'type'       => 'object',
											'properties' => [
												'opening' => [
													'type' => 'string',
												],
												'closing' => [
													'type' => 'string',
												],
											],
										],
									],
								],
							],
						],
					],
				],
			],
			'newspack_listings_locations'         => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Locations', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'jetpack/map',
					'attr'      => 'points',
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Geolocation data for this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'placeTitle'  => [
										'type' => 'string',
									],
									'title'       => [
										'type' => 'string',
									],
									'caption'     => [
										'type' => 'string',
									],
									'id'          => [
										'type' => 'string',
									],
									'coordinates' => [
										'type'       => 'object',
										'properties' => [
											'latitude'  => [
												'type' => 'number',
											],
											'longitude' => [
												'type' => 'number',
											],
										],
									],
								],
							],
						],
					],
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_event_start_date'  => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
				],
				'label'      => __( 'Event dates', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'newspack-listings/event-dates',
					'attr'      => 'startDate',
					'single'    => true,
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => '',
					'description'       => __( 'Start date for this event.', 'newspack-listings' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_price'             => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
				],
				'label'      => __( 'Price', 'newspack-listings' ),
				'source'     => [
					'blockName' => 'newspack-listings/price',
					'attr'      => 'price',
					'single'    => true,
				],
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => '',
					'description'       => __( 'Price for this listing.', 'newspack-listings' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_hide_author'       => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Hide listing author', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => boolval( Settings::get_settings( 'newspack_listings_hide_author' ) ), // Configurable in plugin-wide settings.
					'description'       => __( 'Hide author byline and bio for this listing', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_image_ids'         => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Images associated with this listing.', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => [],
					'description'       => __( 'Images associated with this listing.', 'newspack-listings' ),
					'type'              => 'array',
					'sanitize_callback' => 'Utils\sanitize_array',
					'single'            => false,
					'show_in_rest'      => [
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type' => 'integer',
							],
						],
					],
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_hide_publish_date' => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Hide publish date', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => boolval( Settings::get_settings( 'newspack_listings_hide_publish_date' ) ), // Configurable in plugin-wide settings.
					'description'       => __( 'Hide publish and updated dates for this listing', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_hide_parents'      => [
				'post_types' => [
					'page',
					'post',
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Hide parent listings', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => boolval( Settings::get_settings( 'newspack_listings_hide_parents' ) ), // Configurable in plugin-wide settings.
					'description'       => __( 'Hide parent listings assigned to this post', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_hide_children'     => [
				'post_types' => [
					'page',
					'post',
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Hide child listings', 'newspack-listings' ),
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => boolval( Settings::get_settings( 'newspack_listings_hide_children' ) ), // Configurable in plugin-wide settings.
					'description'       => __( 'Hide child listings assigned to this post', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
		];

		// Return only the fields that are associated with the given $post_type.
		$matching_fields = array_filter(
			$all_meta_fields,
			function( $meta_field ) use ( $post_type ) {
				return in_array( $post_type, $meta_field['post_types'] );
			}
		);

		if ( false === $field_names_only ) {
			return $matching_fields;
		} else {
			return array_keys( $matching_fields );
		}
	}

	/**
	 * Given a post ID and post type, get values for all corresponding Listings meta fields.
	 *
	 * @param int|null    $post_id (Optional) ID for the listing post.
	 * @param string|null $post_type (Optional) Post type.
	 *
	 * @return array|boolean Post meta data, or false if post given is not a listing.
	 */
	public static function get_meta_values( $post_id = null, $post_type = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		if ( null === $post_type ) {
			$post_type = get_post_type( $post_id );
		}

		if ( ! self::is_listing( $post_type ) ) {
			return false;
		}

		$meta_fields = self::get_meta_fields( $post_type, true );
		$meta_values = [];

		foreach ( $meta_fields as $meta_field ) {
			$data = get_post_meta( $post_id, $meta_field, true );

			if ( ! empty( $data ) ) {
				$meta_values[ $meta_field ] = $data;
			}
		}

		return $meta_values;
	}

	/**
	 * Sync data from specific content blocks to post meta.
	 * Source blocks for each meta field are set in the meta config above.
	 *
	 * @param int    $post_id ID of the post being created or updated.
	 * @param object $post Post object of the post being created or updated.
	 */
	public static function sync_post_meta( $post_id, $post ) {
		if ( ! self::is_listing( $post->post_type ) ) {
			return;
		}

		$blocks      = parse_blocks( $post->post_content );
		$meta_fields = self::get_meta_fields( $post->post_type );

		foreach ( $meta_fields as $field_name => $meta_field ) {
			$source = isset( $meta_field['source'] ) ? $meta_field['source'] : false;

			if ( $source ) {
				$data_to_sync = Utils\get_data_from_blocks( $blocks, $source );

				/*
				* If there are no blocks matching the source, or only empty, clear all data.
				* This prevents garbage data from persisting if a block is removed
				* after its data has already been saved as post meta.
				*/
				if ( empty( $data_to_sync ) ) {
					delete_post_meta( $post_id, $field_name );
				} else {
					update_post_meta( $post_id, $field_name, $data_to_sync );
				}
			}
		}
	}

	/**
	 * Filter callback to decide whether to show the author for the current singular post.
	 * Can be used from other plugins and/or themes to modify default template behavior.
	 *
	 * @param boolean $hide_author Whether or not to hide the author.
	 * @return boolean If the current post a.) is a listing and b.) has enabled the option to hide author.
	 */
	public static function hide_author( $hide_author = false ) {
		$post_id = get_the_ID();

		if ( self::is_listing() && ! empty( get_post_meta( $post_id, 'newspack_listings_hide_author', true ) ) ) {
			return true;
		}

		return $hide_author;
	}

	/**
	 * Filter callback to decide whether to show the publish and updated dates for the current singular post.
	 * Can be used from other plugins and/or themes to modify default template behavior.
	 *
	 * @param boolean $hide_publish_dates Whether or not to hide the publish and updated dates.
	 * @return boolean If the current post a.) is a listing and b.) has enabled the option to hide publish and updated dates.
	 */
	public static function hide_publish_date( $hide_publish_dates = false ) {
		$post_id = get_the_ID();

		if ( self::is_listing() && ! empty( get_post_meta( $post_id, 'newspack_listings_hide_publish_date', true ) ) ) {
			return true;
		}

		return $hide_publish_dates;
	}

	/**
	 * If using the single-featured or wide templates, apply a body class to listing posts
	 * so that they inherit theme styles for that template.
	 *
	 * @param array $classes Array of body class names.
	 * @return array Filtered array of body classes.
	 */
	public static function set_template_class( $classes ) {
		if ( self::is_listing() ) {
			$template = get_page_template_slug();
			if ( 'single-feature.php' === $template ) {
				$classes[] = 'post-template-single-feature';
			} elseif ( 'single-wide.php' === $template ) {
				$classes[] = 'post-template-single-wide';
			}
		}

		return $classes;
	}

	/**
	 * If using a Newspack theme, add support for featured image options to all listings.
	 *
	 * @param array $post_types Array of supported post types.
	 * @return array Filtered array of supported post types.
	 */
	public static function support_featured_image_options( $post_types ) {
		return array_merge(
			$post_types,
			array_values( self::NEWSPACK_LISTINGS_POST_TYPES )
		);
	}

	/**
	 * Flush permalinks on plugin activation, ensuring that post types are registered first.
	 */
	public static function activation_hook() {
		self::register_post_types();
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
	}

	/**
	 * If using the Newspack Sponsors plugin, add support for sponsors to all listings.
	 *
	 * @param array $post_types Array of supported post types.
	 * @return array Filtered array of supported post types.
	 */
	public static function support_newspack_sponsors( $post_types ) {
		return array_merge(
			$post_types,
			array_values( self::NEWSPACK_LISTINGS_POST_TYPES )
		);
	}

	/**
	 * Convert legacy custom taxonomies to regular post categories and tags.
	 * Helpful for sites that have been using v1 of the Listings plugin.
	 */
	public static function convert_legacy_taxonomies() {
		$custom_category_slug = 'newspack_lst_cat';
		$custom_tag_slug      = 'newspack_lst_tag';

		$category_args = [
			'hierarchical'  => true,
			'public'        => false,
			'rewrite'       => false,
			'show_in_menu'  => false,
			'show_in_rest'  => false,
			'show_tagcloud' => false,
			'show_ui'       => false,
		];
		$tag_args      = [
			'hierarchical'  => false,
			'public'        => false,
			'rewrite'       => false,
			'show_in_menu'  => false,
			'show_in_rest'  => false,
			'show_tagcloud' => false,
			'show_ui'       => false,
		];

		// Temporarily register the taxonomies for all Listing CPTs.
		$post_types = array_values( self::NEWSPACK_LISTINGS_POST_TYPES );
		register_taxonomy( $custom_category_slug, $post_types, $category_args );
		register_taxonomy( $custom_tag_slug, $post_types, $tag_args );

		// Get a list of the custom terms.
		$custom_terms = get_terms(
			[
				'taxonomy'   => [ $custom_category_slug, $custom_tag_slug ],
				'hide_empty' => false,
			]
		);

		// If we don't have any terms from the legacy taxonomies, no need to proceed.
		if ( is_wp_error( $custom_terms ) || 0 === count( $custom_terms ) ) {
			unregister_taxonomy( $custom_category_slug );
			unregister_taxonomy( $custom_tag_slug );
			return;
		}

		foreach ( $custom_terms as $term ) {
			// See if we have any corresponding terms already.
			$corresponding_taxonomy = $custom_category_slug === $term->taxonomy ? 'category' : 'post_tag';
			$corresponding_term     = get_term_by( 'name', $term->name, $corresponding_taxonomy, ARRAY_A );

			// If not, create the term.
			if ( ! $corresponding_term ) {
				$corresponding_term = wp_insert_term(
					$term->name,
					$corresponding_taxonomy,
					[
						'description' => $term->description,
						'slug'        => $term->slug,
					]
				);
			}

			// Get any posts with the legacy term.
			$posts_with_custom_term = new \WP_Query(
				[
					'post_type' => $post_types,
					'per_page'  => 1000,
					'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => $term->taxonomy,
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						],
					],
				]
			);

			// Apply the new term to the post.
			if ( $posts_with_custom_term->have_posts() ) {
				while ( $posts_with_custom_term->have_posts() ) {
					$posts_with_custom_term->the_post();
					wp_set_post_terms(
						get_the_ID(), // Post ID to apply the new term.
						[ $corresponding_term['term_id'] ], // Term ID of the new term.
						$corresponding_taxonomy, // Category or tag.
						true // Append the term without deleting existing terms.
					);
				}
			}

			// Finally, delete the legacy term.
			wp_delete_term( $term->term_id, $term->taxonomy );
		}

		// Unregister the legacy taxonomies.
		unregister_taxonomy( $custom_category_slug );
		unregister_taxonomy( $custom_tag_slug );
	}
}

Newspack_Listings_Core::instance();
