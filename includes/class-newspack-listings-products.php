<?php
/**
 * Newspack Listings - Sets up WooCommerce products for self-serve listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Settings as Settings;
use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Newspack_Listings_Block_Patterns as Patterns;
use \Newspack_Listings\Utils as Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Products class.
 * Sets up WooCommerce products for listings.
 */
final class Newspack_Listings_Products {

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
		'listing_title'         => 'newspack_listings_order_title',
		'listing_type'          => 'newspack_listings_order_type',
		'listing_purchase_type' => 'newspack_listings_order_type',
	];

	/**
	 * Meta keys for self-serve listing subscriptions.
	 */
	const SUBSCRIPTION_META_KEYS = [
		'listing_subscription' => 'newspack_listings_is_subscription',
	];

	/**
	 * Meta keys for purchased listing posts.
	 */
	const POST_META_KEYS = [
		'listing_order'        => 'newspack_listings_order_id',
		'listing_subscription' => 'newspack_listings_subscription_id',
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
		add_action( 'init', [ __CLASS__, 'init' ] );
		add_action( 'wp_loaded', [ __CLASS__, 'handle_purchase_form' ], 99 );
		add_filter( 'pre_option_woocommerce_enable_guest_checkout', [ __CLASS__, 'require_account_for_listings' ] );
		add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', [ __CLASS__, 'allow_account_creation_and_login_for_listings' ] );
		add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', [ __CLASS__, 'allow_account_creation_and_login_for_listings' ] );
		add_action( 'woocommerce_checkout_billing', [ __CLASS__, 'listing_details_summary' ] );
		add_filter( 'woocommerce_billing_fields', [ __CLASS__, 'listing_details_billing_fields' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'listing_checkout_update_order_meta' ] );
		add_action( 'woocommerce_thankyou_order_received_text', [ __CLASS__, 'listing_append_thank_you' ], 99, 2 );
		add_action( 'woocommerce_my_account_my_orders_actions', [ __CLASS__, 'listing_append_edit_action' ], 10, 2 );
		add_action( 'woocommerce_order_details_after_order_table', [ __CLASS__, 'listing_append_details' ] );
		add_action( 'woocommerce_subscription_status_active', [ __CLASS__, 'listing_subscription_associate_primary_post' ] );
		add_action( 'woocommerce_subscription_status_updated', [ __CLASS__, 'listing_subscription_unpublish_associated_posts' ], 10, 3 );
		add_filter( 'user_has_cap', [ __CLASS__, 'allow_customers_to_edit_own_posts' ], 10, 3 );
		add_action( 'admin_init', [ __CLASS__, 'hide_admin_menu_for_customers' ], 1000 );
		add_filter( 'admin_bar_menu', [ __CLASS__, 'hide_admin_bar_for_customers' ], 1000 );

		// When product settings are updated, make sure to update the corresponding WooCommerce products as well.
		add_action( 'update_option', [ __CLASS__, 'update_products' ], 10, 3 );
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
	 * Valid single listing types that can be purchased.
	 *
	 * @return array Array of listing types.
	 */
	public static function get_listing_types() {
		return [
			[
				'slug' => 'blank',
				'name' => __( 'Blank listing (start from scratch)', 'newspack-listings' ),
			],
			[
				'slug' => 'event',
				'name' => __( 'Event', 'newspack-listings' ),
			],
			[
				'slug' => 'classified',
				'name' => __( 'Classified Ad', 'newspack-listings' ),
			],
			[
				'slug' => 'job',
				'name' => __( 'Job Listing', 'newspack-listings' ),
			],
			[
				'slug' => 'real-estate',
				'name' => __( 'Real Estate Listing', 'newspack-listings' ),
			],
		];
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
	 * Get the WooCommerce product for the parent listings product.
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
	 * Remove all listing products from the cart.
	 */
	public static function clear_cart() {
		$products = self::get_products();
		if ( ! $products || ! self::$wc_is_active ) {
			return;
		}

		$cart = \WC()->cart;

		if ( $cart ) {
			foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
				if ( ! empty( $cart_item['product_id'] ) && in_array( $cart_item['product_id'], array_values( $products ) ) ) {
					$cart->remove_cart_item( $cart_key );
				}
			}
		}
	}

	/**
	 * For self-serve listings, a customer account is required so the user can log in and manage their listings.
	 * If a listing product is in the cart, force the checkout to require an account regardless of WC settings.
	 *
	 * @param string $value String value 'yes' or 'no' of the WC setting to allow guest checkout.
	 *
	 * @return string Filtered value.
	 */
	public static function require_account_for_listings( $value ) {
		$products = self::get_products();
		if ( ! $products || ! self::$wc_is_active ) {
			return $value;
		}

		$cart = \WC()->cart;

		if ( $cart ) {
			foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
				if ( ! empty( $cart_item['product_id'] ) && in_array( $cart_item['product_id'], array_values( $products ) ) ) {
					$value = 'no';
					break;
				}
			}
		}

		return $value;
	}

	/**
	 * For self-serve listings, a customer account is required so the user can log in and manage their listings.
	 * If a listing product is in the cart, force the checkout to allow account creation/login regardless of WC settings.
	 *
	 * @param string $value String value 'yes' or 'no' of the WC setting to allow guest checkout.
	 *
	 * @return string Filtered value.
	 */
	public static function allow_account_creation_and_login_for_listings( $value ) {
		$products = self::get_products();
		if ( ! $products || ! self::$wc_is_active ) {
			return $value;
		}

		$cart = \WC()->cart;

		if ( $cart ) {
			foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
				if ( ! empty( $cart_item['product_id'] ) && in_array( $cart_item['product_id'], array_values( $products ) ) ) {
					$value = 'yes';
					break;
				}
			}
		}

		return $value;
	}

	/**
	 * Handle submission of the purchase form block.
	 */
	public static function handle_purchase_form() {
		$purchase_type = filter_input( INPUT_GET, 'listing-purchase-type', FILTER_SANITIZE_STRING );

		// Only if WC is active.
		if ( ! self::$wc_is_active ) {
			return;
		}

		// Only if purchase type is valid.
		if ( 'single' !== $purchase_type && 'subscription' !== $purchase_type ) {
			return;
		}

		$is_single       = 'single' === $purchase_type;
		$is_subscription = 'subscription' === $purchase_type;

		// Get form submission data.
		$title_single       = filter_input( INPUT_GET, 'listing-title-single', FILTER_SANITIZE_STRING );
		$single_type        = filter_input( INPUT_GET, 'listing-single-type', FILTER_SANITIZE_STRING );
		$featured_upgrade   = filter_input( INPUT_GET, 'listing-featured-upgrade', FILTER_SANITIZE_STRING );
		$title_subscription = filter_input( INPUT_GET, 'listing-title-subscription', FILTER_SANITIZE_STRING );
		$premium_upgrade    = filter_input( INPUT_GET, 'listing-premium-upgrade', FILTER_SANITIZE_STRING );
		$listing_title      = __( 'Untitled listing', 'newspack-listings' );

		// If a title was provided, use it.
		if ( $is_single && ! empty( $title_single ) ) {
			$listing_title = $title_single;
		} elseif ( $is_subscription && ! empty( $title_subscription ) ) {
			$listing_title = $title_subscription;
		}

		// Single purchases must have a valid listing type.
		if ( $is_single && empty( $single_type ) ) {
			return;
		}

		$products = self::get_products();
		if ( ! $products ) {
			return;
		}

		self::clear_cart();
		$products_to_purchase = [];
		$checkout_query_args  = [
			'listing_title'         => sanitize_text_field( $listing_title ),
			'listing_purchase_type' => sanitize_text_field( $purchase_type ),
		];

		if ( $is_single ) {
			$products_to_purchase[]              = $products['newspack_listings_single_price'];
			$checkout_query_args['listing_type'] = sanitize_text_field( $single_type );

			if ( 'on' === $featured_upgrade ) {
				$products_to_purchase[] = $products['newspack_listings_featured_add_on'];
			}
		} else {
			$products_to_purchase[] = $products['newspack_listings_subscription_price'];

			if ( 'on' === $premium_upgrade ) {
				$products_to_purchase[] = $products['newspack_listings_premium_subscription_add_on'];
			}
		}

		foreach ( $products_to_purchase as $product_id ) {
			\WC()->cart->add_to_cart( $product_id );
		}

		$checkout_url = add_query_arg(
			$checkout_query_args,
			\wc_get_page_permalink( 'checkout' )
		);

		// Redirect to checkout.
		\wp_safe_redirect( $checkout_url );
		exit;
	}

	/**
	 * Show listing details in checkout summary.
	 */
	public static function listing_details_summary() {
		$params        = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		$listing_title = isset( $params['listing_title'] ) ? $params['listing_title'] : null;
		$listing_types = self::get_listing_types();
		$listing_type  = array_reduce(
			$listing_types,
			function( $acc, $type ) use ( $params ) {
				if ( isset( $params['listing_type'] ) && $type['slug'] === $params['listing_type'] ) {
					$acc = $type['name'];
				}
				return $acc;
			},
			null
		);

		if ( $listing_title || $listing_type ) : ?>
			<h4><?php echo esc_html__( 'Listing Details', 'newspack-listings' ); ?></h4>
			<p><?php echo esc_html__( 'You can update listing details after purchase.', 'newspack-listings' ); ?>
			<ul>
			<?php
		endif;

		if ( $listing_title ) :
			?>
			<li><strong><?php echo esc_html__( 'Listing Title: ', 'newspack-listings' ); ?></strong><?php echo esc_html( $listing_title ); ?></li>
			<?php
		endif;
		if ( $listing_type ) :
			?>
			<li><strong><?php echo esc_html__( 'Listing Type: ', 'newspack-listings' ); ?></strong><?php echo esc_html( $listing_type ); ?></li>
			<?php
		endif;
		if ( $listing_title || $listing_type ) :
			?>
			</ul>
			<?php
		endif;
	}


	/**
	 * Add hidden billing fields for listing details.
	 *
	 * @param Array $form_fields WC form fields.
	 */
	public static function listing_details_billing_fields( $form_fields ) {
		$params = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );

		if ( is_array( $params ) ) {
			foreach ( $params as $param => $value ) {
				if ( $value && in_array( $param, array_keys( self::ORDER_META_KEYS ) ) ) {
					$form_fields[ sanitize_text_field( $param ) ] = [
						'type'    => 'text',
						'default' => sanitize_text_field( $value ),
						'class'   => [ 'hide' ],
					];
				}
			}
		}

		return $form_fields;
	}

	/**
	 * Update WC order with listing details from hidden form fields.
	 * Also mark the purchasing customer as a Listings customer, create a listing post for the order, and associate it with the customer.
	 *
	 * @param String $order_id WC order id.
	 */
	public static function listing_checkout_update_order_meta( $order_id ) {
		$params = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		if ( is_array( $params ) ) {
			foreach ( $params as $param => $value ) {
				if ( in_array( $param, array_keys( self::ORDER_META_KEYS ) ) ) {
					update_post_meta( $order_id, sanitize_text_field( self::ORDER_META_KEYS[ $param ] ), sanitize_text_field( $value ) );
				}
			}
		}

		// Look up the purchasing customer and set relevant user meta.
		$order = \wc_get_order( $order_id );
		if ( $order ) {
			$customer_id = $order->get_customer_id();
			if ( $customer_id ) {
				update_user_meta( $customer_id, self::CUSTOMER_META_KEYS['is_listings_customer'], 1 );
				$customer = new \WP_User( $customer_id );
				$customer->add_cap( 'edit_posts' ); // Let this customer edit their own posts.
				$customer->add_cap( 'edit_published_posts' ); // Let this customer edit their own posts even after they're published.
				$customer->add_cap( 'upload_files' ); // Let this customer upload media for featured and inline images.

				$purchase_type   = isset( $params['listing_purchase_type'] ) ? $params['listing_purchase_type'] : 'single';
				$is_subscription = 'subscription' === $purchase_type;
				$listing_type    = isset( $params['listing_type'] ) ? $params['listing_type'] : null;
				$post_title      = isset( $params['listing_title'] ) ? $params['listing_title'] : __( 'Untitled listing', 'newspack-listings' );
				$post_type       = Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'];
				$post_content    = false;
				$block_pattern   = false;
				$subscriptions   = false;

				if ( $is_subscription ) {
					$post_type     = Core::NEWSPACK_LISTINGS_POST_TYPES['place'];
					$block_pattern = Patterns::get_block_patterns( 'business_1' );
				} else {
					if ( 'event' === $listing_type ) {
						$post_type = Core::NEWSPACK_LISTINGS_POST_TYPES['event'];
					}
					if ( 'classified' === $listing_type ) {
						$block_pattern = Patterns::get_block_patterns( 'classified_1' );
					}
					if ( 'real-estate' === $listing_type ) {
						$block_pattern = Patterns::get_block_patterns( 'real_estate_1' );
					}
				}

				if ( $block_pattern ) {
					$post_content = $block_pattern['settings']['content'];
				}

				$args = [
					'post_author' => $customer_id,
					'post_status' => 'draft',
					'post_title'  => $post_title,
					'post_type'   => $post_type,
				];

				if ( $post_content ) {
					$args['post_content'] = $post_content;
				}

				$post_id = wp_insert_post( $args );

				if ( is_wp_error( $post_id ) ) {
					return new \WP_Error(
						'newspack_listings_create_self_serve_listing_error',
						esc_html__( 'Error creating a listing for this purchase. Please contact the site administrators to create a listing.', 'newspack-listings' )
					);
				}

				// Associate the generated post with this order.
				update_post_meta( $post_id, self::POST_META_KEYS['listing_order'], $order_id );

				// TODO: If purchasing a featured upgrade, apply featured meta.
				// TODO: Create a daily cron job to automatically unpublish posts after 30 days based on the order date (or if the subscription expires).
			}
		}
	}

	/**
	 * Once a subscription is activated, look up the listing post associated with its purchase order and
	 * associate the subscription with the listing. This will let us unpublish the associated listings
	 * when the subscription expires or is canceled.
	 *
	 * @param WC_Subscription $subscription Subscription object for the activated subscription.
	 */
	public static function listing_subscription_associate_primary_post( $subscription ) {
		$order_id = $subscription->get_parent_id();
		$listing  = self::get_listing_by_order_id( $order_id );

		if ( $listing ) {
			// Mark this subscription as a listing subscription.
			update_post_meta( $subscription->get_id(), self::SUBSCRIPTION_META_KEYS['listing_subscription'], 1 );

			// Associate the post created during purchase with this subscription.
			update_post_meta( $listing->ID, self::POST_META_KEYS['listing_subscription'], $subscription->get_id() );
		}
	}

	/**
	 * If a subscription's status changes from active to something other than active, unpublish any listings
	 * associated with that subscription.
	 *
	 * @param WC_Subscription $subscription Subscription object for the subscription whose status has changed.
	 * @param string          $new_status The string representation of the new status applied to the subscription.
	 * @param string          $old_status The string representation of the subscriptions status before the change was applied.
	 */
	public static function listing_subscription_unpublish_associated_posts( $subscription, $new_status, $old_status ) {
		if ( 'active' === $old_status && 'active' !== $new_status ) {
			$associated_listings = get_posts(
				[
					'meta_key'    => self::POST_META_KEYS['listing_subscription'],
					'meta_value'  => $subscription->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post_status' => 'publish',
					'post_type'   => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				]
			);

			foreach ( $associated_listings as $listing ) {
				wp_update_post(
					[
						'ID'          => $listing->ID,
						'post_status' => 'draft',
					]
				);
			}
		}
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
	 * For listing purchases, append links to edit the purchased listing or manage the account to the thank you message.
	 *
	 * @param string   $message Thank you message, set in Customizer for Newspack sites.
	 * @param WC_Order $order WooCommerce order object.
	 *
	 * @return string Filtered message.
	 */
	public static function listing_append_thank_you( $message, $order ) {
		if ( ! $order ) {
			return $message;
		}

		$order_id     = $order->get_id();
		$listing      = self::get_listing_by_order_id( $order_id );
		$account_page = get_option( 'woocommerce_myaccount_page_id', false );

		if ( $listing ) {
			$message .= sprintf(
				// Translators: edit listing message and link.
				__( ' You can now <a href="%1$s">edit your listing</a>%2$s.', 'newspack-listings' ),
				get_edit_post_link( $listing->ID ),
				// Translators: manage account message and link.
				$account_page ? sprintf( __( ' or <a href="%s">manage your account</a>', 'newspack-listings' ), get_permalink( $account_page ) ) : ''
			);
		}

		return $message;
	}

	/**
	 * Append an "edit listing" action button to the order action column when viewing orders in My Account.
	 *
	 * @param array    $actions Actions to be shown for each order.
	 * @param WC_Order $order WooCommerce order object.
	 *
	 * @return array Filtered actions array.
	 */
	public static function listing_append_edit_action( $actions, $order ) {
		// Rename default "View" button to avoid confusion with "View Listing" button.
		if ( isset( $actions['view'] ) ) {
			$actions['view']['name'] = __( 'View Details', 'newspack-listings' );
		}

		if ( ! $order ) {
			return $actions;
		}

		$order_id = $order->get_id();
		$listing  = self::get_listing_by_order_id( $order_id );

		if ( $listing ) {
			$actions['edit']    = [
				'url'  => get_edit_post_link( $listing->ID ),
				'name' => __( 'Edit Listing', 'newspack-listings' ),
			];
			$actions['preview'] = [
				'url'  => get_permalink( $listing->ID ),
				// Translators: view or preview listing button link.
				'name' => sprintf( __( '%s Listing', 'newspack-listings' ), 'publish' === $listing->post_status ? __( 'View', 'newspack-listings' ) : __( 'Preview', 'newspack-listings' ) ),
			];
		}

		return $actions;
	}

	/**
	 * When viewing a single listing order, append details about the listing and links to edit it.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 */
	public static function listing_append_details( $order ) {
		if ( ! $order ) {
			return;
		}

		$order_id = $order->get_id();
		$listing  = self::get_listing_by_order_id( $order_id );

		if ( $listing ) :
			?>
			<h3><?php echo esc_html__( 'Listing Details', 'newspack-listings' ); ?></h3>
			<ul>
				<li><strong><?php echo esc_html__( 'Listing Title:', 'newspack-listings' ); ?></strong> <a href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></li>
				<li><strong><?php echo esc_html__( 'Listing Status:', 'newspack-listings' ); ?></strong> <?php echo esc_html( 'publish' === $listing->post_status ? __( 'published', 'newspack-listings' ) : $listing->post_status ); ?></strong></li>
			</ul>

			<p>
				<a href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>">
					<?php echo esc_html__( 'Edit this listing', 'newspack-listings' ); ?>
				</a>

				<?php
				echo esc_html(
					sprintf(
						// Translators: listing details edit message and link.
						__( 'to update its content or %s.', 'newspack-listings' ),
						'publish' === $listing->post_status || 'pending' === $listing->post_status ? __( 'unpublish it', 'newspack-listings' ) : __( 'submit it for review', 'newspack-listings' )
					)
				);
				?>
			</p>
			<?php
		endif;
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

		$is_listing_customer = get_user_meta( $user_id, self::CUSTOMER_META_KEYS['is_listings_customer'], true );

		return $is_listing_customer;
	}

	/**
	 * Filter user capability check. Customers who have purchased a listing item have the edit_posts capability added
	 * so that they can be assigned as an author to listing posts, edit those posts, and submit for review to publish.
	 * However, we only want them to be able to edit the specific posts they've purchased, not create new ones.
	 * So we should remove the edit_posts capability if the user tries to edit another post create a new one,
	 * or access other WordPress admin pages that are usually allowed under the edit_posts capability.
	 *
	 * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name and boolean values represent whether the user has that capability.
	 * @param string[] $caps Required primitive capabilities for the requested capability.
	 * @param array    $args Arguments that accompany the requested capability check.
	 *
	 * @return bool[] Filtered array of allowed/disallowed capabilities.
	 */
	public static function allow_customers_to_edit_own_posts( $allcaps, $caps, $args ) {
		$capabilities        = [ 'edit_posts', 'edit_published_posts' ];
		$capability          = $args[0];
		$user_id             = $args[1];
		$is_listing_customer = false;

		if ( in_array( $capability, $capabilities ) && $user_id ) {
			$is_listing_customer = self::is_listing_customer( $user_id );
		}

		if ( (bool) $is_listing_customer ) {
			global $pagenow;
			$is_edit_screen = isset( $_REQUEST['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// If not an edit screen, if the post ID isn't set or isn't in the user's allowed post IDs,
			// or the user is trying to access an admin page other than the post editor, disallow.
			if ( ! $is_edit_screen || 'post.php' !== $pagenow ) {
				$allcaps[ $capability ] = 0;
			}

			// TODO: Allow creating new Marketplace or Event listings if the user has an active premium subscription,
			// and they haven't exceeded their monthly allotment for new posts.

			/**
			 * TODO: Restrict allowed blocks to only the following block categories:
			 * - Text
			 * - Media
			 * - Design
			 * - Embeds
			 */
		}

		return $allcaps;
	}

	/**
	 * For listing customers, hide all admin dashboard links. Capabilities are handled by allow_customers_to_edit_own_posts,
	 * but we also don't want these users to see any dashboard links they can't access while in the post editor.
	 */
	public static function hide_admin_menu_for_customers() {
		global $menu;
		$is_listing_customer = self::is_listing_customer();

		if ( $is_listing_customer && is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) ) {
					remove_menu_page( $item[2] );
				}
			}
		}
	}

	/**
	 * Modifies the admin bar in dashboard to hide most menu items for customers.
	 * This affects the admin bar shown at the top of the editor if not in "full-screen" mode.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP Admin Bar object.
	 */
	public static function hide_admin_bar_for_customers( $wp_admin_bar ) {
		$is_listing_customer = self::is_listing_customer();

		if ( $is_listing_customer ) {
			$nodes = $wp_admin_bar->get_nodes();

			// Allow user-related nodes to get back to "My Account" pages or to log out.
			$allowed_nodes = [
				'edit-profile',
				'logout',
				'my-account',
				'top-secondary',
				'user-actions',
				'user-info',
			];

			// Remove all the other nodes.
			foreach ( $nodes as $id => $node ) {
				if ( ! in_array( $id, $allowed_nodes ) ) {
					$wp_admin_bar->remove_node( $id );
				}
			}
		}

		return $wp_admin_bar;
	}
}

Newspack_Listings_Products::instance();
