<?php
/**
 * Newspack Listings Settings Page
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Manages Settings page.
 */
final class Newspack_Listings_Settings {
	/**
	 * Set up hooks.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'page_init' ] );
	}

	/**
	 * Default values for site-wide settings.
	 *
	 * @return array Array of default settings.
	 */
	public static function get_default_settings() {
		return [
			[
				'description' => __( 'The URL prefix for all listings. This prefix will appear before the listing slug in all listing URLs.', 'newspack-listings' ),
				'key'         => 'newspack_listings_permalink_prefix',
				'label'       => __( 'Listings permalink prefix', 'newspack-listings' ),
				'type'        => 'input',
				'value'       => __( 'listings', 'newspack-listings' ),
			],
			[
				'description' => __( 'The URL slug for event listings.', 'newspack-listings' ),
				'key'         => 'newspack_listings_event_slug',
				'label'       => __( 'Event listings slug', 'newspack-listings' ),
				'type'        => 'input',
				'value'       => __( 'events', 'newspack-listings' ),
			],
			[
				'description' => __( 'The URL slug for generic listings.', 'newspack-listings' ),
				'key'         => 'newspack_listings_generic_slug',
				'label'       => __( 'Generic listings slug', 'newspack-listings' ),
				'type'        => 'input',
				'value'       => __( 'items', 'newspack-listings' ),
			],
			[
				'description' => __( 'The URL slug for marketplace listings.', 'newspack-listings' ),
				'key'         => 'newspack_listings_marketplace_slug',
				'label'       => __( 'Marketplace listings slug', 'newspack-listings' ),
				'type'        => 'input',
				'value'       => __( 'marketplace', 'newspack-listings' ),
			],
			[
				'description' => __( 'The URL slug for place listings.', 'newspack-listings' ),
				'key'         => 'newspack_listings_place_slug',
				'label'       => __( 'Place listings slug', 'newspack-listings' ),
				'type'        => 'input',
				'value'       => __( 'places', 'newspack-listings' ),
			],
			[
				'description' => __( 'This setting can be overridden per listing.', 'newspack-listings' ),
				'key'         => 'newspack_listings_hide_author',
				'label'       => __( 'Hide authors for new listings by default', 'newpack-listings' ),
				'type'        => 'checkbox',
				'value'       => true,
			],
		];
	}

	/**
	 * Get current site-wide settings, or defaults if not set.
	 *
	 * @param string|null $option (Optional) Key name of a single setting to get. If not given, will return all settings.
	 * @return array|Boolean Array of current site-wide settings, or false if returning a single option with no value.
	 */
	public static function get_settings( $option = null ) {
		$defaults = self::get_default_settings();
		$settings = array_reduce(
			$defaults,
			function( $acc, $setting ) {
				$key   = $setting['key'];
				$value = get_option( $key, $setting['value'] );

				// Guard against empty strings, which can happen if an option is set and then unset.
				if ( '' === $value && 'checkbox' !== $setting['type'] ) {
					$value = $setting['value'];
				}

				$acc[ $key ] = $value;
				return $acc;
			},
			[]
		);

		// If passed an option key name, just give that option.
		if ( ! empty( $option ) ) {
			return $settings[ $option ];
		}

		// Otherwise, return all settings.
		return $settings;
	}

	/**
	 * Options page callback
	 */
	public static function create_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Newspack Listings: Site-Wide Settings', 'newspack-listings' ); ?></h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'newspack_listings_options_group' );
				do_settings_sections( 'newspack-listings-settings-admin' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public static function page_init() {
		add_settings_section(
			'newspack_listings_options_group',
			null,
			null,
			'newspack-listings-settings-admin'
		);
		foreach ( self::get_default_settings() as $setting ) {
			register_setting(
				'newspack_listings_options_group',
				$setting['key']
			);
			add_settings_field(
				$setting['key'],
				$setting['label'],
				[ __CLASS__, 'newspack_listings_settings_callback' ],
				'newspack-listings-settings-admin',
				'newspack_listings_options_group',
				$setting
			);
		};
	}

	/**
	 * Render settings fields.
	 *
	 * @param array $setting Settings array.
	 */
	public static function newspack_listings_settings_callback( $setting ) {
		$key   = $setting['key'];
		$type  = $setting['type'];
		$value = get_option( $key, $setting['value'] );

		if ( 'checkbox' === $type ) {
			printf(
				'<input type="checkbox" id="%s" name="%s" %s /><p class="description" for="%s">%s</p>',
				esc_attr( $key ),
				esc_attr( $key ),
				! empty( $value ) ? 'checked' : '',
				esc_attr( $key ),
				esc_html( $setting['description'] )
			);
		} else {
			if ( empty( $value ) ) {
				$value = $setting['value'];
			}
			printf(
				'<input type="text" id="%s" name="%s" value="%s" class="regular-text" /><p class="description" for="%s">%s</p>',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_attr( $value ),
				esc_attr( $key ),
				esc_html( $setting['description'] )
			);
		}
	}
}

if ( is_admin() ) {
	Newspack_Listings_Settings::init();
}
