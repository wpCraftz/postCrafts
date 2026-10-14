/**
 * Internal dependencies
 */
import { getSubString, deepClone, isEqual } from '../../../src/libs/utils';

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
