<?php
/**
 * Newspack Listings - Sets up shadow taxonomies to associate different post types with each other.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 * Sets up custom blocks for listings.
 */
final class Newspack_Listings_Taxonomies {
	const NEWSPACK_LISTINGS_TAXONOMIES = [
		'place' => 'newspack_lstngs_plc',
	];

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Taxonomies
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings_Taxonomies instance.
	 * Ensures only one instance of Newspack_Listings_Taxonomies is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Taxonomies - Main instance.
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
		add_action( 'admin_init', [ __CLASS__, 'handle_missing_terms' ] );
	}

	/**
	 * After WP init.
	 */
	public static function init() {
		self::register_tax();
		self::create_shadow_relationship();
	}

	/**
	 * Registers Places taxonomy which can be applied to Marketplace or Event listing CPTs.
	 * Terms in this taxonomy are not created or edited directly, but are linked to Place posts.
	 */
	public static function register_tax() {
		register_taxonomy(
			self::NEWSPACK_LISTINGS_TAXONOMIES['place'],
			[
				'post',
				'page',
				Core::NEWSPACK_LISTINGS_POST_TYPES['event'],
				Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
				Core::NEWSPACK_LISTINGS_POST_TYPES['place'],
			],
			[
				'hierarchical'  => true,
				'public'        => true,
				'rewrite'       => [ 'slug' => self::NEWSPACK_LISTINGS_TAXONOMIES['place'] ],
				'show_in_menu'  => true, // Set to 'true' to show in WP admin for debugging purposes.
				'show_in_rest'  => true,
				'show_tagcloud' => false,
				'show_ui'       => true,
				'labels'        => [
					'name'                  => __( 'Places', 'newspack-listings' ),
					'singular_name'         => __( 'Places', 'newspack-listings' ),
					'search_items'          => __( 'Search Places', 'newspack-listings' ),
					'all_items'             => __( 'Places', 'newspack-listings' ),
					'parent_item'           => __( 'Parent Place', 'newspack-listings' ),
					'parent_item_colon'     => __( 'Parent Place:', 'newspack-listings' ),
					'edit_item'             => __( 'Edit Place', 'newspack-listings' ),
					'view_item'             => __( 'View Place', 'newspack-listings' ),
					'update_item'           => __( 'Update Place', 'newspack-listings' ),
					'add_new_item'          => __( 'Add New Place', 'newspack-listings' ),
					'new_item_name'         => __( 'New Place Name', 'newspack-listings' ),
					'not_found'             => __( 'No places found.', 'newspack-listings' ),
					'no_terms'              => __( 'No places', 'newspack-listings' ),
					'items_list_navigation' => __( 'Places list navigation', 'newspack-listings' ),
					'items_list'            => __( 'Places list', 'newspack-listings' ),
					'back_to_items'         => __( '&larr; Back to Places', 'newspack-listings' ),
					'menu_name'             => __( 'Places', 'newspack-listings' ),
					'name_admin_bar'        => __( 'Places', 'newspack-listings' ),
					'archives'              => __( 'Places', 'newspack-listings' ),
				],
			]
		);
	}

	/**
	 * Create shadow relationships between taxonomies and their posts.
	 */
	public static function create_shadow_relationship() {
		add_action( 'wp_insert_post', [ __CLASS__, 'update_or_delete_shadow_term' ], 10, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'delete_shadow_term' ] );
	}

	/**
	 * When a Place changes status, add/update the shadow term if the status is `publish`, otherwise delete it.
	 *
	 * @param int   $post_id ID for the post being inserted or saved.
	 * @param array $post Post object for the post being inserted or saved.
	 * @return void
	 */
	public static function update_or_delete_shadow_term( $post_id, $post ) {
		// Bail if the current post type isn't one of the ones to shadow.
		if ( Core::NEWSPACK_LISTINGS_POST_TYPES['place'] === $post->post_type ) {
			return;
		}

		// If the post is published, create or update the shadow term. Otherwise, delete it.
		if ( 'publish' === $post->post_status ) {
			self::update_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES['place'] );
		} else {
			self::delete_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES['place'] );
		}
	}

	/**
	 * Creates a new taxonomy term, or updates an existing one.
	 *
	 * @param array       $post Post object for the post being inserted or saved.
	 * @param string|null $taxonomy Name of taxonomy to create or update.
	 * @return bool|void Nothing if successful, or false if not.
	 */
	public static function update_shadow_term( $post, $taxonomy = null ) {
		// Bail if post is an auto draft.
		if ( 'auto-draft' === $post->post_status || 'Auto Draft' === $post->post_title || empty( $taxonomy ) ) {
			return false;
		}

		// Check for a shadow term associated with this post.
		$shadow_term = self::get_shadow_term( $post, $taxonomy );

		// If there isn't already a shadow term, create it.
		if ( empty( $shadow_term ) ) {
			self::create_shadow_term( $post, $taxonomy );
		} else {
			// Otherwise, update the existing term.
			wp_update_term(
				$shadow_term->term_id,
				$taxonomy,
				[
					'name' => $post->post_title,
					'slug' => $post->post_name,
				]
			);
		}
	}

	/**
	 * Deletes an existing shadow taxonomy term when the post is being deleted.
	 *
	 * @param array  $post Post object for the post being deleted.
	 * @param string $taxonomy Name of taxonomy to delete.
	 * @return bool|void Nothing if successful, or false if not.
	 */
	public static function delete_shadow_term( $post, $taxonomy = null ) {
		// Bail if no taxonomy passed.
		if ( empty( $taxonomy ) ) {
			return false;
		}

		// Check for a shadow term associated with this post.
		$shadow_term = self::get_shadow_term( $post, $taxonomy );

		if ( ! empty( $shadow_term ) ) {
			return false;
		}

		wp_delete_term( $shadow_term->term_id, $taxonomy );
	}

	/**
	 * Looks up a shadow taxonomy term linked to a given post.
	 *
	 * @param array  $post Post object to look up.
	 * @param string $taxonomy Name of taxonomy to get.
	 * @return array|bool Term object of the linked term, if any, or false.
	 */
	public static function get_shadow_term( $post, $taxonomy = null ) {
		if ( empty( $post ) || empty( $post->post_title ) || empty( $taxonomy ) ) {
			return false;
		}

		$shadow_term = get_term_by( 'name', $post->post_title, $taxonomy );

		if ( empty( $shadow_term ) ) {
			return false;
		}

		return $shadow_term;
	}

	/**
	 * Creates a shadow taxonomy term linked to the given post.
	 *
	 * @param array  $post Post object for which to create a shadow term.
	 * @param string $taxonomy Name of taxonomy to create.
	 * @return array|bool Term object if successful, false if not.
	 */
	public static function create_shadow_term( $post, $taxonomy = null ) {
		// Bail if no taxonomy passed.
		if ( empty( $taxonomy ) ) {
			return false;
		}

		$new_term = wp_insert_term(
			$post->post_title,
			$taxonomy,
			[
				'slug' => $post->post_name,
			]
		);

		if ( is_wp_error( $new_term ) ) {
			return false;
		}

		// Apply the term to the parent post.
		wp_set_post_terms( $post->ID, $new_term->term_id, $taxonomy );

		return $new_term;
	}

	/**
	 * Handle any published Place posts that are missing a corresponding shadow term.
	 */
	public static function handle_missing_terms() {
		$args = [
			'post_type'      => Core::NEWSPACK_LISTINGS_POST_TYPES['place'],
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'tax_query'      => [  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => self::NEWSPACK_LISTINGS_TAXONOMIES['place'],
					'operator' => 'NOT EXISTS',
				],
			],
		];

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				$post    = get_post( $post_id );

				// Check for a shadow term associated with this post.
				$shadow_term = self::get_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES['place'] );

				// If there isn't already a shadow term, create it. Otherwise, apply the term to the post.
				if ( empty( $shadow_term ) ) {
					self::create_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES['place'] );
				} else {
					wp_set_post_terms( $post_id, $shadow_term->term_id, self::NEWSPACK_LISTINGS_TAXONOMIES['place'] );
				}
			}
		}
	}
}

Newspack_Listings_Taxonomies::instance();
