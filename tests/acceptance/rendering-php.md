# Acceptance criteria: PHP rendering, excerpts, REST and registration (PR #10)

Scope: server-side behavior of `fix/qa-issues`. Pagination markup and the AJAX
handler, and all editor (JS) behavior, are covered elsewhere. Each criterion is
covered by PHPUnit tests that cite its ID in their docblock.

Test files:
- `tests/php/test-excerpt.php` (RNDP-01 to RNDP-13)
- `tests/php/test-card-templates.php` (RNDP-20 to RNDP-30)
- `tests/php/test-rest-fields.php` (RNDP-40 to RNDP-44)
- `tests/php/test-rest-style.php` (RNDP-50 to RNDP-56)
- `tests/php/test-registration.php` (RNDP-60 to RNDP-66)

## Excerpt source and trimming

`post_crafts_get_excerpt_source( $post_id )` returns the untrimmed plain text a
card excerpt is built from. `post_crafts_excerpt_length( $post_id, $length )`
trims it and returns `the_excerpt` markup.

- **RNDP-01 Manual excerpt wins.** Given a post with a hand-written excerpt and different content, when the source is requested, then it is the manual excerpt text and contains no content words.
- **RNDP-02 Block-level boundaries separate words.** Given content where words touch across block-level tags (`<p>`, `<div>`, `<li>`, `<h1>`–`<h6>`, `<br>`, `<figure>`/`<figcaption>`, `<blockquote>`, table cells), when the source is requested, then each pair of adjacent words is separated by exactly one space (e.g. `<p>totam</p><p>Porro</p>` gives `totam Porro`).
- **RNDP-03 Nested layout blocks keep their text, separated.** Given paragraphs inside `core/group` and `core/columns` › `core/column` blocks, when the source is requested, then every paragraph's text is present and no two paragraphs' words are merged.
- **RNDP-04 Inline tags don't split words.** Given a word split across `<strong>`, `<em>` or `<a>` tags, when the source is requested, then the word stays whole.
- **RNDP-05 Shortcodes are stripped.** Given content with a registered shortcode, when the source is requested, then neither the shortcode tag nor its output appears and the surrounding words remain.
- **RNDP-06 Non-text blocks are removed.** Given content with an image block (with caption) between paragraphs, when the source is requested, then the caption and image markup don't appear and the paragraphs remain.
- **RNDP-07 A PostCrafts block inside the content isn't rendered.** Given a post whose content holds a Post Grid block plus a paragraph, and other published posts exist, when the source is requested, then only the paragraph text is returned (no other post's title or excerpt, no recursion).
- **RNDP-08 Whitespace is collapsed and trimmed.** Given text with newlines, tabs and runs of spaces, when the source is requested, then words are separated by single spaces with no leading or trailing whitespace.
- **RNDP-09 Password-protected posts expose nothing.** Given a password-protected post and no password cookie, when the source or the trimmed excerpt is requested, then both are `''`.
- **RNDP-10 Trimmed to N words with an ellipsis.** Given content longer than N words, when `post_crafts_excerpt_length( $id, N )` is called, then the text holds exactly the first N words followed by `&hellip;`; given content of at most N words, no ellipsis is added.
- **RNDP-11 Empty text gives an empty string.** Given a post with no text (empty content, or only non-text blocks), when either function is called, then it returns `''` (not an empty `<p>`).
- **RNDP-12 Output is `the_excerpt` markup for the given post.** Given a different global `$post` is set up, when `post_crafts_excerpt_length( $id, N )` is called, then it returns the text of post `$id` (not the global post) wrapped in `<p>…</p>`.
- **RNDP-13 No site-wide excerpt filters.** Given the plugin is loaded, when core applies `excerpt_length` and `excerpt_more`, then the values are unchanged (55 and ` [&hellip;]`) and a theme's `get_the_excerpt()` is not affected by the plugin.

## Card templates (`inc/templates/block-templates/post-grid.php`, `post-list.php`)

Each criterion applies to both the Post Grid and the Post List block.

