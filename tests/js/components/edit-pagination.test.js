/**
 * Editor pagination preview: mirrors the front-end markup from
 * inc/templates/block-templates/{pagination,arrow,loadmore}.php.
 */

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PaginationEdit from '../../../src/components/edit-pagination';

describe( 'PaginationEdit', () => {
	it( 'EDJS-20 numeric: ul.pcrafts-pages > li.page-numbers > button.pcrafts-page-btn, page 1 current', () => {
		const { container } = render(
			<PaginationEdit type="pagination" alignment="left" />
		);

		const wrapper = container.firstChild;
		expect( wrapper ).toHaveClass( 'pcrafts-pagination', 'pcrafts-numberic' );
		expect( wrapper ).toHaveAttribute( 'role', 'navigation' );
		expect( wrapper ).toHaveAttribute( 'aria-label', 'Posts pagination' );

		const items = container.querySelectorAll(
			'ul.pcrafts-pages > li.page-numbers'
		);
		expect( items ).toHaveLength( 4 );

		const buttons = container.querySelectorAll(
			'ul.pcrafts-pages > li.page-numbers > button.pcrafts-page-btn'
		);
		expect( buttons ).toHaveLength( 4 );
		buttons.forEach( ( button ) =>
			expect( button ).toHaveAttribute( 'type', 'button' )
		);
		expect( [ ...buttons ].map( ( b ) => b.textContent ) ).toEqual( [
			'1',
			'2',
			'3',
			'Next',
		] );

		const current = container.querySelectorAll( '[aria-current]' );
		expect( current ).toHaveLength( 1 );
		expect( current[ 0 ] ).toHaveAttribute( 'aria-current', 'page' );
		expect( current[ 0 ] ).toHaveTextContent( '1' );
		expect( current[ 0 ].parentElement ).toHaveClass( 'current' );
		expect( container.querySelectorAll( 'li.current' ) ).toHaveLength( 1 );

		expect(
			screen.getByRole( 'button', { name: 'Next page' } )
		).toHaveTextContent( 'Next' );
	} );

	it( 'EDJS-21 arrow: labelled prev/next buttons inside a navigation landmark', () => {
		const { container } = render(
			<PaginationEdit type="arrow" alignment="center" />
		);

		const wrapper = container.firstChild;
		expect( wrapper ).toHaveClass( 'pcrafts-pagination', 'pcrafts-arrow' );
		expect( wrapper ).toHaveAttribute( 'role', 'navigation' );
		expect( wrapper ).toHaveAttribute( 'aria-label', 'Posts pagination' );

		const prev = screen.getByRole( 'button', { name: 'Previous page' } );
		const next = screen.getByRole( 'button', { name: 'Next page' } );

		expect( prev ).toHaveClass( 'pcrafts-prev' );
		expect( prev ).toBeDisabled();
		expect( prev ).toHaveAttribute( 'type', 'button' );
		expect( next ).toHaveClass( 'pcrafts-next' );
		expect( next ).toBeEnabled();
		expect( next ).toHaveAttribute( 'type', 'button' );
		expect( container.querySelector( '.pcrafts-pages' ) ).toBeNull();
	} );

	it( 'EDJS-22 loadmore: a type=button .pcrafts-loadmore-btn', () => {
		const { container } = render(
			<PaginationEdit type="loadmore" alignment="right" />
		);

		expect( container.firstChild ).toHaveClass(
			'pcrafts-pagination',
			'pcrafts-loadmore'
		);
		const button = screen.getByRole( 'button', { name: 'Load More' } );
		expect( button ).toHaveClass( 'pcrafts-loadmore-btn' );
		expect( button ).toHaveAttribute( 'type', 'button' );
		expect( container.querySelectorAll( 'button' ) ).toHaveLength( 1 );
	} );

	it.each( [ 'pagination', 'arrow', 'loadmore' ] )(
		'EDJS-23 applies the alignment class to the %s wrapper',
		( type ) => {
			const { container } = render(
				<PaginationEdit type={ type } alignment="center" />
			);

			expect( container.firstChild ).toHaveClass(
				'pcrafts-pagination',
				'center'
			);
		}
	);
} );
