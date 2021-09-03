<?php
/**
 * Newspack Listings Settings Page
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;

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
				'allow_empty' => true,
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
				'label'       => __( 'Hide authors for listings by default', 'newpack-listings' ),
				'type'        => 'checkbox',
				'value'       => true,
			],
			[
				'description' => __( 'This setting can be overridden per listing.', 'newspack-listings' ),
				'key'         => 'newspack_listings_hide_publish_date',
				'label'       => __( 'Hide publish and updated dates for listings by default', 'newpack-listings' ),
				'type'        => 'checkbox',
				'value'       => true,
			],
			[
				'description' => __( 'This setting can be overridden per listing, post, or page.', 'newspack-listings' ),
				'key'         => 'newspack_listings_hide_parents',
				'label'       => __( 'Hide parent listings by default', 'newpack-listings' ),
				'type'        => 'checkbox',
				'value'       => false,
			],
			[
				'description' => __( 'This setting can be overridden per listing, post, or page.', 'newspack-listings' ),
				'key'         => 'newspack_listings_hide_children',
				'label'       => __( 'Hide child listings by default', 'newpack-listings' ),
				'type'        => 'checkbox',
				'value'       => false,
			],
		];
	}

	/**
	 * Get current site-wide settings, or defaults if not set.
	 *
	 * @param string|null $option (Optional) Key name of a single setting to get. If not given, will return all settings.
	 * @param boolean     $get_default (Optional) If true, return the default value.
	 *
	 * @return array|boolean Array of current site-wide settings, or false if returning a single option with no value.
	 */
	public static function get_settings( $option = null, $get_default = false ) {
		$defaults = self::get_default_settings();

		$settings = array_reduce(
			$defaults,
			function( $acc, $setting ) use ( $get_default ) {
				$key   = $setting['key'];
				$value = $get_default ? $setting['value'] : get_option( $key, '' );

				// Guard against empty strings, which can happen if an option is set and then unset.
				if ( empty( $setting['allow_empty'] ) && '' === $value && 'checkbox' !== $setting['type'] ) {
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

			// Flush permalinks when permalink option is updated.
			$is_permalink_option = preg_match( '/newspack_listings_(.*)(_prefix|_slug)/', $setting['key'] );
			if ( $is_permalink_option ) {
				add_action( 'update_option_' . $setting['key'], [ __CLASS__, 'flush_permalinks' ], 10, 3 );
			}
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
			if ( empty( $value ) && empty( $setting['allow_empty'] ) ) {
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

	/**
	 * Flush permalinks automatically if updating a permalink slug option.
	 *
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option Name of the option to update.
	 */
	public static function flush_permalinks( $old_value, $new_value, $option ) {
		// Prevent empty slug value.
		if ( empty( $new_value ) ) {
			$defaults = self::get_default_settings();
			$matching = array_reduce(
				$defaults,
				function( $acc, $default_option_config ) use ( $option ) {
					if ( $option === $default_option_config['key'] ) {
						$acc = $default_option_config;
					}
					return $acc;
				},
				false
			);

			if ( $matching && empty( $matching['allow_empty'] ) ) {
				return update_option( $option, $matching['value'] ); // Return early to prevent flushing rewrite rules twice.
			}
		}

		Core::activation_hook();
	}
}

if ( is_admin() ) {
	Newspack_Listings_Settings::init();
}
