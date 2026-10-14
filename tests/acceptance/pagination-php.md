# Acceptance criteria: PHP pagination markup and AJAX pagination

Scope: `post_crafts_pagination()` (`inc/helpers/custom-functions.php`), the pagination templates in
`inc/templates/block-templates/` (`pagination.php`, `arrow.php`, `loadmore.php`) and the AJAX handler
`Plugin::post_crafts_pagination()` (`inc/classes/class-plugin.php`), as changed in PR #10.

Tests: `tests/php/test-pagination-markup.php`, `tests/php/test-pagination-templates.php`,
`tests/php/test-ajax-pagination-handler.php`. Each test names the criteria it covers with `@covers-ac`.

"Visible" below means the item does not have the `hide` class (the front-end CSS hides `.hide`).

## A. Numeric page list: `post_crafts_pagination( $max_page, $current_page )`

- **PAGP-01** Given `max_page` is 1, 0 or negative, when the function runs, then it returns no markup (empty/null).
- **PAGP-02** Given `max_page` ≥ 2, then it returns one `<ul class="pcrafts-pages">` whose children are `li`
  elements; every clickable `li` has the `page-numbers` class and a `data-page` attribute and contains exactly one
  `<button type="button" class="pcrafts-page-btn">`. (Saved block CSS targets `.pcrafts-pages li`, so the `li`
  keeps its classes and `data-page`.)
- **PAGP-03** Given any `current_page`, then exactly one `li` has the class `current`, it is the middle-page item
  whose `data-page` equals the current page, and only its button has `aria-current="page"`.
- **PAGP-04** The first item is Prev (`li.prev.page-numbers`, `data-page` = current − 1, button
  `aria-label="Previous page"`), the last item is Next (`li.next.page-numbers`, `data-page` = current + 1, button
  `aria-label="Next page"`). Prev is hidden on page 1 and Next is hidden on the last page; otherwise both are visible.
- **PAGP-05** Dots items (`li.page-dots.first`, `li.page-dots.last`) contain the text `...`, have
  `aria-hidden="true"`, contain no button and have no `data-page`.
- **PAGP-06** The visible page sequence is correct for these (max, current) pairs, where `[n]` is the current page
  and `...` is a visible dots item:

  | max/current | visible items |
  |---|---|
  | 2/1 | `[1] 2 Next` |
  | 3/2 | `Prev 1 [2] 3 Next` |
  | 4/1 | `[1] 2 3 4 Next` |
  | 4/4 | `Prev 1 2 3 [4]` |
  | 6/1 | `[1] 2 3 ... 6 Next` |
  | 6/3 | `Prev 1 2 [3] 4 ... 6 Next` |
  | 6/4 | `Prev 1 ... 3 [4] 5 6 Next` |
  | 6/6 | `Prev 1 ... 4 5 [6]` |
  | 7/4 | `Prev 1 ... 3 [4] 5 ... 7 Next` |

- **PAGP-07** For every `max_page` in 2..8 and every `current_page` in 1..max: the visible numbered buttons are
  strictly increasing and within 1..`max_page`; page 1 and page `max_page` always have a visible numbered button;
  the current page is always visible; and a visible dots item sits exactly where at least one page number is
  skipped (no dots between consecutive numbers, no gap without dots).
- **PAGP-08** The DOM order and class names of the items are fixed so the front-end script can update them:
  `prev`, [`first-page`, `page-dots first`] (only when `max_page` > 3), three (or two when `max_page` = 2)
  `middle-pages`, `page-dots last`, [`last-page`] (only when `max_page` > 3), `next`.
- **PAGP-09** Given a `current_page` below 1 or above `max_page`, then it is clamped into 1..`max_page` and no item
  links to a page outside 1..`max_page` except the hidden Prev (0) / Next (`max_page` + 1).
- **PAGP-10** The Prev/Next labels and their aria-labels go through the `post-crafts` text domain and are escaped:
  a translation containing `<`, `>` or `"` is output as entities, never as markup.

## B. Pagination templates

- **PAGP-11** Numeric template (`block-templates/pagination`): the wrapper is
  `div.pcrafts-pagination.pcrafts-numberic.<alignment>` and carries `data-query` (JSON of `post_query`),
  `data-page` (current page, default 1), `data-max-page`, `data-post-id` (the current post ID), `data-block-id` and
  `data-template`. It contains the `post_crafts_pagination()` list.
- **PAGP-12** Numeric template: when there is more than one page the wrapper has `role="navigation"` and
  `aria-label="Posts pagination"`; with a single page it has neither and contains no `ul`.
