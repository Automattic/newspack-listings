<?php
/**
 * Newspack Listings Settings Page
 *
 * @package Newspack
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
		add_action( 'admin_menu', [ __CLASS__, 'add_plugin_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'page_init' ] );
	}

	/**
	 * Default values for site-wide settings.
	 *
	 * @return array Array of default settings.
	 */
	public static function get_default_settings() {
		$defaults = [
			'permalink_prefix' => __( 'listings', 'newspack-listings' ),
		];

		return $defaults;
	}

	/**
	 * Get current site-wide settings, or defaults if not set.
	 *
	 * @return array Array of current site-wide settings.
	 */
	public static function get_settings() {
		$defaults = self::get_default_settings();
		$settings = [
			'permalink_prefix' => get_option( 'newspack_listings_permalink_prefix', $defaults['permalink_prefix'] ),
		];

		// Guard against empty strings, which can happen if an option is set and then unset.
		foreach ( $settings as $key => $value ) {
			if ( empty( $value ) ) {
				$settings[ $key ] = $defaults[ $key ];
			}
		}

		return $settings;
	}

	/**
	 * Get list of settings fields.
	 *
	 * @return array Settings list.
	 */
	public static function get_settings_list() {
		$defaults = self::get_default_settings();

		return [
			[
				'label' => __( 'Listings permalink prefix', 'newspack-listings' ),
				'value' => $defaults['permalink_prefix'],
				'key'   => 'newspack_listings_permalink_prefix',
				'type'  => 'input',
			],
		];
	}

	/**
	 * Add options page
	 */
	public static function add_plugin_page() {
		add_menu_page(
			'Newspack Listings',
			'Listings',
			'edit_posts',
			'newspack-listings',
			'',
			'dashicons-list-view',
			35
		);
		add_submenu_page(
			'newspack-listings',
			__( 'Newspack Listings: Site-Wide Settings', 'newspack-listings' ),
			__( 'Settings', 'newspack-listings' ),
			'manage_options',
			'newspack-listings-settings-admin',
			[ __CLASS__, 'create_admin_page' ]
		);
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
		foreach ( self::get_settings_list() as $setting ) {
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
		$value = ( ! empty( get_option( $key, false ) ) ) ? get_option( $key, false ) : $setting['value'];

		if ( 'textarea' === $type ) {
			printf(
				'<textarea id="%s" name="%s" class="widefat" rows="4">%s</textarea>',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		} else {
			printf(
				'<input type="text" id="%s" name="%s" value="%s" class="widefat" />',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}
}

if ( is_admin() ) {
	Newspack_Listings_Settings::init();
}
