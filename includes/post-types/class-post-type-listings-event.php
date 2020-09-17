<?php
/**
 * Newspack Listings Event post type.
 *
 * Registers custom post type, taxonomies, and meta for Events.
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
final class Post_Type_Listings_Event {

	/**
	 * The single instance of the class.
	 *
	 * @var Post_Type_Listings_Event
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
	 *
	 * @return Post_Type_Listings_Event - Main instance.
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
	 * Registers custom metadata fields for Events.
	 */
	public static function register_meta() {
		$post_type   = Core::NEWSPACK_LISTINGS_POST_TYPES['event'];
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
			'public'       => true,
			'rewrite'      => [ 'slug' => $prefix . '/events' ],
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'editor', 'excerpt', 'title', 'custom-fields', 'thumbnail' ],
		];

		register_post_type( Core::NEWSPACK_LISTINGS_POST_TYPES['event'], $args );
	}

	/**
	 * Create custom rewrite rule to handle namespaced permalinks.
	 */
	public static function create_rewrite() {
		$prefix = Settings::get_settings( 'permalink_prefix' );

		add_rewrite_rule( '^' . $prefix . '/events/([^/]+)/?$', 'index.php?name=$matches[1]&post_type=' . Core::NEWSPACK_LISTINGS_POST_TYPES['event'], 'top' );
	}
}

Post_Type_Listings_Event::instance();
