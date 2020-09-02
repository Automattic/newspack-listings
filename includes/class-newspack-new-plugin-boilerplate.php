<?php
/**
 * Newspack New Plugin Boilerplate
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

require_once NEWSPACK_NEW_PLUGIN_BOILERPLATE_PLUGIN_FILE . '/vendor/autoload.php';

/**
 * Main Newspack New Plugin Boilerplate Class.
 */
final class Newspack_New_Plugin_Boilerplate {
	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_New_Plugin_Boilerplate
	 */
	protected static $instance = null;

	/**
	 * Main Newspack New Plugin Boilerplate Instance.
	 * Ensures only one instance of Newspack New Plugin Boilerplate Instance is loaded or can be loaded.
	 *
	 * @return Newspack New Plugin Boilerplate Instance - Main instance.
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
	}
}
Newspack_New_Plugin_Boilerplate::instance();
