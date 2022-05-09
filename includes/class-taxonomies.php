<?php
/**
 * Newspack Listings - Sets up taxonomies to associate different post types with each other.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Core;
use \Newspack_Listings\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomies class.
 * Sets up taxonomies for listings.
 */
final class Taxonomies {
	const NEWSPACK_LISTINGS_TAXONOMY_PREFIX = 'np_lst_';

	/**
	 * The single instance of the class.
	 *
	 * @var Taxonomies
	 */
	protected static $instance = null;

	/**
	 * Main Taxonomies instance.
	 * Ensures only one instance of Taxonomies is loaded or can be loaded.
	 *
	 * @return Taxonomies - Main instance.
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
		register_activation_hook( NEWSPACK_LISTINGS_FILE, [ __CLASS__, 'activation_hook' ] );
	}

	/**
	 * After WP init.
	 */
	public static function init() {
		self::register_taxonomies();
	}

	/**
	 * Register listings-only taxonomies.
	 */
	public static function register_taxonomies() {
		$default_config = [
			'hierarchical'       => true,
			'public'             => true,
			'show_in_menu'       => true,
			'show_in_quick_edit' => false,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_ui'            => true,
		];
	}

	/**
	 * Register taxonomies on plugin activation.
	 */
	public static function activation_hook() {
		self::register_taxonomies();
	}
}

Taxonomies::instance();
