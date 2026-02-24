# Newspack Listings agent guide

Shared conventions (Docker, `n` script, coding standards, git rules) are in `../../AGENTS.md`.

## Gotchas

- **No PHP autoloader.** All classes use manual `require_once`. Adding a new class requires a corresponding `require_once` in `newspack-listings.php` (or `class-products.php` for product subclasses). Forgetting this fails silently until the class is instantiated.

- **CPT slug is abbreviated.** Marketplace post type is `newspack_lst_mktplce`, not `newspack_lst_marketplace`. Always use `Core::NEWSPACK_LISTINGS_POST_TYPES` instead of hardcoding slugs.

- **Featured priority uses a custom DB table** (`wp_newspack_listings_priority`), not post meta. `get_post_meta()` won't return priority values. Use `Featured::get_priority()`. Schema changes require bumping `Featured::TABLE_VERSION`.

- **Block attribute-to-meta syncing.** Block attributes auto-sync to post meta via `Core::sync_post_meta()` on `save_post`. The mapping lives in `Core::get_meta_fields()` using a `source` key. Renaming a block attribute or block name without updating the source mapping silently breaks syncing.

- **"newspack-listings/listing" is not a real block.** The listing block dynamically registers per-CPT variants (`newspack-listings/event`, `newspack-listings/generic`, etc.) at runtime from `window.newspack_listings_data.post_types`. See `src/blocks/listing/index.js`.

- **All block JS compiles into `dist/editor.js`.** Block directories under `src/blocks/` have `block.json` but no individual JS bundles in `dist/`.

- **Front-end scripts are auto-discovered.** Any `.js` file in `src/assets/front-end/` automatically becomes a webpack entry point (`webpack.config.js` uses `fs.readdirSync()`).

- **All blocks are server-side rendered.** Every `save` function returns `null`. Rendering is in `view.php` templates.

- **WooCommerce integration is conditional.** `class-products.php` and subclasses in `includes/products/` only activate when WooCommerce is present. Self-serve listings won't work without it, with no visible error.

- **No JavaScript tests.** `npm test` is a no-op. Only PHPUnit tests exist.

- **Mixed React patterns.** Most blocks use `withSelect`/`withDispatch` HOCs. Only `event-dates` uses modern hooks. Match the existing pattern when modifying a block; prefer hooks for new code.

## Dominant pattern: adding a PHP class

```php
// 1. Create includes/class-my-feature.php
class My_Feature {
	private static $instance;
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	private function __construct() {
		// Register hooks here.
	}
}
My_Feature::instance(); // Self-instantiate at end of file.
```

```php
// 2. Add require_once in newspack-listings.php (no autoloader).
require_once NEWSPACK_LISTINGS_PLUGIN_FILE . '/includes/class-my-feature.php';
```
