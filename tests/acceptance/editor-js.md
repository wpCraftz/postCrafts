# Acceptance criteria: editor components (JS)

Scope: the editor-side React components changed in PR #10 (`fix/qa-issues`).
Each criterion is covered by Jest + React Testing Library tests whose `it()`
names start with the criterion ID.

| Area | Source | Tests |
|---|---|---|
| Select Posts picker / Query Builder | `src/components/query-builder/index.js`, `post-selection.js` | `tests/js/components/query-builder/index.test.js` |
| Styles tab | `src/components/style-settings.js` | `tests/js/components/style-settings.test.js` |
| Editor pagination preview | `src/components/edit-pagination.js` | `tests/js/components/edit-pagination.test.js` |

## Query Builder: trigger and summary

- **EDJS-01** The trigger button reads "Select Posts" when `postIds` is empty and "Modify Selection" when it has posts. The "Clear" and "Sort Posts" buttons appear only when there is a selection.
- **EDJS-02** With a selection smaller than `postsPerPage`, the panel says how many more posts can be selected, pluralized: "You can select 1 more post" / "You can select N more posts". The hint is hidden when the selection is full.

## Select Posts modal: draft selection

- **EDJS-03** Opening the modal preloads the draft from the current `postIds`: those rows are pressed (`aria-pressed="true"`), the rest are not.
- **EDJS-04** Clicking a row toggles it in the draft only. `setAttributes` is not called until "Insert Selected".
- **EDJS-05** A focused row toggles with Enter and with Space, again without calling `setAttributes`.
- **EDJS-06** Every result row is keyboard accessible: `role="button"`, `tabIndex` 0, and `aria-pressed` reflecting the draft selection.
- **EDJS-07** When the draft reaches the limit (`postsPerPage`), unselected rows are `aria-disabled="true"` and clicking them does not add them; selected rows stay enabled and can still be deselected, which re-enables the others.
- **EDJS-08** The selection counter pluralizes: "1 item is selected", "N items are selected"; at the limit it reads "Max 1 post is already selected" / "Max N posts are already selected".

## Select Posts modal: committing

- **EDJS-09** "Insert Selected" with a non-empty draft and no previous selection sets `postIds` to the draft in pick order, stores `paginationBeforeSelection` = the previous `pagination` value, sets `pagination: false`, and closes the modal.
- **EDJS-10** "Insert Selected" replacing an existing selection sets `postIds` and `pagination: false` but never writes `paginationBeforeSelection`, so the originally saved value survives.
- **EDJS-11** "Insert Selected" with an empty draft over an existing selection behaves exactly like Clear (EDJS-14).
- **EDJS-12** With no previous selection and an empty draft, "Insert Selected" is disabled and nothing is written.

## Select Posts modal: discarding

- **EDJS-13** The modal's X (Close) button, Escape, and "Cancel" each close the modal and discard the draft: `setAttributes` is not called, and reopening the modal shows the saved selection, not the discarded draft.

## Clear and sort

- **EDJS-14** "Clear" sets `postIds: []` and restores `pagination` from `paginationBeforeSelection` (true or false), then unsets `paginationBeforeSelection`. When `paginationBeforeSelection` is unset (blocks saved before it existed, or after a reload without it), `pagination` falls back to `true`.
- **EDJS-15** Reordering in the "Sort Selected Posts" modal writes only `postIds` (in the new order); it never touches `pagination` or `paginationBeforeSelection`.

## Post search request

- **EDJS-16** The initial (empty-term) request does not send `search_columns`. Typing a search term sends `search=<term>` with `search_columns=post_title` (and resets to page 1).

## Styles tab (StyleSettings)

- **EDJS-17** Renders a "Colors" panel with the Category, Title, Title Hover, Meta and Excerpt controls, and a "Typography" panel with a "Title Font Size" control.
- **EDJS-18** Picking a color in each color control calls `setAttributes` with exactly that control's attribute: `categoryColor`, `titleColor`, `titleHoverColor`, `metaColor`, `excerptColor`.
- **EDJS-19** Changing the title font size on Desktop calls `setAttributes( { titleFontSize: { value, unit } } )`, keeping existing tablet/mobile values; on Tablet/Mobile it writes the value under `tablet` / `mobile`. Blocks without a saved `titleFontSize` default to unit `px`.

## Editor pagination preview (PaginationEdit)

- **EDJS-20** `type="pagination"` (numeric) renders the front-end structure: `div.pcrafts-pagination.pcrafts-numberic[role=navigation][aria-label="Posts pagination"] > ul.pcrafts-pages > li.page-numbers > button.pcrafts-page-btn[type=button]`, pages 1–3 plus "Next"; only page 1 has `li.current` and `aria-current="page"`; "Next" has `aria-label="Next page"`.
- **EDJS-21** `type="arrow"` renders `div.pcrafts-pagination.pcrafts-arrow` with `role="navigation"` and `aria-label="Posts pagination"` (as on the front end), a disabled `button.pcrafts-prev` labelled "Previous page" and an enabled `button.pcrafts-next` labelled "Next page", both `type="button"`.
- **EDJS-22** `type="loadmore"` renders `div.pcrafts-pagination.pcrafts-loadmore > button.pcrafts-loadmore-btn[type=button]` reading "Load More".
- **EDJS-23** The `alignment` prop is added as a class on the wrapper for every type.