- **PAGP-13** Arrow template (`block-templates/arrow`): the wrapper is `div.pcrafts-pagination.pcrafts-arrow.<alignment>`
  with `role="navigation"`, `aria-label="Posts pagination"` and the same `data-*` attributes (`data-page="1"`).
  It has two `type="button"` buttons: `.pcrafts-prev` (aria-label "Previous page") and `.pcrafts-next`
  (aria-label "Next page"). Prev is initially disabled (attribute and class). Next is disabled (attribute and class)
  only when `max_page` ≤ 1. Arrow icons are `aria-hidden`.
- **PAGP-14** Load More template (`block-templates/loadmore`): the wrapper is
  `div.pcrafts-pagination.pcrafts-loadmore.<alignment>` with the same `data-*` attributes plus
  `data-posts-per-page`. It has one `button.pcrafts-loadmore-btn` with `type="button"` and the text "Load More",
  disabled (attribute and class) only when `max_page` ≤ 1.
- **PAGP-15** Template defaults: with no variables passed, alignment is `left`, `data-template` is `post-grid`,
  `data-max-page` is 1, `data-query` is `[]`, and nothing raises a PHP notice.
- **PAGP-16** Rendering a Post Grid or Post List block with `pagination` on and each `paginationType`
  (`pagination`, `arrow`, `loadmore`) outputs the matching template after the posts wrapper, with
  `data-template` = the block's loop template, `data-block-id` = `blockId`, `data-max-page` = the query's page
  count, `data-query` = `post_crafts_query_builder()` output for the block, `data-post-id` = the post being viewed,
  the alignment class from `paginationAlignment`, and (Load More) `data-posts-per-page` = `postsPerPage`.
  With `pagination` off, no `.pcrafts-pagination` is output.

## C. AJAX handler: `wp_ajax(_nopriv)_paginate_posts`

- **PAGP-17** Given a valid nonce and the payload the front-end script sends (`action`, `_ajax_nonce`, `postId`,
  `blockId`, `paged`, `template`, `query` = the block's `data-query` plus `paged`), when page N is requested, then the
  response is `{ success: true, data: [html…] }` with one rendered loop item per post on page N, using the
  requested template (`article.post-grid` or `article.post-list`).
- **PAGP-18** The page comes from `query[paged]`; requesting a page past the last returns
  `{ success: false }` with the message "No more posts found".
- **PAGP-19** Given a missing or invalid nonce, then the handler returns without output, without sending JSON and
  without calling `wp_die()` (admin-ajax.php then ends the request with its default `0`).
- **PAGP-20** Given a `template` other than `post-grid` / `post-list` (including path traversal such as
  `../../../wp-config`, `post-grid/../../x`, an empty value or another template like `pagination`), then the
  response is `{ success: false, data: ["Invalid template"] }` with HTTP status 400 and no template is included.
  The value is passed through `sanitize_key()` first, so a value that normalizes to an allowed name (e.g.
  `Post-Grid`) is served as that template; nothing else can reach the `include`. (The 400 status itself is not
  asserted by PHPUnit: `wp_send_json()` only calls `status_header()` while headers are unsent, and they are always
  sent in the CLI run.)
- **PAGP-21** Query sanitization (`post_crafts_sanitize_query_args()`): `post_status` is always `publish` even when
  `draft`, `private` or `any` is requested; `posts_per_page` is clamped to 1..100 (default 6); `paged` is at least 1;
  keys outside the allow-list (e.g. `meta_query`, `s`, `author`, `fields`, `post_status`) are dropped; `orderby` is
  kept only for `date`, `title`, `post__in`; `order` is `ASC` or `DESC`; `post_type` is kept only when viewable;
  ID lists are `absint`ed with zeros removed; `tax_query` keeps only `category` / `post_tag` clauses with
  `field = term_id`, `absint` terms, an operator in `IN`/`NOT IN`/`AND` (otherwise `IN`) and relation `AND`/`OR`,
  including nested groups. A non-array query falls back to the defaults.
- **PAGP-22** End to end, a request for `post_status=draft` (or `private`) returns only published posts.
- **PAGP-23** A logged-out visitor (`wp_ajax_nopriv_paginate_posts`) with a valid logged-out nonce gets the same
  successful response.
- **PAGP-24** Block attributes (`excerpt`, `excerptLength`) are looked up by `blockId` in `postId`'s content,
  including when the block is nested inside `core/group` (or deeper).
- **PAGP-25** When the stored block omits `excerpt` / `excerptLength`, the block.json defaults apply: the excerpt is
  shown, trimmed to 30 words.
- **PAGP-26** When the block isn't found in the post content (wrong `blockId`, post without blocks, missing
  `postId`), the handler falls back to the block.json defaults of `post-crafts/{template}` and still succeeds.
- **PAGP-27** No request path in this section raises a PHP notice, warning or deprecation.
