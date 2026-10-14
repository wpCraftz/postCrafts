/**
 * Front-end AJAX pagination (src/scripts/pagination.js).
 *
 * Acceptance criteria: tests/acceptance/frontend-js.md (FEJS-01 – FEJS-36).
 */

/**
 * Internal dependencies
 */
import {
	QUERY,
	article,
	blockMarkup,
	pagesMarkup,
	pagesState,
} from './pagination-fixtures';

const AJAX_URL = 'https://example.test/wp-admin/admin-ajax.php';
const NONCE = 'n0nce123';

/** Pending jQuery.post calls, in call order. */
let requests = [];

/**
 * Minimal jQuery: `jQuery( el ).append|html( string|string[] )` and a
 * `jQuery.post` that returns a promise the test settles by hand.
 */
const installGlobals = () => {
	requests = [];

	const jQuery = ( element ) => ( {
		append( content ) {
			element.insertAdjacentHTML(
				'beforeend',
				[].concat( content ).join( '' )
			);
			return this;
		},
		html( content ) {
			element.innerHTML = [].concat( content ).join( '' );
			return this;
		},
	} );

	jQuery.post = jest.fn( () => {
		const request = {};
		request.promise = new Promise( ( resolve, reject ) => {
			request.resolve = resolve;
			request.reject = reject;
		} );
		requests.push( request );
		return request.promise;
	} );

	global.jQuery = jQuery;
	global.POSTCRAFTS = { urls: { ajaxUrl: AJAX_URL }, nonce: NONCE };
};

/** Let the request/then chain run to completion. */
const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

const ok = ( ids ) => ( { success: true, data: ids.map( article ) } );
const ERROR = { success: false, data: 'error' };

const respond = async ( index, response ) => {
	requests[ index ].resolve( response );
	await flush();
};

const fail = async ( index ) => {
	requests[ index ].reject( { status: 500, statusText: 'error' } );
	await flush();
};

/** Click the way a user does: a bubbling, cancelable event (also on disabled buttons). */
const click = ( element ) =>
	element.dispatchEvent(
		new MouseEvent( 'click', { bubbles: true, cancelable: true } )
	);

/**
 * Render blocks and boot the script (it binds on import via `new Pagination()`).
 *
 * @param {...Object} blocks blockMarkup() options, one per block.
 */
const mount = ( ...blocks ) => {
	document.body.innerHTML = ( blocks.length ? blocks : [ {} ] )
		.map( blockMarkup )
		.join( '' );
	jest.isolateModules( () => {
		require( '../../../src/scripts/pagination' );
	} );
};

const $ = ( selector, root = document ) => root.querySelector( selector );
const wrapper = ( root = document ) => $( '.pcrafts-pagination', root );
const posts = ( root = document ) => $( '.pcrafts-posts-wrapper', root );
const postIds = ( root = document ) =>
	Array.from( posts( root ).querySelectorAll( 'article' ) ).map( ( a ) =>
		Number( a.id.replace( 'post-', '' ) )
	);
const pageItem = ( page, root = document ) =>
	$( `li.middle-pages[data-page="${ page }"]`, root );
const expectedPages = ( maxPage, currentPage ) => {
	const container = document.createElement( 'div' );
	container.innerHTML = pagesMarkup( maxPage, currentPage );
	return pagesState( container );
};

beforeEach( () => {
	installGlobals();
	window.matchMedia = undefined;
} );

afterEach( () => {
	document.body.innerHTML = '';
	delete global.jQuery;
	delete global.POSTCRAFTS;
} );

