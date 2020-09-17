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
	 *
	 * @return Boolean Whehter or not the post is a Curated List.
	 */
	public static function is_curated_list() {
		return get_post_type() === self::NEWSPACK_LISTINGS_POST_TYPES['curated_list'];
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
			 * Curated List metadata.
			 */
			'newspack_listings_show_numbers'      => [
				'post_types' => [ self::NEWSPACK_LISTINGS_POST_TYPES['curated_list'] ],
				'label'      => __( 'Show numbers?', 'newspack-listings' ),
				'type'       => 'toggle',
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => true,
					'description'       => __( 'Display numbers for the items in this list.', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],
			'newspack_listings_show_map'          => [
				'post_types' => [ self::NEWSPACK_LISTINGS_POST_TYPES['curated_list'] ],
				'label'      => __( 'Show map?', 'newspack-listings' ),
				'type'       => 'toggle',
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => true,
					'description'       => __( 'Display a map with this list if at least one listing has geolocation data.', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
				'type'       => 'toggle',
			],
			'newspack_listings_show_sort_by_date' => [
				'post_types' => [ self::NEWSPACK_LISTINGS_POST_TYPES['curated_list'] ],
				'label'      => __( 'Show sort-by-date UI?', 'newspack-listings' ),
				'type'       => 'toggle',
				'settings'   => [
					'object_subtype'    => $post_type,
					'default'           => false,
					'description'       => __( 'Display sort-by-date controls (only applicable to lists of events).', 'newspack-listings' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				],
			],

			/**
			 * Metadata for various listing types.
			 */
			'newspack_listings_contact_email'     => [
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
			'newspack_listings_contact_phone'     => [
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
