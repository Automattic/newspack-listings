<?php
/**
 * Newspack Listings - Sets up WooCommerce products for self-serve listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Settings as Settings;
use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Featured as Featured;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/products/class-newspack-listings-products-purchase.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/products/class-newspack-listings-products-ui.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/products/class-newspack-listings-products-user.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/products/class-newspack-listings-products-cron.php';

/**
 * Products class.
 * Sets up WooCommerce products for listings.
 */
class Newspack_Listings_Products {
	/**
	 * The option name for the product ID.
	 */
	const PRODUCT_OPTION = 'newspack_listings_product_id';

	/**
	 * Meta keys for self-serve listing products.
	 */
	const PRODUCT_META_KEYS = [
		'single'       => 'newspack_listings_single_price',
		'featured'     => 'newspack_listings_featured_add_on',
		'subscription' => 'newspack_listings_subscription_price',
		'premium'      => 'newspack_listings_premium_subscription_add_on',
	];

	/**
	 * Meta keys for self-serve listing orders.
	 */
	const ORDER_META_KEYS = [
		'listing_title'          => 'newspack_listings_order_title',
		'listing_type'           => 'newspack_listings_order_type',
		'listing_original_order' => 'newspack_listings_original_order',
		'listing_renewed'        => 'newspack_listings_renewed_id',
		'purchase_type'          => 'newspack_listings_order_type',
	];

	/**
	 * Meta keys for self-serve listing subscriptions.
	 */
	const SUBSCRIPTION_META_KEYS = [
		'listing_subscription' => 'newspack_listings_is_subscription',
		'is_premium'           => 'newspack_listings_is_premium_subscription',
	];

	/**
	 * Meta keys for purchased listing posts.
	 */
	const POST_META_KEYS = [
		'listing_order'        => 'newspack_listings_order_id',
		'listing_subscription' => 'newspack_listings_subscription_id',
		'listing_has_expired'  => 'newspack_listings_has_expired',
	];

	/**
	 * User meta keys for self-serve listing customers.
	 */
	const CUSTOMER_META_KEYS = [
		'is_listings_customer' => 'newspack_listings_self_serve_customer',
	];

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Products
	 */
	protected static $instance = null;

	/**
	 * Is WooCommerce active? If not, we can't use any of its functionality.
	 *
	 * @var $wc_is_active
	 */
	protected static $wc_is_active = false;

	/**
	 * Main Newspack_Listings_Products instance.
	 * Ensures only one instance of Newspack_Listings_Products is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Products - Main instance.
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
		// WP actions to create the necessary products, and to handle submission of the Self-Serve Listings block form.
		add_action( 'init', [ __CLASS__, 'init' ] );

		// When product settings are updated, make sure to update the corresponding WooCommerce products as well.
		add_action( 'update_option', [ __CLASS__, 'update_products' ], 10, 3 );
		add_action( 'updated_post_meta', [ __CLASS__, 'update_product_option' ], 10, 4 );
	}

	/**
	 * After WP init.
	 */
	public static function init() {
		// Check whether WooCommerce is active and available.
		if ( class_exists( 'WooCommerce' ) && class_exists( 'WC_Subscriptions_Product' ) ) {
			self::$wc_is_active = true;
			self::create_products();
		}
	}