describe( 'requests and loading state', () => {
	it.each( [
		[ 'pagination', () => click( $( 'li.middle-pages[data-page="2"]' ) ) ],
		[ 'arrow', () => click( $( 'button.pcrafts-next' ) ) ],
		[ 'loadmore', () => click( $( '.pcrafts-loadmore-btn' ) ) ],
	] )( 'FEJS-01: sends the unchanged payload (%s)', ( type, act ) => {
		mount( {
			type,
			blockId: 'blk00001',
			postId: 77,
			template: 'post-list',
		} );

		act();

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 1 );
		const [ url, data ] = global.jQuery.post.mock.calls[ 0 ];
		expect( url ).toBe( AJAX_URL );
		expect( data ).toEqual( {
			action: 'paginate_posts',
			_ajax_nonce: NONCE,
			postId: '77',
			blockId: 'blk00001',
			paged: 2,
			template: 'post-list',
			query: { ...QUERY, paged: 2 },
		} );
		expect( Object.keys( data ).sort() ).toEqual(
			[
				'_ajax_nonce',
				'action',
				'blockId',
				'paged',
				'postId',
				'query',
				'template',
			].sort()
		);
	} );

	it( 'FEJS-02: ignores a second click while loading (numeric)', () => {
		mount();

		click( pageItem( 2 ) );
		click( pageItem( 2 ) );
		click( pageItem( 3 ) );

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'FEJS-02: ignores a second click while loading (arrow and load more)', () => {
		mount(
			{ type: 'arrow', blockId: 'arrow001' },
			{ type: 'loadmore', blockId: 'more0001' }
		);
		const [ arrowBlock, moreBlock ] =
			document.querySelectorAll( '.pcrafts-block' );

		click( $( 'button.pcrafts-next', arrowBlock ) );
		click( $( 'button.pcrafts-next', arrowBlock ) );
		click( $( '.pcrafts-loadmore-btn', moreBlock ) );
		click( $( '.pcrafts-loadmore-btn', moreBlock ) );

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 2 );
	} );

	it.each( [
		[ 'success', ( i ) => respond( i, ok( [ 4, 5, 6 ] ) ) ],
		[ 'error response', ( i ) => respond( i, ERROR ) ],
		[ 'rejected request', ( i ) => fail( i ) ],
	] )(
		'FEJS-03: accepts clicks again after a %s',
		async ( _label, settle ) => {
			mount( { type: 'arrow', maxPage: 5 } );

			click( $( 'button.pcrafts-next' ) );
			await settle( 0 );

			const next = $( 'button.pcrafts-next' );
			click( next );

			expect( global.jQuery.post ).toHaveBeenCalledTimes( 2 );
		}
	);

	it( 'FEJS-04: two blocks on a page do not block each other', () => {
		mount( { blockId: 'blockaaa' }, { blockId: 'blockbbb' } );
		const [ first, second ] = document.querySelectorAll( '.pcrafts-block' );

		click( pageItem( 2, first ) );
		click( pageItem( 2, second ) );

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 2 );
		expect( global.jQuery.post.mock.calls[ 0 ][ 1 ].blockId ).toBe(
			'blockaaa'
		);
		expect( global.jQuery.post.mock.calls[ 1 ][ 1 ].blockId ).toBe(
			'blockbbb'
		);
		expect( wrapper( second ) ).toHaveClass( 'is-loading' );
	} );

	it( 'FEJS-05: marks the wrapper and posts busy while loading', () => {
		mount( { type: 'loadmore' } );

		expect( wrapper() ).not.toHaveClass( 'is-loading' );
		expect( posts() ).not.toHaveAttribute( 'aria-busy' );

		click( $( '.pcrafts-loadmore-btn' ) );

		expect( wrapper() ).toHaveClass( 'is-loading' );
		expect( posts() ).toHaveClass( 'is-loading' );
		expect( posts() ).toHaveAttribute( 'aria-busy', 'true' );
	} );

	it.each( [
		[ 'pagination', 'success', ( i ) => respond( i, ok( [ 4, 5, 6 ] ) ) ],
		[ 'pagination', 'error response', ( i ) => respond( i, ERROR ) ],
		[ 'pagination', 'rejected request', ( i ) => fail( i ) ],
		[ 'arrow', 'success', ( i ) => respond( i, ok( [ 4, 5, 6 ] ) ) ],
		[ 'arrow', 'rejected request', ( i ) => fail( i ) ],
		[ 'loadmore', 'success', ( i ) => respond( i, ok( [ 4, 5, 6 ] ) ) ],
		[ 'loadmore', 'error response', ( i ) => respond( i, ERROR ) ],
		[ 'loadmore', 'rejected request', ( i ) => fail( i ) ],
	] )(
		'FEJS-06: clears the loading state (%s, after %s)',
		async ( type, _label, settle ) => {
			mount( { type } );
			const control =
				$( 'li.middle-pages[data-page="2"]' ) ||
				$( 'button.pcrafts-next' ) ||
				$( '.pcrafts-loadmore-btn' );

			click( control );
			expect( wrapper() ).toHaveClass( 'is-loading' );

			await settle( 0 );

			expect( wrapper() ).not.toHaveClass( 'is-loading' );
			expect( posts() ).not.toHaveClass( 'is-loading' );
			expect( posts() ).not.toHaveAttribute( 'aria-busy' );
		}
	);
} );

