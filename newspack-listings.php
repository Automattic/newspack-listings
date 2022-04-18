<?php
/**
 * Plugin Name:     Newspack Listings
 * Plugin URI:      https://newspack.pub
 * Description:     Listings and directories for Newspack sites.
 * Author:          Automattic
 * Author URI:      https://newspack.pub
 * Text Domain:     newspack-listings
 * Domain Path:     /languages
 * Version:         2.9.4
 *
 * @package         Newspack_Listings
 */

defined( 'ABSPATH' ) || exit;

// Define NEWSPACK_LISTINGS_PLUGIN_FILE.
if ( ! defined( 'NEWSPACK_LISTINGS_PLUGIN_FILE' ) ) {
	define( 'NEWSPACK_LISTINGS_FILE', __FILE__ );
	define( 'NEWSPACK_LISTINGS_PLUGIN_FILE', plugin_dir_path( NEWSPACK_LISTINGS_FILE ) );
	define( 'NEWSPACK_LISTINGS_URL', plugin_dir_url( NEWSPACK_LISTINGS_FILE ) );
	define( 'NEWSPACK_LISTINGS_VERSION', '1.2.0' );
}

// Include plugin resources.
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/vendor/autoload.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/utils.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-settings.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-core.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-blocks.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-block-patterns.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-taxonomies.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-api.php';

// Enable experimental/in-progress self-serve listings functionality.
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-featured.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-products.php';

// CLI importer files.
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/contracts/contract-importer-mode.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/contracts/contract-listings-type-mapper.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/importer-utils.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-import-mode.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-importer-mode.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-marketplace-type.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-listing-type.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-listings-type-mapper.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-newspack-listings-importer.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-newspack-listings-callable-importer.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/contracts/contract-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/contracts/contract-iterable-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/contracts/contract-csv-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-abstract-callable-pre-create.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-abstract-callable-post-create.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-abstract-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-abstract-iterable-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-json-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-csv-file.php';
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/importer/class-file-import-factory.php';

// Migration utilities.
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/migration/class-migration.php';
