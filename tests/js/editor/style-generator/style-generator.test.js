/**
 * Save-time CSS generator (src/editor/style-generator + dynamic-attributes).
 *
 * Acceptance criteria: tests/acceptance/frontend-js.md (FEJS-50 – FEJS-59).
 */

/**
 * Internal dependencies
 */
import styleGenerator from '../../../../src/editor/style-generator';
import { range, color } from '../../../../src/editor/style-generator/helpers';
import { LEGACY_CASES } from './legacy-fixtures';

const GRID = 'post-crafts/post-grid';
const LIST = 'post-crafts/post-list';

// Output of the `main` branch (pre-PR #10) generator for LEGACY_CASES, in order.
const PRE_PR_CSS = [
	'.pcrafts-block-abc12345 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(3, 1fr); } .pcrafts-block-abc12345 .pcrafts-grid-items-wrapper { column-gap: 20px; } .pcrafts-block-abc12345 .pcrafts-grid-items-wrapper { row-gap: 20px; } .pcrafts-block-abc12345 .pcrafts-pages li { border-radius: 2px; }',
	'.pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(4, 1fr); } .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { column-gap: 30px; } .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { row-gap: 2em; } .pcrafts-block-grid0001 .pcrafts-pages li { border-radius: 50%; } .pcrafts-block-grid0001 .pcrafts-pages li { color: #ffffff; } .pcrafts-block-grid0001 .pcrafts-pages li { background-color: #111111; }@media (max-width: 991px) { .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(2, 1fr); } .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { column-gap: 20px; } .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { row-gap: 1.5em; } }@media (max-width: 767px) { .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(1, 1fr); } .pcrafts-block-grid0001 .pcrafts-grid-items-wrapper { column-gap: 10px; } .pcrafts-block-grid0001 .pcrafts-pages li { border-radius: 4px; } }',
	'.pcrafts-block-grad0001 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(3, 1fr); } .pcrafts-block-grad0001 .pcrafts-grid-items-wrapper { column-gap: 0px; } .pcrafts-block-grad0001 .pcrafts-grid-items-wrapper { row-gap: 20px; } .pcrafts-block-grad0001 .pcrafts-pages li { border-radius: 2px; } .pcrafts-block-grad0001 .pcrafts-pages li { background: linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%); }',
	'.pcrafts-block-nopag001 .pcrafts-grid-items-wrapper { grid-template-columns: repeat(2, 1fr); } .pcrafts-block-nopag001 .pcrafts-grid-items-wrapper { column-gap: 20px; } .pcrafts-block-nopag001 .pcrafts-grid-items-wrapper { row-gap: 20px; }',
	'.pcrafts-block-list0001 .pcrafts-list-items-wrapper .post-list { margin-bottom: 20px; } .pcrafts-block-list0001 .pcrafts-pages li { border-radius: 2px; }',
	'.pcrafts-block-list0002 .pcrafts-list-items-wrapper .post-list { margin-bottom: 3rem; } .pcrafts-block-list0002 .pcrafts-pages li { border-radius: 6px; } .pcrafts-block-list0002 .pcrafts-pages li { color: red; } .pcrafts-block-list0002 .pcrafts-pages li { background: linear-gradient(red, blue); }@media (max-width: 991px) { .pcrafts-block-list0002 .pcrafts-list-items-wrapper .post-list { margin-bottom: 2rem; } .pcrafts-block-list0002 .pcrafts-pages li { border-radius: 4px; } }@media (max-width: 767px) { .pcrafts-block-list0002 .pcrafts-list-items-wrapper .post-list { margin-bottom: 12px; } }',
];

const scope = ( id ) => `.pcrafts-block.pcrafts-block-${ id }`;

describe( 'pre-PR attributes', () => {
	it.each( LEGACY_CASES.map( ( c, i ) => [ c.name, c, PRE_PR_CSS[ i ] ] ) )(
		'FEJS-50: CSS is unchanged from main: %s',
		( _name, { block, attributes }, expected ) => {
			expect( styleGenerator( block, attributes ) ).toBe( expected );
		}
	);

	it.each( LEGACY_CASES.map( ( c ) => [ c.name, c ] ) )(
		'FEJS-51: unset new attributes add nothing: %s',
		( _name, { block, attributes } ) => {
			const css = styleGenerator( block, attributes );

			expect( css ).not.toContain( '.pcrafts-block.pcrafts-block-' );
			expect( css ).not.toContain( 'entry-title' );
			expect( css ).not.toContain( 'undefined' );
		}
	);
} );