describe( 'Load More', () => {
	const button = () => $( '.pcrafts-loadmore-btn' );
	const expectDisabled = ( disabled ) => {
		if ( disabled ) {
			expect( button() ).toBeDisabled();
			expect( button() ).toHaveClass( 'disabled' );
		} else {
			expect( button() ).toBeEnabled();
			expect( button() ).not.toHaveClass( 'disabled' );
		}
	};

	it( 'FEJS-10: appends the next page and updates data-page', async () => {
		mount( { type: 'loadmore', maxPage: 4 } );

		click( button() );
		expect( global.jQuery.post.mock.calls[ 0 ][ 1 ].paged ).toBe( 2 );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expect( postIds() ).toEqual( [ 1, 2, 3, 4, 5, 6 ] );
		expect( wrapper().dataset.page ).toBe( '2' );

		click( button() );
		expect( global.jQuery.post.mock.calls[ 1 ][ 1 ].paged ).toBe( 3 );
		await respond( 1, ok( [ 7, 8, 9 ] ) );

		expect( postIds() ).toEqual( [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ] );
		expect( wrapper().dataset.page ).toBe( '3' );
	} );

	it( 'FEJS-11: keeps the button enabled after a full page that is not the last', async () => {
		mount( { type: 'loadmore', maxPage: 3 } );
		button().focus();

		click( button() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expectDisabled( false );
		expect( document.activeElement ).toBe( button() );
	} );

	it( 'FEJS-12: disables the button after loading the max page', async () => {
		mount( { type: 'loadmore', maxPage: 2 } );

		click( button() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expectDisabled( true );
	} );

	it( 'FEJS-13: disables the button after a short page', async () => {
		mount( { type: 'loadmore', maxPage: 5, postsPerPage: 3 } );

		click( button() );
		await respond( 0, ok( [ 4, 5 ] ) );

		expect( postIds() ).toEqual( [ 1, 2, 3, 4, 5 ] );
		expectDisabled( true );
	} );

	it( 'FEJS-14: disables the button on an error response without appending', async () => {
		mount( { type: 'loadmore', maxPage: 5 } );

		click( button() );
		await respond( 0, ERROR );

		expectDisabled( true );
		expect( postIds() ).toEqual( [ 1, 2, 3 ] );
		expect( wrapper().dataset.page ).toBe( '1' );
	} );

	it( 'FEJS-15: keeps the button enabled after a network failure so the user can retry', async () => {
		mount( { type: 'loadmore', maxPage: 5 } );

		click( button() );
		await fail( 0 );

		expectDisabled( false );
		expect( postIds() ).toEqual( [ 1, 2, 3 ] );
		expect( wrapper().dataset.page ).toBe( '1' );

		click( button() );
		expect( global.jQuery.post ).toHaveBeenCalledTimes( 2 );
		expect( global.jQuery.post.mock.calls[ 1 ][ 1 ].paged ).toBe( 2 );
	} );

	it( 'FEJS-16: a server-disabled button (single page) ignores clicks', () => {
		mount( { type: 'loadmore', maxPage: 1 } );

		expectDisabled( true );
		click( button() );

		expect( global.jQuery.post ).not.toHaveBeenCalled();
	} );

	it( 'FEJS-16: a button with only the legacy disabled class ignores clicks', () => {
		mount( { type: 'loadmore', maxPage: 5 } );
		button().classList.add( 'disabled' );

		click( button() );

		expect( global.jQuery.post ).not.toHaveBeenCalled();
	} );

	it( 'FEJS-16: a button disabled after the last page ignores further clicks', async () => {
		mount( { type: 'loadmore', maxPage: 2 } );

		click( button() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );
		click( button() );

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'FEJS-17: moves focus to the first new post when the button becomes disabled', async () => {
		mount( { type: 'loadmore', maxPage: 2 } );
		button().focus();
		expect( document.activeElement ).toBe( button() );

		click( button() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		const firstNew = $( '#post-4' );
		expect( firstNew ).toHaveAttribute( 'tabindex', '-1' );
		expect( document.activeElement ).toBe( firstNew );
	} );

	it( 'FEJS-17: focuses with preventScroll and keeps an existing tabindex', async () => {
		mount( { type: 'loadmore', maxPage: 2 } );
		const focusSpy = jest.spyOn( HTMLElement.prototype, 'focus' );

		click( button() );
		requests[ 0 ].resolve( {
			success: true,
			data: [
				'<article id="post-4" class="post-grid" tabindex="0"></article>',
			],
		} );
		await flush();

		const firstNew = $( '#post-4' );
		expect( firstNew ).toHaveAttribute( 'tabindex', '0' );
		const call = focusSpy.mock.contexts.indexOf( firstNew );
		expect( call ).toBeGreaterThan( -1 );
		expect( focusSpy.mock.calls[ call ][ 0 ] ).toEqual( {
			preventScroll: true,
		} );
		focusSpy.mockRestore();
	} );

	it( 'FEJS-18: never scrolls the page', async () => {
		mount( { type: 'loadmore', maxPage: 2 } );
		const block = $( '.pcrafts-block' );
		block.getBoundingClientRect = () => ( { top: -500 } );
		block.scrollIntoView = jest.fn();

		click( button() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expect( block.scrollIntoView ).not.toHaveBeenCalled();
	} );
} );

describe( 'numeric pagination', () => {
	it( 'FEJS-20: clicking the li loads that page and replaces the posts', async () => {
		mount( { maxPage: 5 } );

		click( pageItem( 3 ) );
		expect( global.jQuery.post.mock.calls[ 0 ][ 1 ].paged ).toBe( 3 );
		await respond( 0, ok( [ 7, 8, 9 ] ) );

		expect( postIds() ).toEqual( [ 7, 8, 9 ] );
		expect( wrapper().dataset.page ).toBe( '3' );
	} );

	it( 'FEJS-20: clicking the inner button loads that page and replaces the posts', async () => {
		mount( { maxPage: 5 } );

		click( $( 'button.pcrafts-page-btn', pageItem( 2 ) ) );
		expect( global.jQuery.post.mock.calls[ 0 ][ 1 ].paged ).toBe( 2 );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expect( postIds() ).toEqual( [ 4, 5, 6 ] );
	} );

	it( 'FEJS-21: moves the current class and aria-current to the new page', async () => {
		mount( { maxPage: 5 } );

		click( pageItem( 2 ) );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		const current = wrapper().querySelectorAll( '.current' );
		expect( current ).toHaveLength( 1 );
		expect( current[ 0 ] ).toBe( pageItem( 2 ) );

		const ariaCurrent = wrapper().querySelectorAll( '[aria-current]' );
		expect( ariaCurrent ).toHaveLength( 1 );
		expect( ariaCurrent[ 0 ] ).toBe( $( 'button', pageItem( 2 ) ) );
		expect( ariaCurrent[ 0 ] ).toHaveAttribute( 'aria-current', 'page' );
	} );

	describe.each( [ 2, 3, 4, 5, 10 ] )(
		'FEJS-22: matches post_crafts_pagination() (max %i)',
		( maxPage ) => {
			let calls = 0;

			const go = async ( target, expectedPage ) => {
				click( target );
				expect( global.jQuery.post ).toHaveBeenCalledTimes( calls + 1 );
				expect(
					global.jQuery.post.mock.calls[ calls ][ 1 ].paged
				).toBe( expectedPage );
				await respond( calls, ok( [ expectedPage * 10 ] ) );
				calls++;

				expect( wrapper().dataset.page ).toBe( String( expectedPage ) );
				expect( pagesState( wrapper() ) ).toEqual(
					expectedPages( maxPage, expectedPage )
				);
			};

			beforeEach( () => {
				calls = 0;
				mount( { maxPage } );
			} );

			it( 'FEJS-22: initial markup is the PHP markup', () => {
				expect( pagesState( wrapper() ) ).toEqual(
					expectedPages( maxPage, 1 )
				);
			} );

			it( 'FEJS-22: Next through every page, then Prev back to 1', async () => {
				for ( let page = 2; page <= maxPage; page++ ) {
					await go( $( 'li.next button' ), page );
				}
				for ( let page = maxPage - 1; page >= 1; page-- ) {
					await go( $( 'li.prev button' ), page );
				}
			} );

			it( 'FEJS-22: first/last page items and middle pages', async () => {
				if ( maxPage > 3 ) {
					await go( $( 'li.last-page' ), maxPage );
				}
				if ( maxPage > 4 ) {
					await go( $( 'li.first-page' ), 1 );
				}
				const middle = Array.from(
					wrapper().querySelectorAll( 'li.middle-pages' )
				).filter( ( li ) => ! li.classList.contains( 'current' ) );
				const target = middle[ middle.length - 1 ];
				await go( target, Number( target.dataset.page ) );
			} );
		}
	);

	it( 'FEJS-23: clicks on the current page, hidden items and dots do nothing', () => {
		mount( { maxPage: 10 } );
		const before = pagesState( wrapper() );

		click( pageItem( 1 ) ); // current
		click( $( 'button', pageItem( 1 ) ) );
		click( $( 'li.prev' ) ); // hidden on page 1
		click( $( 'li.prev button' ) );
		click( $( 'li.first-page' ) ); // hidden on page 1
		click( $( 'li.page-dots.first' ) );
		click( $( 'li.page-dots.last' ) );

		expect( global.jQuery.post ).not.toHaveBeenCalled();
		expect( pagesState( wrapper() ) ).toEqual( before );
		expect( wrapper() ).not.toHaveClass( 'is-loading' );
	} );

	it.each( [
		[ 'an error response', ( i ) => respond( i, ERROR ) ],
		[ 'a rejected request', ( i ) => fail( i ) ],
	] )(
		'FEJS-24: %s leaves data-page, items and posts unchanged',
		async ( _label, settle ) => {
			mount( { maxPage: 5 } );
			const before = pagesState( wrapper() );

			click( pageItem( 3 ) );
			await settle( 0 );

			expect( wrapper().dataset.page ).toBe( '1' );
			expect( pagesState( wrapper() ) ).toEqual( before );
			expect( postIds() ).toEqual( [ 1, 2, 3 ] );
		}
	);
} );

describe( 'arrow pagination', () => {
	const prev = () => $( 'button.pcrafts-prev' );
	const next = () => $( 'button.pcrafts-next' );
	const expectDisabled = ( button, disabled ) => {
		if ( disabled ) {
			expect( button ).toBeDisabled();
			expect( button ).toHaveClass( 'disabled' );
		} else {
			expect( button ).toBeEnabled();
			expect( button ).not.toHaveClass( 'disabled' );
		}
	};

	it( 'FEJS-30: Next and Prev request the adjacent page and replace the posts', async () => {
		mount( { type: 'arrow', maxPage: 3 } );

		click( next() );
		expect( global.jQuery.post.mock.calls[ 0 ][ 1 ].paged ).toBe( 2 );
		await respond( 0, ok( [ 4, 5, 6 ] ) );
		expect( postIds() ).toEqual( [ 4, 5, 6 ] );
		expect( wrapper().dataset.page ).toBe( '2' );

		click( prev() );
		expect( global.jQuery.post.mock.calls[ 1 ][ 1 ].paged ).toBe( 1 );
		await respond( 1, ok( [ 1, 2, 3 ] ) );
		expect( postIds() ).toEqual( [ 1, 2, 3 ] );
		expect( wrapper().dataset.page ).toBe( '1' );
	} );

	it( 'FEJS-30: a click on the arrow icon counts as a click on its button', () => {
		mount( { type: 'arrow', maxPage: 3 } );

		click( $( 'svg', next() ) );

		expect( global.jQuery.post ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'FEJS-31: Prev is disabled on page 1 and Next on the last page, updated after navigation', async () => {
		mount( { type: 'arrow', maxPage: 3 } );
		expectDisabled( prev(), true );
		expectDisabled( next(), false );

		click( next() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );
		expectDisabled( prev(), false );
		expectDisabled( next(), false );

		click( next() );
		await respond( 1, ok( [ 7, 8, 9 ] ) );
		expectDisabled( prev(), false );
		expectDisabled( next(), true );

		click( prev() );
		await respond( 2, ok( [ 4, 5, 6 ] ) );
		expectDisabled( prev(), false );
		expectDisabled( next(), false );

		click( prev() );
		await respond( 3, ok( [ 1, 2, 3 ] ) );
		expectDisabled( prev(), true );
		expectDisabled( next(), false );
	} );

	it( 'FEJS-31: with two pages Next is disabled right after the first navigation', async () => {
		mount( { type: 'arrow', maxPage: 2 } );

		click( next() );
		await respond( 0, ok( [ 4, 5, 6 ] ) );

		expectDisabled( prev(), false );
		expectDisabled( next(), true );
	} );

	it( 'FEJS-32: disabled arrows ignore clicks', async () => {
		mount( { type: 'arrow', maxPage: 1 } );
		expectDisabled( next(), true );

		click( prev() );
		click( next() );

		expect( global.jQuery.post ).not.toHaveBeenCalled();
	} );

	it( 'FEJS-32: an arrow with only the legacy disabled class ignores clicks', () => {
		mount( { type: 'arrow', maxPage: 3 } );
		prev().removeAttribute( 'disabled' );

		click( prev() );

		expect( global.jQuery.post ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'an error response', ( i ) => respond( i, ERROR ) ],
		[ 'a rejected request', ( i ) => fail( i ) ],
	] )(
		'FEJS-33: %s leaves posts, data-page and buttons unchanged',
		async ( _label, settle ) => {
			mount( { type: 'arrow', maxPage: 3 } );

			click( next() );
			await settle( 0 );

			expect( postIds() ).toEqual( [ 1, 2, 3 ] );
			expect( wrapper().dataset.page ).toBe( '1' );
			expectDisabled( prev(), true );
			expectDisabled( next(), false );
		}
	);
} );

describe( 'reveal after replacing posts', () => {
	const navigate = {
		numeric: async () => {
			click( pageItem( 2 ) );
			await respond( 0, ok( [ 4, 5, 6 ] ) );
		},
		arrow: async () => {
			click( $( 'button.pcrafts-next' ) );
			await respond( 0, ok( [ 4, 5, 6 ] ) );
		},
	};
	const mountType = ( type ) =>
		mount( { type: type === 'numeric' ? 'pagination' : 'arrow' } );
	const mockBlock = ( top ) => {
		const block = $( '.pcrafts-block' );
		block.getBoundingClientRect = jest.fn( () => ( { top } ) );
		block.scrollIntoView = jest.fn();
		return block;
	};

	it.each( [ 'numeric', 'arrow' ] )(
		'FEJS-34: focuses the posts wrapper with preventScroll (%s)',
		async ( type ) => {
			mountType( type );
			mockBlock( 10 );
			const focusSpy = jest.spyOn( posts(), 'focus' );

			await navigate[ type ]();

			expect( posts() ).toHaveAttribute( 'tabindex', '-1' );
			expect( focusSpy ).toHaveBeenCalledWith( { preventScroll: true } );
			expect( document.activeElement ).toBe( posts() );
		}
	);

	it( 'FEJS-34: keeps an existing tabindex on the posts wrapper', async () => {
		mountType( 'numeric' );
		mockBlock( 10 );
		posts().setAttribute( 'tabindex', '0' );

		await navigate.numeric();

		expect( posts() ).toHaveAttribute( 'tabindex', '0' );
		expect( document.activeElement ).toBe( posts() );
	} );

	it.each( [ 'numeric', 'arrow' ] )(
		'FEJS-35: does not scroll when the block top is in view (%s)',
		async ( type ) => {
			mountType( type );
			const block = mockBlock( 0 );

			await navigate[ type ]();

			expect( block.getBoundingClientRect ).toHaveBeenCalled();
			expect( block.scrollIntoView ).not.toHaveBeenCalled();
		}
	);

	it.each( [ 'numeric', 'arrow' ] )(
		'FEJS-35: scrolls the block to the top when its top is above the viewport (%s)',
		async ( type ) => {
			mountType( type );
			const block = mockBlock( -1 );

			await navigate[ type ]();

			expect( block.scrollIntoView ).toHaveBeenCalledTimes( 1 );
			expect( block.scrollIntoView.mock.calls[ 0 ][ 0 ] ).toMatchObject( {
				block: 'start',
			} );
		}
	);

	it.each( [
		[ true, 'auto' ],
		[ false, 'smooth' ],
	] )(
		'FEJS-36: reduced motion %s scrolls with behavior %s',
		async ( reduce, behavior ) => {
			window.matchMedia = jest.fn( ( query ) => ( {
				matches: query === '(prefers-reduced-motion: reduce)' && reduce,
				media: query,
			} ) );
			mountType( 'arrow' );
			const block = mockBlock( -200 );

			await navigate.arrow();

			expect( window.matchMedia ).toHaveBeenCalledWith(
				'(prefers-reduced-motion: reduce)'
			);
			expect( block.scrollIntoView ).toHaveBeenCalledWith( {
				behavior,
				block: 'start',
			} );
		}
	);

	it( 'FEJS-36: scrolls smoothly when matchMedia is unavailable', async () => {
		mountType( 'numeric' );
		const block = mockBlock( -200 );

		await navigate.numeric();

		expect( block.scrollIntoView ).toHaveBeenCalledWith( {
			behavior: 'smooth',
			block: 'start',
		} );
	} );
} );
