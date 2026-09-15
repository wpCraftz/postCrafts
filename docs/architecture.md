# Architecture & Execution Flow

How requests move through PostCrafts. Paths are relative to the repo root. Known bugs along these paths are listed in [AGENTS.md](../AGENTS.md#known-issues-verified-fix-deliberately-dont-rediscover).

## 1. Boot (every request)
```
post-crafts.php
 ├─ define POST_CRAFTS_PATH | _URL | _BUILD, POST_CRAFTS_REST_NAMESPACE = 'post-crafts/v1', POST_CRAFTS_VERSION
 ├─ require inc/helpers/autoloader.php        spl_autoload for PostCrafts\Blocks\*
 ├─ require inc/helpers/custom-functions.php  global post_crafts_*() helpers
 └─ Plugin::get_instance()
      ├─ Assets, Blocks, Admin, Media, Api, Style_loader ::get_instance()   each registers its hooks
      └─ Plugin::setup_hooks()
```

| Hook | Callback | Effect |
|---|---|---|
| `init` (1) | `Plugin::localize_scripts` | Enqueues empty handle `post-crafts-localized-script` with `window.POSTCRAFTS = { urls: { restBase, ajaxUrl }, nonce }` (nonce action `post-crafts`) |
| `init` | `Blocks::register_blocks` | `register_block_type()` for each `build/blocks/*` (block.json declares editorScript, styles, `render.php`) |
| `block_categories_all` | `Blocks::register_block_categories` | Prepends the `post-crafts` category ("Advanced Post Blocks") |
| `after_setup_theme` | `Media::add_image_sizes` | `thumb-*` crops |
| `rest_api_init` | `Api::register_end_points` | `GET`/`POST /style` |
| `rest_prepare_post` | `Plugin::add_post_class_in_rest_response` | Adds `post_class` and `featured_image{src,srcset,sizes,alt,…}` (thumb-330x185) for the editor preview |
| `excerpt_more` / `excerpt_length` | `Plugin` | `&hellip;` / 55 words (site-wide) |
| `enqueue_block_editor_assets` | `Assets` | `build/src/editor/index.js` + `build/src/styles/editor.css` |
| `enqueue_block_assets` | `Assets` | `styles/main.css` always; `scripts/pagination.js` (jQuery) when the post `has_block()` grid/list |
| `admin_enqueue_scripts` | `Assets` | `styles/admin.css` |
| `wp` | `Style_loader::load_css` | Singular pages only: loads the CSS meta, then hooks `wp_head` to print it |
| `wp_ajax_paginate_posts` (+ `nopriv`) | `Plugin::post_crafts_pagination` | AJAX pages (§5) |

## 2. Editor: block preview
`src/blocks/<block>/edit.js`
```
1. blockId      if empty → setAttributes({ blockId: clientId.substring(0, 8) })   permanent CSS scope + AJAX lookup key
2. live CSS     styleGenerator(name, attributes) → inline <style> in the block (same generator as §3)
3. posts        useFetchPosts() (src/libs/fetchPosts.js) → core-data getEntityRecords('postType', postType, query) → /wp/v2/posts
                  query = { per_page, order, orderby, exclude: [currentPostId] }
                  postIds set      → include + orderby 'include'
                  taxQuery set     → categories | categories_exclude, tags, tax_relation (keys = taxonomy rest_base)
                  postIds reorder only → arrayMoveImmutable on cached posts (no refetch)
                  postsPerPage lowered → slice cached posts
4. lookups      getUsers(authors) + category terms → blockContexts (title, excerpt from content.raw, image, author, first category)
5. markup       mirrors inc/templates/block-templates/<block>.php; <PaginationEdit> is a static placeholder
Inspector       GridSetttings | ListSetttings, QueryBuilder (select/sort posts, count ≤40, order, taxonomies,
                exclude current, ignore sticky), PaginationSettings, ExcerptSettings
```
Selecting specific posts in QueryBuilder turns `pagination` off. Clearing the selection restores it.

## 3. Save → front-end CSS
```
src/editor/subscriber.js   wp.data.subscribe(), runs on every store change
  when isPublishingPost || (isSavingPost && !isAutosavingPost) || isPreviewingPost   (800 ms throttle)
  → css-manager.parseStyle()     top-level getBlocks(), names containing 'post-crafts'
      → style-generator(name, attributes) per block, concatenated
  → libs/fetchStyle.updateStyle(postId, 'all', css, isPreviewing)
      POST /wp-json/post-crafts/v1/style { post_id, block_id: 'all', style, is_previewing }
  → Api::save_style()  update_post_meta(post_id, is_previewing ? 'post-crafts-preview-style' : 'post-crafts-style', css)
```
**Style generator** (`src/editor/style-generator/index.js`): for each key in `dynamic-attributes/<block>.js`, it skips the key when the attribute value is falsy or `condition` is false. Helpers replace the `$value` / `$unit` tokens in `selector`. A responsive value `{ value, unit, tablet?, mobile? }` produces a desktop rule plus `@media (max-width: 991px)` (tablet) and `@media (max-width: 767px)` (mobile) rules.

CSS is stored per post and regenerated **only on save**. Generator changes reach existing posts only after they are re-saved.

## 4. Front-end render
```
GET singular page
 ├─ wp       Style_loader: meta 'post-crafts-style' ('post-crafts-preview-style' when is_preview())
 │           → wp_head: <style class="pcrafts-dynamic-styles">
 └─ content  render_block → build/blocks/<block>/render.php ($attributes)
      $args = post_crafts_query_builder($attributes)
        posts_per_page, post_status 'publish', paged, order/orderby ← sorting
        ignoreSticky → ignore_sticky_posts;  excludeCurrentPost → post__not_in [get_the_ID()]
        postIds  → post__in + orderby 'post__in' (taxonomy filters ignored)
        cat+tag  → tax_query { relation: taxRelation, category (catOperator), post_tag (tagOperator) }
        one only → category__in | __and | __not_in   or   tag__in | __and | __not_in
      new WP_Query($args); loop skips the current post, stops at postsPerPage
        post_crafts_template('block-templates/post-grid|post-list', { excerpt, excerpt_length }, echo)
      if pagination → template pagination | loadmore | arrow with data-* attributes:
        data-query (JSON $args), data-page, data-max-page, data-post-id, data-block-id,
        data-template (post-grid|post-list), data-posts-per-page (loadmore)
```
DOM hooks: `.pcrafts-block.pcrafts-block-{blockId}.pcrafts-postgrid-wrapper|pcrafts-postlist-wrapper` › `.pcrafts-posts-wrapper.pcrafts-grid-items-wrapper|pcrafts-list-items-wrapper` › `article.post-grid|post-list`, then `.pcrafts-pagination.pcrafts-numberic|pcrafts-loadmore|pcrafts-arrow`.

Template helpers: `post_crafts_get_primary_category()` (Yoast primary term, falling back to the first category), `post_crafts_get_author()`, `post_crafts_posted_on()`, and `post_crafts_excerpt_length()` (`wp_trim_words` on the content).

## 5. AJAX pagination
```
src/scripts/pagination.js   new Pagination() binds every .pcrafts-pagination by type
  click → jQuery.post(POSTCRAFTS.urls.ajaxUrl, {
            action: 'paginate_posts', _ajax_nonce: POSTCRAFTS.nonce,
            postId, blockId, paged, template, query: { ...JSON(data-query), paged } })
  → Plugin::post_crafts_pagination()
      wp_verify_nonce('post-crafts') (bare return on failure)
      attrs = post_crafts_get_block_attributes(postId, blockId)   parse_blocks, top level, match attrs.blockId
      WP_Query($_POST['query']) → [ rendered 'block-templates/{template}' per post ]
      wp_send_json_success(html[]) | wp_send_json_error
  ← loadmore  append to .pcrafts-posts-wrapper; button gets .disabled on error or a short page
    arrow     replace; toggle .disabled on prev/next using data-max-page
    numeric   replace; updatePages() recomputes the prev/first/dots/3 middle/last/next items
              client-side, mirroring PHP post_crafts_pagination()
```

## 6. Data contracts

### Attributes (`src/blocks/*/block.json`)
| Attribute | Default | Notes |
|---|---|---|
| `blockId` | auto (8 chars of clientId) | CSS scope, AJAX lookup. Never regenerate |
| `postType` | `post` | Editor only (PHP reads `post_type`) |
| `postsPerPage` | `6` | |
| `sorting` | `{ order: 'desc', orderBy: 'date' }` | |
| `postIds` | `[]` | Specific posts, in this order |
| `taxQuery` | – | `{ category: [termIds], post_tag: [termIds] }` |
| `taxRelation` / `catOperator` / `tagOperator` | `AND` / `IN` / `IN` | Operators: `IN`, `NOT IN`, `AND` |
| `excludeCurrentPost` / `ignoreSticky` | `false` / `false` | |
| `excerpt` / `excerptLength` | `"true"` (string) / `30` words | |
| `pagination` / `paginationType` | `true` / `pagination` | Type: `pagination` \| `loadmore` \| `arrow` |
| `paginationAlignment` | `left` | |
| `paginationColor` / `paginationBg` / `paginationGradient` | – | CSS strings; the gradient overrides the background |
| `paginationBorderRadius` | `{ unit: 'px', value: 2 }` | Responsive |
| `rowGap` | `{ unit: 'px', value: 20 }` | Responsive |
| `columns`, `columnGap` | `{ value: 3 }`, `{ unit: 'px', value: 20 }` | **Grid only**, responsive |

Responsive shape: `{ value, unit, tablet?, mobile? }`.

### Storage & endpoints
| Kind | Key / route | Details |
|---|---|---|
| Post meta | `post-crafts-style` | CSS for all blocks in the post. A legacy JSON-per-block format is written when `block_id ≠ 'all'`; current JS never does that |
| Post meta | `post-crafts-preview-style` | Same, used for previews |
| Option | `post_crafts_installed` | Set on activation (hook currently never fires) |
| REST | `POST /wp-json/post-crafts/v1/style` | Capability `edit_posts`; body `{ post_id*, style*, block_id, is_previewing }` |
| REST | `GET /wp-json/post-crafts/v1/style?post_id=` | Public; returns the CSS string or null. Unused by JS |
| AJAX | `admin-ajax.php` `action=paginate_posts` | Logged-in and logged-out users; nonce action `post-crafts` |
| JS global | `window.POSTCRAFTS` | `{ urls: { restBase, ajaxUrl }, nonce }` |

### Build output
| Source | Output | Loaded by |
|---|---|---|
| `src/blocks/*/` (block.json, index.js, render.php, scss) | `build/blocks/*/` | `Blocks::register_blocks` (default wp-scripts config) |
| `src/editor/index.js` | `build/src/editor/index.js` | `enqueue_block_editor_assets` |
| `src/styles/{main,editor,admin}.scss` | `build/src/styles/*.css` (+ `-rtl.css`, stub `.js`) | `Assets` |
| `src/scripts/*.js` | `build/src/scripts/*.js` | `enqueue_block_assets` |

`webpack.config.js` exports two configs that share the `build/` output. It also silences Sass's `legacy-js-api` deprecation, because wp-scripts 27 bundles sass-loader 12.