	/**
	 * Check whether the required plugins are active, for use outside of the class.
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return self::$wc_is_active;
	}

	/**
	 * Create the WooCommerce products for self-serve listings.
	 */
	public static function create_products() {
		if ( ! self::$wc_is_active ) {
			return false;
		}

		$products = self::get_products();

		if ( ! $products ) {
			$settings     = Settings::get_settings();
			$product_name = __( 'Self-Serve Listings', 'newspack-listings' );

			// Parent product.
			$parent_product = new \WC_Product_Grouped();
			$parent_product->set_name( $product_name );
			$parent_product->set_catalog_visibility( 'hidden' );
			$parent_product->set_virtual( true );
			$parent_product->set_downloadable( true );
			$parent_product->set_sold_individually( true );

			// Single listing product.
			$single_product = new \WC_Product_Simple();
			/* translators: %s: Product name */
			$single_product->set_name( __( 'Single Listing', 'newspack-listings' ) );
			$single_product->set_regular_price( $settings[ self::PRODUCT_META_KEYS['single'] ] );
			$single_product->update_meta_data( '_newspack_listings_product_slug', self::PRODUCT_META_KEYS['single'] );
			$single_product->set_virtual( true );
			$single_product->set_downloadable( true );
			$single_product->set_catalog_visibility( 'hidden' );
			$single_product->set_sold_individually( true );
			$single_product->save();

			// Single "featured" listing upgrade.
			$featured_upgrade_single = new \WC_Product_Simple();
			/* translators: %s: Product name */
			$featured_upgrade_single->set_name( __( '“Featured” Listing Upgrade', 'newspack-listings' ) );
			$featured_upgrade_single->set_regular_price( $settings[ self::PRODUCT_META_KEYS['featured'] ] );
			$featured_upgrade_single->update_meta_data( '_newspack_listings_product_slug', self::PRODUCT_META_KEYS['featured'] );
			$featured_upgrade_single->set_virtual( true );
			$featured_upgrade_single->set_downloadable( true );
			$featured_upgrade_single->set_catalog_visibility( 'hidden' );
			$featured_upgrade_single->set_sold_individually( true );
			$featured_upgrade_single->save();

			// Monthly subscription product.
			$monthly_product = new \WC_Product_Subscription();
			/* translators: %s: Product name */
			$monthly_product->set_name( __( 'Listing Subscription', 'newspack-listings' ) );
			$monthly_product->set_regular_price( $settings[ self::PRODUCT_META_KEYS['subscription'] ] );
			$monthly_product->update_meta_data( '_newspack_listings_product_slug', self::PRODUCT_META_KEYS['subscription'] );
			$monthly_product->update_meta_data( '_subscription_price', wc_format_decimal( $settings[ self::PRODUCT_META_KEYS['subscription'] ] ) );
			$monthly_product->update_meta_data( '_subscription_period', 'month' );
			$monthly_product->update_meta_data( '_subscription_period_interval', 1 );
			$monthly_product->set_virtual( true );
			$monthly_product->set_downloadable( true );
			$monthly_product->set_catalog_visibility( 'hidden' );
			$monthly_product->set_sold_individually( true );
			$monthly_product->save();

			// Monthly "premium subscription" upgrade.
			$premium_upgrade_monthly = new \WC_Product_Subscription();
			/* translators: %s: Product name */
			$premium_upgrade_monthly->set_name( __( 'Premium Subscription Upgrade', 'newspack-listings' ) );
			$premium_upgrade_monthly->set_regular_price( $settings[ self::PRODUCT_META_KEYS['premium'] ] );
			$premium_upgrade_monthly->update_meta_data( '_newspack_listings_product_slug', self::PRODUCT_META_KEYS['premium'] );
			$premium_upgrade_monthly->update_meta_data( '_subscription_price', wc_format_decimal( $settings[ self::PRODUCT_META_KEYS['premium'] ] ) );
			$premium_upgrade_monthly->update_meta_data( '_subscription_period', 'month' );
			$premium_upgrade_monthly->update_meta_data( '_subscription_period_interval', 1 );
			$premium_upgrade_monthly->set_virtual( true );
			$premium_upgrade_monthly->set_downloadable( true );
			$premium_upgrade_monthly->set_catalog_visibility( 'hidden' );
			$premium_upgrade_monthly->set_sold_individually( true );
			$premium_upgrade_monthly->save();

			$parent_product->set_children(
				[
					$single_product->get_id(),
					$featured_upgrade_single->get_id(),
					$monthly_product->get_id(),
					$premium_upgrade_monthly->get_id(),
				]
			);
			$parent_product->save();
			update_option( self::PRODUCT_OPTION, $parent_product->get_id() );
		}
	}

	/**
	 * When Newspack Listing settings are updated, update the corresopnding WC products as well.
	 *
	 * @param string $option Name of the option to update.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $new_value The new option value.
	 */
	public static function update_products( $option, $old_value, $new_value ) {
		if ( ! self::$wc_is_active ) {
			return false;
		}

		// Only if the updated option is a Newspack Listing setting.
		$settings = Settings::get_settings();
		if ( ! in_array( $option, array_keys( $settings ) ) ) {
			return;
		}

		$product_id = get_option( self::PRODUCT_OPTION, false );
		if ( $product_id ) {
			$parent_product = \wc_get_product( $product_id );
			$parent_product->set_status( 'publish' );
			$parent_product->save();

			foreach ( $parent_product->get_children() as $child_id ) {
				$child_product = \wc_get_product( $child_id );

				if ( ! $child_product ) {
					continue;
				}

				$settings_slug = $child_product->get_meta( '_newspack_listings_product_slug' );
				if ( $option === $settings_slug ) {
					// Set base price.
					$child_product->set_regular_price( $new_value );

					// Set subscription price, if applicable.
					if ( 'subscription' === $child_product->get_type() ) {
						$child_product->update_meta_data( '_subscription_price', \wc_format_decimal( $new_value ) );
					}

					$child_product->save();
				}
			}
		}
	}

