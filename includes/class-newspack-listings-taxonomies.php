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
 * Taxonomies class.
 * Sets up shadow taxonomies for listings.
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
		add_action( 'admin_init', [ __CLASS__, 'handle_orphaned_terms' ] );
		add_filter( 'rest_prepare_taxonomy', [ __CLASS__, 'hide_taxonomy_sidebar' ], 10, 2 );
		add_filter( 'the_content', [ __CLASS__, 'maybe_append_parent_listings' ] );
	}

	/**
	 * After WP init.
	 */
	public static function init() {
		self::register_tax();
		self::create_shadow_relationship();
	}

	/**
	 * Get shadow taxonomy config object.
	 *
	 * @return object Config options for all shadow taxonomies.
	 */
	public static function get_shadow_taxonomy_config() {
		return [
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
					'show_in_menu'  => true, // Set to 'true' to show in WP admin for debugging purposes.
					'show_in_rest'  => true,
					'show_tagcloud' => false,
					'show_ui'       => true,
					'labels'        => get_post_type_object( Core::NEWSPACK_LISTINGS_POST_TYPES['place'] )->labels,
				],
			],
		];
	}

	/**
	 * Registers shadow taxonomies which can be applied to other listing CPTs, pages, or posts.
	 * Terms in these taxonomies are not created or edited directly, but are linked to Listing CPT posts.
	 */
	public static function register_tax() {
		$shadow_taxonomies = self::get_shadow_taxonomy_config();

		// Register shadow taxonomies for each source post type.
		foreach ( $shadow_taxonomies as $post_type_to_shadow => $shadow_taxonomy ) {
			$taxonomy_slug = self::NEWSPACK_LISTINGS_TAXONOMIES[ $post_type_to_shadow ];
			register_taxonomy(
				$taxonomy_slug,
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
	 * Given a post type, get the name of the shadow taxonomy for that post type.
	 *
	 * @param string $post_type Post type.
	 * @return string}boolean The shadow taxonomy for the given post type, or false if none.
	 */
	public static function get_taxonomy_by_post_type( $post_type ) {
		$shadow_taxonomy = false;
		$post_type_slugs = array_keys( Core::NEWSPACK_LISTINGS_POST_TYPES, $post_type );
		$post_type_slug  = reset( $post_type_slugs );

		if ( ! empty( $post_type_slug ) && ! empty( self::NEWSPACK_LISTINGS_TAXONOMIES[ $post_type_slug ] ) ) {
			$shadow_taxonomy = self::NEWSPACK_LISTINGS_TAXONOMIES[ $post_type_slug ];
		}

		return $shadow_taxonomy;
	}

	/**
	 * Create shadow relationships between taxonomies and their posts.
	 */
	public static function create_shadow_relationship() {
		add_action( 'wp_insert_post', [ __CLASS__, 'update_or_delete_shadow_term' ], 10, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'delete_shadow_term' ] );
	}

	/**
	 * When a listing post type changes status, add/update its shadow term if the status is `publish`, otherwise delete it.
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

		// Get the taxonomy to update or delete.
		$shadow_taxonomy = self::get_taxonomy_by_post_type( $post->post_type );

		// If the post is published, create or update the shadow term. Otherwise, delete it.
		if ( 'publish' === $post->post_status && ! empty( $shadow_taxonomy ) ) {
			self::update_shadow_term( $post, $shadow_taxonomy );
		} else {
			self::delete_shadow_term( $post, $shadow_taxonomy );
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

		// Try finding the shadow term by slug first.
		$shadow_term = get_term_by( 'slug', $post->post_name, $taxonomy );

		// If we can't find a term by slug, the post slug may have been updated. Try finding by title instead.
		if ( empty( $shadow_term ) ) {
			$shadow_term = get_term_by( 'name', $post->post_title, $taxonomy );
		}

		// If we can't find a term by either slug or title, it probably doesn't exist.
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
		$new_term_id = $new_term['term_id'];

		// Apply the term to the parent post. This lets us check for missing terms.
		wp_set_post_terms( $post_id, $new_term_id, $taxonomy, append );

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

		$query = new \WP_Query(
			[
				'post_type'      => self::get_post_types_to_shadow(),
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			]
		);

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
					wp_set_post_terms( $post_id, $shadow_term->term_id, self::NEWSPACK_LISTINGS_TAXONOMIES[ $term_slug ], true );
				}
			}
		}
	}

	/**
	 * Delete any shadow terms that no longer have a post to shadow.
	 */
	public static function handle_orphaned_terms() {
		$all_terms  = get_terms(
			[
				'taxonomy'   => array_values( self::NEWSPACK_LISTINGS_TAXONOMIES ),
				'hide_empty' => false,
			]
		);
		$term_slugs = array_column( $all_terms, 'slug' );
		$query      = new \WP_Query(
			[
				'post_type'      => self::get_post_types_to_shadow(),
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'post_name__in'  => $term_slugs,
			]
		);

		if ( $query->have_posts() ) {
			$post_slugs     = array_column( $query->posts, 'post_name' );
			$orphaned_slugs = array_diff( $term_slugs, $post_slugs );
			$orphaned_terms = array_filter(
				$all_terms,
				function( $term ) use ( $orphaned_slugs ) {
					return in_array( $term->slug, $orphaned_slugs );
				}
			);

			foreach ( $orphaned_terms as $orphaned_term ) {
				wp_delete_term( $orphaned_term->term_id, $orphaned_term->taxonomy );
			}
		}
	}

	/**
	 * Workaround to hide the default taxonomy sidebars, since we're registering our own UI for managing shadow terms.
	 * The taxonomies must be available via REST from our custom UI, but hidden to Gutenberg's default taxonomy UI.
	 * This workaround is necessary until the parent issue is resolved and merged to WP Core:
	 * https://github.com/WordPress/gutenberg/issues/6912#issuecomment-428403380
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Taxonomy      $taxonomy The taxonomy object.
	 *
	 * @return WP_REST_Response The filtered response object.
	 */
	public static function hide_taxonomy_sidebar( $response, $taxonomy ) {
		if ( in_array( $taxonomy->name, array_values( self::NEWSPACK_LISTINGS_TAXONOMIES ) ) ) {
			$response->data['visibility']['show_ui'] = false;
		}
		return $response;
	}

	/**
	 * Get parent listing posts for the given shadow taxonomy terms by term slug.
	 * Parent listings are listings that are assigned to a post, page, or other listing
	 * via their corresponding shadow term.
	 *
	 * @param array $slugs Array of term slug to use for looking up post.
	 * @return array Array of parent listing posts.
	 */
	public static function get_parent_listings( $slugs ) {
		$parent_listings = new \WP_Query(
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

		if ( empty( $parent_listings->posts ) || is_wp_error( $parent_listings ) ) {
			return [];
		}

		return $parent_listings->posts;
	}

	/**
	 * Get child listings, posts, and pages for the given post ID.
	 * Children are the posts, pages, and other listings that have been assigned a listing shadow term.
	 *
	 * @param object $params Array of WP_Query args, including at least the post ID of the parent post.
	 * @return array Array of child posts that have the parent's shadow term assigned.
	 */
	public static function get_child_posts( $params ) {
		$post_id         = $params['post_id'];
		$post_type       = ! empty( $params['post_type'] ) ? $params['post_type'] : 'post';
		$per_page        = ! empty( $params['per_page'] ) ? $params['per_page'] : 10;
		$post            = get_post( $post_id );
		$shadow_taxonomy = self::get_taxonomy_by_post_type( $post->post_type );
		$shadow_term     = self::get_shadow_term( $post, $shadow_taxonomy );
		$child_posts     = new \WP_Query(
			[
				'post_type'      => $post_type,
				'posts_per_page' => $per_page,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => $shadow_taxonomy,
						'terms'    => $shadow_term->slug,
						'field'    => 'slug',
					],
				],
			]
		);

		if ( empty( $child_posts->posts ) || is_wp_error( $child_posts ) ) {
			return [];
		}

		return $child_posts->posts;
	}

	/**
	 * Apply or remove the given parent post's shadow term to or from the given child posts.
	 *
	 * @param object $params Params passed from the REST API request.
	 * @return bool|WP_Error True if children were updated successfully, or WP_Error.
	 */
	public static function set_child_posts( $params ) {
		$parent   = ! empty( $params['parent'] ) ? $params['parent'] : null;
		$children = ! empty( $params['children'] ) ? $params['children'] : [];
		$removed  = ! empty( $params['removed'] ) ? $params['removed'] : [];

		if ( empty( $parent ) ) {
			return false;
		}

		$parent_post = get_post( $parent );
		$taxonomy    = self::get_taxonomy_by_post_type( $parent_post->post_type );
		$shadow_term = self::get_shadow_term( $parent_post, $taxonomy );

		// Apply the parent's shadow term to the `children` post IDs.
		foreach ( $children as $child ) {
			$added_child = wp_set_post_terms( $child, $shadow_term->term_id, $taxonomy, true );

			// Bail if error.
			if ( is_wp_error( $added_child ) ) {
				return $added_child;
			}
		}

		// Remove the parent's shadow term from the `removed` post IDs.
		foreach ( $removed as $child_to_remove ) {
			$removed_child = wp_remove_object_terms( $child_to_remove, $shadow_term->term_id, $taxonomy );

			// Bail if error.
			if ( is_wp_error( $removed_child ) ) {
				return $removed_child;
			}
		}

		return true;
	}

	/**
	 * If the post has been assigned any parent listings, append a link to the listing at the end of the content.
	 *
	 * @param string $content Post content.
	 * @return string The filtered post content.
	 */
	public static function maybe_append_parent_listings( $content ) {
		$post_id      = get_the_ID();
		$hide_parents = get_post_meta( $post_id, 'newspack_listings_hide_parents', true );

		// Bail early if the post is set to not show parent listings.
		if ( $hide_parents ) {
			return $content;
		}

		$listing_terms = wp_get_post_terms( $post_id, array_values( self::NEWSPACK_LISTINGS_TAXONOMIES ) );
		$listing_terms = array_filter(
			$listing_terms,
			function( $listing_term ) use ( $post_id ) {
				// Don't show the post on itself.
				return get_post_field( 'post_name', get_post( $post_id ) ) !== $listing_term->slug;
			}
		);

		if ( 0 < count( $listing_terms ) ) {
			$parent_listing_ids = self::get_parent_listings( array_column( $listing_terms, 'slug' ) );

			if ( 0 < count( $parent_listing_ids ) ) {
				$parent_section_title = Core::is_listing( get_post_type() ) ? __( 'Listed by', 'newspack-listings' ) : __( 'Related listings', 'newspack-listings' );
				$content             .= '<hr class="wp-block-separator is-style-wide newspack-listings__separator" />';
				$content             .= '<h3 class="accent-header newspack-listings__related-section-title">' . $parent_section_title . '</h3>';
			}

			foreach ( $parent_listing_ids as $parent_listing_id ) {
				$listing_title   = get_the_title( $parent_listing_id );
				$listing_excerpt = Utils\get_listing_excerpt( get_post( $parent_listing_id ) );
				$listing_url     = get_permalink( $parent_listing_id );
				$featured_image  = get_the_post_thumbnail( $parent_listing_id, 'thumbnail', [ 'class' => 'avatar' ] );

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
}

Newspack_Listings_Taxonomies::instance();
