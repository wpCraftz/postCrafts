/**
 * Query Builder + Select Posts modal (PostBlockSettings / PostSelector).
 *
 * The REST search goes through a mocked @wordpress/api-fetch. TaxonomyControls
 * (needs the core-data store) and Sortable (drag-and-drop) are replaced by
 * light stand-ins; everything else, including the Modal, renders for real.
 */

/**
 * External dependencies
 */
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import QueryBuilder from '../../../../src/components/query-builder';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock(
	'../../../../src/components/query-builder/taxonomoy-controls',
	() => ( { __esModule: true, default: () => null } )
);

// Stand-in for the drag-and-drop list: one button reverses the order.
jest.mock( '../../../../src/components/query-builder/sortable', () => {
	const { createElement } = jest.requireActual( 'react' );
	return {
		__esModule: true,
		default: ( { items, onChange } ) =>
			createElement(
				'button',
				{
					type: 'button',
					onClick: () =>
						onChange( items.map( ( item ) => item.id ).reverse() ),
				},
				'Reverse order'
			),
	};
} );

const POSTS = [
	{ id: 11, title: 'Alpha post' },
	{ id: 12, title: 'Bravo post' },
	{ id: 13, title: 'Charlie post' },
	{ id: 14, title: 'Delta post' },
].map( ( { id, title } ) => ( {
	id,
	link: `https://example.test/?p=${ id }`,
	title: { rendered: title },
	status: 'publish',
	excerpt: { rendered: '' },
	featured_media: 0,
} ) );

const mockResponse = ( posts = POSTS ) => ( {
	headers: { get: () => '1' },
	json: () => Promise.resolve( posts ),
} );

const BASE_ATTRIBUTES = {
	postIds: [],
	pagination: true,
	postsPerPage: 3,
	taxQuery: { category: [], post_tag: [] },
};

const renderBuilder = ( attributes = {}, props = {} ) => {
	const setAttributes = jest.fn();
	const user = userEvent.setup();
	render(
		<QueryBuilder
			initialOpen
			attributes={ { ...BASE_ATTRIBUTES, ...attributes } }
			setAttributes={ setAttributes }
			{ ...props }
		/>
	);
	return { setAttributes, user };
};

/** Merge every setAttributes call into one object (later calls win). */
const merged = ( setAttributes ) =>
	Object.assign( {}, ...setAttributes.mock.calls.map( ( [ a ] ) => a ) );

/** Whether any setAttributes call touched `key`. */
const touched = ( setAttributes, key ) =>
	setAttributes.mock.calls.some( ( [ a ] ) => key in a );

const getRow = ( title ) => screen.getByText( title ).closest( 'li' );

const openModal = async ( user ) => {
	await user.click(
		screen.getByRole( 'button', {
			name: /^(Select Posts|Modify Selection)$/,
		} )
	);
	const dialog = await screen.findByRole( 'dialog' );
	await within( dialog ).findByText( 'Alpha post', {}, { timeout: 2000 } );
	return dialog;
};

const pressedIds = () =>
	POSTS.filter(
		( post ) =>
			getRow( post.title.rendered ).getAttribute( 'aria-pressed' ) ===
			'true'
	).map( ( post ) => post.id );

beforeEach( () => {
	apiFetch.mockReset();
	apiFetch.mockImplementation( () => Promise.resolve( mockResponse() ) );
} );

