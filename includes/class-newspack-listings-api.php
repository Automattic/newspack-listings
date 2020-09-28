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

		// GET listings posts by ID or title search term.
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
		$id         = ! empty( $params['id'] ) ? $params['id'] : null;
		$post_types = ! empty( $params['type'] ) ? $params['type'] : [];
		$per_page   = ! empty( $params['per_page'] ) ? $params['per_page'] : 20;

		if ( empty( $search ) && empty( $id ) ) {
			return new \WP_REST_Response( [] );
		}

		// If not given a post type, look up posts of any listing type.
		if ( empty( $post_types ) ) {
			foreach ( Core::NEWSPACK_LISTINGS_POST_TYPES as $label => $post_type ) {
				if ( 'curated_list' !== $label ) {
					$post_types[] = $post_type;
				}
			}
		}

		// Query args.
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
		];

		// Look up by title only if provided with a search term and not an ID.
		if ( ! empty( $search ) && empty( $id ) ) {
			$args['s'] = esc_sql( $search );
		}

		// If given an ID, just look up that post.
		if ( ! empty( $id ) ) {
			$args['p'] = esc_sql( $id );
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			return new \WP_REST_Response(
				array_map(
					function( $post ) use ( $fields ) {
						$response = [
							'id'    => $post->ID,
							'title' => $post->post_title,
						];

						// If $fields includes excerpt, get the post excerpt.
						if ( in_array( 'excerpt', $fields ) ) {
							$response['excerpt'] = wpautop( get_the_excerpt( $post->ID ) );
						}

						// If $fields includes locations, get location data.
						if ( in_array( 'locations', $fields ) ) {
							$locations = Utils\get_location_data( $post->ID );

							if ( ! empty( $locations ) ) {
								$response['locations'] = $locations;
							}
						}

						// If $fields includes media, get the featured image + caption.
						if ( in_array( 'media', $fields ) ) {
							$response['media'] = [
								'image'   => get_the_post_thumbnail_url( $post->ID, 'medium' ),
								'caption' => get_the_post_thumbnail_caption( $post->ID ),
							];
						}

						// If $fields includes meta, get all Newspack Listings meta fields.
						if ( in_array( 'meta', $fields ) ) {
							$post_meta = array_filter(
								get_post_meta( $post->ID ),
								function( $key ) {
									return is_numeric( strpos( $key, 'newspack_listings_' ) );
								},
								ARRAY_FILTER_USE_KEY
							);

							$response['meta'] = $post_meta;
						}

						return $response;
					},
					$query->posts
				),
				200
			);
		}

		return new \WP_REST_Response( [] );
	}
}

Newspack_Listings_Api::instance();
