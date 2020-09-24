<?php
/**
 * Newspack Listings Blocks.
 *
 * Custom Gutenberg Blocks for Newspack Listings.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings;

use \Newspack_Listings\Newspack_Listings_Core as Core;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 * Sets up custom blocks for listings.
 */
final class Newspack_Listings_Blocks {

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Listings_Blocks
	 */
	protected static $instance = null;

	/**
	 * Main Newspack_Listings_Blocks instance.
	 * Ensures only one instance of Newspack_Listings_Blocks is loaded or can be loaded.
	 *
	 * @return Newspack_Listings_Blocks - Main instance.
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
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'manage_editor_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register_block_patterns' ] );
		add_action( 'init', [ __CLASS__, 'manage_view_assets' ] );
	}

	/**
	 * Enqueue editor assets.
	 */
	public static function manage_editor_assets() {
		wp_enqueue_script(
			'newspack-listings-editor',
			NEWSPACK_LISTINGS_URL . 'dist/editor.js',
			[],
			NEWSPACK_LISTINGS_VERSION,
			true
		);

		$post_type = get_post_type();

		wp_localize_script(
			'newspack-listings-editor',
			'newspack_listings_data',
			[
				'post_type'   => get_post_type_object( $post_type )->labels->singular_name,
				'post_types'  => Core::NEWSPACK_LISTINGS_POST_TYPES,
				'meta_fields' => Core::get_meta_fields( $post_type ),
			]
		);

		wp_register_style(
			'newspack-listings-editor',
			plugins_url( '../dist/editor.css', __FILE__ ),
			[],
			NEWSPACK_LISTINGS_VERSION
		);
		wp_style_add_data( 'newspack-listings-editor', 'rtl', 'replace' );
		wp_enqueue_style( 'newspack-listings-editor' );
	}

	/**
	 * Enqueue front-end assets.
	 */
	public static function manage_view_assets() {
		// Do nothing in editor environment.
		if ( is_admin() ) {
			return;
		}

		$src_directory  = NEWSPACK_LISTINGS_PLUGIN_FILE . 'src/blocks/';
		$dist_directory = NEWSPACK_LISTINGS_PLUGIN_FILE . 'dist/';
		$iterator       = new \DirectoryIterator( $src_directory );

		foreach ( $iterator as $block_directory ) {
			if ( ! $block_directory->isDir() || $block_directory->isDot() ) {
				continue;
			}
			$type = $block_directory->getFilename();

			/* If view.php is found, include it and use for block rendering. */
			$view_php_path = $src_directory . $type . '/view.php';
			if ( file_exists( $view_php_path ) ) {
				include_once $view_php_path;
				continue;
			}

			/* If view.php is missing but view Javascript file is found, do generic view asset loading. */
			$view_js_path = $dist_directory . $type . '/view.js';
			if ( file_exists( $view_js_path ) ) {
				register_block_type(
					"newspack-listings/{$type}",
					array(
						'render_callback' => function( $attributes, $content ) use ( $type ) {
							Newspack_Blocks::enqueue_view_assets( $type );
							return $content;
						},
					)
				);
			}
		}
	}

	/**
	 * Register custom block patterns for Newspack Listings.
	 * These patterns are only available for certain CPTs.
	 */
	public static function register_block_patterns() {
		if ( ! is_admin() || ! Core::is_listing() ) {
			return;
		}

		// Register a custom block pattern category for Newspack Listings.
		register_block_pattern_category(
			'newspack-listings-patterns',
			[ 'label' => __( 'Newspack Listings', 'newspack-listings' ) ]
		);

		// Block pattern config.
		$block_patterns = [
			'business' => [
				'post_types' => [
					Core::NEWSPACK_LISTINGS_POST_TYPES['marketplace'],
					Core::NEWSPACK_LISTINGS_POST_TYPES['place'],
				],
				'settings'   => [
					'title'       => __( 'Business Listing', 'newspack-listings' ),
					'categories'  => [ 'newspack-listings-patterns' ],
					'description' => _x(
						'Business description, website and social media links, and hours of operation.',
						'Block pattern description',
						'newspack-listings'
					),
					'content'     => '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":66.66} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:paragraph --><p>Consectetur a urna hendrerit scelerisque suspendisse inceptos scelerisque neque parturient a mi adipiscing euismod mus. Ad felis morbi magna augue consectetur eleifend sit sem habitant suspendisse posuere amet felis adipiscing a himenaeos ipsum vivamus dictum vestibulum lacus consectetur vestibulum erat dignissim per sem integer. Cras class ac adipiscing inceptos a enim porta a elit scelerisque tincidunt hac ad netus accumsan parturient conubia vestibulum nec quisque parturient interdum fringilla curabitur cras sociosqu interdum. Porta aenean id a mus consectetur lacus lacus ut parturient sapien ut a sociosqu potenti ridiculus non tristique cursus a at parturient condimentum a duis convallis per. Dictum elementum ultricies ac risus vestibulum adipiscing placerat imperdiet malesuada scelerisque dictum mus adipiscing a at at fermentum scelerisque nisl a dignissim suscipit sapien taciti nulla curabitur vestibulum.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":33.33} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link" href="https://newspack.pub">Visit our website</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- wp:social-links --><ul class="wp-block-social-links"><!-- wp:social-link {"url":"https://newspack.pub","service":"wordpress"} /--><!-- wp:social-link {"url":"https://facebook.com","service":"facebook"} /--><!-- wp:social-link {"url":"https://twitter.com","service":"twitter"} /--><!-- wp:social-link {"url":"https://instagram.com","service":"instagram"} /--><!-- wp:social-link {"service":"linkedin"} /--><!-- wp:social-link {"service":"youtube"} /--></ul><!-- /wp:social-links --><!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator is-style-wide"/><!-- /wp:separator --><!-- wp:heading {"level":4} --><h4>Hours of Operation</h4><!-- /wp:heading --><!-- wp:jetpack/business-hours /--></div><!-- /wp:column --></div><!-- /wp:columns -->',
				],
			],
		];

		// Register block patterns for particular post types.
		$current_post_type = get_post_type();

		foreach ( $block_patterns as $pattern_name => $config ) {
			if ( in_array( $current_post_type, $config['post_types'] ) ) {
				register_block_pattern(
					'newspack-listings/' . $pattern_name,
					$config['settings']
				);
			}
		}
	}
}

Newspack_Listings_Blocks::instance();
