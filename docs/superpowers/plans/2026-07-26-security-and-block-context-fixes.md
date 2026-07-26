# Security Hardening & Block Context Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close four security holes (stored XSS, IDOR, LFI, and query injection) in PostCrafts' REST/AJAX endpoints, and fix the block-context gap that makes the current-post-ID resolution wrong outside the classic post editor.

**Architecture:** Each fix is a targeted, backward-compatible patch to existing files — no new files, no attribute schema changes, no data migration. PHP fixes tighten input/output handling in three existing classes. JS fixes thread post-ID resolution through Gutenberg's `usesContext` mechanism instead of a hardcoded `core/editor` selector call buried inside a shared hook.

**Tech Stack:** WordPress 7.4+/PHP, Gutenberg block.json (`apiVersion: 3`), React via `@wordpress/element`/`@wordpress/data`, `@wordpress/scripts` build.

## Global Constraints

- PHP 7.4+ / MySQL 5.6+ (per `post-crafts.php` plugin header and CLAUDE.md) — no PHP 8-only syntax.
- Plugin is **live with existing content** — no attribute schema changes, no post-meta format changes, no data migration routines.
- No new automated test scaffolding (Jest/PHPUnit) — out of scope per the design spec; verify each fix manually as described in each task.
- No changes to the AJAX request payload shape sent by `src/scripts/pagination.js` (`query`, `template`, `postId`, `blockId`, `paged`) — only server-side validation changes.
- Design spec: `docs/superpowers/specs/2026-07-26-security-and-block-context-fixes-design.md` — every task below implements one section of it.

---

### Task 1: Sanitize dynamic CSS output (stored XSS fix)

**Files:**
- Modify: `inc/classes/class-style-loader.php:62-69`

**Interfaces:**
- Consumes: none — standalone fix.
- Produces: none — no other task depends on this change.

**Problem being fixed:** `print_css()` echoes `$this->css` (sourced from post meta) into `wp_head` with zero sanitization, allowing stored XSS if the meta ever contains injected markup (see Task 2 for how untrusted writes are prevented going forward).

- [ ] **Step 1: Read the current method to confirm line numbers before editing**

Open `inc/classes/class-style-loader.php` and confirm `print_css()` still matches:

```php
public function print_css() {

	if ( ! empty( $this->css ) ) {
		echo "\n";
		echo '<style class="pcrafts-dynamic-styles">';
		echo $this->css;
		echo '</style>';
	}
}
```

If the line numbers or content differ from this, re-read the surrounding lines before proceeding.

- [ ] **Step 2: Apply the sanitization fix**

Replace the body of `print_css()` so the CSS is stripped of any HTML tags immediately before output:

```php
public function print_css() {

	if ( ! empty( $this->css ) ) {
		echo "\n";
		echo '<style class="pcrafts-dynamic-styles">';
		echo wp_strip_all_tags( $this->css );
		echo '</style>';
	}
}
```

- [ ] **Step 3: Manually verify the fix**

1. In the WordPress admin, edit any post that uses a Post Grid or Post List block and save it (this populates `post-crafts-style` meta via the existing editor flow).
2. Using WP-CLI in the site's shell (Local's "Open Site Shell", or wherever `wp` runs against this install), inject a test payload directly into the meta to simulate a bypassed save path:
   ```bash
   wp post meta update <POST_ID> post-crafts-style '.test{color:red}</style><script>window.__pc_xss=1</script>'
   ```
3. Visit that post on the front end and view page source. Confirm the `<script>` fragment is **not** present verbatim inside the `<style class="pcrafts-dynamic-styles">` block — only the stripped text remains, and `window.__pc_xss` is not defined in the browser console.
4. Re-save the post normally afterward to restore legitimate style meta.

- [ ] **Step 4: Commit**

```bash
git add inc/classes/class-style-loader.php
git commit -m "fix: sanitize dynamic CSS output to prevent stored XSS"
```

---

### Task 2: Harden the style-save REST endpoint (IDOR + input sanitization)

**Files:**
- Modify: `inc/classes/class-api.php:41-106`

**Interfaces:**
- Consumes: none — standalone fix.
- Produces: none — no other task depends on this change.

