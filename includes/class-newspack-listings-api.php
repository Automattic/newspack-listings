<?php
/**
 * Newspack Listings API.
 *
 * Custom API endpoints for Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Blocks as Blocks;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

/**
 * API class.
 * Sets up API endpoints and handlers for listings.
 */
final class Newspack_Listings_Api {

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Api
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings_Api instance.
	 * Ensures only one instance of Newspack_Listings_Api is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Api - Main instance.
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
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register REST API endpoints.
	 */
	public static function register_routes() {

		// GET listings posts by ID, query args, or title search term.
		register_rest_route(
			'newspack-listings/v1',
			'listings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_items' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// GET listings taxonomy terms by name search term.
		register_rest_route(
			'newspack-listings/v1',
			'terms',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_terms' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Gets all listing post types.
	 *
	 * @return array Array of post type slugs.
	 */
	public static function get_listing_post_types() {
		return array_values( Core::NEWSPACK_LISTINGS_POST_TYPES );
	}

	/**
	 * Build query args for listings.
	 *
	 * @param array $query Query options given as block attributes from Curated List block.
	 * @param array $args (Optional) Args to overwrite defaults with.
	 *
	 * @return array WP_Query args array.
	 */
	public static function build_listings_query( $query, $args = [] ) {
		$default_args = [
			'post_type'      => [],
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
		];

		$args = wp_parse_args( $args, $default_args );

		if ( ! empty( $query['type'] ) && 'any' !== $query['type'] ) {
			$args['post_type'] = $query['type'];
		} else {
			// If not given a post type, look up posts of any listing type.
			$args['post_type'] = self::get_listing_post_types();
		}
		if ( ! empty( $query['maxItems'] ) ) {
			$args['posts_per_page'] = $query['maxItems'];
		}
		if ( ! empty( $query['authors'] ) ) {
			$args['author__in'] = $query['authors'];
		}
		if ( ! empty( $query['order'] ) ) {
			$args['order'] = $query['order'];
		}
		if ( ! empty( $query['sortBy'] ) ) {
			$args['orderby'] = $query['sortBy'];
		}

		if (
			! empty( $query['categories'] ) ||
			! empty( $query['tags'] ) ||
			! empty( $query['categoryExclusions'] ) ||
			! empty( $query['tagExclusions'] )
		) {
			$args['tax_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( ! empty( $query['categories'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => Core::NEWSPACK_LISTINGS_CAT,
				'field'    => 'term_id',
				'terms'    => $query['categories'],
			];
		}
		if ( ! empty( $query['tags'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => Core::NEWSPACK_LISTINGS_TAG,
				'field'    => 'term_id',
				'terms'    => $query['tags'],
			];
		}
		if ( ! empty( $query['categoryExclusions'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => Core::NEWSPACK_LISTINGS_CAT,
				'field'    => 'term_id',
				'terms'    => $query['categoryExclusions'],
				'operator' => 'NOT IN',
			];
		}
		if ( ! empty( $query['tagExclusions'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => Core::NEWSPACK_LISTINGS_TAG,
				'field'    => 'term_id',
				'terms'    => $query['tagExclusions'],
				'operator' => 'NOT IN',
			];
		}
		if ( ! empty( $args['tax_query'] ) && 1 < count( $args['tax_query'] ) ) {
			$args['tax_query']['relation'] = 'AND';
		}

		return $args;
	}

	/**
	 * Lookup individual posts by title search or post ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response.
	 */
	public static function get_items( $request ) {
		$params     = $request->get_params();
		$fields     = explode( ',', $params['_fields'] );
		$search     = ! empty( $params['search'] ) ? $params['search'] : null;
		$query      = ! empty( $params['query'] ) ? $params['query'] : null;
		$id         = ! empty( $params['id'] ) ? $params['id'] : null;
		$post_types = ! empty( $params['type'] ) ? $params['type'] : [];
		$per_page   = ! empty( $params['per_page'] ) ? $params['per_page'] : 10;
		$page       = ! empty( $params['page'] ) ? $params['page'] : 1;
		$next_page  = $page + 1;
		$attributes = ! empty( $params['attributes'] ) ? $params['attributes'] : [];
		$is_amp     = $request->get_param( 'amp' );

		// If not given a post type, look up posts of any listing type.
		if ( empty( $post_types ) ) {
			$post_types = self::get_listing_post_types();
		}

		// Query args.
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		];

		// Look up by title only if provided with a search term and not an ID or query terms.
		if ( ! empty( $search ) && empty( $id ) && empty( $query ) ) {
			$args['s'] = esc_sql( $search );
		}

		// If given query terms but not an ID, search using those terms.
		if ( ! empty( $query ) && empty( $id ) ) {
			$args = self::build_listings_query( $query, $args );
		}

		// If given an ID, just look up that post.
		if ( ! empty( $id ) ) {
			$args['p'] = esc_sql( $id );
		}

		$listings_query = new \WP_Query( $args );

		if ( $listings_query->have_posts() ) {
			$response = new \WP_REST_Response(
				array_map(
					function( $post ) use ( $attributes, $fields, $is_amp, $next_page, $query ) {
						$item = [
							'id'    => $post->ID,
							'title' => $post->post_title,
						];

						// if $fields includes html, get rendered HTML for the post.
						if ( in_array( 'html', $fields ) && ! empty( $attributes ) ) {
							$html = Blocks::template_include(
								'listing',
								[
									'attributes' => $attributes,
									'post'       => $post,
								]
							);

							// If an AMP page, convert to valid AMP HTML.
							if ( $is_amp ) {
								$html = Utils\generate_amp_partial( $html );
							}

							$item['html'] = $html;
						}

						// If $fields includes category, get the post categories.
						if ( in_array( 'category', $fields ) ) {
							$item['category'] = get_the_terms( $post->ID, Core::NEWSPACK_LISTINGS_CAT );
						}

						// If $fields includes author, get the post author.
						if ( in_array( 'author', $fields ) ) {
							$item['author'] = get_the_author_meta( 'display_name', $post->post_author );
						}

						// If $fields includes excerpt, get the post excerpt.
						if ( in_array( 'excerpt', $fields ) ) {
							$item['excerpt'] = Utils\get_listing_excerpt( $post );
						}

						// If $fields includes media, get the featured image + caption.
						if ( in_array( 'media', $fields ) ) {
							$item['media'] = [
								'image'   => get_the_post_thumbnail_url( $post->ID, 'medium' ),
								'caption' => get_the_post_thumbnail_caption( $post->ID ),
							];
						}

						// If $fields includes meta, get all Newspack Listings meta fields.
						if ( in_array( 'meta', $fields ) ) {
							$post_meta = Core::get_meta_values( $post->ID, $post->post_type );

							if ( ! empty( $post_meta ) ) {
								$item['meta'] = $post_meta;
							}
						}

						// If $fields includes type, get the post type.
						if ( in_array( 'type', $fields ) ) {
							$item['type'] = $post->post_type;
						}

						return $item;
					},
					$listings_query->posts
				),
				200
			);

			// Provide next URL if there are more pages.
			if ( $next_page <= $listings_query->max_num_pages ) {
				$next_url = add_query_arg(
					[
						'attributes' => $attributes,
						'query'      => $query,
						'page'       => $next_page,
						'amp'        => $is_amp,
						'_fields'    => 'html',
					],
					rest_url( '/newspack-listings/v1/listings' )
				);
			}

			if ( ! empty( $next_url ) ) {
				$response->header( 'next-url', esc_url( $next_url ) );
			}

			return $response;
		}

		return new \WP_REST_Response( [] );
	}

	/**
	 * Look up Listing taxonomy terms by name.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response.
	 */
	public static function get_terms( $request ) {
		$params = $request->get_params();

		if ( empty( $params['taxonomy'] ) ) {
			$params['taxonomy'] = Core::NEWSPACK_LISTINGS_CAT;
		}

		$terms = get_terms( $params );

		if ( is_array( $terms ) ) {
			return new \WP_REST_Response(
				array_map(
					function( $term ) {
						return [
							'id'   => $term->term_id,
							'name' => $term->name,
						];
					},
					$terms
				),
				200
			);
		}

		return new \WP_REST_Response( [] );
	}
}

Newspack_Listings_Api::instance();
