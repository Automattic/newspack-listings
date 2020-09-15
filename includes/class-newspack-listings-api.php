<?php
/**
 * Newspack Listings Core.
 *
 * Registers custom post types and taxonomies.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;

defined( 'ABSPATH' ) || exit;

/**
 * Main Core class.
 * Sets up CPTs and taxonomies for listings.
 */
final class Newspack_Listings_Api {

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Api
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings instance.
	 * Ensures only one instance of Newspack_Listings is loaded or can be loaded.
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
	 * Lookup individual posts by title only.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response.
	 */
	public static function get_items( $request ) {
		$params     = $request->get_params();
		$search     = ! empty( $params['search'] ) ? $params['search'] : null;
		$id         = ! empty( $params['id'] ) ? $params['id'] : null;
		$post_types = ! empty( $params['type'] ) ? $params['type'] : [];
		$per_page   = $params['per_page'];

		if ( empty( $search ) && empty( $id ) ) {
			return new \WP_REST_Response( [] );
		}

		if ( empty( $post_types ) ) {
			foreach ( Core::NEWSPACK_LISTINGS_POST_TYPES as $label => $post_type ) {
				if ( 'curated_list' !== $label ) {
					$post_types[] = $post_type;
				}
			}
		}

		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
		];

		if ( ! empty( $search ) ) {
			$args['s'] = esc_sql( $search );
		}

		if ( ! empty( $id ) ) {
			$args['p'] = esc_sql( $id );
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			return new \WP_REST_Response(
				array_map(
					function( $post ) {
						return [
							'id'      => $post->ID,
							'title'   => $post->post_title,
							'content' => $post->post_content,
						];
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
