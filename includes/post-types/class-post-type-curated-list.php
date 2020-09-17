<?php
/**
 * Newspack Listings Curated List post type.
 *
 * Registers custom post type, taxonomies, and meta for Curated Lists.
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
final class Post_Type_Curated_List {

	const NEWSPACK_CURATED_LIST_CPT = 'newspack_lst_curated';

	/**
	 * The single instance of the class.
	 *
	 * @var Post_Type_Curated_List
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
	 *
	 * @return Post_Type_Curated_List - Main instance.
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

		add_filter( 'allowed_block_types', [ __CLASS__, 'restrict_block_types' ] );
		add_filter( 'the_content', [ __CLASS__, 'add_list_wrapper_tags' ], 20 );
	}

	/**
	 * Registers Listings custom post types.
	 */
	public static function register_cpt() {
		$prefix = Settings::get_settings( 'permalink_prefix' );
		$args   = [
			'labels'       => [
				'name'               => _x( 'Curated Lists', 'post type general name', 'newspack-listings' ),
				'singular_name'      => _x( 'Curated List', 'post type singular name', 'newspack-listings' ),
				'menu_name'          => _x( 'Curated Lists', 'admin menu', 'newspack-listings' ),
				'name_admin_bar'     => _x( 'Curated List', 'add new on admin bar', 'newspack-listings' ),
				'add_new'            => _x( 'Add New', 'popup', 'newspack-listings' ),
				'add_new_item'       => __( 'Add New Curated List', 'newspack-listings' ),
				'new_item'           => __( 'New Curated List', 'newspack-listings' ),
				'edit_item'          => __( 'Edit Curated List', 'newspack-listings' ),
				'view_item'          => __( 'View Curated List', 'newspack-listings' ),
				'all_items'          => __( 'Curated Lists', 'newspack-listings' ),
				'search_items'       => __( 'Search Curated Lists', 'newspack-listings' ),
				'parent_item_colon'  => __( 'Parent curated list:', 'newspack-listings' ),
				'not_found'          => __( 'No curated lists found.', 'newspack-listings' ),
				'not_found_in_trash' => __( 'No curated lists found in Trash.', 'newspack-listings' ),
			],
			'public'       => true,
			'rewrite'      => [ 'slug' => $prefix . '/lists' ],
			'show_in_menu' => 'newspack-listings',
			'show_in_rest' => true,
			'show_ui'      => true,
			'supports'     => [ 'author', 'editor', 'title', 'custom-fields', 'thumbnail' ],
		];

		register_post_type( Core::NEWSPACK_LISTINGS_POST_TYPES['curated_list'], $args );
	}

	/**
	 * Registers custom metadata fields for Curated Lists.
	 */
	public static function register_meta() {
		$post_type   = Core::NEWSPACK_LISTINGS_POST_TYPES['curated_list'];
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
	 * Create custom rewrite rule to handle namespaced permalinks.
	 */
	public static function create_rewrite() {
		$prefix = Settings::get_settings( 'permalink_prefix' );

		add_rewrite_rule( '^' . $prefix . '/lists/([^/]+)/?$', 'index.php?name=$matches[1]&post_type=' . Core::NEWSPACK_LISTINGS_POST_TYPES['curated_list'], 'top' );
	}

	/**
	 * Restrict block types allowed for Curated Lists.
	 *
	 * @param Array|Boolean $allowed_blocks Array of allowed block types, or true for all core blocks.
	 * @return Array Array of only the allowed blocks for this post type.
	 */
	public static function restrict_block_types( $allowed_blocks ) {
		if ( get_post_type() === Core::NEWSPACK_LISTINGS_POST_TYPES['curated_list'] ) {
			return [
				'newspack-listings/event',
				'newspack-listings/generic',
				'newspack-listings/marketplace',
				'newspack-listings/place',
			];
		}

		return $allowed_blocks;
	}

	/**
	 * Wrap post content with <ol></ol> tags.
	 *
	 * @param string $content Content to be filtered.
	 * @return string Filtered content.
	 */
	public static function add_list_wrapper_tags( $content ) {
		if ( Core::is_curated_list() && is_singular() && in_the_loop() && is_main_query() ) {
			$post_id      = get_the_ID();
			$show_map     = get_post_meta( $post_id, 'newspack_listings_show_map', true );
			$show_numbers = get_post_meta( $post_id, 'newspack_listings_show_numbers', true );
			$classes      = 'newspack-listings__curated-list';

			if ( ! empty( $show_map ) ) {
				$classes .= ' newspack-listings__show-map';
			}

			if ( ! empty( $show_numbers ) ) {
				$classes .= ' newspack-listings__show-numbers';
			}

			$content = '<ol class="' . $classes . '">' . $content . '</ol>';
		}

		return $content;
	}
}

Post_Type_Curated_List::instance();