	/**
	 * When a listing product's price is updated in the WooCommerce UI, also update the corresponding plugin setting.
	 *
	 * @param int    $meta_id Meta field ID.
	 * @param int    $post_id Post ID of the product being updated via WooCommerce.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Value of $meta_key.
	 */
	public static function update_product_option( $meta_id, $post_id, $meta_key, $meta_value ) {
		// Only run if post being updated is a product and meta field being updated is '_price'.
		if ( 'product' !== get_post_type( $post_id ) || '_price' !== $meta_key ) {
			return;
		}

		// Only run if we've created listing products.
		$products = self::get_products();
		if ( ! $products || ! is_array( $products ) ) {
			return;
		}

		// If the product being updated is a Listings product, update the corresponding plugin setting.
		$product_key = array_search( $post_id, $products, true );
		if ( $product_key && in_array( $product_key, self::PRODUCT_META_KEYS ) ) {
			update_option( $product_key, (float) $meta_value );
		}
	}

	/**
	 * Get the WooCommerce product for the parent listings product.
	 *
	 * @return boolean|array Array of product IDs, keyed by meta key.
	 *                       False if none created or if WC is inactive.
	 */
	public static function get_products() {
		if ( ! self::$wc_is_active ) {
			return false;
		}

		$product_id = get_option( self::PRODUCT_OPTION, false );

		// If missing a product option, the products need to be created.
		if ( ! $product_id ) {
			return false;
		}

		// If missing a parent product, the products need to be created.
		$parent_product = \wc_get_product( $product_id );
		if ( ! $parent_product || ! $parent_product->is_type( 'grouped' ) || 'publish' !== get_post_status( $product_id ) ) {
			return false;
		}

		$products = [
			'newspack_listings_parent_product' => $product_id,
		];

		foreach ( $parent_product->get_children() as $child_id ) {
			$child_product = \wc_get_product( $child_id );

			// If missing a child product, the products need to be created.
			if ( ! $child_product ) {
				return false;
			}

			$settings_slug              = $child_product->get_meta( '_newspack_listings_product_slug' );
			$products[ $settings_slug ] = $child_id;
		}

		return $products;
	}

	/**
	 * Given a WC order ID, find the listing associated with that order.
	 *
	 * @param int $order_id ID of the WooCommerce order.
	 *
	 * @return WP_Post|boolean The associated listing, or false if none.
	 */
	public static function get_listing_by_order_id( $order_id ) {
		$listing          = false;
		$associated_posts = get_posts(
			[
				'meta_key'    => self::POST_META_KEYS['listing_order'],
				'meta_value'  => $order_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'post_status' => 'any',
				'post_type'   => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
			]
		);

		if ( 0 < count( $associated_posts ) ) {
			$listing = reset( $associated_posts );
		}

		return $listing;
	}

	/**
	 * Check whether the given or currently logged-in user is a listings customer.
	 *
	 * @param int|null $user_id ID of the user to check. If not given, will check the current user.
	 *
	 * @return boolean True if the user is a customer, false if not.
	 */
	public static function is_listing_customer( $user_id = null ) {
		global $user_ID;

		if ( ! $user_id ) {
			$user_id = $user_ID;
		}

		$user_meta      = get_userdata( $user_id );
		$user_roles     = $user_meta->roles;
		$customer_roles = [ 'subscriber', 'customer' ]; // Avoid limiting capabilities for contributor/author/editor/admin roles.

		$is_listing_customer = 0 < count( array_intersect( $user_roles, $customer_roles ) ) && get_user_meta( $user_id, self::CUSTOMER_META_KEYS['is_listings_customer'], true );

		return $is_listing_customer;
	}

	/**
	 * Given a user ID and post ID, check whether the user owns (is the author of) the post.
	 *
	 * @param int|null $user_id ID of the user to check. If not given, will check the current user.
	 * @param int      $post_id ID of the post to check. Must be a listing post type.
	 *
	 * @return boolean True if the user owns the post, false if not.
	 */
	public static function does_user_own_listing( $user_id = null, $post_id = null ) {
		global $user_ID;

		if ( ! $user_id ) {
			$user_id = $user_ID;
		}

		$post = get_post( $post_id );
		if ( ! $post || ! Core::is_listing( $post->post_type ) ) {
			return false;
		}

		return (int) $post->post_author === (int) $user_id;
	}
}

Newspack_Listings_Products::instance();
