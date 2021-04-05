<?php
/**
 * Newspack Listings - Sets up shadow taxonomies to associate different post types with each other.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 * Sets up custom blocks for listings.
 */
final class Newspack_Listings_Taxonomies {
	const NEWSPACK_LISTINGS_TAXONOMIES = [
		'event'       => 'newspack_lstngs_evt',
		'generic'     => 'newspack_lstngs_gen',
		'marketplace' => 'newspack_lstngs_mkt',
		'place'       => 'newspack_lstngs_plc',
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
		add_filter( 'the_content', [ __CLASS__, 'maybe_append_related_listings' ] );
		add_filter( 'wpseo_primary_term_taxonomies', [ __CLASS__, 'disable_yoast_primary_category_picker' ], 10, 2 );
	}

	/**
	 * After WP init.
	 */
	public static function init() {
		self::register_tax();
		self::create_shadow_relationship();
	}

	/**
	 * Registers shadow taxonomies which can be applied to other listing CPTs, pages, or posts.
	 * Terms in these taxonomies are not created or edited directly, but are linked to Listing CPT posts.
	 */
	public static function register_tax() {
		$shadow_taxonomies = [
			'event'       => [
				'post_types' => [
					'post',
					'page',
					Core::NEWSPACK_LISTINGS_POST_TYPES['event'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['generic'],
				],
				'config'     => [
					'hierarchical'  => true,
					'public'        => true,
					'rewrite'       => [ 'slug' => self::NEWSPACK_LISTINGS_TAXONOMIES['event'] ],
					'show_in_menu'  => false, // Set to 'true' to show in WP admin for debugging purposes.
					'show_in_rest'  => true,
					'show_tagcloud' => false,
					'show_ui'       => true,
					'labels'        => get_post_type_object( Core::NEWSPACK_LISTINGS_POST_TYPES['event'] )->labels,
				],
			],
			'generic'     => [
				'post_types' => [
					'post',
					'page',
					Core::NEWSPACK_LISTINGS_POST_TYPES['generic'],
				],
				'config'     => [
					'hierarchical'  => true,
					'public'        => true,
					'rewrite'       => [ 'slug' => self::NEWSPACK_LISTINGS_TAXONOMIES['event'] ],
					'show_in_menu'  => false, // Set to 'true' to show in WP admin for debugging purposes.
					'show_in_rest'  => true,
					'show_tagcloud' => false,
					'show_ui'       => true,
					'labels'        => get_post_type_object( Core::NEWSPACK_LISTINGS_POST_TYPES['generic'] )->labels,
				],
			],
			'marketplace' => [
				'post_types' => [
					'post',
					'page',
					Core::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
				],
				'config'     => [
					'hierarchical'  => true,
					'public'        => true,
					'rewrite'       => [ 'slug' => self::NEWSPACK_LISTINGS_TAXONOMIES['marketplace'] ],
					'show_in_menu'  => false, // Set to 'true' to show in WP admin for debugging purposes.
					'show_in_rest'  => true,
					'show_tagcloud' => false,
					'show_ui'       => true,
					'labels'        => get_post_type_object( Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'] )->labels,
				],
			],
			'place'       => [
				'post_types' => [
					'post',
					'page',
					Core::NEWSPACK_LISTINGS_POST_TYPES['event'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['generic'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'config'     => [
					'hierarchical'  => true,
					'public'        => true,
					'rewrite'       => [ 'slug' => self::NEWSPACK_LISTINGS_TAXONOMIES['place'] ],
					'show_in_menu'  => false, // Set to 'true' to show in WP admin for debugging purposes.
					'show_in_rest'  => true,
					'show_tagcloud' => false,
					'show_ui'       => true,
					'labels'        => get_post_type_object( Core::NEWSPACK_LISTINGS_POST_TYPES['place'] )->labels,
				],
			],
		];

		// Register shadow taxonomies for each source post type.
		foreach ( $shadow_taxonomies as $post_type_to_shadow => $shadow_taxonomy ) {
			register_taxonomy(
				self::NEWSPACK_LISTINGS_TAXONOMIES[ $post_type_to_shadow ],
				$shadow_taxonomy['post_types'],
				$shadow_taxonomy['config']
			);
		}
	}

	/**
	 * Using the array of shadow taxonomies, get an array of corresponding post types being shadowed.
	 *
	 * @return array Array of post types being shadowed.
	 */
	public static function get_post_types_to_shadow() {
		return array_values(
			array_intersect_key(
				Core::NEWSPACK_LISTINGS_POST_TYPES,
				self::NEWSPACK_LISTINGS_TAXONOMIES
			)
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
		if ( ! in_array( $post->post_type, self::get_post_types_to_shadow() ) ) {
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

		if ( empty( $shadow_term ) ) {
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

		$post_id     = $post->ID;
		$new_term_id = $new_term->term_id;

		// Apply the term to the parent post.
		wp_set_post_terms( $post_id, $new_term_id, $taxonomy );

		return $new_term;
	}

	/**
	 * Handle any published posts of the relevant types that are missing a corresponding shadow term.
	 */
	public static function handle_missing_terms() {
		$tax_query = [ 'relation' => 'OR' ];

		foreach ( self::NEWSPACK_LISTINGS_TAXONOMIES as $post_type_to_shadow => $shadow_taxonomy ) {
			$tax_query[] = [
				'taxonomy' => $shadow_taxonomy,
				'operator' => 'NOT EXISTS',
			];
		}

		$args = [
			'post_type'      => self::get_post_types_to_shadow(),
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		];

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$post      = get_post( $post_id );
				$post_type = $post->post_type;
				$term_slug = array_keys( Core::NEWSPACK_LISTINGS_POST_TYPES, $post_type );
				$term_slug = reset( $term_slug );

				// Bail if not a post type to be shadowed.
				if ( empty( $term_slug ) || ! in_array( $term_slug, array_keys( self::NEWSPACK_LISTINGS_TAXONOMIES ) ) ) {
					continue;
				}

				// Check for a shadow term associated with this post.
				$shadow_term = self::get_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES[ $term_slug ] );

				// If there isn't already a shadow term, create it. Otherwise, apply the term to the post.
				if ( empty( $shadow_term ) ) {
					self::create_shadow_term( $post, self::NEWSPACK_LISTINGS_TAXONOMIES[ $term_slug ] );
				} else {
					wp_set_post_terms( $post_id, $shadow_term->term_id, self::NEWSPACK_LISTINGS_TAXONOMIES[ $term_slug ] );
				}
			}
		}
	}

	/**
	 * Get related listing posts for the given shadow taxonomy terms by term slug.
	 *
	 * @param array $slugs Array of term slug to use for looking up post.
	 * @return array Array of related listing posts.
	 */
	public static function get_related_listings( $slugs ) {
		$related_listings = new \WP_Query(
			[
				'post_type'      => self::get_post_types_to_shadow(),
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'post_name__in'  => $slugs,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'order'          => 'ASC',
				'orderby'        => 'type title',
			]
		);

		if ( empty( $related_listings->posts ) || is_wp_error( $related_listings ) ) {
			return [];
		}

		return $related_listings->posts;
	}

	/**
	 * If the post has been assigned any listing shadow terms, append a link to the listing at the end of the content.
	 *
	 * @param string $content Post content.
	 * @return string The filtered post content.
	 */
	public static function maybe_append_related_listings( $content ) {
		$post_id       = get_the_ID();
		$listing_terms = wp_get_post_terms( $post_id, array_values( self::NEWSPACK_LISTINGS_TAXONOMIES ) );
		$listing_terms = array_filter(
			$listing_terms,
			function( $listing_term ) use ( $post_id ) {
				// Don't show the post on itself.
				return get_post_field( 'post_name', get_post( $post_id ) ) !== $listing_term->slug;
			}
		);

		if ( 0 < count( $listing_terms ) ) {
			$related_listing_ids = self::get_related_listings( array_column( $listing_terms, 'slug' ) );

			if ( 0 < count( $related_listing_ids ) ) {
				$related_section_title = Core::is_listing( get_post_type() ) ? __( 'Listed by', 'newspack-listings' ) : __( 'Related listings', 'newspack-listings' );
				$content              .= '<hr class="wp-block-separator is-style-wide newspack-listings__separator" />';
				$content              .= '<h3 class="accent-header newspack-listings__related-section-title">' . $related_section_title . '</h3>';
			}

			foreach ( $related_listing_ids as $related_listing_id ) {
				$listing_title   = get_the_title( $related_listing_id );
				$listing_excerpt = Utils\get_listing_excerpt( get_post( $related_listing_id ) );
				$listing_url     = get_permalink( $related_listing_id );
				$featured_image  = get_the_post_thumbnail( $related_listing_id, 'thumbnail', [ 'class' => 'avatar' ] );

				$content .= '<div class="author-bio sponsor-bio newspack-listings__related-listing">'; // <a href="'. $listing_url . '">';

				if ( $featured_image ) {
					$content .= '<a href="' . $listing_url . '">';
					$content .= $featured_image;
					$content .= '</a>';

				}

				$content .= '<div class="author-bio-text">';
				$content .= '<div class="author-bio-header">';
				$content .= '<h2 class="accent-header">' . $listing_title . '</h2>';
				$content .= '</div>'; // author-bio-header.
				$content .= $listing_excerpt;
				$content .= '<p><a class="author-link" href="' . $listing_url . '">' . __( 'More info about ', 'newspack-listings' ) . $listing_title . '</a></p>';
				$content .= '</div>'; // author-bio-text.
				$content .= '</div>'; // author-bio.
			}
		}

		return $content;
	}

	/**
	 * Disable the Yoast primary category picker for listing posts and terms.
	 *
	 * @param array  $taxonomies Array of taxonomies.
	 * @param string $post_type Post type of the current post.
	 */
	public static function disable_yoast_primary_category_picker( $taxonomies, $post_type ) {
		foreach ( array_values( self::NEWSPACK_LISTINGS_TAXONOMIES ) as $shadow_taxonomy ) {
			if ( isset( $taxonomies[ $shadow_taxonomy ] ) ) {
				unset( $taxonomies[ $shadow_taxonomy ] );
			}
		}

		return $taxonomies;
	}

}

Newspack_Listings_Taxonomies::instance();
