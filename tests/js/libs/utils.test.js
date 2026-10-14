/**
 * Internal dependencies
 */
import {
	getSubString,
	deepClone,
	isEqual,
	trimWords,
} from '../../../src/libs/utils';

/**
 * trimWords: acceptance criteria FEJS-40 – FEJS-47 (tests/acceptance/frontend-js.md).
 */
describe( 'trimWords', () => {
	it( 'FEJS-40: keeps the first N words and appends the default ellipsis', () => {
		expect( trimWords( 'one two three four five', 3 ) ).toBe(
			'one two three…'
		);
		expect( trimWords( 'one two', 1 ) ).toBe( 'one…' );
	} );

	it( 'FEJS-41: returns text with exactly N or fewer words without the ellipsis', () => {
		expect( trimWords( 'one two three', 3 ) ).toBe( 'one two three' );
		expect( trimWords( 'one two', 5 ) ).toBe( 'one two' );
		expect( trimWords( 'one', 1 ) ).toBe( 'one' );
	} );

	it( 'FEJS-42: splits on runs of spaces, tabs and line breaks and joins with one space', () => {
		const text = '  one \t two\n\nthree\r\nfour  ';

		expect( trimWords( text, 2 ) ).toBe( 'one two…' );
		expect( trimWords( text, 4 ) ).toBe( 'one two three four' );
		expect( trimWords( text, 10 ) ).toBe( 'one two three four' );
	} );

	it( 'FEJS-42: keeps punctuation and non-breaking spaces inside words', () => {
		expect( trimWords( 'one, two. three!', 2 ) ).toBe( 'one, two.…' );
		expect( trimWords( 'one two three', 1 ) ).toBe( 'one two…' );
		expect( trimWords( 'one two three', 2 ) ).toBe( 'one two three' );
	} );

	it.each( [ '', null, undefined, ' ', '  \n\t \r\n ' ] )(
		'FEJS-43: returns an empty string for %p',
		( text ) => {
			expect( trimWords( text, 3 ) ).toBe( '' );
			expect( trimWords( text, 0 ) ).toBe( '' );
		}
	);

	it( 'FEJS-44: with 0 words returns only the ellipsis for non-empty text', () => {
		expect( trimWords( 'one two', 0 ) ).toBe( '…' );
		expect( trimWords( 'one', 0, ' [more]' ) ).toBe( ' [more]' );
	} );

	it.each( [
		[ '3', 'one two three…' ],
		[ 2.9, 'one two…' ],
		[ '2.9', 'one two…' ],
		[ -2, 'one two…' ],
		[ '-3', 'one two three…' ],
		[ 'abc', '…' ],
		[ undefined, '…' ],
		[ null, '…' ],
		[ NaN, '…' ],
	] )(
		'FEJS-45: converts numWords %p like PHP absint()',
		( numWords, expected ) => {
			expect( trimWords( 'one two three four', numWords ) ).toBe(
				expected
			);
		}
	);

	it( 'FEJS-46: appends a custom more string only when trimmed', () => {
		expect( trimWords( 'one two three', 2, ' [more]' ) ).toBe(
			'one two [more]'
		);
		expect( trimWords( 'one two three', 3, ' [more]' ) ).toBe(
			'one two three'
		);
		expect( trimWords( 'one two three', 2, '' ) ).toBe( 'one two' );
	} );

	// Expected values from PHP running the word branch of WP core's
	// wp_trim_words( $text, absint( $n ), '…' ).
	it.each( [
		[ 'one two three four five', 3, 'one two three…' ],
		[ 'one two three', 3, 'one two three' ],
		[ 'one two', 5, 'one two' ],
		[ '  one \t two\n\nthree\r\nfour  ', 2, 'one two…' ],
		[ '  one \t two\n\nthree\r\nfour  ', 10, 'one two three four' ],
		[ '', 3, '' ],
		[ '  \n\t ', 3, '' ],
		[ 'one two', 0, '…' ],
		[ '', 0, '' ],
		[ 'one two three', 1, 'one two…' ],
		[ 'one, two. three!', 2, 'one, two.…' ],
		[ 'one two three four', -2, 'one two…' ],
	] )(
		'FEJS-47: matches PHP wp_trim_words( %p, absint( %p ) )',
		( text, numWords, expected ) => {
			expect( trimWords( text, numWords, '…' ) ).toBe( expected );
		}
	);
} );

describe( 'getSubString', () => {
	it( 'returns short strings unchanged', () => {
		expect( getSubString( 'Hello world', 20 ) ).toBe( 'Hello world' );
	} );

	it( 'keeps the first N words and appends an ellipsis', () => {
		expect( getSubString( 'one two three four', 2 ) ).toBe( 'one two...' );
	} );
} );

describe( 'deepClone / isEqual', () => {
	it( 'clones nested objects without sharing references', () => {
		const source = { a: [ 1, { b: 2 } ], c: { d: 'e' } };
		const clone = deepClone( source );

		expect( isEqual( clone, source ) ).toBe( true );
		expect( clone.a ).not.toBe( source.a );
		expect( clone.c ).not.toBe( source.c );
	} );

	it( 'detects differences', () => {
		expect( isEqual( { a: [ 1, 2 ] }, { a: [ 2, 1 ] } ) ).toBe( false );
	} );
} );