describe.each( [
	[ GRID, 'post-grid' ],
	[ LIST, 'post-list' ],
] )( 'new card attributes (%s)', ( block, articleClass ) => {
	const base = { blockId: 'card0001', pagination: true };
	const css = ( attributes ) =>
		styleGenerator( block, { ...base, ...attributes } );
	const s = scope( 'card0001' );

	it( 'FEJS-51: emits nothing when the new attributes are unset or empty', () => {
		expect( css( {} ) ).toBe( '' );
		expect(
			css( {
				categoryColor: '',
				titleColor: '',
				titleHoverColor: '',
				metaColor: '',
				excerptColor: '',
				titleFontSize: { unit: 'px' },
			} )
		).toBe( '' );
	} );

	it.each( [
		[ 'categoryColor', `${ s } .cat-links a { color: #c00; }` ],
		[ 'titleColor', `${ s } .entry-title a { color: #c00; }` ],
		[
			'metaColor',
			`${ s } .entry-meta, ${ s } .entry-meta a { color: #c00; }`,
		],
		[
			'excerptColor',
			`${ s } .entry-summary, ${ s } .entry-summary p { color: #c00; }`,
		],
	] )( 'FEJS-52: %s emits its scoped rule', ( attribute, expected ) => {
		expect( css( { [ attribute ]: '#c00' } ) ).toBe( expected );
	} );

	it( 'FEJS-53: titleHoverColor targets the link and the card hover', () => {
		expect(
			css( { titleHoverColor: 'var(--wp--preset--color--accent)' } )
		).toBe(
			`${ s } .entry-title a:hover, ${ s } article.${ articleClass }:hover .entry-title a { color: var(--wp--preset--color--accent); }`
		);
	} );

	it( 'FEJS-52: all colors together, in map order', () => {
		expect(
			css( {
				categoryColor: '#111',
				titleColor: '#222',
				titleHoverColor: '#333',
				metaColor: '#444',
				excerptColor: '#555',
			} )
		).toBe(
			[
				`${ s } .cat-links a { color: #111; }`,
				`${ s } .entry-title a { color: #222; }`,
				`${ s } .entry-title a:hover, ${ s } article.${ articleClass }:hover .entry-title a { color: #333; }`,
				`${ s } .entry-meta, ${ s } .entry-meta a { color: #444; }`,
				`${ s } .entry-summary, ${ s } .entry-summary p { color: #555; }`,
			].join( ' ' )
		);
	} );

	it( 'FEJS-54: titleFontSize emits desktop, tablet and mobile rules', () => {
		expect(
			css( {
				titleFontSize: {
					unit: 'px',
					value: 24,
					tablet: { value: 20 },
					mobile: { unit: 'rem', value: 1.1 },
				},
			} )
		).toBe(
			`${ s } .entry-title { font-size: 24px; line-height: 1.25; }` +
				`@media (max-width: 991px) { ${ s } .entry-title { font-size: 20px; line-height: 1.25; } }` +
				`@media (max-width: 767px) { ${ s } .entry-title { font-size: 1.1rem; line-height: 1.25; } }`
		);
	} );

	it( 'FEJS-55: titleFontSize with only a tablet value emits only the tablet rule', () => {
		expect(
			css( { titleFontSize: { unit: 'px', tablet: { value: 18 } } } )
		).toBe(
			`@media (max-width: 991px) { ${ s } .entry-title { font-size: 18px; line-height: 1.25; } }`
		);
	} );

	it( 'FEJS-55: titleFontSize with only a mobile value emits only the mobile rule', () => {
		expect(
			css( { titleFontSize: { unit: 'em', mobile: { value: 1 } } } )
		).toBe(
			`@media (max-width: 767px) { ${ s } .entry-title { font-size: 1em; line-height: 1.25; } }`
		);
	} );

	it( 'FEJS-58: card styles do not depend on pagination', () => {
		expect( css( { pagination: false, titleColor: '#222' } ) ).toBe(
			`${ s } .entry-title a { color: #222; }`
		);
	} );

	it( 'FEJS-59: a device entry without a value emits no empty media query', () => {
		expect(
			css( {
				titleFontSize: {
					unit: 'px',
					value: 22,
					tablet: { unit: 'em' },
					mobile: { unit: 'rem' },
				},
			} )
		).toBe( `${ s } .entry-title { font-size: 22px; line-height: 1.25; }` );
	} );

	it( 'FEJS-50: new attributes are appended after the pre-PR rules', () => {
		const legacy = LEGACY_CASES.find( ( c ) => c.block === block );
		const before = styleGenerator( legacy.block, legacy.attributes );
		const after = styleGenerator( legacy.block, {
			...legacy.attributes,
			titleColor: '#222',
		} );
		const id = legacy.attributes.blockId;

		expect( after ).toBe(
			`${ before } ${ scope( id ) } .entry-title a { color: #222; }`
		);
	} );
} );

describe( 'helpers', () => {
	const selector = '.x { width: $value$unit; }';

	it.each( [
		[ 'undefined value', { unit: 'px' } ],
		[ 'null value', { value: null, unit: 'px' } ],
		[ 'empty string value', { value: '', unit: 'px' } ],
		[ 'empty object', {} ],
		[ 'no value object', undefined ],
	] )( "FEJS-56: range() returns '' for %s", ( _label, value ) => {
		expect( range( selector, value, 'px' ) ).toBe( '' );
	} );

	it( 'FEJS-56: range() treats 0 as a value', () => {
		expect( range( selector, { value: 0, unit: 'px' } ) ).toBe(
			'.x { width: 0px; }'
		);
	} );

	it( 'FEJS-57: range() prefers the value unit, then the fallback unit', () => {
		expect( range( selector, { value: 2, unit: 'em' }, 'px' ) ).toBe(
			'.x { width: 2em; }'
		);
		expect( range( selector, { value: 2 }, 'px' ) ).toBe(
			'.x { width: 2px; }'
		);
		expect(
			range( '.g { grid-template-columns: repeat($value, 1fr); }', {
				value: 3,
			} )
		).toBe( '.g { grid-template-columns: repeat(3, 1fr); }' );
	} );

	it( 'FEJS-57: color() substitutes the value', () => {
		expect( color( '.x { color: $value; }', '#abc' ) ).toBe(
			'.x { color: #abc; }'
		);
	} );
} );
