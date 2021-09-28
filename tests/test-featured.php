<?php
/**
 * Class Featured Test
 *
 * @package Newspack_Listings
 */

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Featured as Featured;
use \Newspack_Listings\Utils as Utils;

/**
 * Featured listing test case.
 */
class FeaturedTest extends WP_UnitTestCase {
	private static $listings = []; // phpcs:ignore Squiz.Commenting.VariableComment.Missing

	public function setUp() { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing

		// Remove any listings (from previous tests).
		foreach ( self::$listings as $listing_id ) {
			wp_delete_post( $listing_id );
		}
	}

	/**
	 * Create a listing.
	 *
	 * @param array $options Options for creating the listing post.
	 * @return int Post ID of the created listing.
	 */
	private function create_listing( $options = [] ) {
		$args = wp_parse_args(
			$options,
			[
				'title'    => 'Listing Title',
				'type'     => 'place',
				'featured' => false,
				'priority' => 5,
				'expires'  => '',
			]
		);

		$title      = $args['title'];
		$type       = $args['type'];
		$listing_id = self::factory()->post->create(
			[
				'post_type'    => Core::NEWSPACK_LISTINGS_POST_TYPES[ $type ],
				'post_title'   => $title,
				'post_content' => 'Some ' . $type . ' listing content',
			]
		);

		// Add featured meta.
		if ( $args['featured'] ) {
			update_post_meta( $listing_id, Featured::META_KEYS['featured'], true );
			update_post_meta( $listing_id, Featured::META_KEYS['priority'], $args['priority'] );
			update_post_meta( $listing_id, Featured::META_KEYS['query'], $args['priority'] );
			update_post_meta( $listing_id, Featured::META_KEYS['expires'], $args['expires'] );
		}

		self::$listings[] = $listing_id;
		return $listing_id;
	}

	/**
	 * Control query with no featured listings.
	 */
	public function test_control_query() {
		// Generate 50 standard (non-featured) listings.
		$number_of_listings = 50;
		$current_listing    = 0;
		while ( $current_listing < $number_of_listings ) {
			$current_listing ++;
			self::create_listing( [ 'title' => 'Listing title ' . zeroise( $current_listing, 2 ) ] );
		}

		// Execute a query.
		$control_listings = get_posts(
			[
				'post_type'      => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				'posts_per_page' => 50,
				'order'          => 'ASC',
				'orderby'        => 'title',
			]
		);

		$index = 0;
		foreach ( $control_listings as $listing ) {
			$index             ++;
			$stringified_index = zeroise( $index, 2 );
			self::assertEquals(
				$listing->post_title,
				'Listing title ' . $stringified_index,
				$listing->post_title . ' is item number ' . $index . ' in the query results.'
			);
		}
	}

	/**
	 * Test featured listing sort order.
	 */
	public function test_featured_sort_order() {
		// Generate 50 standard (non-featured) listings.
		$number_of_listings = 50;
		$current_listing    = 0;
		while ( $current_listing < $number_of_listings ) {
			$current_listing ++;
			self::create_listing( [ 'title' => 'Listing title ' . zeroise( $current_listing, 2 ) ] );
		}

		// Generate a featured listing.
		$featured_listing = self::create_listing(
			[
				'title'    => 'Listing title 51',
				'featured' => true,
			]
		);

		// Execute a query.
		$listings_with_featured = new WP_Query(
			[
				'post_type'      => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				'posts_per_page' => 51,
				'order'          => 'ASC',
				'orderby'        => 'title',
			]
		);

		$index = 0;
		foreach ( $listings_with_featured->get_posts() as $listing ) {
			$index             ++;
			$stringified_index = zeroise( $index - 1, 2 ); // Index will be offset by one because the featured listing comes first.
			if ( 1 === $index ) {
				self::assertEquals(
					$listing->post_title,
					get_the_title( $featured_listing ),
					'Featured listing appears first in results regardless of sort order.'
				);
			} else {
				self::assertEquals(
					$listing->post_title,
					'Listing title ' . $stringified_index,
					$listing->post_title . ' is item number ' . $index . ' in the query results.'
				);
			}
		}
	}

	/**
	 * Test featured listing sort order with priority.
	 */
	public function test_featured_priority_sort_order() {
		// Generate 50 standard (non-featured) listings.
		$number_of_listings = 50;
		$current_listing    = 0;
		while ( $current_listing < $number_of_listings ) {
			$current_listing ++;
			self::create_listing( [ 'title' => 'Listing title ' . zeroise( $current_listing, 2 ) ] );
		}

		// Generate a featured listing with default priority.
		$featured_listing_priority_default = self::create_listing(
			[
				'title'    => 'Listing title 51',
				'featured' => true,
			]
		);

		// Generate a featured listing with higher priority.
		$featured_listing_priority_high = self::create_listing(
			[
				'title'    => 'Listing title 52',
				'featured' => true,
				'priority' => 9,
			]
		);

		// Execute a query.
		$listings_with_feature_priority = new WP_Query(
			[
				'post_type'      => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				'posts_per_page' => 52,
				'order'          => 'ASC',
				'orderby'        => 'title',
			]
		);

		$index = 0;
		foreach ( $listings_with_feature_priority->get_posts() as $listing ) {
			$index             ++;
			$stringified_index = zeroise( $index - 2, 2 ); // Index will be offset by two because the featured listings come first.
			if ( 1 === $index ) {
				self::assertEquals(
					$listing->post_title,
					get_the_title( $featured_listing_priority_high ),
					'Featured listings with higher priority appear first.'
				);
			} elseif ( 2 === $index ) {
				self::assertEquals(
					$listing->post_title,
					get_the_title( $featured_listing_priority_default ),
					'Featured listings with lower priority still appear before non-featured results.'
				);
			} else {
				self::assertEquals(
					$listing->post_title,
					'Listing title ' . $stringified_index,
					$listing->post_title . ' is item number ' . $index . ' in the query results.'
				);
			}
		}
	}
}
