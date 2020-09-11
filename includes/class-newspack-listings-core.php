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

	const NEWSPACK_LISTINGS_POST_TYPES = [
		'curated_list' => 'newspack_lst_curated',
		'event'        => 'newspack_lst_event',
		'generic'      => 'newspack_lst_generic',
		'marketplace'  => 'newspack_lst_mktplce',
		'place'        => 'newspack_lst_place',

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
		add_action( 'init', [ __CLASS__, 'init' ] );
	}

	/**
	 * After WP init, register all the necessary post types and blocks.
	 */
	public static function init() {
		include_once NEWSPACK_LISTINGS_PLUGIN_FILE . 'includes/post-types/class-post-type-curated-list.php';
		include_once NEWSPACK_LISTINGS_PLUGIN_FILE . 'includes/post-types/class-post-type-listings-event.php';
		include_once NEWSPACK_LISTINGS_PLUGIN_FILE . 'includes/post-types/class-post-type-listings-place.php';
		include_once NEWSPACK_LISTINGS_PLUGIN_FILE . 'includes/post-types/class-post-type-listings-marketplace.php';
		include_once NEWSPACK_LISTINGS_PLUGIN_FILE . 'includes/post-types/class-post-type-listings-generic.php';
	}

	/**
	 * Is the current post a listings post type?
	 *
	 * @returns Boolean Whether or not the current post type matches one of the listings CPTs.
	 */
	public static function is_listing() {
		$current_post_type = get_post_type();

		foreach ( self::NEWSPACK_LISTINGS_POST_TYPES as $label => $post_type ) {
			if ( 'curated_list' !== $label && $post_type === $current_post_type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is the current post a curated list post type?
	 */
	public static function is_curated_list() {
		return get_post_type() === self::NEWSPACK_LISTINGS_POST_TYPES['curated_list'];
	}
}

Newspack_Listings_Core::instance();
