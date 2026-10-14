/**
 * DOM fixtures for the front-end pagination tests.
 *
 * They mirror the server markup: src/blocks/<block>/render.php, the templates in
 * inc/templates/block-templates/{pagination,arrow,loadmore}.php and
 * post_crafts_pagination() in inc/helpers/custom-functions.php.
 */

export const QUERY = {
	posts_per_page: 3,
	post_status: 'publish',
	paged: 1,
	order: 'DESC',
	orderby: 'date',
	post_type: 'post',
	ignore_sticky_posts: true,
};

const escAttr = ( value ) =>
	String( value )
		.replace( /&/g, '&amp;' )
		.replace( /"/g, '&quot;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );

/**
 * Article markup like inc/templates/block-templates/post-grid.php (trimmed).
 *
 * @param {number} id Post ID.
 * @return {string} HTML.
 */
export const article = ( id ) =>
	`<article id="post-${ id }" class="post-grid post-${ id }"><h2 class="entry-title"><a href="#p${ id }">Post ${ id }</a></h2></article>`;

/**
 * Port of PHP post_crafts_pagination( $max_page, $current_page ).
 *
 * @param {number} maxPage     Max page.
 * @param {number} currentPage Current page.
 * @return {string} HTML (empty when there is a single page).
 */
export const pagesMarkup = ( maxPage, currentPage ) => {
	if ( maxPage <= 1 ) {
		return '';
	}
	if ( currentPage < 1 ) {
		currentPage = 1;
	}

	const hide = ' hide';
	const item = ( cls, page, attrs, label ) =>
		`<li class="${ cls }" data-page="${ page }"><button type="button" class="pcrafts-page-btn"${ attrs }>${ label }</button></li>`;

	let html = '<ul class="pcrafts-pages">';

	html += item(
		'prev page-numbers' + ( currentPage === 1 ? hide : '' ),
		currentPage - 1,
		' aria-label="Previous page"',
		'Prev'
	);

	if ( maxPage > 4 ) {
		html += item(
			'page-numbers first-page' + ( currentPage < 3 ? hide : '' ),
			1,
			'',
			'1'
		);
		html += `<li class="page-dots first${
			currentPage < 4 ? hide : ''
		}" aria-hidden="true">...</li>`;
	}

	let middle = [];
	if ( maxPage >= 3 ) {
		middle = [ 1, 2, 3 ];
		if ( currentPage >= 3 && currentPage === maxPage ) {
			middle = [ currentPage - 2, currentPage - 1, currentPage ];
		} else if ( currentPage >= 3 ) {
			middle = [ currentPage - 1, currentPage, currentPage + 1 ];
		}
	} else if ( maxPage === 2 ) {
		middle = [ 1, 2 ];
	}

	middle.forEach( ( page ) => {
		const isCurrent = currentPage === page;
		html += item(
			'page-numbers middle-pages' + ( isCurrent ? ' current' : '' ),
			page,
			isCurrent ? ' aria-current="page"' : '',
			page
		);
	} );

	html += `<li class="page-dots last${
		maxPage <= currentPage + 2 ? hide : ''
	}" aria-hidden="true">...</li>`;

	if ( maxPage > 3 ) {
		html += item(
			'page-numbers last-page' +
				( maxPage <= currentPage + 1 ? hide : '' ),
			maxPage,
			'',
			maxPage
		);
	}

	html += item(
		'next page-numbers' + ( currentPage === maxPage ? hide : '' ),
		currentPage + 1,
		' aria-label="Next page"',
		'Next'
	);

	return html + '</ul>';
};

const dataAttrs = ( { maxPage, page, blockId, postId, template, query } ) =>
	`data-query="${ escAttr(
		JSON.stringify( query )
	) }" data-page="${ page }" data-max-page="${ maxPage }" data-post-id="${ postId }" data-block-id="${ blockId }" data-template="${ template }"`;

/**
 * Pagination wrapper markup.
 *
 * @param {Object} options Options.
 * @return {string} HTML.
 */
const paginationMarkup = ( options ) => {
	const { type, maxPage, postsPerPage } = options;

	if ( type === 'loadmore' ) {
		const disabled = maxPage <= 1;
		return `<div class="pcrafts-pagination pcrafts-loadmore left" ${ dataAttrs(
			{ ...options, page: 1 }
		) } data-posts-per-page="${ postsPerPage }"><button type="button" class="pcrafts-loadmore-btn${
			disabled ? ' disabled' : ''
		}"${ disabled ? " disabled='disabled'" : '' }>Load More</button></div>`;
	}

	if ( type === 'arrow' ) {
		const nextDisabled = maxPage <= 1;
		return `<div class="pcrafts-pagination pcrafts-arrow left" role="navigation" aria-label="Posts pagination" ${ dataAttrs(
			{ ...options, page: 1 }
		) }><button type="button" class="pcrafts-prev disabled" aria-label="Previous page" disabled><svg aria-hidden="true"><path d=""/></svg></button><button type="button" class="pcrafts-next${
			nextDisabled ? ' disabled' : ''
		}" aria-label="Next page"${
			nextDisabled ? " disabled='disabled'" : ''
		}><svg aria-hidden="true"><path d=""/></svg></button></div>`;
	}

	return `<div class="pcrafts-pagination pcrafts-numberic left" role="navigation" aria-label="Posts pagination" ${ dataAttrs(
		options
	) }>${ pagesMarkup( maxPage, options.page ) }</div>`;
};

/**
 * Full block markup (posts wrapper + pagination).
 *
 * @param {Object} overrides Options.
 * @return {string} HTML.
 */
export const blockMarkup = ( overrides = {} ) => {
	const options = {
		type: 'pagination',
		blockId: 'abc12345',
		postId: 42,
		template: 'post-grid',
		maxPage: 5,
		page: 1,
		postsPerPage: 3,
		query: QUERY,
		postIds: [ 1, 2, 3 ],
		...overrides,
	};

	return `<div class="wp-block-post-crafts-post-grid pcrafts-postgrid-wrapper pcrafts-block pcrafts-block-${
		options.blockId
	}"><div class="pcrafts-grid-items-wrapper pcrafts-posts-wrapper">${ options.postIds
		.map( article )
		.join( '' ) }</div>${ paginationMarkup( options ) }</div>`;
};

/**
 * Normalized state of numeric pagination items, for comparing with PHP output.
 *
 * @param {HTMLElement} root Element containing `ul.pcrafts-pages`.
 * @return {Array<Object>} One entry per li.
 */
export const pagesState = ( root ) =>
	Array.from( root.querySelectorAll( 'ul.pcrafts-pages > li' ) ).map(
		( li ) => {
			const button = li.querySelector( 'button' );
			return {
				classes: Array.from( li.classList ).sort().join( ' ' ),
				page: li.getAttribute( 'data-page' ),
				label: li.textContent,
				liAriaCurrent: li.getAttribute( 'aria-current' ),
				buttonAriaCurrent: button
					? button.getAttribute( 'aria-current' )
					: null,
			};
		}
	);
