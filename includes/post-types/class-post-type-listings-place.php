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

defined( 'ABSPATH' ) || exit;

/**
 * Main Core class.
 * Sets up CPTs and taxonomies for listings.
 */
final class Post_Type_Listings_Place {

	const NEWSPACK_PLACE_CPT = 'newspack_lst_place';

	/**
	 * The single instance of the class.
	 *
	 * @var Post_Type_Listings_Place
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
	 *
	 * @return Post_Type_Listings_Place - Main instance.
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
	}

	/**
	 * Registers Listings custom post types.
	 */
	public static function register_cpt() {
		$args = [
			'labels'       => [
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
			'public'       => true,
			'rewrite'      => [ 'slug' => 'places' ],
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'editor', 'title', 'custom-fields', 'thumbnail' ],
		];

		register_post_type( Core::NEWSPACK_LISTINGS_POST_TYPES['place'], $args );
	}
}

Post_Type_Listings_Place::instance();