**Problem being fixed:** `save_style`'s permission callback only checks the blanket `edit_posts` capability, not whether the caller may edit the *specific* `post_id` supplied — any user with `edit_posts` can overwrite any post's style meta. The `style` parameter is also stored without sanitization.

- [ ] **Step 1: Read the current file to confirm line numbers before editing**

Open `inc/classes/class-api.php` and confirm `register_end_points()` and `save_style()` still match the versions shown below before editing.

- [ ] **Step 2: Replace the inline permission callback with a capability-checked method**

In `register_end_points()`, change:

```php
'permission_callback' => function () {
	return current_user_can( 'edit_posts' );
},
```

to:

```php
'permission_callback' => array( $this, 'can_save_style' ),
```

- [ ] **Step 3: Add the `can_save_style` method and sanitize input in `save_style`**

Add a new method directly above `save_style()`:

```php
/**
 * Check if the current user may save style for the given post.
 *
 * @param \WP_REST_Request $request Request object.
 *
 * @return bool
 */
public function can_save_style( $request ) {
	$post_id = (int) $request->get_param( 'post_id' );

	return $post_id && current_user_can( 'edit_post', $post_id );
}
```

Then update `save_style()` to sanitize the incoming CSS before it's ever persisted (defense in depth alongside Task 1's output-side fix):

```php
function save_style( $request ) {
	$post_id       = $request->get_param( 'post_id' );
	$style         = wp_strip_all_tags( $request->get_param( 'style' ) );
	$block_id      = $request->get_param( 'block_id' );
	$is_previewing = $request->get_param( 'is_previewing' );

	if ( $block_id === 'all' ) {
		update_post_meta( $post_id, true == $is_previewing ? 'post-crafts-preview-style' : 'post-crafts-style', $style );
	} else {
		$current_style = get_post_meta( $post_id, 'post-crafts-style', true );
		$new_style     = array();

		if ( ! empty( $current_style ) ) {
			$new_style              = json_decode( $current_style, true );
			$new_style[ $block_id ] = $style;
		} else {
			$new_style[ $block_id ] = $style;
		}

		update_post_meta( $post_id, 'post-crafts-style', json_encode( $new_style ) );
	}

	return rest_ensure_response( 'Style saved successfully' );
}
```

(Only the `$style = wp_strip_all_tags( ... )` line changes in this method — the rest is unchanged, shown in full for clarity.)

- [ ] **Step 4: Manually verify the authorization fix**

You'll need two user accounts on the test site: one Administrator, one Contributor (create with `wp user create contributor1 contributor1@example.test --role=contributor` via the site's shell if you don't have one).

1. Log in to `wp-admin` as the Contributor in one browser session.
2. Open browser dev tools' Network/Console tab and run (adjust the REST nonce — grab it from `wp.api.nonce` in the console, or from the block editor's own successful style-save request headers while on a post the Contributor *can* edit):
   ```js
   fetch('/wp-json/post-crafts/v1/style', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wpApiSettings.nonce },
     body: JSON.stringify({ post_id: <ID_OF_A_POST_THE_CONTRIBUTOR_CANNOT_EDIT>, block_id: 'all', style: '.x{color:blue}' })
   }).then(r => r.json()).then(console.log);
   ```
