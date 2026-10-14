# Acceptance criteria: front-end pagination, text utils, style generator (PR #10)

Each criterion is a testable statement of intended behavior. Jest tests name their
`it()` with the criterion ID (e.g. `FEJS-04: …`). Tests live in:

- `tests/js/scripts/pagination.test.js` (FEJS-01 – FEJS-36)
- `tests/js/libs/utils.test.js` (FEJS-40 – FEJS-47)
- `tests/js/editor/style-generator/style-generator.test.js` (FEJS-50 – FEJS-59)

Fixtures mirror the PHP markup: `.pcrafts-block` › `.pcrafts-posts-wrapper` +
`.pcrafts-pagination` with `data-*` attributes (`inc/templates/block-templates/{pagination,arrow,loadmore}.php`),
numeric items from `post_crafts_pagination()` (`ul.pcrafts-pages > li.page-numbers[data-page] > button.pcrafts-page-btn`).

## 1. Pagination script (`src/scripts/pagination.js`)

### Requests and loading state (all pagination types)

| ID | Criterion |
|---|---|
| FEJS-01 | A click sends one `jQuery.post( POSTCRAFTS.urls.ajaxUrl, data )` where `data` is exactly `{ action: 'paginate_posts', _ajax_nonce: POSTCRAFTS.nonce, postId, blockId, paged, template, query }`; `postId`, `blockId`, `template` come from the wrapper's `data-*`, `paged` is the requested page (number) and `query` is `{ ...JSON.parse( data-query ), paged }`. Same shape for numeric, arrow and Load More. |
| FEJS-02 | A wrapper runs one request at a time: a second click (same or different control) made before the first request settles sends no request. |
| FEJS-03 | Once a request settles (success, error response or rejected/network failure) the wrapper accepts clicks again. |
| FEJS-04 | Two blocks on one page don't block each other: a pending request in block A does not stop a click in block B from sending its request. |
| FEJS-05 | While a request is in flight the pagination wrapper and the block's posts wrapper have the `is-loading` class and the posts wrapper has `aria-busy="true"`. |
| FEJS-06 | When the request settles, `is-loading` is removed from both and `aria-busy` is removed, on success, on an error response and on a rejected request. |

### Load More

| ID | Criterion |
|---|---|
| FEJS-10 | A successful Load More appends the returned posts after the existing ones (nothing removed) and sets `data-page` to the loaded page. |
| FEJS-11 | After loading a full page that is not the last one, the button stays enabled (no `disabled` attribute, no `disabled` class) and focus is not moved. |
| FEJS-12 | After loading page `data-max-page`, the button is disabled: `disabled` attribute **and** `disabled` class. |
| FEJS-13 | After loading a short page (fewer posts than `data-posts-per-page`), the button is disabled (attribute and class), even before `data-max-page`. |
| FEJS-14 | An error response (`success: false`) disables the button (attribute and class), appends nothing and leaves `data-page` unchanged. |
| FEJS-15 | A rejected request (network failure) leaves the button enabled, appends nothing and leaves `data-page` unchanged, so the user can retry. |
| FEJS-16 | A disabled button ignores clicks (no request): both the server-rendered `disabled` attribute (max page ≤ 1) and the legacy `disabled` class alone. |
| FEJS-17 | When the button becomes disabled after a load, focus moves to the first newly appended post, which gets `tabindex="-1"` (an existing `tabindex` is kept) and is focused with `preventScroll: true`. |
| FEJS-18 | Load More never scrolls the page (`scrollIntoView` is not called). |

### Numeric pagination

| ID | Criterion |
|---|---|
| FEJS-20 | Clicking a page item's `li` or its inner `button.pcrafts-page-btn` requests that item's `data-page` and **replaces** the posts with the response. |
| FEJS-21 | After a successful navigation exactly one item has `current`, it is the item for the new page, and `aria-current="page"` is on its button and nowhere else. |
| FEJS-22 | After every successful navigation (Next, Prev, first/last page, middle pages) the items match what `post_crafts_pagination( max, newPage )` renders for the new page: same `hide`/`current` classes, `data-page` values, labels and `aria-current`, for max pages 2, 3, 4, 5 and 10. |
| FEJS-23 | Clicks on the current page, on hidden items (`hide`) and on the dots send no request and change nothing. |
| FEJS-24 | `data-page` on the wrapper changes only on success; an error response or a rejected request leaves `data-page`, the items and the posts unchanged. |

### Arrow pagination