describe( 'Query Builder trigger and summary', () => {
	it( 'EDJS-01 shows "Select Posts" without a selection, no Clear/Sort', () => {
		renderBuilder();

		expect(
			screen.getByRole( 'button', { name: 'Select Posts' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /Clear/ } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Sort Posts' } )
		).not.toBeInTheDocument();
	} );

	it( 'EDJS-01 shows "Modify Selection", Clear and Sort with a selection', () => {
		renderBuilder( { postIds: [ 11 ], pagination: false } );

		expect(
			screen.getByRole( 'button', { name: 'Modify Selection' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Select Posts' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /Clear/ } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Sort Posts' } )
		).toBeInTheDocument();
	} );

	it.each( [
		[ [ 11, 12 ], 3, 'You can select 1 more post' ],
		[ [ 11 ], 3, 'You can select 2 more posts' ],
	] )(
		'EDJS-02 postIds %j of %i reads "%s"',
		( postIds, postsPerPage, text ) => {
			const { container } = render(
				<QueryBuilder
					initialOpen
					attributes={ {
						...BASE_ATTRIBUTES,
						postIds,
						postsPerPage,
					} }
					setAttributes={ jest.fn() }
				/>
			);
			expect(
				container.querySelector( '.sortable-help' ).textContent
			).toBe( text );
		}
	);

	it( 'EDJS-02 hides the hint when the selection is full', () => {
		const { container } = render(
			<QueryBuilder
				initialOpen
				attributes={ {
					...BASE_ATTRIBUTES,
					postIds: [ 11, 12, 13 ],
				} }
				setAttributes={ jest.fn() }
			/>
		);
		expect( container.querySelector( '.sortable-help' ) ).toBeNull();
	} );
} );

describe( 'Select Posts modal: draft selection', () => {
	it( 'EDJS-03 preloads the draft from the current postIds', async () => {
		const { user } = renderBuilder( {
			postIds: [ 13, 11 ],
			pagination: false,
		} );
		await openModal( user );

		expect( pressedIds() ).toEqual( [ 11, 13 ] );
		expect( screen.getByText( '2 items are selected' ) ).toBeInTheDocument();
	} );

	it( 'EDJS-04 clicking rows toggles the draft without calling setAttributes', async () => {
		const { user, setAttributes } = renderBuilder();
		await openModal( user );

		await user.click( getRow( 'Bravo post' ) );
		await user.click( getRow( 'Delta post' ) );
		expect( pressedIds() ).toEqual( [ 12, 14 ] );

		await user.click( getRow( 'Bravo post' ) );
		expect( pressedIds() ).toEqual( [ 14 ] );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'Enter', '{Enter}' ],
		[ 'Space', ' ' ],
	] )(
		'EDJS-05 %s on a focused row toggles the draft only',
		async ( _name, key ) => {
			const { user, setAttributes } = renderBuilder();
			await openModal( user );

			const row = getRow( 'Charlie post' );
			row.focus();
			expect( row ).toHaveFocus();

			await user.keyboard( key );
			expect( row ).toHaveAttribute( 'aria-pressed', 'true' );

			await user.keyboard( key );
			expect( row ).toHaveAttribute( 'aria-pressed', 'false' );

			expect( setAttributes ).not.toHaveBeenCalled();
		}
	);

	it( 'EDJS-06 rows are role=button, tabIndex 0, with aria-pressed', async () => {
		const { user } = renderBuilder( {
			postIds: [ 12 ],
			pagination: false,
		} );
		await openModal( user );

		POSTS.forEach( ( post ) => {
			const row = getRow( post.title.rendered );
			expect( row ).toHaveAttribute( 'role', 'button' );
			expect( row ).toHaveAttribute( 'tabindex', '0' );
			expect( row ).toHaveAttribute(
				'aria-pressed',
				post.id === 12 ? 'true' : 'false'
			);
		} );
	} );

	it( 'EDJS-07 at the limit, unselected rows are aria-disabled and cannot be added; selected rows can be removed', async () => {
		const { user, setAttributes } = renderBuilder( { postsPerPage: 2 } );
		await openModal( user );

		await user.click( getRow( 'Alpha post' ) );
		expect( getRow( 'Bravo post' ) ).toHaveAttribute(
			'aria-disabled',
			'false'
		);
		await user.click( getRow( 'Bravo post' ) );

		// Limit reached.
		expect( getRow( 'Charlie post' ) ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
		expect( getRow( 'Delta post' ) ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
		expect( getRow( 'Alpha post' ) ).toHaveAttribute(
			'aria-disabled',
			'false'
		);

		await user.click( getRow( 'Charlie post' ) );
		getRow( 'Delta post' ).focus();
		await user.keyboard( '{Enter}' );
		expect( pressedIds() ).toEqual( [ 11, 12 ] );

		// Deselect at the limit, which re-enables the others.
		await user.click( getRow( 'Alpha post' ) );
		expect( pressedIds() ).toEqual( [ 12 ] );
		expect( getRow( 'Charlie post' ) ).toHaveAttribute(
			'aria-disabled',
			'false'
		);
		await user.click( getRow( 'Charlie post' ) );
		expect( pressedIds() ).toEqual( [ 12, 13 ] );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'EDJS-08 the selection counter pluralizes', async () => {
		const { user } = renderBuilder( { postsPerPage: 3 } );
		await openModal( user );

		expect( screen.getByText( '0 items are selected' ) ).toBeInTheDocument();
		await user.click( getRow( 'Alpha post' ) );
		expect( screen.getByText( '1 item is selected' ) ).toBeInTheDocument();
		await user.click( getRow( 'Bravo post' ) );
		expect( screen.getByText( '2 items are selected' ) ).toBeInTheDocument();
		await user.click( getRow( 'Charlie post' ) );
		expect(
			screen.getByText( 'Max 3 posts are already selected' )
		).toBeInTheDocument();
	} );

	it( 'EDJS-08 the limit message is singular for a limit of 1', async () => {
		const { user } = renderBuilder( { postsPerPage: 1 } );
		await openModal( user );

		await user.click( getRow( 'Alpha post' ) );
		expect(
			screen.getByText( 'Max 1 post is already selected' )
		).toBeInTheDocument();
	} );
} );

describe( 'Select Posts modal: committing', () => {
	it.each( [ true, false ] )(
		'EDJS-09 first insert stores postIds in pick order, pagination false, paginationBeforeSelection=%s',
		async ( pagination ) => {
			const { user, setAttributes } = renderBuilder( { pagination } );
			await openModal( user );

			await user.click( getRow( 'Charlie post' ) );
			await user.click( getRow( 'Alpha post' ) );
			await user.click(
				screen.getByRole( 'button', { name: 'Insert Selected' } )
			);

			expect( merged( setAttributes ) ).toEqual( {
				postIds: [ 13, 11 ],
				paginationBeforeSelection: pagination,
				pagination: false,
			} );
			await waitFor( () =>
				expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument()
			);
		}
	);

	it( 'EDJS-10 replacing a selection never overwrites paginationBeforeSelection', async () => {
		const { user, setAttributes } = renderBuilder( {
			postIds: [ 11 ],
			pagination: false,
			paginationBeforeSelection: true,
		} );
		await openModal( user );

		await user.click( getRow( 'Delta post' ) );
		await user.click(
			screen.getByRole( 'button', { name: 'Insert Selected' } )
		);

		expect( merged( setAttributes ) ).toEqual( {
			postIds: [ 11, 14 ],
			pagination: false,
		} );
		expect( touched( setAttributes, 'paginationBeforeSelection' ) ).toBe(
			false
		);
	} );

	it( 'EDJS-11 inserting an empty draft over a selection behaves like Clear', async () => {
		const { user, setAttributes } = renderBuilder( {
			postIds: [ 11 ],
			pagination: false,
			paginationBeforeSelection: false,
		} );
		await openModal( user );

		await user.click( getRow( 'Alpha post' ) );
		const insert = screen.getByRole( 'button', {
			name: 'Insert Selected',
		} );
		expect( insert ).toBeEnabled();
		await user.click( insert );

		const result = merged( setAttributes );
		expect( result ).toEqual( {
			postIds: [],
			pagination: false,
			paginationBeforeSelection: undefined,
		} );
		expect( 'paginationBeforeSelection' in result ).toBe( true );
		await waitFor( () =>
			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument()
		);
	} );

	it( 'EDJS-12 with no selection and an empty draft, Insert is disabled and writes nothing', async () => {
		const { user, setAttributes } = renderBuilder();
		await openModal( user );

		const insert = screen.getByRole( 'button', {
			name: 'Insert Selected',
		} );
		expect( insert ).toBeDisabled();
		await user.click( insert );

		expect( setAttributes ).not.toHaveBeenCalled();
		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
	} );
} );

describe( 'Select Posts modal: discarding', () => {
	const discardWays = [
		[
			'the X (Close) button',
			( user, dialog ) =>
				user.click(
					within( dialog ).getByRole( 'button', { name: 'Close' } )
				),
		],
		[ 'Escape', ( user ) => user.keyboard( '{Escape}' ) ],
		[
			'Cancel',
			( user, dialog ) =>
				user.click(
					within( dialog ).getByRole( 'button', { name: 'Cancel' } )
				),
		],
	];

	it.each( discardWays )(
		'EDJS-13 %s discards the draft; reopening shows the saved selection',
		async ( _name, discard ) => {
			const { user, setAttributes } = renderBuilder( {
				postIds: [ 12 ],
				pagination: false,
				paginationBeforeSelection: true,
			} );
			const dialog = await openModal( user );

			await user.click( getRow( 'Alpha post' ) );
			await user.click( getRow( 'Bravo post' ) );
			expect( pressedIds() ).toEqual( [ 11 ] );

			await discard( user, dialog );
			await waitFor( () =>
				expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument()
			);
			expect( setAttributes ).not.toHaveBeenCalled();

			await openModal( user );
			expect( pressedIds() ).toEqual( [ 12 ] );
		}
	);
} );

describe( 'Clear and sort', () => {
	it.each( [
		[ true, true ],
		[ false, false ],
		[ undefined, true ],
	] )(
		'EDJS-14 Clear with paginationBeforeSelection=%s restores pagination=%s and unsets it',
		async ( paginationBeforeSelection, expected ) => {
			const { user, setAttributes } = renderBuilder( {
				postIds: [ 11, 12 ],
				pagination: false,
				paginationBeforeSelection,
			} );

			await user.click( screen.getByRole( 'button', { name: /Clear/ } ) );

			const result = merged( setAttributes );
			expect( result ).toEqual( {
				postIds: [],
				pagination: expected,
				paginationBeforeSelection: undefined,
			} );
			expect( 'paginationBeforeSelection' in result ).toBe( true );
		}
	);

	it( 'EDJS-15 reordering writes postIds only', async () => {
		const { user, setAttributes } = renderBuilder(
			{
				postIds: [ 11, 12, 13 ],
				pagination: false,
				paginationBeforeSelection: true,
			},
			{
				postMeta: [
					{ id: 11, title: 'Alpha post', status: 'publish' },
					{ id: 12, title: 'Bravo post', status: 'publish' },
					{ id: 13, title: 'Charlie post', status: 'publish' },
				],
			}
		);

		await user.click( screen.getByRole( 'button', { name: 'Sort Posts' } ) );
		const dialog = await screen.findByRole( 'dialog', {
			name: 'Sort Selected Posts',
		} );
		await user.click(
			within( dialog ).getByRole( 'button', { name: 'Reverse order' } )
		);

		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( {
			postIds: [ 13, 12, 11 ],
		} );
	} );
} );

describe( 'Post search request', () => {
	const lastQuery = () => {
		const { path } = apiFetch.mock.calls.at( -1 )[ 0 ];
		return new URL( path, 'https://example.test' ).searchParams;
	};
	const searchColumns = ( params ) =>
		[ ...params.entries() ]
			.filter( ( [ key ] ) => key.startsWith( 'search_columns' ) )
			.map( ( [ , value ] ) => value );

	it( 'EDJS-16 sends search_columns=post_title only with a search term', async () => {
		const { user } = renderBuilder();
		await openModal( user );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		const initial = lastQuery();
		expect( initial.get( 'search' ) || '' ).toBe( '' );
		expect( searchColumns( initial ) ).toEqual( [] );
		expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).toMatch(
			/^\/wp\/v2\/posts\?/
		);

		await user.type(
			screen.getByLabelText( 'Look for a Post' ),
			'bravo'
		);

		await waitFor(
			() => expect( lastQuery().get( 'search' ) ).toBe( 'bravo' ),
			{ timeout: 2000 }
		);
		const searched = lastQuery();
		expect( searchColumns( searched ) ).toEqual( [ 'post_title' ] );
		expect( searched.get( 'page' ) ).toBe( '1' );
	} );
} );
