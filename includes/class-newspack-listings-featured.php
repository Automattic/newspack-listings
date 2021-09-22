<?php
/**
 * Newspack Listings - Infrastructure for featured listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Settings as Settings;
use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Products class.
 * Sets up WooCommerce products for listings.
 */
final class Newspack_Listings_Featured {
	/**
	 * The meta keys for featured listing meta.
	 */
	const META_KEYS = [
		'featured' => 'newspack_listings_featured',
		'priority' => 'newspack_listings_featured_priority',
		'query'    => 'newspack_listings_featured_query_priority',
		'expires'  => 'newspack_listings_featured_expires',
	];

	/**
	 * Hook name for the cron job used to check featured expiration dates daily.
	 */
	const CRON_HOOK = 'newspack_listings_expiration_checker';

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Featured
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings_Featured instance.
	 * Ensures only one instance of Newspack_Listings_Featured is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Featured - Main instance.
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
		add_action( 'init', [ __CLASS__, 'register_featured_meta' ] );
		add_action( 'init', [ __CLASS__, 'cron_init' ] );
		add_action( self::CRON_HOOK, [ $this, 'check_expired_featured_items' ] );
		add_action( 'save_post', [ __CLASS__, 'set_feature_priority' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'show_featured_listings_first' ], 11 );
		add_filter( 'post_class', [ __CLASS__, 'add_featured_classes' ] );
		add_filter( 'newspack_blocks_term_classes', [ __CLASS__, 'add_featured_classes' ] );
	}

	/**
	 * Register post meta fields for featured listing status.
	 */
	public static function register_featured_meta() {
		$meta_config = [
			'featured' => [
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
				'default'           => false,
				'description'       => __( 'Is this listing a featured listing?', 'newspack-gdg' ),
				'sanitize_callback' => 'rest_sanitize_boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'type'              => 'boolean',
			],
			'priority' => [
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
				'default'           => 5,
				'description'       => __( 'What priority should this featured listing have (1–10)?', 'newspack-gdg' ),
				'sanitize_callback' => 'absint',
				'single'            => true,
				'show_in_rest'      => true,
				'type'              => 'integer',
			],
			'query'    => [
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
				'default'           => 5,
				'description'       => __( 'Indexed feature priority used for query purposes.', 'newspack-gdg' ),
				'sanitize_callback' => 'absint',
				'single'            => true,
				'show_in_rest'      => true,
				'type'              => 'integer',
			],
			'expires'  => [
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
				'default'           => '',
				'description'       => __( 'When should the listing’s featured status expire?', 'newspack-gdg' ),
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'show_in_rest'      => true,
				'type'              => 'string',
			],
		];

		foreach ( Core::NEWSPACK_LISTINGS_POST_TYPES as $post_type => $post_type_slug ) {
			foreach ( $meta_config as $key => $settings ) {
				$settings['object_subtype'] = $post_type_slug;
				register_meta(
					'post',
					self::META_KEYS[ $key ],
					$settings
				);
			}
		}
	}

	/**
	 * Is the given/current post a featured post?
	 *
	 * @param int $post_id Post ID. If none given, use the current post ID.
	 *
	 * @return boolean True if the listing is currently featured.
	 */
	public static function is_featured( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return get_post_meta( $post_id, self::META_KEYS['featured'], true );
	}

	/**
	 * Get the feature priority for the given/current post.
	 *
	 * @param int $post_id Post ID. If none given, use the current post ID.
	 *
	 * @return int Feature priority level (default = 5).
	 *             Will be returned whether or not the listing is featured.
	 */
	public static function get_featured_priority( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return get_post_meta( $post_id, self::META_KEYS['priority'], true );
	}

	/**
	 * Get the query priority for the given/current post.
	 *
	 * @param int $post_id Post ID. If none given, use the current post ID.
	 *
	 * @return int Feature priority level for query purposes (default = 5).
	 *             Will return 0 if the listing isn't currently featured.
	 */
	public static function get_query_priority( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! self::is_featured( $post_id ) ) {
			return 0;
		}

		return get_post_meta( $post_id, self::META_KEYS['query'], true );
	}

	/**
	 * Get the expiration date string for the given/current post.
	 *
	 * @param int $post_id Post ID. If none given, use the current post ID.
	 *
	 * @return string Featured status expiration date, or an empty string if none.
	 */
	public static function get_featured_expiration( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return get_post_meta( $post_id, self::META_KEYS['expires'], true );
	}

	/**
	 * On save, duplicate the feature priority meta value to a query meta key if the the item is featured.
	 * If the item is not featured, delete the query meta value.
	 * This lets us query based only on this meta value instead of checking both feature status and priority.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function set_feature_priority( $post_id ) {
		if ( Core::is_listing() ) {
			$is_featured      = self::is_featured( $post_id );
			$feature_priority = self::get_featured_priority( $post_id );

			// If the post is featured, ensure it has a query priority. Otherwise, ensure it has no value.
			if ( $is_featured ) {
				update_post_meta( $post_id, self::META_KEYS['query'], $feature_priority );
			} else {
				delete_post_meta( $post_id, self::META_KEYS['query'] );
			}
		}
	}

	/**
	 * Show featured items in query results first, in order of feature priority.
	 * Then order by the query's original ordering criteria, if any were specified.
	 * Limit query modifications to only queries that will include listing posts.
	 *
	 * @param WP_Query $query Query object.
	 */
	public static function show_featured_listings_first( $query ) {

		// Only front-end queries (also affects block queries in the post editor, though, which we want).
		if (
			! is_admin() &&
			! is_comment_feed() &&
			! is_embed() &&
			! is_feed() &&
			! is_privacy_policy() &&
			! is_robots() &&
			! is_trackback()
		) {
			// Let's only modify the query if it's going to include listings.
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_post_type = $query->get( 'post_type' );
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_tax_query = $query->get( 'tax_query' );
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_is_search = $query->is_search();
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_is_meta = ! empty( $query->get( 'meta_query' ) );

			// If the query is already a meta query, we don't want to mess with it.
			if ( $query_is_meta ) {
				return;
			}

			// If post_type and tax_query aren't specified in the query, WP_Query defaults to "post", so let's bail.
			// If the query is a search, an empty post_type means 'any'.
			if ( empty( $query_post_type ) && empty( $query_tax_query ) && ! $query_is_search ) {
				return;
			}

			// However, if tax_query is specified but post_type isn't, post_type defaults to 'any'. Let's make that explicit.
			// If the query is a search, an empty post_type means 'any'.
			if ( empty( $query_post_type ) && ! empty( $query_tax_query ) || $query_is_search ) {
				$query_post_type = [ 'any' ];
			}

			// Query will include listings if any listing post type, or literally "any" post type, is specified.
			$listing_post_types = array_merge(
				array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				[ 'any' ]
			);

			// Post type can be specified as either a string (for a single post type) or array.
			// Let's standardize to an array so we can check if it contains listing or "any" post types using array_intersect.
			if ( ! is_array( $query_post_type ) ) {
				$query_post_type = [ $query_post_type ];
			}
			$query_contains_listings = 0 < count( array_intersect( $listing_post_types, $query_post_type ) );

			// If the query won't include listings, there's no need to modify it.
			if ( ! $query_contains_listings ) {
				return;
			}

			// If the query contains listings, let's add a meta query for feature priority so we can sort by it.
			$meta_query                     = [ 'relation' => 'OR' ];
			$meta_query['feature_priority'] = [
				'key'     => self::META_KEYS['query'],
				'compare' => 'EXISTS',
			];
			$meta_query['no_priority']      = [
				'key'     => self::META_KEYS['query'],
				'compare' => 'NOT EXISTS',
			];

			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query->set(
				'meta_query',
				$meta_query
			);

			// Let's try to preserve the query's specified ordering after we sort by feature priority.
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_order = $query->get( 'order' );
			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query_by = $query->get( 'orderby' );

			// Sort by feature priority first.
			$order = [
				'no_priority' => 'DESC',
			];

			// Then sort by whatever the query's ordering criteria were.
			if ( $query_by && $query_order ) {
				if ( is_array( $query_by ) ) {
					$order = array_merge( $order, $query_by );
				} else {
					$order[ $query_by ] = $query_order;
				}
			} else {
				// If the query didn't specify any order, let's just use WP_Query's default ordering as a secondary criterion.
				$order['date'] = 'DESC';
			}

			// phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
			$query->set(
				'orderby',
				$order
			);
		}
	}

	/**
	 * Append featured classes to the given array of class names.
	 *
	 * @param array $classes Array of class names.
	 *
	 * @return array Filtered array of class names.
	 */
	public static function add_featured_classes( $classes ) {
		if ( Core::is_listing() ) {
			$post_id         = get_the_ID();
			$feature_classes = [];
			$is_featured     = self::is_featured( $post_id );
			if ( $is_featured ) {
				$feature_priority  = self::get_featured_priority( $post_id );
				$feature_classes[] = 'featured-listing';
				$feature_classes[] = 'featured-listing-priority-' . strval( $feature_priority );
				$classes           = array_merge( $classes, $feature_classes );
			}
		}

		return $classes;
	}

	/**
	 * Set up the cron job. Will run once daily and remove featured status for all listings whose expiration date has passed.
	 */
	public static function cron_init() {
		register_deactivation_hook( NEWSPACK_LISTINGS_FILE, [ __CLASS__, 'cron_deactivate' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( self::get_start_time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear the cron job when this plugin is deactivated.
	 */
	public static function cron_deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Unset featured status for the given post. Also delete the query priority meta key.
	 *
	 * @param int $post_id Post ID to update.
	 *
	 * @return boolean True if the post was featured and updated to unfeatured; false if the post wasn't updated or doesn't exist.
	 */
	public static function unset_featured_status( $post_id = null ) {
		if ( null === $post_id ) {
			return false;
		}

		$expiration_date = self::get_featured_expiration( $post_id );
		$timezone        = get_option( 'timezone_string', 'UTC' );

		// Guard against 'Unknown or bad timezone' PHP error.
		if ( empty( trim( $timezone ) ) ) {
			$timezone = 'UTC';
		}

		$parsed_date     = new \DateTime( $expiration_date, new \DateTimeZone( $timezone ) );
		$date_has_passed = 0 > $parsed_date->getTimestamp() - time();

		// If the expiration date has already passed, remove the featured status and query priority.
		if ( $date_has_passed ) {
			update_post_meta( $post_id, 'newspack_listings_featured', false );
			delete_post_meta( $post_id, 'newspack_listings_featured_query_priority' );
			return true;
		}

		return false;
	}

	/**
	 * Check for featured items whose expiration date has passed, and remove their featured status.
	 * Realistically, most sites shouldn't have this many featured items at a time, but just in case,
	 * fetch results in batches of 100 and iterate through the batches so all results are processed.
	 */
	public static function check_expired_featured_items() {
		// Start with first page of 100 results, then we'll see if there are more pages to iterate through.
		$current_page = 1;
		$args         = [
			'post_type'      => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
			'post_status'    => [ 'draft', 'future', 'pending', 'private', 'publish', 'trash' ],
			'posts_per_page' => 100,
			'paged'          => $current_page,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				[
					'key'   => self::META_KEYS['featured'],
					'value' => 1,
				],
				[
					'key'     => self::META_KEYS['expires'],
					'compare' => 'EXISTS',
				],
				[
					'key'     => self::META_KEYS['expires'],
					'compare' => '!=',
					'value'   => '',
				],
			],
		];

		// Get featured listings with an expiration date.
		$results         = new \WP_Query( $args );
		$number_of_pages = $results->max_num_pages;

		foreach ( $results->posts as $featured_listing ) {
			self::unset_featured_status( $featured_listing->ID );
		}

		// If there were more than 1 page of results, repeat with subsequent pages until all posts are processed.
		if ( 1 < $number_of_pages ) {
			while ( $current_page < $number_of_pages ) {
				$current_page  ++;
				$args['paged'] = $current_page;
				$results       = new \WP_Query( $args );

				foreach ( $results->posts as $featured_listing ) {
					self::unset_featured_status( $featured_listing->ID );
				}
			}
		}
	}

	/**
	 * Get the UNIX timestamp for the next occurrence of midnight in the site's local timezone.
	 */
	public static function get_start_time() {
		$timezone = get_option( 'timezone_string', 'UTC' );

		// Guard against 'Unknown or bad timezone' PHP error.
		if ( empty( trim( $timezone ) ) ) {
			$timezone = 'UTC';
		}

		$next_midnight = new \DateTime( 'tomorrow', new \DateTimeZone( $timezone ) );
		return $next_midnight->getTimestamp();
	}
}

Newspack_Listings_Featured::instance();
