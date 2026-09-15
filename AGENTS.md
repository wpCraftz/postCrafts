# PostCrafts: Agent Guide

WordPress plugin ([wp.org/plugins/postcrafts](https://wordpress.org/plugins/postcrafts/), text domain `post-crafts`, v1.0.0) with two **dynamic Gutenberg blocks**: Post Grid (`post-crafts/post-grid`) and Post List (`post-crafts/post-list`). Features: query builder (categories/tags/specific posts, sort, reorder, exclude current/sticky), AJAX pagination (numeric, load more, arrows), responsive spacing, pagination colors, and excerpt settings.

The plugin is **live on real sites**. Keep block attributes, post-meta keys and front-end markup backward compatible.

Execution flows and data contracts are in **[docs/architecture.md](docs/architecture.md)**. Read it before changing rendering, styles, queries or pagination.

## Stack
- PHP ≥ 7.4 (no 8.x-only syntax), WP ≥ 5.6. Local `wp-env` runs PHP 8.0.
- JS: `@wordpress/scripts` 27 (webpack 5 + Sass), block `apiVersion` 3, React via WP globals.
- **pnpm 12.4.1** (pinned in `packageManager`). `yarn.lock` is stale; don't use yarn or npm.
- PHPCS with WordPress-Core/Docs/Extra, installed via Composer (`phpcs.xml`).

## Commands
| Task | Command |
|---|---|
| Install | `pnpm install && composer install` |
| Dev watch | `pnpm start` |
| Build | `pnpm build`. **Required:** `build/` is gitignored and PHP loads assets only from `build/` |
| Lint PHP | `pnpm lint:php` (fix: `pnpm lint:php:fix`) |
| Lint SCSS | `pnpm lint:css` |
| Lint JS | `pnpm lint:js`. Currently crashes (TypeScript 7 vs `@typescript-eslint` 6) |
| POT file | `pnpm make-pot` |
| Release zip | `pnpm plugin-zip` (ships only `package.json#files`) |

There are no automated tests; verify in a browser. The parent `wp-local` repo runs `wp-env` with this folder mounted under `wp-content/plugins` (`pnpm env:start` there).

## Repo map
```
post-crafts.php                  constants, autoloader, helpers, boots Plugin singleton
inc/classes/
  class-plugin.php               boot + filters, AJAX pagination handler, window.POSTCRAFTS
  class-blocks.php               register_block_type() for every build/blocks/*, block category
  class-assets.php               enqueues build/src/{styles,editor,scripts}
  class-api.php                  REST post-crafts/v1/style (save/get block CSS)
  class-style-loader.php         prints saved CSS in wp_head
  class-media.php                thumb-* image sizes (templates use thumb-330x185)
  class-admin.php                placeholder "PostCrafts" admin page
inc/helpers/custom-functions.php post_crafts_*(): query builder, template loader, pagination markup, block-attr lookup
inc/templates/block-templates/   post-grid.php / post-list.php (loop item), pagination|loadmore|arrow.php
src/blocks/<block>/              block.json, edit.js (editor preview), render.php (front end), save.js → null
src/components/                  Inspector UI: QueryBuilder, Range, ColorControl, PaginationSettings…
src/editor/                      save-time CSS: subscriber → css-manager → style-generator + dynamic-attributes
src/libs/                        useFetchPosts (editor query), REST style client, utils
src/scripts/pagination.js        front-end AJAX pagination (jQuery)
src/styles/                      main.scss (front), editor.scss, admin.scss; partials via @use
webpack.config.js                [default blocks config, extra entries for styles/editor/scripts]
```

## Conventions
- PHP namespace `PostCrafts\Blocks\Inc`; classes `use Traits\Singleton` and add hooks in the constructor or `setup_hooks()`. Instantiate new classes in `Plugin::__construct()`. The autoloader maps `Inc\Foo_Bar` → `inc/classes/class-foo-bar.php`.
- Prefixes: PHP functions `post_crafts_`, handles/meta `post-crafts`, CSS classes `pcrafts-`.
- Every string uses `__( '…', 'post-crafts' )`. Escape late. PHP files start with the `ABSPATH` guard.
- JS follows WordPress import groups (`WordPress dependencies` / `External` / `Internal` doc headers) and tab indentation.
- Blocks are dynamic, so markup lives in **two places**: `edit.js` (React preview) and `inc/templates/block-templates/<block>.php` (front end). Grid and List are near-duplicates, so change both.
- Edit `src/`, never `build/`.

## Recipes
**Styled attribute** (e.g. title color):
1. Add it to both `block.json` `attributes`.
2. Add a control in the block's InspectorControls (or a `src/components` panel).
3. Add an entry to `src/editor/dynamic-attributes/<block>.js`, for example `{ function: 'range'|'color', selector: '.pcrafts-block-${ attributes.blockId } .x { prop: $value$unit; }', responsive?, condition? }`. That one map drives both the editor `<style>` and the saved front-end CSS, so no PHP is needed. New value types need a helper in `src/editor/style-generator/helpers.js`.

**Query option:** change **both** `src/libs/fetchPosts.js` (editor REST query) and `post_crafts_query_builder()` (front end + AJAX), or the preview and the live output diverge.

**New block:** copy a `src/blocks/*` folder, then:
- Add a loop template in `inc/templates/block-templates/`.
- Add a `dynamic-attributes` map and a `mappedBlocks` entry in `style-generator/index.js`.
- Add the block to the `has_block()` check in `Assets::enqueue_block_assets()`, so the pagination script loads.

Registration is automatic.

## Known issues (verified; fix deliberately, don't rediscover)
- **Security, unfixed** (a fix design was in git `f0bbee9`, then deleted):
  - `Style_loader::print_css()` echoes the meta value unescaped.
  - The REST save checks `edit_posts`, not `edit_post` on the given `post_id`.
  - The AJAX `paginate_posts` handler (also registered for logged-out users) passes raw `$_POST['query']` to `WP_Query` and raw `$_POST['template']` into an `include` path. The only valid templates are `post-grid` and `post-list`.
- `post_crafts_query_builder()` reads `$attributes['post_type']`, but the attribute is `postType`, so the front end always queries `post`.
- `fetchPosts.js` sends tag `NOT IN` as `categories_exclude` instead of `tags_exclude`.
- Current post: the editor always excludes it, while the front end excludes it only when `excludeCurrentPost` is set. `render.php` also skips `get_the_ID()` in the loop.
- Nested blocks (inside Group/Columns) get no saved CSS, because the collector uses top-level `getBlocks()`. They also get empty attributes in AJAX, because `post_crafts_get_block_attributes()` only scans top-level blocks.
- `src/editor/index.js` (the save subscriber) is bundled into both block bundles and the editor script, so the style POST fires up to 3× per save.
- `register_activation_hook( __FILE__ )` and the textdomain path in `class-plugin.php` resolve to the class file, not the plugin root. Activation never runs and translations won't load from `languages/`.
- `excerpt` defaults to the string `"true"`. `view-script.js` files are unused. The `excerpt_more`/`excerpt_length` filters apply site-wide.
- Both webpack configs clean the same `build/` folder, so `pnpm start` can delete the other config's files. Run `pnpm build` if assets 404.
