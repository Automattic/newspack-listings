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
use \Newspack_Listings\Newspack_Listings_Featured as Featured;
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
	 * String representing the "create listing" action, for security purposes.
	 */
	const CREATE_LISTING_NONCE = 'newspack_listings_create_nonce';

	/**
	 * String representing the "delete listing" action, for security purposes.
	 */
	const DELETE_LISTING_NONCE = 'newspack_listings_delete_nonce';

	/**
	 * String representing the "renew listing" action, for security purposes.
	 */
	const RENEW_LISTING_NONCE = 'newspack_listings_renew_nonce';

	/**
	 * String representing the cron jobs to expire single-purchase listings.
	 */
	const EXPIRE_LISTING_CRON_HOOK = 'newspack_expire_listings';

	/**
	 * Number of free listings granted to premium subscribers.
	 */
	const TOTAL_FREE_LISTINGS = 10;

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
		'listing_purchase_type'  => 'newspack_listings_order_type',
		'listing_original_order' => 'newspack_listings_original_order',
		'listing_renewed'        => 'newspack_listings_renewed_id',
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
		add_action( 'wp_loaded', [ __CLASS__, 'handle_purchase_form' ], 99 );
		add_action( 'wp_loaded', [ __CLASS__, 'create_or_delete_premium_listing' ], 99 );

		// When product settings are updated, make sure to update the corresponding WooCommerce products as well.
		add_action( 'update_option', [ __CLASS__, 'update_products' ], 10, 3 );

		// WooCommerce checkout actions (when purchasing a listing product).
		add_filter( 'pre_option_woocommerce_enable_guest_checkout', [ __CLASS__, 'force_require_account_for_listings' ] );
		add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', [ __CLASS__, 'force_account_creation_and_login_for_listings' ] );
		add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', [ __CLASS__, 'force_account_creation_and_login_for_listings' ] );
		add_action( 'woocommerce_checkout_billing', [ __CLASS__, 'listing_details_summary' ] );
		add_filter( 'woocommerce_billing_fields', [ __CLASS__, 'listing_details_billing_fields' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'listing_checkout_update_order_meta' ] );
		add_action( 'woocommerce_thankyou_order_received_text', [ __CLASS__, 'listing_append_thank_you' ], 99, 2 );

		// WooCommerce account actions (post-purchase).
		add_action( 'woocommerce_my_account_my_orders_actions', [ __CLASS__, 'listing_append_edit_action' ], 10, 2 );
		add_filter( 'woocommerce_my_account_my_orders_columns', [ __CLASS__, 'listing_order_status_column' ] );
		add_action( 'woocommerce_my_account_my_orders_column_order-listing-status', [ __CLASS__, 'listing_order_status_column_content' ] );
		add_action( 'woocommerce_order_details_after_order_table', [ __CLASS__, 'listing_append_details' ] );
		add_action( 'woocommerce_subscription_status_active', [ __CLASS__, 'listing_subscription_associate_primary_post' ] );
		add_action( 'woocommerce_subscription_status_updated', [ __CLASS__, 'listing_subscription_unpublish_associated_posts' ], 10, 3 );
		add_action( 'woocommerce_subscription_details_after_subscription_table', [ __CLASS__, 'listing_subscription_manage_premium_listings' ] );
		add_action( 'wcs_user_removed_item', [ __CLASS__, 'listing_subscription_removed_item' ], 10, 2 );
		add_action( 'wcs_user_readded_item', [ __CLASS__, 'listing_subscription_readded_item' ], 10, 2 );

		// WP user actions to add capabilities and admin access for WC customers, who normally can't access the post editor.
		add_filter( 'user_has_cap', [ __CLASS__, 'allow_customers_to_edit_own_posts' ], 10, 3 );
		add_filter( 'allowed_block_types_all', [ __CLASS__, 'restrict_blocks_for_customers' ], 10, 2 );
		add_action( 'admin_menu', [ __CLASS__, 'hide_admin_menu_for_customers' ], 1000 ); // Late execution to override other plugins like Jetpack.
		add_filter( 'admin_bar_menu', [ __CLASS__, 'hide_admin_bar_for_customers' ], 1000 ); // Late execution to override other plugins like Jetpack.

		// Handle expiration for single-purchase listings.
		add_action( 'init', [ __CLASS__, 'cron_init' ] );
		add_action( self::EXPIRE_LISTING_CRON_HOOK, [ __CLASS__, 'expire_single_purchase_listings' ] );
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
	public static function force_require_account_for_listings( $value ) {
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
	public static function force_account_creation_and_login_for_listings( $value ) {
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
		$listing_to_renew   = filter_input( INPUT_GET, 'listing-renew', FILTER_SANITIZE_NUMBER_INT );
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
			'listing-title'         => sanitize_text_field( $listing_title ),
			'listing-purchase-type' => sanitize_text_field( $purchase_type ),
		];

		if ( $is_single ) {
			$products_to_purchase[]              = $products[ self::PRODUCT_META_KEYS['single'] ];
			$checkout_query_args['listing-type'] = sanitize_text_field( $single_type );

			if ( ! empty( $listing_to_renew ) ) {
				$checkout_query_args['listing_renewed'] = $listing_to_renew;
			}

			if ( 'on' === $featured_upgrade ) {
				$products_to_purchase[] = $products[ self::PRODUCT_META_KEYS['featured'] ];
			}
		} else {
			$products_to_purchase[] = $products[ self::PRODUCT_META_KEYS['subscription'] ];

			if ( 'on' === $premium_upgrade ) {
				$products_to_purchase[] = $products[ self::PRODUCT_META_KEYS['premium'] ];
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
		$listing_title = isset( $params['listing-title'] ) ? $params['listing-title'] : null;
		$listing_types = self::get_listing_types();
		$is_renewal    = $params['listing_renewed'];
		$listing_type  = array_reduce(
			$listing_types,
			function( $acc, $type ) use ( $params ) {
				if ( isset( $params['listing-type'] ) && $type['slug'] === $params['listing-type'] ) {
					$acc = $type['name'];
				}
				return $acc;
			},
			null
		);

		if ( $listing_title || $listing_type ) : ?>
			<h4><?php echo esc_html__( 'Listing Details', 'newspack-listings' ); ?></h4>
			<?php if ( $is_renewal ) : ?>
				<?php $single_expiration_period = Settings::get_settings( 'newspack_listings_single_purchase_expiration' ); ?>
				<p>
					<?php
						echo esc_html(
							sprintf(
								// Translators: if renewing, explain what that does.
								__( 'The following listing will be renewed for %d days from today:', 'newspack-listings' ),
								$single_expiration_period
							)
						);
					?>
				</p>
			<?php else : ?>
				<p><?php echo esc_html__( 'You can update listing details after purchase.', 'newspack-listings' ); ?></p>
			<?php endif; ?>
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
	 * This is the big function that ties together Newspack Listings with WooCommerce functionality.
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

				// Get info on purchased products.
				$products        = self::get_products();
				$purchased_items = array_map(
					function( $item ) {
						return $item->get_product_id();
					},
					$order->get_items()
				);

				$purchase_type    = isset( $params['listing-purchase-type'] ) ? $params['listing-purchase-type'] : 'single';
				$is_subscription  = 'subscription' === $purchase_type && in_array( $products[ self::PRODUCT_META_KEYS['subscription'] ], $purchased_items );
				$is_single        = ! $is_subscription && in_array( $products[ self::PRODUCT_META_KEYS['single'] ], $purchased_items );
				$listing_type     = isset( $params['listing-type'] ) ? $params['listing-type'] : null;
				$single_upgrade   = $is_single && in_array( $products[ self::PRODUCT_META_KEYS['featured'] ], $purchased_items );
				$premium_upgrade  = $is_subscription && in_array( $products['newspack_listings_premium_subscription_add_on'], $purchased_items );
				$post_title       = isset( $params['listing-title'] ) ? $params['listing-title'] : __( 'Untitled listing', 'newspack-listings' );
				$post_type        = Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'];
				$listing_to_renew = isset( $params['listing_renewed'] ) ? $params['listing_renewed'] : null;
				$post_content     = false;
				$block_pattern    = false;
				$subscriptions    = false;

				// If we're rewnewing a previously purchased listing, republish it instead of creating a new listing.
				if ( $listing_to_renew ) {
					$listing = get_post( (int) $listing_to_renew );

					if ( $listing ) {
						$original_order_id = get_post_meta( $listing->ID, self::POST_META_KEYS['listing_order'], true );
						$now               = current_time( 'mysql' ); // Current time in local timezone.

						// When renewing a single-purchase post, set the post status to 'publish' and
						// also update the publish date to "now" to reset the expiration clock.
						wp_update_post(
							[
								'ID'            => $listing->ID,
								'post_date'     => $now,
								'post_date_gmt' => get_gmt_from_date( $time ),
								'post_status'   => 'publish',
							]
						);

						// Clear "expired" meta flag so that the listing is no longer displayed as expired in the My Account UI.
						delete_post_meta( $listing->ID, self::POST_META_KEYS['listing_has_expired'] );

						// Associate the original order with the new order so we can show details in the Order Details screen.
						if ( $original_order_id ) {
							update_post_meta( $order_id, sanitize_text_field( self::ORDER_META_KEYS['listing_original_order'] ), $original_order_id );
						}
					} else {
						return new \WP_Error(
							'newspack_listings_renew_self_serve_listing_error',
							sprintf(
								// Translators: error message when we're not able to renew the given post ID.
								__( 'Error renewing listing with ID %d. Please contact the site administrators to renew.', 'newspack-listings' ),
								$listing_to_renew
							)
						);
					}

					return;
				}

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

				// If purchasing an upgrade product, set the post to featured status.
				if ( $single_upgrade || $premium_upgrade ) {
					Featured::set_featured_status( $post_id );
				}
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

			// If the order included a premium upgrade, mark this subscription as premium.
			$order           = \wc_get_order( $order_id );
			$products        = self::get_products();
			$purchased_items = array_map(
				function( $item ) {
					return $item->get_product_id();
				},
				$order->get_items()
			);
			$premium_upgrade = in_array( $products[ self::PRODUCT_META_KEYS['premium'] ], $purchased_items );
			if ( $premium_upgrade ) {
				update_post_meta( $subscription->get_id(), self::SUBSCRIPTION_META_KEYS['is_premium'], 1 );
			}
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
	 * Get the base URL of the current page or the My Account page, stripped of query args.
	 *
	 * @return string Cleaned URL.
	 */
	public static function get_base_url() {
		return isset( $_SERVER['REQUEST_URI'] ) ? site_url( strtok( sanitize_text_field( $_SERVER['REQUEST_URI'] ), '?' ) ) : get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
	}

	/**
	 * Active premium subscriptions grant customers the ability to create up to 10 Marketplace or Event listings for free.
	 * Show controls to create and manage these listings in the Subscription account page.
	 *
	 * @param WC_Subscription $subscription Subscription object for the subscription whose status has changed.
	 */
	public static function listing_subscription_manage_premium_listings( $subscription ) {
		$subscription_id  = $subscription->get_id();
		$is_active        = 'active' === $subscription->get_status();
		$is_premium       = $is_active && get_post_meta( $subscription_id, self::SUBSCRIPTION_META_KEYS['is_premium'], true );
		$customer_id      = $subscription->get_user_id();
		$premium_listings = get_posts(
			[
				'meta_key'       => self::POST_META_KEYS['listing_subscription'],
				'meta_value'     => $subscription->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'post_status'    => [ 'draft', 'future', 'pending', 'private', 'publish' ],
				'post_type'      => [
					Core::NEWSPACK_LISTINGS_POST_TYPES['event'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
				],
				'posts_per_page' => self::TOTAL_FREE_LISTINGS,
			]
		);

		// If not a premium subscription and there are no previously created premium listings, no need to show the UI.
		if ( ! $is_premium && 0 === count( $premium_listings ) ) {
			return;
		}

		$remaining       = self::TOTAL_FREE_LISTINGS - count( $premium_listings );
		$base_url        = self::get_base_url();
		$marketplace_url = wp_nonce_url(
			add_query_arg(
				[
					'customer_id'     => $customer_id,
					'subscription_id' => $subscription_id,
					'create'          => 'marketplace',
				],
				$base_url
			),
			self::CREATE_LISTING_NONCE
		);
		$event_url       = wp_nonce_url(
			add_query_arg(
				[
					'customer_id'     => $customer_id,
					'subscription_id' => $subscription_id,
					'create'          => 'event',
				],
				$base_url
			),
			self::CREATE_LISTING_NONCE
		);

		?>
			<h2><?php echo esc_html( __( 'Premium subscription listings', 'newspack-listings' ) ); ?></h2>
			<p>
				<?php
					echo esc_html(
						sprintf(
							// translators: explanation of premium subscription benefits.
							__( 'A premium subscription lets you create up to %1$d free Marketplace or Event listings. %2$s', 'newspack-listings' ),
							self::TOTAL_FREE_LISTINGS,
							$remaining && $is_premium ?
								// translators: explanation of remaining free listings.
								sprintf( __( 'You have %d free listings remaining.', 'newspack-listings' ), $remaining ) :
								__( 'You don’t have any free listings available. To create additional listings, please purchase them.', 'newspack-listings' )
						)
					);
				?>
			</p>
		<?php

		// To create new listings, subscription must be active and premium, and must have not used up all free listings.
		if ( $is_premium && $remaining ) :
			?>
			<p>
				<a class="woocommerce-button button" href="<?php echo esc_url( $marketplace_url ); ?>"><?php echo esc_html__( 'Create New Marketplace Listing', 'newspack-listings' ); ?></a>
				<a class="woocommerce-button button" href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html__( 'Create New Event Listing', 'newspack-listings' ); ?></a>
			</p>
			<?php
		endif;

		// Show a table with previously created premium listings. This is shown even if the subscription is no longer premium (so that the user can still see their own previously created listings), but if that's the case the "edit" button will no longer be available.
		if ( 0 < count( $premium_listings ) ) :
			?>
			<table class="shop_table shop_table_responsive my_account_orders">
				<thead>
					<tr>
						<th class="woocommerce-orders-table__header"><?php echo esc_html__( 'Listing Title', 'newspack-listings' ); ?></th>
						<th class="woocommerce-orders-table__header"><?php echo esc_html__( 'Listing Status', 'newspack-listings' ); ?></th>
						<th class="woocommerce-orders-table__header"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $premium_listings as $listing ) : ?>
						<?php
						$trash_listing_url = wp_nonce_url(
							add_query_arg(
								[
									'customer_id'     => $customer_id,
									'subscription_id' => $subscription_id,
									'delete'          => $listing->ID,
									'redirect_uri'    => urlencode( $base_url ),
								],
								$base_url
							),
							self::DELETE_LISTING_NONCE
						);
						?>
					<tr>
						<td class="woocommerce-orders-table__cell"><a href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>"><?php echo wp_kses_post( $listing->post_title ); ?></a></td>
						<td class="woocommerce-orders-table__cell"><?php echo esc_html( self::get_listing_status( $listing ) ); ?></td>
						<td class="woocommerce-orders-table__cell">
							<?php if ( $is_premium ) : ?>
								<a class="woocommerce-button button" href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>"><?php echo esc_html__( 'Edit', 'newspack-listings' ); ?></a>
							<?php endif; ?>
							<a class="woocommerce-button button" href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>"><?php echo esc_html( 'publish' === $listing->post_status ? __( 'View', 'newspack-listings' ) : __( 'Preview', 'newspack-listings' ) ); ?></a>
							<a class="woocommerce-button button" href="<?php echo esc_url( $trash_listing_url ); ?>" onclick="return confirm(' <?php echo esc_html__( 'Are you sure you want to delete this listing? This cannot be undone.', 'newspack-listings' ); ?> ');"><?php echo esc_html__( 'Delete', 'newspack-listings' ); ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;
	}

	/**
	 * Intercept GET params and create a Marketplace or Event listing for a premium subscription.
	 */
	public static function create_or_delete_premium_listing() {
		$nonce           = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
		$customer_id     = filter_input( INPUT_GET, 'customer_id', FILTER_SANITIZE_STRING );
		$subscription_id = filter_input( INPUT_GET, 'subscription_id', FILTER_SANITIZE_STRING );
		$post_type_slug  = filter_input( INPUT_GET, 'create', FILTER_SANITIZE_STRING );
		$delete_post     = filter_input( INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT );
		$redirect_uri    = urldecode( filter_input( INPUT_GET, 'redirect_uri', FILTER_SANITIZE_STRING ) );

		// Only if WC is active.
		if ( ! self::$wc_is_active ) {
			return;
		}

		// Only if we have all of the required query args.
		if ( ! $customer_id || ! $subscription_id || ( ! $delete_post && 'event' !== $post_type_slug && 'marketplace' !== $post_type_slug ) ) {
			return;
		}

		// If deleting the post.
		if ( $delete_post && $redirect_uri ) {
			// Check nonce in case someone tries to delete a listing by visiting the URL with the expected params.
			if ( ! wp_verify_nonce( $nonce, self::DELETE_LISTING_NONCE ) ) {
				return;
			}

			wp_trash_post( $delete_post );
			wp_safe_redirect( $redirect_uri );
			exit;
		}

		// Check nonce in case someone tries to create a listing by visiting the URL with the expected params.
		if ( ! wp_verify_nonce( $nonce, self::CREATE_LISTING_NONCE ) ) {
			return;
		}

		// Get order info and remaining premium listings.
		$subscription = new \WC_Subscription( $subscription_id );
		$args         = [
			'post_author' => $customer_id,
			'post_status' => 'draft',
			'post_title'  => sprintf(
				// translators: default "untitled" listing title.
				__( 'Untitled %s listing', 'newspack-listings' ),
				'event' === $post_type_slug ? __( 'event', 'newspack-listings' ) : __( 'marketplace', 'newspack-listings' )
			),
			'post_type'   => 'event' === $post_type_slug ? Core::NEWSPACK_LISTINGS_POST_TYPES['event'] : Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
		];
		$post_id      = wp_insert_post( $args );

		if ( $post_id ) {
			// Associate the new post with this subscription order.
			update_post_meta( $post_id, self::POST_META_KEYS['listing_subscription'], $subscription_id );

			// Redirect to post editor.
			wp_safe_redirect( html_entity_decode( get_edit_post_link( $post_id ) ) );
			exit;
		} else {
			return new \WP_Error(
				'newspack_listings_create_listing_error',
				__( 'There was an error creating your listing. Please try again or contact the site administrators for help.', 'newspack-listings' )
			);
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

		$order_id          = $order->get_id();
		$original_order_id = get_post_meta( $order_id, self::ORDER_META_KEYS['listing_original_order'], true );

		if ( $original_order_id ) {
			$order_id = $original_order_id;
		}

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
			if ( get_post_meta( $listing->ID, self::POST_META_KEYS['listing_has_expired'], true ) ) {
				$listing_type = Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'] === $listing->post_type ? 'marketplace' : 'event';
				$renew_url    = add_query_arg(
					[
						'listing-purchase-type' => 'single',
						'listing-renew'         => $listing->ID,
						'listing-title-single'  => $listing->post_title,
						'listing-single-type'   => $listing_type,
					],
					self::get_base_url()
				);

				$actions['renew'] = [
					'url'  => $renew_url,
					'name' => __( 'Renew Listing', 'newspack-listings' ),
				];
			} else {
				$actions['edit'] = [
					'url'  => get_edit_post_link( $listing->ID ),
					'name' => __( 'Edit Listing', 'newspack-listings' ),
				];
			}
			$actions['preview'] = [
				'url'  => get_permalink( $listing->ID ),
				// Translators: view or preview listing button link.
				'name' => sprintf( __( '%s Listing', 'newspack-listings' ), 'publish' === $listing->post_status ? __( 'View', 'newspack-listings' ) : __( 'Preview', 'newspack-listings' ) ),
			];
		}

		// If this order was a renewal of an existing listing, link back to the original order.
		$original_order_id = get_post_meta( $order_id, self::ORDER_META_KEYS['listing_original_order'], true );
		if ( $original_order_id ) {
			$original_order = \wc_get_order( $original_order_id );

			if ( $original_order ) {
				$actions['original'] = [
					'url'  => $original_order->get_view_order_url(),
					'name' => __( 'View Original Order', 'newspack-listings' ),
				];
			}
		}

		return $actions;
	}

	/**
	 * Add a column to display the listing's current status in the My Orders table.
	 *
	 * @param array $columns Array of table columns.
	 *
	 * @return array Filtered array of table columns.
	 */
	public static function listing_order_status_column( $columns ) {
		$new_columns = [];

		foreach ( $columns as $key => $name ) {
			$new_columns[ $key ] = $name;

			// Add Listing Status column after Total column.
			if ( 'order-total' === $key ) {
				$new_columns['order-listing-status'] = __( 'Listing Status', 'newspack-listings' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content in the new Listing Status column in the My Orders table.
	 * This column is rendered for every order if the Listings plugin is enabled,
	 * but will only show content for orders that represent a listings purchase.
	 *
	 * @param WC_Order $order Order object for the current table row.
	 */
	public static function listing_order_status_column_content( $order ) {
		$order_id          = $order->get_id();
		$original_order_id = get_post_meta( $order_id, self::ORDER_META_KEYS['listing_original_order'], true );

		// If this order was a renewal of an existing listing, use the original order ID to get the listing details.
		if ( $original_order_id ) {
			$order_id = $original_order_id;
		}

		$listing = self::get_listing_by_order_id( $order_id );
		if ( $listing ) {
			$status                  = self::get_listing_status( $listing );
			$expired_or_expires_soon = 'expired' === $status || false !== stripos( $status, 'expires soon' ); // TODO: refactor to handle non-English sites.
			if ( $expired_or_expires_soon ) :
				?>
				<mark class="order-status">
				<?php
			endif;

			echo esc_html( $status );

			if ( $expired_or_expires_soon ) :
				?>
				</mark>
				<?php
			endif;
		} else {
			// Translators: status to output when the current order is not a listing, a.k.a. "not available".
			echo esc_html__( 'n/a', 'newspack-listings' );
		}
	}

	/**
	 * Get the string representation of the given listing's post status.
	 *
	 * @param WP_Post $listing WP Post object representing a listing.
	 *
	 * @return string Listing status: 'published', 'pending', 'future', 'draft', or 'expired'.
	 */
	public static function get_listing_status( $listing ) {
		if ( ! $listing->ID ) {
			return __( 'Listing not found: please contact the site administrators.', 'newspack-listings' );
		}

		// If the listing is flagged as expired.
		if ( get_post_meta( $listing->ID, self::POST_META_KEYS['listing_has_expired'], true ) ) {
			return __( 'expired', 'newspack-listings' );
		}

		$single_expiration_period = Settings::get_settings( 'newspack_listings_single_purchase_expiration' );
		$is_published             = 'publish' === $listing->post_status;
		if ( $is_published && 0 < $single_expiration_period ) {
			$publish_date = new \DateTime( $listing->post_date );
			$expires_soon = ( $publish_date->getTimestamp() < strtotime( '-' . strval( $single_expiration_period - 3 ) . ' days' ) );

			// Warn users when a listing will expire within 3 days.
			if ( $expires_soon ) {
				return __( 'published (expires soon)', 'newspack-listings' );
			}
		}

		return $is_published ? __( 'published', 'newspack-listings' ) : $listing->post_status;
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

		$order_id          = $order->get_id();
		$original_order    = false;
		$original_order_id = get_post_meta( $order_id, self::ORDER_META_KEYS['listing_original_order'], true );

		// If this order was a renewal of an existing listing, use the original order ID to get the listing details.
		if ( $original_order_id ) {
			$order_id       = $original_order_id;
			$original_order = \wc_get_order( $original_order_id );
		}

		// Get the listing associated with this order ID.
		$listing = self::get_listing_by_order_id( $order_id );

		if ( $listing ) :
			$status       = self::get_listing_status( $listing );
			$is_expired   = 'expired' === $status;
			$is_published = 'publish' === $listing->post_status;
			$listing_type = Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'] === $listing->post_type ? 'marketplace' : 'event';
			$renew_url    = add_query_arg(
				[
					'listing-purchase-type' => 'single',
					'listing-renew'         => $listing->ID,
					'listing-title-single'  => $listing->post_title,
					'listing-single-type'   => $listing_type,
				],
				self::get_base_url()
			);

			$single_expiration_period = Settings::get_settings( 'newspack_listings_single_purchase_expiration' );

			?>
			<h3><?php echo esc_html__( 'Listing Details', 'newspack-listings' ); ?></h3>

			<?php if ( $original_order ) : ?>
				<p>
					<?php echo esc_html__( 'This order was a renewal of an expired listing.', 'newspack-listings' ); ?>
					<a href="<?php echo esc_url( $original_order->get_view_order_url() ); ?>"><?php echo esc_html__( 'Click here to view the original order details', 'newspack-listings' ); ?></a>.
				</p>
			<?php endif; ?>
			<ul>
				<li><strong><?php echo esc_html__( 'Listing Title:', 'newspack-listings' ); ?></strong> <a href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></li>
				<li><strong><?php echo esc_html__( 'Listing Status:', 'newspack-listings' ); ?></strong> <?php echo esc_html( $status ); ?></strong></li>
			</ul>

			<?php if ( $is_expired ) : ?>
				<p><?php echo esc_html__( 'Your listing has expired and is no longer published.', 'newspack-listings' ); ?></p>
			<?php else : ?>
				<p>
					<a href="<?php echo esc_url( get_edit_post_link( $listing->ID ) ); ?>">
						<?php echo esc_html__( 'Edit this listing', 'newspack-listings' ); ?>
					</a>

					<?php
					echo esc_html(
						sprintf(
							// Translators: listing details edit message and link.
							__( 'to update its content or %s. ', 'newspack-listings' ),
							'publish' === $listing->post_status || 'pending' === $listing->post_status ? __( 'unpublish it', 'newspack-listings' ) : __( 'submit it for review', 'newspack-listings' )
						)
					);
					?>
				</p>
				<?php
			endif;

			if ( $is_published || $is_expired ) :
				$expires_in = '';

				if ( $is_published && ! $is_expired ) {
					$expires_date = new \DateTime( $listing->post_date );
					$expires_date->modify( '+' . (string) $single_expiration_period . ' days' );

					$date_diff  = $expires_date->diff( new \DateTime() );
					$expires_in = sprintf(
						// Translators: message describing how many days are left before this listing expires.
						__( 'This listing expires in %d days. ' ),
						$date_diff->days
					);
				}
				?>
				<p>
					<?php
						echo esc_html(
							sprintf(
								// Translators: message explaining how many days single-purchase listings are active, and how to renew.
								__( 'Listings are active for %1$d days after publication. %2$sTo renew for another %3$d days:', 'newspack-listings' ),
								$single_expiration_period,
								$expires_in,
								$single_expiration_period
							)
						);
					?>
				</p>
				<p><a href="<?php echo esc_url( $renew_url ); ?>" class="button"><?php esc_html_e( 'Renew', 'woocommerce' ); ?></a></p>
				<?php
			endif;

			if ( $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', [ 'completed' ] ) ) ) :
				?>
				<p><?php echo esc_html__( 'To quickly purchase a new blank Marketplace listing:', 'newspack-listings' ); ?></p>
				<?php
			endif;
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

		$user_meta      = get_userdata( $user_id );
		$user_roles     = $user_meta->roles;
		$customer_roles = [ 'subscriber', 'customer' ]; // Avoid limiting capabilities for contributor/author/editor/admin roles.

		$is_listing_customer = 0 < count( array_intersect( $user_roles, $customer_roles ) ) && get_user_meta( $user_id, self::CUSTOMER_META_KEYS['is_listings_customer'], true );

		return $is_listing_customer;
	}

	/**
	 * When a subscription product is removed from a listing subscription, also unpublish the primary listing, if published.
	 * WHen an upgrade product is removed from a premium subscription, unset the Featured status of the primary listing.
	 * Also disallow creation of new related Marketplace listings.
	 *
	 * @param WC_Product      $line_item Product object of the item removed from the subscription.
	 * @param WC_Subscription $subscription Subscription object from which the product was removed.
	 */
	public static function listing_subscription_removed_item( $line_item, $subscription ) {
		$product_id              = $line_item->get_product_id();
		$products                = self::get_products();
		$is_subscription_product = $product_id === $products[ self::PRODUCT_META_KEYS['subscription'] ];
		$is_premium_product      = $product_id === $products[ self::PRODUCT_META_KEYS['premium'] ];
		$associated_listings     = [];

		if ( $is_subscription_product || $is_premium_product ) {
			$associated_listings = get_posts(
				[
					'meta_key'    => self::POST_META_KEYS['listing_subscription'],
					'meta_value'  => $subscription->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post_status' => 'publish',
					'post_type'   => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				]
			);
		}

		// If removing the subscription product, unpublish any associated listings.
		if ( $is_subscription_product ) {
			foreach ( $associated_listings as $listing ) {
				wp_update_post(
					[
						'ID'          => $listing->ID,
						'post_status' => 'draft',
					]
				);
			}
		}

		// If removing the premium upgrade product, unset the featured status of any associated listings.
		if ( $is_premium_product ) {
			delete_post_meta( $subscription->get_id(), self::SUBSCRIPTION_META_KEYS['is_premium'] );

			foreach ( $associated_listings as $listing ) {
				Featured::unset_featured_status( $listing->ID, true );
			}
		}
	}

	/**
	 * When a product is removed from a subscription, the customer has an opportunity to undo this action.
	 * When undoing a product removal, we should restore the Featured status of any premium subscription items
	 * and re-allow creation of new related Marketplace listings.
	 *
	 * If a primary listing was unpublished due to removal of a subscription product, the customer will need to
	 * resubmit the listing for review before it can be published again. This is to prevent a loophole where
	 * customers could theoretically publish changes without editorial approval by removing and readding the
	 * subscription product from their subscription.
	 *
	 * @param WC_Product      $line_item Product object of the item re-added to the subscription.
	 * @param WC_Subscription $subscription Subscription object to which the product was re-added.
	 */
	public static function listing_subscription_readded_item( $line_item, $subscription ) {
		$product_id          = $line_item->get_product_id();
		$products            = self::get_products();
		$is_premium_product  = $product_id === $products[ self::PRODUCT_META_KEYS['premium'] ];
		$associated_listings = [];

		// If re-adding the premium upgrade product, reset the featured status for any associated listings.
		// Feature priority will be reset to the default (5) since there's no way to retrieve older values.
		if ( $is_premium_product ) {
			update_post_meta( $subscription->get_id(), self::SUBSCRIPTION_META_KEYS['is_premium'], 1 );
			$associated_listings = get_posts(
				[
					'meta_key'    => self::POST_META_KEYS['listing_subscription'],
					'meta_value'  => $subscription->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post_status' => 'publish',
					'post_type'   => array_values( Core::NEWSPACK_LISTINGS_POST_TYPES ),
				]
			);

			foreach ( $associated_listings as $listing ) {
				$priority = Featured::get_priority( $listing->ID );

				if ( ! $priority ) {
					Featured::set_featured_status( $listing->ID );
				}
			}
		}
	}

	/**
	 * Filter user capability check. Customers who have purchased a listing item have the edit_posts capability added
	 * so that they can be assigned as an author to listing posts, edit those posts, and submit for review to publish.
	 * However, we only want them to be able to edit the specific posts they've purchased, not create new ones.
	 * So we should remove the edit_posts capability if the user tries to edit another post create a new one,
	 * or access other WordPress admin pages that are usually allowed under the edit_posts capability.
	 *
	 * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name and
	 *                          boolean values represent whether the user has that capability.
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
			$actions        = [ 'edit', 'editposts' ];
			$action         = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( sanitize_text_field( $_REQUEST['action'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_edit_screen = $action && in_array( $action, $actions );

			// If not an edit screen, if the post ID isn't set or isn't in the user's allowed post IDs,
			// or the user is trying to access an admin page other than the post editor, disallow.
			if ( ! $is_edit_screen || 'post.php' !== $pagenow ) {
				$allcaps[ $capability ] = 0;
			}
		}

		return $allcaps;
	}

	/**
	 * Customer users should have access to a basic set of core blocks only.
	 *
	 * @param bool|array              $allowed_block_types Array of block type slugs, or boolean to enable/disable all. Default true (all registered block types supported).
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 *
	 * @return bool|array Filtered boolean or array of allowed blocks.
	 */
	public static function restrict_blocks_for_customers( $allowed_block_types, $block_editor_context ) {
		$is_listing_customer = self::is_listing_customer();

		if ( $is_listing_customer ) {
			$allowed_block_types = [
				'core/paragraph',
				'core/image',
				'core/heading',
				'core/gallery',
				'core/list',
				'core/quote',
				'core/audio',
				'core/button',
				'core/buttons',
				'core/calendar',
				'core/code',
				'core/columns',
				'core/column',
				'core/cover',
				'core/embed',
				'core/file',
				'core/group',
				'core/freeform',
				'core/html',
				'core/media-text',
				'core/more',
				'core/nextpage',
				'core/preformatted',
				'core/pullquote',
				'core/rss',
				'core/search',
				'core/separator',
				'core/block',
				'core/social-links',
				'core/social-link',
				'core/spacer',
				'core/table',
				'core/text-columns',
				'core/verse',
				'core/video',
				'core/site-logo',
				'core/site-tagline',
				'core/site-title',
				'core/post-title',
				'core/post-content',
				'core/post-date',
				'core/post-excerpt',
				'core/post-featured-image',
				'core/post-terms',
				'jetpack/business-hours',
				'jetpack/button',
				'jetpack/field-text',
				'jetpack/field-name',
				'jetpack/field-email',
				'jetpack/field-url',
				'jetpack/field-date',
				'jetpack/field-telephone',
				'jetpack/field-textarea',
				'jetpack/field-checkbox',
				'jetpack/field-consent',
				'jetpack/field-checkbox-multiple',
				'jetpack/field-radio',
				'jetpack/field-select',
				'jetpack/contact-info',
				'jetpack/address',
				'jetpack/email',
				'jetpack/phone',
				'jetpack/gif',
				'jetpack/image-compare',
				'jetpack/instagram-gallery',
				'jetpack/map',
				'jetpack/markdown',
				'jetpack/opentable',
				'jetpack/pinterest',
				'jetpack/podcast-player',
				'jetpack/rating-star',
				'jetpack/repeat-visitor',
				'jetpack/send-a-message',
				'jetpack/whatsapp-button',
				'jetpack/simple-payments',
				'jetpack/slideshow',
				'jetpack/story',
				'jetpack/tiled-gallery',
				'newspack-listings/event-dates',
				'newspack-listings/price',
			];
		}

		return $allowed_block_types;
	}

	/**
	 * For listing customers, hide all admin dashboard links. Capabilities are handled by allow_customers_to_edit_own_posts,
	 * but we also don't want these users to see any dashboard links they can't access while in the post editor.
	 */
	public static function hide_admin_menu_for_customers() {
		global $menu;
		$is_listing_customer = self::is_listing_customer();

		if ( $is_listing_customer && is_array( $menu ) ) {
			$allowed_items = [ 'index.php' ];
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && ! in_array( $item[2], $allowed_items ) ) {
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
				'wp-logo',
				'site-name',
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

	/**
	 * Set up the cron job. Will run once daily and automatically unpublish single-purchase listings
	 * whose publish dates are older than the expiration period defined in plugin settings.
	 */
	public static function cron_init() {
		register_deactivation_hook( NEWSPACK_LISTINGS_FILE, [ __CLASS__, 'cron_deactivate' ] );

		$single_expiration_period = Settings::get_settings( 'newspack_listings_single_purchase_expiration' );
		if ( 0 < $single_expiration_period ) {
			if ( ! wp_next_scheduled( self::EXPIRE_LISTING_CRON_HOOK ) ) {
				wp_schedule_event( Utils\get_next_midnight(), 'daily', self::EXPIRE_LISTING_CRON_HOOK );
			}
		} else {
			if ( wp_next_scheduled( self::EXPIRE_LISTING_CRON_HOOK ) ) {
				self::cron_deactivate(); // If the option has been updated to 0, no need to run the cron job.
			}
		}
	}

	/**
	 * Clear the cron job when this plugin is deactivated.
	 */
	public static function cron_deactivate() {
		wp_clear_scheduled_hook( self::EXPIRE_LISTING_CRON_HOOK );
	}

	/**
	 * Callback function to expire single-purchase listings whose publish date is older than the set expiration period.
	 * Single-purchase listings can be distinguished because they should have an order ID meta value, but no subscription ID.
	 * Subscription primary listings have both an order ID and a subscription ID.
	 * Premium subscription "free" listings have a subscription ID, but no order ID.
	 */
	public static function expire_single_purchase_listings() {
		$single_expiration_period = Settings::get_settings( 'newspack_listings_single_purchase_expiration' );

		if ( 0 < $single_expiration_period ) {
			$args = [
				'post_status' => 'publish',
				'post_type'   => [
					Core::NEWSPACK_LISTINGS_POST_TYPES['event'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
				],
				'date_query'  => [
					'before' => (string) $single_expiration_period . ' days ago',
				],
				'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					[
						'key'     => self::POST_META_KEYS['listing_order'],
						'compare' => 'EXISTS',
					],
					[
						'key'     => self::POST_META_KEYS['listing_subscription'],
						'compare' => 'NOT EXISTS',
					],
				],
			];

			Utils\execute_callback_with_paged_query( $args, [ __CLASS__, 'expire_single_purchase_listing' ] );
		} else {
			self::cron_deactivate(); // If the option has been updated to 0, no need to run the cron job.
		}
	}

	/**
	 * Given a post ID for a published post, unpublish it and flag it as expired.
	 *
	 * @param int $post_id ID for the post to expire.
	 */
	public static function expire_single_purchase_listing( $post_id ) {
		if ( $post_id ) {
			$updated = wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => 'draft',
				]
			);

			if ( $updated ) {
				update_post_meta( $post_id, self::POST_META_KEYS['listing_has_expired'], 1 );
			} else {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						// Translators: error message logged when we're unable to expire a listing via cron job.
						__( 'Newspack Listings: Error expiring listing with ID %d.', 'newspack-listings' ),
						$post_id
					)
				);
			}
		}
	}
}

Newspack_Listings_Products::instance();