| ID | Criterion |
|---|---|
| FEJS-30 | Next requests `data-page + 1` and Prev requests `data-page - 1`; the posts are replaced. |
| FEJS-31 | Prev is disabled on page 1 and Next on the last page (attribute and class); both are updated after every successful navigation, and enabled again when leaving the edge. |
| FEJS-32 | A disabled arrow ignores clicks (no request). |
| FEJS-33 | An error response or rejected request leaves the posts, `data-page` and both buttons' states unchanged. |

### Reveal after replacing posts (numeric and arrow)

| ID | Criterion |
|---|---|
| FEJS-34 | After a successful numeric or arrow navigation the posts wrapper gets `tabindex="-1"` (an existing `tabindex` is kept) and is focused with `preventScroll: true`. |
| FEJS-35 | The block is scrolled into view (`block: 'start'`) only when its top is above the viewport (`getBoundingClientRect().top < 0`); otherwise `scrollIntoView` is not called. |
| FEJS-36 | Scrolling uses `behavior: 'auto'` when `(prefers-reduced-motion: reduce)` matches, otherwise `'smooth'` (also when `matchMedia` is unavailable). |

## 2. `trimWords( text, numWords, more = '…' )` (`src/libs/utils.js`)

Mirrors `post_crafts_excerpt_length()` → `wp_trim_words( $text, absint( $length ), '&hellip;' )` for
word-based locales, so the editor excerpt matches the front end. Expected values were
checked against PHP running the word branch of WP core's `wp_trim_words()`.

| ID | Criterion |
|---|---|
| FEJS-40 | Text with more than N words returns the first N words followed by `more` (default `…`, U+2026, no space before it). |
| FEJS-41 | Text with exactly N or fewer words is returned without `more`. |
| FEJS-42 | Words are split on runs of space, tab, CR and LF; leading/trailing whitespace is dropped and words are joined with a single space. Other characters (punctuation, non-breaking space) are part of words. |
| FEJS-43 | Empty, `null`, `undefined` and whitespace-only text return `''` (never `more`). |
| FEJS-44 | `numWords` of 0 returns only `more` for non-empty text and `''` for empty text. |
| FEJS-45 | `numWords` is converted like PHP `absint()`: numeric strings and floats are truncated to an integer, negative numbers use their absolute value, and non-numeric/missing values count as 0. |
| FEJS-46 | A custom `more` string is appended only when the text was trimmed. |
| FEJS-47 | Output equals the PHP `wp_trim_words()` reference for a fixed table of inputs. |

## 3. Style generator (`src/editor/style-generator`, `src/editor/dynamic-attributes`)

| ID | Criterion |
|---|---|
| FEJS-50 | For blocks that only use pre-PR #10 attributes, the generated CSS is byte-for-byte the CSS the `main` branch (pre-PR) generator produced (grid and list, defaults, responsive values, pagination colors, gradient, pagination off). |
| FEJS-51 | Unset new attributes (`categoryColor`, `titleColor`, `titleHoverColor`, `metaColor`, `excerptColor`, `titleFontSize`) emit nothing; an empty string color emits nothing. |
| FEJS-52 | `categoryColor`, `titleColor`, `metaColor`, `excerptColor` emit their documented rules scoped to `.pcrafts-block.pcrafts-block-{blockId}` (meta/excerpt also target the inner `a`/`p`). |
| FEJS-53 | `titleHoverColor` targets both `.entry-title a:hover` and the card hover (`article.post-grid:hover` / `article.post-list:hover` `.entry-title a`). |
| FEJS-54 | `titleFontSize` emits `font-size: {value}{unit}; line-height: 1.25` on `.entry-title`; tablet values go in `@media (max-width: 991px)` and mobile in `@media (max-width: 767px)`, falling back to the desktop unit when the device has none. |
| FEJS-55 | `titleFontSize` with only a tablet or mobile value (no desktop value) emits only that media query and no `undefined` text. |
| FEJS-56 | `range()` returns `''` when the value is missing (`undefined`, `null`, `''`, or no value object), never `undefinedpx`; `0` is a valid value. |
| FEJS-57 | `range()` uses the value's own unit, falling back to the passed fallback unit; `color()` substitutes the value. |
| FEJS-58 | New card style rules don't depend on `pagination` (emitted when pagination is off). |
| FEJS-59 | A responsive device entry with no value (e.g. only a unit picked on Tablet/Mobile) emits no rule and no empty `@media` block. |
