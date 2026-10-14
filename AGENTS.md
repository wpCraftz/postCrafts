# PostCrafts: Agent Guide

WordPress plugin ([wp.org/plugins/postcrafts](https://wordpress.org/plugins/postcrafts/), text domain `post-crafts`, v1.0.0) with two **dynamic Gutenberg blocks**: Post Grid (`post-crafts/post-grid`) and Post List (`post-crafts/post-list`). Features: query builder (categories/tags/specific posts, sort, reorder, exclude current/sticky), AJAX pagination (numeric, load more, arrows), responsive spacing, pagination colors, and excerpt settings.

The plugin is **live on real sites**. Keep block attributes, post-meta keys and front-end markup backward compatible.

Execution flows and data contracts are in **[docs/architecture.md](docs/architecture.md)**. Read it before changing rendering, styles, queries or pagination.

## Stack
- PHP ≥ 7.4 (no 8.x-only syntax), WP ≥ 5.6. Local `wp-env` runs PHP 8.0.
- JS: `@wordpress/scripts` 27 (webpack 5 + Sass), block `apiVersion` 3, React via WP globals.
- **pnpm 12.4.1** (pinned in `packageManager`). `yarn.lock` is stale; don't use yarn or npm.
- PHPCS with WordPress-Core/Docs/Extra, WordPress-VIP-Go and PHPCompatibilityWP (7.4+), installed via Composer (`phpcs.xml`).

## Commands
| Task | Command |
|---|---|
| Install | `pnpm install && composer install` |
| Dev watch | `pnpm start` |
| Build | `pnpm build`. **Required:** `build/` is gitignored and PHP loads assets only from `build/` |
| Lint PHP | `pnpm lint:php` (fix: `pnpm lint:php:fix`) |
| Lint SCSS | `pnpm lint:css` |
| Lint JS | `pnpm lint:js` (fix: `pnpm lint:js --fix`). `typescript` is pinned to 5.x: TypeScript 7 breaks `@typescript-eslint` 6 |
| POT file | `pnpm make-pot` |
| Release zip | `pnpm plugin-zip` (ships only `package.json#files`) |
| Install WP test suite | `composer test:install` (once per WP version; optional arg: version) |
| Test PHP | `composer test` or `pnpm test:php` (needs `pnpm build`) |
| Test JS | `pnpm test:unit` |
| All tests | `pnpm test` |

See [Testing](#testing). Editor and front-end behavior still need a browser check. The parent `wp-local` repo runs `wp-env` with this folder mounted under `wp-content/plugins` (`pnpm env:start` there).

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
inc/helpers/custom-functions.php post_crafts_*(): query builder, template loader, pagination markup, block-attr lookup
inc/templates/block-templates/   post-grid.php / post-list.php (loop item), pagination|loadmore|arrow.php
src/blocks/<block>/              block.json, edit.js (editor preview), render.php (front end), save.js → null
src/components/                  Inspector UI: QueryBuilder, Range, ColorControl, PaginationSettings…
src/editor/                      save-time CSS: subscriber → css-manager → style-generator + dynamic-attributes
src/libs/                        useFetchPosts (editor query), REST style client, utils
src/scripts/pagination.js        front-end AJAX pagination (jQuery)
src/styles/                      main.scss (front), editor.scss, admin.scss; partials via @use
webpack.config.js                [default blocks config, extra entries for styles/editor/scripts]
tests/php/                       PHPUnit integration tests (WP test suite on SQLite), TestCase.php, bootstrap.php
tests/js/                        Jest unit tests (mirror src/ paths), setup.js
tests/bin/install-wp-tests.sh    downloads WP core + test library + SQLite drop-in into a cache dir
```

## Conventions
- PHP namespace `PostCrafts\Blocks\Inc`; classes `use Traits\Singleton` and add hooks in the constructor or `setup_hooks()`. Instantiate new classes in `Plugin::__construct()`. The autoloader maps `Inc\Foo_Bar` → `inc/classes/class-foo-bar.php`.
- Prefixes: PHP functions `post_crafts_`, handles/meta `post-crafts`, CSS classes `pcrafts-`.
- Every string uses `__( '…', 'post-crafts' )`. Escape late. PHP files start with the `ABSPATH` guard.
- JS follows WordPress import groups (`WordPress dependencies` / `External` / `Internal` doc headers) and tab indentation.
- Blocks are dynamic, so markup lives in **two places**: `edit.js` (React preview) and `inc/templates/block-templates/<block>.php` (front end). Grid and List are near-duplicates, so change both.
- Edit `src/`, never `build/`.

## Testing
**PHP** (PHPUnit 9.6 + Yoast polyfills, WordPress test library):
- `composer test:install` downloads WordPress, the test library and the SQLite Database Integration drop-in to `${TMPDIR:-/tmp}/postcrafts-wp-tests/<version>` (`latest` is a symlink). There is no MySQL: tests run on SQLite. Overrides: `WP_VERSION`, `WP_TESTS_DIR`, `POSTCRAFTS_WP_TESTS_CACHE` (use the same values when running the tests).
- The DB file lives in `tests/.tmp/` (gitignored, override with `POSTCRAFTS_TESTS_DB_DIR`). Each checkout or worktree gets its own, so parallel runs don't collide. It is reinstalled on every run.
- Run `pnpm build` first: blocks register from `build/blocks`, and the bootstrap stops if it's missing.
- Files are `tests/php/test-*.php`; classes `Test_*` in namespace `PostCrafts\Blocks\Tests`, extending `TestCase` (a `WP_UnitTestCase`, so each test is rolled back). Use `self::factory()` for posts/terms/users, plus `TestCase::create_post_with_block()`, `block_markup()` and `render_block()`.
- AJAX: extend `WP_Ajax_UnitTestCase`, set `$_POST` (nonce `wp_create_nonce( 'post-crafts' )`), call `$this->_handleAjax( 'paginate_posts' )`, catch `WPAjaxDieContinueException`, and read `$this->_last_response`. Don't tag tests `@group ajax`, because the WP bootstrap skips that group by default. Example: `tests/php/test-ajax-pagination.php`.
- REST: `rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/post-crafts/v1/style' ) )` after `wp_set_current_user()`.

**JS** (Jest via `wp-scripts test-unit-js`, jsdom, Testing Library):
- Files are `tests/js/**/*.test.js`, mirroring `src/` paths (e.g. `tests/js/libs/utils.test.js`). They're outside `src/`, so webpack and `plugin-zip` never see them. `jest.config.js` only looks in `tests/js`.
- `@wordpress/*` packages are webpack externals but real devDependencies for Jest. If you import a module that pulls in `@wordpress/block-editor`, `blocks` or `editor` (not installed), add the package or `jest.mock()` it. If an ESM-only package fails with "Unexpected token 'export'", add it to `transformIgnorePatterns` in `jest.config.js`.

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
- Blocks in site editor templates get no saved CSS: the style save is skipped when the post ID isn't numeric.
- Blocks outside the post content (e.g. template parts) use `block.json` defaults for AJAX pages, not their saved attributes.
