<?php
/**
 * Newspack Listings Core.
 *
 * Registers custom post types and taxonomies.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Settings as Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main Core class.
 * Sets up CPTs and taxonomies for listings.
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
		'marketplace' => 'markeptlace',
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
	}

	/**
	 * Add options page.
	 */
	public static function add_plugin_page() {
		add_menu_page(
			'Newspack Listings',
			'Listings',
			'edit_posts',
			'newspack-listings',
			'',
			'dashicons-list-view',
			35
		);
		add_submenu_page(
			'newspack-listings',
			__( 'Newspack Listings: Site-Wide Settings', 'newspack-listings' ),
			__( 'Settings', 'newspack-listings' ),
			'manage_options',
			'newspack-listings-settings-admin',
			[ __CLASS__, 'create_admin_page' ]
		);
	}

	/**
	 * Is the current post a listings post type?
	 *
	 * @returns Boolean Whether or not the current post type matches one of the listings CPTs.
	 */
	public static function is_listing() {
		if ( in_array( get_post_type(), self::NEWSPACK_LISTINGS_POST_TYPES ) ) {
			return true;
		}

		return false;
	}

	/**
	 * After WP init, register all the necessary post types and blocks.
	 */
	public static function register_post_types() {
		$prefix            = Settings::get_settings( 'permalink_prefix' );
		$default_config    = [
			'public'       => true,
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'editor', 'excerpt', 'title', 'custom-fields', 'thumbnail' ],
		];
		$post_types_config = [
			'event'       => [
				'labels'  => [
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
				'rewrite' => [ 'slug' => $prefix . '/' . self::NEWSPACK_LISTINGS_PERMALINK_SLUGS['event'] ],
			],
			'generic'     => [
				'labels'  => [
					'name'               => _x( 'Generic Listings', 'post type general name', 'newspack-listings' ),
					'singular_name'      => _x( 'Listing', 'post type singular name', 'newspack-listings' ),
					'menu_name'          => _x( 'Generic Listings', 'admin menu', 'newspack-listings' ),
					'name_admin_bar'     => _x( 'Listing', 'add new on admin bar', 'newspack-listings' ),
					'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
					'add_new_item'       => __( 'Add New Listing', 'newspack-listings' ),
					'new_item'           => __( 'New Listing', 'newspack-listings' ),
					'edit_item'          => __( 'Edit Listing', 'newspack-listings' ),
					'view_item'          => __( 'View Listing', 'newspack-listings' ),
					'all_items'          => __( 'Generic Listings', 'newspack-listings' ),
					'search_items'       => __( 'Search Listings', 'newspack-listings' ),
					'parent_item_colon'  => __( 'Parent Listing:', 'newspack-listings' ),
					'not_found'          => __( 'No listings found.', 'newspack-listings' ),
					'not_found_in_trash' => __( 'No listings found in Trash.', 'newspack-listings' ),
				],
				'rewrite' => [ 'slug' => $prefix . '/' . self::NEWSPACK_LISTINGS_PERMALINK_SLUGS['generic'] ],
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
				'rewrite' => [ 'slug' => $prefix . '/' . self::NEWSPACK_LISTINGS_PERMALINK_SLUGS['marketplace'] ],
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
				'rewrite' => [ 'slug' => $prefix . '/' . self::NEWSPACK_LISTINGS_PERMALINK_SLUGS['place'] ],
			],
		];

		foreach ( $post_types_config as $post_type_slug => $post_type_config ) {
			$post_type = self::NEWSPACK_LISTINGS_POST_TYPES[ $post_type_slug ];
			$permalink = self::NEWSPACK_LISTINGS_PERMALINK_SLUGS[ $post_type_slug ];

			// Register the post type.
			register_post_type( $post_type, wp_parse_args( $post_type_config, $default_config ) );

			// Register meta fields for this post type.
			$meta_fields = self::get_meta_fields( $post_type );
			foreach ( $meta_fields as $name => $meta_field ) {
				register_meta(
					'post',
					$name,
					$meta_field['settings']
				);
			}

			// Create a rewrite rule to handle the prefixed permalink.
			add_rewrite_rule( '^' . $prefix . '/' . $permalink . '/([^/]+)/?$', 'index.php?name=$matches[1]&post_type=' . $post_type, 'top' );
		}
	}

	/**
	 * Define and return meta fields for any Newspack Listings CPTs.
	 *
	 * @param String $post_type Post type to get corresponding meta fields.
	 * @return Array Array of meta fields for the given $post_type.
	 */
	public static function get_meta_fields( $post_type = null ) {
		if ( empty( $post_type ) ) {
			return [];
		}

		$all_meta_fields = [
			/**
			 * Metadata for various listing types.
			 */
			'newspack_listings_contact_email' => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Email address', 'newspack-listings' ),
				'type'       => 'input',
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => '',
					'description'       => __( 'Email address to contact for this listing.', 'newspack-listings' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_contact_phone' => [
				'post_types' => [
					self::NEWSPACK_LISTINGS_POST_TYPES['event'],
					self::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					self::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					self::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'label'      => __( 'Phone number', 'newspack-listings' ),
				'type'       => 'input',
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => '',
					'description'       => __( 'Phone number to contact for this listing.', 'newspack-listings' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
		];

		// Return only the fields that are associated with the given $post_type.
		return array_filter(
			$all_meta_fields,
			function( $meta_field ) use ( $post_type ) {
				return in_array( $post_type, $meta_field['post_types'] );
			}
		);
	}
}

Newspack_Listings_Core::instance();