3. Confirm the response is a `403 rest_forbidden` error, not `"Style saved successfully"`.
4. Repeat step 2 using a `post_id` the Contributor *does* own/can edit — confirm it now succeeds (proves the fix isn't overly restrictive).
5. Log in as an Editor/Administrator and confirm saving style on any arbitrary post still works as before (block editor's normal save-style flow).

- [ ] **Step 5: Commit**

```bash
git add inc/classes/class-api.php
git commit -m "fix: enforce per-post authorization and sanitize input on style save endpoint"
```

---

### Task 3: Whitelist the AJAX pagination template parameter (LFI fix)

**Files:**
- Modify: `inc/classes/class-plugin.php:74-104`

**Interfaces:**
- Consumes: none — standalone fix.
- Produces: none — Task 4 edits the same method but a different part of it; apply Task 3 first so Task 4's diff context matches.

**Problem being fixed:** `$_POST['template']` is concatenated directly into an `include`-based template path with no whitelist, allowing path traversal to include arbitrary `.php` files. The action is registered `nopriv`, so this is anonymously reachable.

- [ ] **Step 1: Read the current method to confirm it matches before editing**

Open `inc/classes/class-plugin.php` and confirm `post_crafts_pagination()` still matches:

```php
public function post_crafts_pagination() {
	if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'post-crafts' ) ) {
		return;
	}

	$attributes    = post_crafts_get_block_attributes( $_POST['postId'], $_POST['blockId'] );
	$fetched_posts = new \WP_Query( $_POST['query'] );

	if ( $fetched_posts->have_posts() ) {
		$new_posts = array();
		while ( $fetched_posts->have_posts() ) {
			$fetched_posts->the_post();
			$new_posts[] = post_crafts_template(
				'block-templates/' . $_POST['template'],
				array(
					'excerpt'        => $attributes['excerpt'],
					'excerpt_length' => $attributes['excerptLength'],
				),
			);
		}
		wp_send_json_success( $new_posts );
		wp_reset_postdata();
	} else {
		wp_send_json_error(
			array(
				__( 'No more posts found', 'post-crafts' ),
			)
		);
	}
	wp_die();
}
```

- [ ] **Step 2: Add the template whitelist**

Add this line at the top of the method, right after the nonce check:

```php
public function post_crafts_pagination() {
	if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'post-crafts' ) ) {
		return;
	}

	$allowed_templates = array( 'pagination', 'loadmore', 'arrow' );
	$template           = in_array( $_POST['template'], $allowed_templates, true ) ? $_POST['template'] : 'pagination';

	$attributes    = post_crafts_get_block_attributes( $_POST['postId'], $_POST['blockId'] );
	$fetched_posts = new \WP_Query( $_POST['query'] );
```

Then change the `post_crafts_template()` call to use the validated `$template` variable instead of the raw superglobal:

```php
			$new_posts[] = post_crafts_template(
				'block-templates/' . $template,
				array(
					'excerpt'        => $attributes['excerpt'],
					'excerpt_length' => $attributes['excerptLength'],
				),
			);
```

(Leave the `new \WP_Query( $_POST['query'] )` line as-is for now — Task 4 replaces it.)

- [ ] **Step 3: Manually verify the fix**

1. Load a page containing a Post Grid or Post List block with pagination enabled, and confirm normal pagination/load-more still works by clicking through pages in the browser.
2. Using browser dev tools (or curl with a valid nonce captured from the page's `data-*` attributes / the `POSTCRAFTS.nonce` global), send a request with a malicious `template` value:
   ```js
   fetch(POSTCRAFTS.urls.ajaxUrl, {
     method: 'POST',
     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
     body: new URLSearchParams({
       action: 'paginate_posts',
       _ajax_nonce: POSTCRAFTS.nonce,
       postId: '<A_VALID_POST_ID>',
       blockId: '<A_VALID_BLOCK_ID>',
       paged: '1',
       template: '../../../../wp-config',
       query: JSON.stringify({ paged: 1 }),
     }),
   }).then(r => r.json()).then(console.log);
   ```
3. Confirm the response succeeds using the default `pagination` template markup (no error, no leaked file contents) — proving the traversal value was rejected and replaced with the safe default rather than used directly.

- [ ] **Step 4: Commit**

```bash
git add inc/classes/class-plugin.php
git commit -m "fix: whitelist AJAX pagination template parameter to prevent local file inclusion"
```

---

### Task 4: Allowlist AJAX pagination query parameters and sanitize IDs (query injection fix)

**Files:**
- Modify: `inc/classes/class-plugin.php:74-104` (as updated by Task 3)

**Interfaces:**
- Consumes: the `$template` variable and whitelist pattern introduced in Task 3 — this task must run after Task 3.
- Produces: none — no other task depends on this change.

**Problem being fixed:** `$_POST['query']` is passed directly into `new WP_Query()` with no key restrictions, letting an anonymous caller request private/draft posts or an unbounded `posts_per_page`. `$_POST['postId']`/`$_POST['blockId']` are also used raw.

- [ ] **Step 1: Read the current (post-Task-3) method to confirm it matches before editing**

Confirm `post_crafts_pagination()` now looks like the result of Task 3 (template whitelist in place, `$_POST['query']` still used raw).

- [ ] **Step 2: Sanitize `postId`/`blockId` and build an allowlisted query args array**

Replace:

```php
	$attributes    = post_crafts_get_block_attributes( $_POST['postId'], $_POST['blockId'] );
	$fetched_posts = new \WP_Query( $_POST['query'] );
```

with:

```php
	$post_id  = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
	$block_id = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';

	$attributes = post_crafts_get_block_attributes( $post_id, $block_id );
	$raw_query  = isset( $_POST['query'] ) && is_array( $_POST['query'] ) ? $_POST['query'] : array();

	$allowed_query_keys = array(
		'post_type',
		'posts_per_page',
		'paged',
		'orderby',
		'order',
		'tax_query',
		'post__in',
		'post__not_in',
		'ignore_sticky_posts',
	);

	$query_args = array_intersect_key( $raw_query, array_flip( $allowed_query_keys ) );

	if ( isset( $query_args['posts_per_page'] ) ) {
		$query_args['posts_per_page'] = min( 50, max( 1, (int) $query_args['posts_per_page'] ) );
	}

	// Never allow the client to control post visibility.
	$query_args['post_status'] = 'publish';

	$fetched_posts = new \WP_Query( $query_args );
```

- [ ] **Step 3: Manually verify the fix**

1. Confirm normal pagination and load-more still work end-to-end in the browser (same check as Task 3, Step 3.1).
2. Using the same dev-tools `fetch()` pattern as Task 3, send a request with a malicious `query`:
   ```js
   fetch(POSTCRAFTS.urls.ajaxUrl, {
     method: 'POST',
     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
     body: new URLSearchParams({
       action: 'paginate_posts',
       _ajax_nonce: POSTCRAFTS.nonce,
       postId: '<A_VALID_POST_ID>',
       blockId: '<A_VALID_BLOCK_ID>',
       paged: '1',
       template: 'pagination',
       query: JSON.stringify({ paged: 1, post_status: ['private', 'draft'], posts_per_page: -1 }),
     }),
   }).then(r => r.json()).then(console.log);
   ```
   First, create a private or draft post on the test site so there's something to try to leak.
3. Confirm the returned markup does **not** include the private/draft post, and that the count of returned posts is bounded (not the entire published post set at once) — proving `post_status` and `posts_per_page` were overridden server-side rather than taken from the request.

- [ ] **Step 4: Commit**

```bash
git add inc/classes/class-plugin.php
git commit -m "fix: allowlist AJAX pagination query args and sanitize post/block IDs"
```

---

### Task 5: Make `useFetchPosts` accept post ID and exclusion flag as parameters

**Files:**
- Modify: `src/libs/fetchPosts.js`

**Interfaces:**
- Consumes: none — standalone refactor of the shared hook.
- Produces: `useFetchPosts(props)` gains two new optional props: `currentPostId` (`number|undefined`) and `excludeCurrentPost` (`boolean|undefined`). When `excludeCurrentPost` is falsy, or `currentPostId` is falsy, no `exclude` key is added to the underlying `getEntityRecords` query. When both are truthy, `exclude: [currentPostId]` is merged into the query exactly as before. **Tasks 6 and 7 call `useFetchPosts` with these two new props and rely on this exact behavior.**

**Problem being fixed:** The hook currently calls `select('core/editor').getCurrentPostId()` directly, which only works on the classic post-edit screen, and it always excludes the current post regardless of the `excludeCurrentPost` attribute (never wired in from either block's `edit.js`).

- [ ] **Step 1: Read the current file to confirm it matches before editing**

Open `src/libs/fetchPosts.js` and confirm the `useFetchPosts` function signature (lines 25-39) and the query-building block (lines 62-80) still match the current codebase.

- [ ] **Step 2: Add the new props to the destructure**

Change:

```js
const useFetchPosts = ( props ) => {
	const {
		postIds,
		postsPerPage,
		customQuery = {},
		postType = 'post',
		taxQuery = {},
		taxRelation,
		catOperator,
		tagOperator,
		withTaxRelation,
	} = props;
```

to:

```js
const useFetchPosts = ( props ) => {
	const {
		postIds,
		postsPerPage,
		customQuery = {},
		postType = 'post',
		taxQuery = {},
		taxRelation,
		catOperator,
		tagOperator,
		withTaxRelation,
		currentPostId,
		excludeCurrentPost,
	} = props;
```

- [ ] **Step 3: Replace the internal selector call with the passed-in value, gated by the exclusion flag**

Change:

```js
			/* eslint @wordpress/no-unused-vars-before-return: 0 */
			const { getEntityRecords, getTaxonomies } = select( coreStore );
			const currentPostId = select( 'core/editor' ).getCurrentPostId();

			const taxonomies = getTaxonomies( {
				per_page: -1,
				context: 'view',
			} );

			let query = {
				exclude: [ currentPostId ],
			};

			if ( Object.keys( customQuery ).length > 0 ) {
				query = {
					...customQuery,
					exclude: [ currentPostId ],
				};
			}
```

to:

```js
			/* eslint @wordpress/no-unused-vars-before-return: 0 */
			const { getEntityRecords, getTaxonomies } = select( coreStore );

			const taxonomies = getTaxonomies( {
				per_page: -1,
				context: 'view',
			} );

			const excludeArgs =
				excludeCurrentPost && currentPostId
					? { exclude: [ currentPostId ] }
					: {};

			let query = { ...excludeArgs };

			if ( Object.keys( customQuery ).length > 0 ) {
				query = {
					...customQuery,
					...excludeArgs,
				};
			}
```

- [ ] **Step 4: Add the new props to the `useSelect` dependency array**

Change the dependency array at the end of the `useSelect` call:

```js
		[
			taxQuery,
			postIds,
			customQuery,
			postsPerPage,
			postType,
			taxRelation,
			catOperator,
			tagOperator,
			withTaxRelation,
		]
```

to:

```js
		[
			taxQuery,
			postIds,
			customQuery,
			postsPerPage,
			postType,
			taxRelation,
			catOperator,
			tagOperator,
			withTaxRelation,
			currentPostId,
			excludeCurrentPost,
		]
```

- [ ] **Step 5: Verify the build compiles**

Run: `npm run build` (or `npx wp-scripts build` if that's the underlying script in `package.json`)
Expected: build completes with no new errors. (Manual functional verification happens in Tasks 6 and 7 once the hook is actually wired up with real values — this hook has no consumers passing the new props until then, so there's nothing user-visible to check yet.)

- [ ] **Step 6: Commit**

```bash
git add src/libs/fetchPosts.js
git commit -m "refactor: make useFetchPosts accept currentPostId/excludeCurrentPost as params"
```

---

### Task 6: Wire block context into the Post Grid block

**Files:**
- Modify: `src/blocks/post-grid/block.json`
- Modify: `src/blocks/post-grid/edit.js`

**Interfaces:**
- Consumes: `useFetchPosts` from Task 5 — calls it with `currentPostId` and `excludeCurrentPost` props.
- Produces: none.

**Problem being fixed:** The block doesn't declare `usesContext`, so it can't resolve the current post ID correctly in Query Loop/Site Editor/reusable-block contexts, and it never passes its own `excludeCurrentPost` attribute to `useFetchPosts`, so editor preview always excludes the current post regardless of that setting.

- [ ] **Step 1: Read both files to confirm they match before editing**

Confirm `src/blocks/post-grid/block.json` attributes still include `excludeCurrentPost` (currently around line 83-86), and `edit.js`'s `Edit` function signature and `useFetchPosts` call (lines 62-76 and 104-113) still match.

- [ ] **Step 2: Add `usesContext` to `block.json`**

In `src/blocks/post-grid/block.json`, add a top-level `usesContext` key. Insert it after `"description"` and before `"supports"`:

```json
	"description": "Arrange posts in Grids.",
	"usesContext": [ "postId", "postType" ],
	"supports": {
```

- [ ] **Step 3: Accept `context` in the Edit function and destructure `excludeCurrentPost`**

Change:

```js
export default function Edit( { name, attributes, setAttributes, clientId } ) {
	const {
		blockId,
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerptLength,
		pagination,
		paginationType,
		paginationAlignment,
	} = attributes;
```

to:

```js
export default function Edit( { name, attributes, setAttributes, clientId, context } ) {
	const {
		blockId,
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerptLength,
		pagination,
		paginationType,
		paginationAlignment,
		excludeCurrentPost,
	} = attributes;
```

- [ ] **Step 4: Resolve the current post ID from context, with the existing editor selector as fallback**

Add this right before the `useFetchPosts` call (before the `/** * Fetch or Reorder posts */` comment block, around line 101):

```js
	const currentPostId = useSelect(
		( select ) =>
			context?.postId ?? select( 'core/editor' ).getCurrentPostId(),
		[ context?.postId ]
	);
```

- [ ] **Step 5: Pass the new values into `useFetchPosts`**

Change:

```js
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
		taxRelation,
		catOperator,
		tagOperator,
	} );
```

to:

```js
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
		taxRelation,
		catOperator,
		tagOperator,
		currentPostId,
		excludeCurrentPost,
	} );
```

- [ ] **Step 6: Manually verify in the browser**

1. Run `npm run start` (or the project's watch/build script) so editor changes are compiled.
2. On the classic post-edit screen, add a Post Grid block, enable "Exclude current post" in its settings (Query Builder panel), and confirm the block's editor preview no longer shows the post currently being edited. Disable the toggle and confirm the current post now **does** appear in the preview (this is the regression that didn't work before — verify it actually toggles now).
3. In the Site Editor, add a Query Loop block, place a Post Grid block inside it with "Exclude current post" enabled, and confirm each looped post's grid preview excludes that specific looped post (not post ID `0`/undefined) — this proves `context.postId` is being consumed correctly.
4. Confirm the front end still renders correctly for an existing page using this block (no console errors, posts display as before).

- [ ] **Step 7: Commit**

```bash
git add src/blocks/post-grid/block.json src/blocks/post-grid/edit.js
git commit -m "fix: resolve current post ID from block context in Post Grid block"
```

---

### Task 7: Wire block context into the Post List block

**Files:**
- Modify: `src/blocks/post-list/block.json`
- Modify: `src/blocks/post-list/edit.js`

**Interfaces:**
- Consumes: `useFetchPosts` from Task 5 — calls it with `currentPostId` and `excludeCurrentPost` props. Same pattern as Task 6, applied to the Post List block.
- Produces: none.

**Problem being fixed:** Same as Task 6, for the Post List block.

- [ ] **Step 1: Read both files to confirm they match before editing**

Confirm `src/blocks/post-list/block.json` attributes include `excludeCurrentPost`, and `edit.js`'s `Edit` function signature and `useFetchPosts` call (lines 61-74 and 102-111) still match.

- [ ] **Step 2: Add `usesContext` to `block.json`**

In `src/blocks/post-list/block.json`, add `"usesContext": [ "postId", "postType" ]` after `"description"` and before `"supports"`, same placement pattern as Task 6.

- [ ] **Step 3: Accept `context` in the Edit function and destructure `excludeCurrentPost`**

Change:

```js
export default function Edit( { name, attributes, setAttributes, clientId } ) {
	const {
		blockId,
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerptLength,
		pagination,
		paginationType,
	} = attributes;
```

to:

```js
export default function Edit( { name, attributes, setAttributes, clientId, context } ) {
	const {
		blockId,
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerptLength,
		pagination,
		paginationType,
		excludeCurrentPost,
	} = attributes;
```

- [ ] **Step 4: Resolve the current post ID from context, with the existing editor selector as fallback**

Add this right before the `useFetchPosts` call (before the `/** * Fetch or Reorder posts */` comment block, around line 99):

```js
	const currentPostId = useSelect(
		( select ) =>
			context?.postId ?? select( 'core/editor' ).getCurrentPostId(),
		[ context?.postId ]
	);
```

- [ ] **Step 5: Pass the new values into `useFetchPosts`**

Change:

```js
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
		taxRelation,
		catOperator,
		tagOperator,
	} );
```

to:

```js
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
		taxRelation,
		catOperator,
		tagOperator,
		currentPostId,
		excludeCurrentPost,
	} );
```

- [ ] **Step 6: Manually verify in the browser**

Repeat the same verification as Task 6, Step 6, using a Post List block instead of Post Grid.

- [ ] **Step 7: Commit**

```bash
git add src/blocks/post-list/block.json src/blocks/post-list/edit.js
git commit -m "fix: resolve current post ID from block context in Post List block"
```
