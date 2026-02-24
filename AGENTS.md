# Newspack Listings: Agent Instructions

This file covers what is specific to `newspack-listings`. Shared conventions (Docker commands, `n` script, coding standards, git rules, etc.) are in the root `newspack-workspace/AGENTS.md`.

## Core Architecture

### Custom Post Types

All four listing CPTs are defined in [`Core::NEWSPACK_LISTINGS_POST_TYPES`](includes/class-core.php#L27) ([`includes/class-core.php:27`](includes/class-core.php#L27)):

| Key           | Slug                   | Permalink    |
|---------------|------------------------|--------------|
| `event`       | `newspack_lst_event`   | `/events/`   |
| `generic`     | `newspack_lst_generic` | `/items/`    |
| `marketplace` | `newspack_lst_mktplce` | `/marketplace/` |
| `place`       | `newspack_lst_place`   | `/places/`   |

Use [`Core::is_listing( $post_type )`](includes/class-core.php#L131) to check whether a post type is a listing.

### Meta Syncing from Blocks

Block attributes are synced to post meta on every `save_post` via [`Core::sync_post_meta()`](includes/class-core.php#L641) ([`includes/class-core.php:641`](includes/class-core.php#L641)). The flow:

1. `save_post` fires for a listing CPT.
2. Post content is parsed into blocks via `parse_blocks()`.
3. Each meta field registered in [`Core::get_meta_fields()`](includes/class-core.php#L270) that has a `source` key is matched to a block type + attribute.
4. [`Utils\get_data_from_blocks()`](includes/utils.php#L121) ([`includes/utils.php:121`](includes/utils.php#L121)) extracts the attribute value from the matching block.
5. Post meta is updated (or deleted if the block is removed).

See [`class-core.php:428`](includes/class-core.php#L428) for the meta field definition format.

### Featured Listings

Featured priority is stored in a custom DB table (`wp_newspack_listings_priority`) instead of post meta for query performance. Defined in [`includes/class-featured.php`](includes/class-featured.php):

- **Table columns**: `post_id` (PK), `feature_priority` (0 = not featured, 1-9 = priority)
- **Sorting**: `posts_clauses` filter joins the table at query time ([`Featured::sort_featured_listings`](includes/class-featured.php#L326))
- **Meta keys**: `newspack_listings_featured`, `newspack_listings_featured_expires`

### Block Hierarchy

Blocks split into two groups based on context ([`src/editor/index.js`](src/editor/index.js)). The [`isListing()`](src/editor/utils.js#L17) utility checks `window.newspack_listings_data.post_type` against registered listing CPTs:

- **Editor-only blocks** (registered when `isListing()` is true): only available when editing a listing CPT.
- **Frontend blocks** (registered when `isListing()` is false): available in regular posts/pages.

Discover all blocks via `src/blocks/*/block.json`.

### Template Pattern

Server-side templates use closures for variable isolation (`call_user_func` wrapping `$data`). Templates are loaded via [`Utils\template_include( 'listing', $data )`](includes/utils.php#L41) ([`includes/utils.php:41`](includes/utils.php#L41)), which uses output buffering to return rendered HTML. See [`src/templates/listing.php`](src/templates/listing.php#L12) for the pattern.

## Gotchas

1. **Meta syncing is automatic** - Changing a block's attribute name or block name breaks the `source` mapping in [`Core::get_meta_fields()`](includes/class-core.php#L270). Always update both together.
2. **Featured priority uses a custom table**, not post meta. Direct `get_post_meta()` calls won't return priority values. Use [`Featured::get_priority()`](includes/class-featured.php#L229).
3. **`isListing()` controls block visibility** - Editor-only blocks won't appear outside listing CPTs. If a block should appear everywhere, register it in the non-listing branch of [`src/editor/index.js`](src/editor/index.js).
4. **Block `save` returns `null`** - All blocks use server-side rendering via `view.php` templates. Don't add JSX to `save` functions.
5. **Template variable isolation** - Templates wrap in `call_user_func` closures. Access data only through the `$data` parameter, not global variables.
6. **CPT slugs are abbreviated** (`newspack_lst_mktplce`, not `newspack_lst_marketplace`). Always reference `Core::NEWSPACK_LISTINGS_POST_TYPES` instead of hardcoding slugs.
7. **All major classes use the singleton pattern** - Access instances via `ClassName::instance()`, not `new ClassName()`.
8. **WooCommerce integration is optional** - `class-products.php` only loads if WooCommerce is active. Don't assume its classes exist.
9. **No JavaScript tests** in this repo. Only PHPUnit tests in `tests/`.

## Recipes

### Add a New Synced Meta Field

**Goal**: Store a block attribute as post meta so it's queryable.

1. **Register the meta field** in [`Core::get_meta_fields()`](includes/class-core.php#L270). Add an entry following the existing format (see [`class-core.php:428`](includes/class-core.php#L428) for an example).
2. **Add the attribute** to the block's `block.json` ([`src/blocks/event-dates/block.json`](src/blocks/event-dates/block.json)).
3. **Use the attribute** in the block's `edit.js` component and `view.php` template.
4. **Test**: Save a listing, then verify with `get_post_meta( $post_id, 'newspack_listings_my_field', true )`.

The `save_post` hook in [`Core::sync_post_meta()`](includes/class-core.php#L641) handles the rest automatically.

### Add a New Block

1. **Create the block directory** under `src/blocks/my-block/` with `block.json`, `index.js`, `edit.js`, and `view.php`.
2. **Register in the editor** ([`src/editor/index.js`](src/editor/index.js)) in the appropriate branch (isListing or not).
3. **Register server-side rendering** in [`includes/class-blocks.php`](includes/class-blocks.php) if the block needs PHP rendering.
4. **Follow existing patterns**: `save: () => null`, functional React components, `InspectorControls` for sidebar settings.
