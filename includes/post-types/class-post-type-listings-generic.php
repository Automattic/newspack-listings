<?php
/**
 * Newspack Listings Place post type.
 *
 * Registers custom post type, taxonomies, and meta for Places.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Settings as Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main Core class.
 * Sets up CPTs and taxonomies for listings.
 */
final class Post_Type_Listings_Generic {

	const NEWSPACK_GENERIC_CPT = 'newspack_lst_generic';

	/**
	 * The single instance of the class.
	 *
	 * @var Post_Type_Listings_Generic
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
	 *
	 * @return Post_Type_Listings_Generic - Main instance.
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
		self::register_cpt();
		self::register_meta();
		self::create_rewrite();
	}

	/**
	 * Registers custom metadata fields for Generic listings.
	 */
	public static function register_meta() {
		$post_type   = Core::NEWSPACK_LISTINGS_POST_TYPES['generic'];
		$meta_fields = Core::get_meta_fields( $post_type );

		foreach ( $meta_fields as $name => $meta_field ) {
			register_meta(
				'post',
				$name,
				$meta_field['settings']
			);
		}
	}

	/**
	 * Registers Listings custom post types.
	 */
	public static function register_cpt() {
		$prefix = Settings::get_settings( 'permalink_prefix' );
		$args   = [
			'labels'       => [
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
			'public'       => true,
			'rewrite'      => [ 'slug' => $prefix . '/items' ],
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'editor', 'excerpt', 'title', 'custom-fields', 'thumbnail' ],
		];

		register_post_type( Core::NEWSPACK_LISTINGS_POST_TYPES['generic'], $args );
	}

	/**
	 * Create custom rewrite rule to handle namespaced permalinks.
	 */
	public static function create_rewrite() {
		$prefix = Settings::get_settings( 'permalink_prefix' );

		add_rewrite_rule( '^' . $prefix . '/items/([^/]+)/?$', 'index.php?name=$matches[1]&post_type=' . Core::NEWSPACK_LISTINGS_POST_TYPES['generic'], 'top' );
	}
}

Post_Type_Listings_Generic::instance();