- **RNDP-20 Article wrapper.** When a block renders a post, then the card is `<article id="post-{ID}" class="post-grid|post-list">`.
- **RNDP-21 Content order.** Given a categorized post with text, when the card renders, then the children of `.post-content` are, in order: `.cat-links`, `.entry-title`, `.entry-meta`, `.entry-summary`.
- **RNDP-22 Featured image.** Given a post with a real featured image, when the card renders, then `figure.post-thumbnail > a > img` exists (thumb-330x185) and there is no `.image-placeholder`.
- **RNDP-23 No thumbnail.** Given a post without a featured image, when the card renders, then `figure.post-thumbnail > a > span.image-placeholder` exists and there is no `<img>`.
- **RNDP-24 Broken thumbnail.** Given `_thumbnail_id` points to a missing ID, a non-attachment post, or holds an array, so `has_post_thumbnail()` is true but no image HTML exists, when the card renders, then `span.image-placeholder` is shown and the figure link isn't empty.
- **RNDP-25 Excerpt off.** Given `excerpt: false`, when the card renders, then there is no `.entry-summary`.
- **RNDP-26 No empty summary.** Given excerpts are on but the post has no excerpt text, when the card renders, then there is no `.entry-summary` element.
- **RNDP-27 Excerpt length.** Given `excerptLength: N` and a longer post, when the card renders, then `.entry-summary` contains exactly N words plus an ellipsis; when `excerptLength` is unset, the block default (30) applies.
- **RNDP-28 Meta line.** When the card renders, then `.entry-meta` contains `span.byline` with a link to the author's posts page, then `span.separator`, then `span.posted-on > time.entry-date` with a W3C `datetime`.
- **RNDP-29 Category link.** Given a post in category C, when the card renders, then `.cat-links a` links to C's archive with C's name.
- **RNDP-30 Template defaults.** Given `post_crafts_template( 'block-templates/<block>' )` is called for a set-up global post without `excerpt`/`excerpt_length` variables, then the excerpt is shown (default on).

## REST response fields (`/wp/v2/posts`)

- **RNDP-40 `pcrafts_excerpt` present.** When posts are listed via `GET /wp/v2/posts`, then each item has a string `pcrafts_excerpt`.
- **RNDP-41 Source contract.** Then `pcrafts_excerpt` equals `wptexturize( post_crafts_get_excerpt_source( id ) )`, including manual excerpts and texturized quotes.
- **RNDP-42 Untrimmed.** Given content longer than any `excerptLength`, then `pcrafts_excerpt` holds every word and no ellipsis.
- **RNDP-43 Password-protected.** Given a password-protected post, when it's fetched without the password, then `pcrafts_excerpt` is `''` and contains none of the protected words.
- **RNDP-44 `featured_image` only with a thumbnail.** Given a post with a featured image, then `featured_image.src` is a non-empty URL; given a post without one, then the response has no `featured_image` key.

## REST style route (`/post-crafts/v1/style`)

Permission is `edit_post` on the given `post_id`.

- **RNDP-50** An author saving style for their own post gets 200 and the meta is stored.
- **RNDP-51** An author saving style for another user's post gets 403 and the meta is unchanged.
- **RNDP-52** An administrator saving style for any post gets 200.
- **RNDP-53** Saving style for a post ID that doesn't exist gets 403, even for an administrator.
- **RNDP-54** A logged-out request gets 401 (or 403) and nothing is stored.
- **RNDP-55** With `is_previewing`, the style is stored in `post-crafts-preview-style` and `post-crafts-style` is untouched.
- **RNDP-56** `GET /post-crafts/v1/style?post_id=` returns the saved style string, or `null` when none is saved.

## Registration and admin

- **RNDP-60 No empty admin page.** Given an administrator, when `admin_menu` fires, then no menu or submenu page with slug `post-crafts` is registered.
- **RNDP-61 Admin class removed.** Then `PostCrafts\Blocks\Inc\Admin` doesn't exist.
- **RNDP-62 New optional attributes have no defaults.** Then both blocks declare `paginationBeforeSelection` (boolean), `categoryColor`, `titleColor`, `titleHoverColor`, `metaColor`, `excerptColor` (string) and `titleFontSize` (object), none with a `default`, so `prepare_attributes_for_render( array() )` doesn't set them.
- **RNDP-63 `excerpt` defaults to boolean `true`.**
- **RNDP-64 Keywords.** Then both blocks' keywords include `postcrafts`; Post Grid includes `post grid` and Post List includes `post list`.
- **RNDP-65 Backward compatibility.** Given a block saved before this PR (only older attributes, e.g. `blockId`, `postsPerPage`, `pagination`, `excerptLength`), when it renders, then it outputs the posts with excerpts as before and no output depends on the new attributes.
- **RNDP-66 Legacy `excerpt` values.** Given a saved block whose `excerpt` is the legacy string `"true"`, when it renders, then excerpts are shown.
