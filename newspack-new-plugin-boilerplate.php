<?php
/**
 * Plugin Name:     Newspack New Plugin Boilerplate
 * Plugin URI:      https://newspack.pub
 * Description:     A new thing.
 * Author:          Automattic
 * Author URI:      https://newspack.pub
 * Text Domain:     newspack-new-plugin-boilerplate
 * Domain Path:     /languages
 * Version:         0.0.0
 *
 * @package         Newspack_New_Plugin_Boilerplate
 */

defined( 'ABSPATH' ) || exit;

// Define NEWSPACK_NEW_PLUGIN_BOILERPLATE_PLUGIN_FILE.
if ( ! defined( 'NEWSPACK_NEW_PLUGIN_BOILERPLATE_PLUGIN_FILE' ) ) {
	define( 'NEWSPACK_NEW_PLUGIN_BOILERPLATE_PLUGIN_FILE', plugin_dir_path( __FILE__ ) );
}

// Include the main Newspack New Plugin Boilerplate class.
if ( ! class_exists( 'Newspack_New_Plugin_Boilerplate' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-newspack-new-plugin-boilerplate.php';
}
