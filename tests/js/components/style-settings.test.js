/**
 * Styles tab: StyleSettings wiring of color and typography controls.
 *
 * ColorControl and Range are rendered for real. Only modules that are not
 * installed (@wordpress/block-editor, @wordpress/editor) and the editor device
 * hook (needs the core/editor store) are mocked.
 */

/**
 * External dependencies
 */
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import StyleSettings from '../../../src/components/style-settings';
import { useDevice } from '../../../src/hooks';

const PALETTE = [ { name: 'Brand Red', slug: 'brand-red', color: '#c00000' } ];

jest.mock(
	'@wordpress/block-editor',
	() => ( {
		useSettings: () => [
			[ { name: 'Brand Red', slug: 'brand-red', color: '#c00000' } ],
			[],
		],
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ), {
	virtual: true,
} );

// Popover positioning (floating-ui) never settles in jsdom, so render the
// Dropdown content inline instead of in a popover. Toggle wiring is unchanged.
jest.mock( '@wordpress/components', () => {
	const actual = jest.requireActual( '@wordpress/components' );
	const { useState } = jest.requireActual( 'react' );
	const Dropdown = ( { renderToggle, renderContent, className } ) => {
		const [ isOpen, setIsOpen ] = useState( false );
		const onClose = () => setIsOpen( false );
		return (
			<div className={ className }>
				{ renderToggle( {
					isOpen,
					onToggle: () => setIsOpen( ! isOpen ),
					onClose,
				} ) }
				{ isOpen && renderContent( { isOpen, onClose } ) }
			</div>
		);
	};
	return { ...actual, Dropdown };
} );

jest.mock( '../../../src/hooks', () => ( {
	useDevice: jest.fn( () => 'Desktop' ),
} ) );

// ExtraControls dispatches setDeviceType to core/editor; register a stub store.
register(
	createReduxStore( 'core/editor', {
		reducer: ( state = {} ) => state,
		actions: { setDeviceType: () => ( { type: 'NOOP' } ) },
		selectors: { getDeviceType: () => 'Desktop' },
	} )
);

const renderStyles = ( attributes = {} ) => {
	const setAttributes = jest.fn();
	render(
		<StyleSettings
			attributes={ attributes }
			setAttributes={ setAttributes }
		/>
	);
	return setAttributes;
};

const COLOR_CONTROLS = [
	[ 'Category', 'categoryColor' ],
	[ 'Title', 'titleColor' ],
	[ 'Title Hover', 'titleHoverColor' ],
	[ 'Meta', 'metaColor' ],
	[ 'Excerpt', 'excerptColor' ],
];

describe( 'StyleSettings', () => {
	beforeEach( () => {
		useDevice.mockReturnValue( 'Desktop' );
	} );

	it( 'EDJS-17 renders the Colors and Typography panels with every control', async () => {
		const user = userEvent.setup();
		renderStyles();

		expect(
			screen.getByRole( 'button', { name: 'Colors' } )
		).toHaveAttribute( 'aria-expanded', 'true' );
		COLOR_CONTROLS.forEach( ( [ label ] ) =>
			expect(
				screen.getByRole( 'button', { name: label } )
			).toBeInTheDocument()
		);

		const typography = screen.getByRole( 'button', {
			name: 'Typography',
		} );
		expect( typography ).toHaveAttribute( 'aria-expanded', 'false' );
		await user.click( typography );
		expect(
			screen.getByRole( 'slider', { name: 'Title Font Size' } )
		).toBeInTheDocument();
	} );

	it.each( COLOR_CONTROLS )(
		'EDJS-18 the %s color control sets %s',
		async ( label, attribute ) => {
			const user = userEvent.setup();
			const setAttributes = renderStyles();

			await user.click( screen.getByRole( 'button', { name: label } ) );
			const swatch = await screen.findByRole( 'option', {
				name: new RegExp( PALETTE[ 0 ].name ),
			} );
			await user.click( swatch );

			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				[ attribute ]: PALETTE[ 0 ].color,
			} );
		}
	);

	const openTypography = async () => {
		await userEvent
			.setup()
			.click( screen.getByRole( 'button', { name: 'Typography' } ) );
		return screen.getByRole( 'slider', { name: 'Title Font Size' } );
	};

	it( 'EDJS-19 Desktop: writes titleFontSize { value, unit } and keeps tablet/mobile', async () => {
		const setAttributes = renderStyles( {
			titleFontSize: {
				value: 20,
				unit: 'px',
				tablet: { value: 18, unit: 'px' },
				mobile: { value: 16, unit: 'px' },
			},
		} );

		fireEvent.change( await openTypography(), {
			target: { value: '32' },
		} );

		expect( setAttributes ).toHaveBeenLastCalledWith( {
			titleFontSize: {
				value: 32,
				unit: 'px',
				tablet: { value: 18, unit: 'px' },
				mobile: { value: 16, unit: 'px' },
			},
		} );
	} );

	it( 'EDJS-19 defaults to px when the block has no saved titleFontSize', async () => {
		const setAttributes = renderStyles();

		fireEvent.change( await openTypography(), {
			target: { value: '24' },
		} );

		expect( setAttributes ).toHaveBeenLastCalledWith( {
			titleFontSize: { value: 24, unit: 'px' },
		} );
	} );

	it.each( [
		[ 'Tablet', 'tablet' ],
		[ 'Mobile', 'mobile' ],
	] )(
		'EDJS-19 %s: writes the value under titleFontSize.%s',
		async ( device, key ) => {
			useDevice.mockReturnValue( device );
			const setAttributes = renderStyles( {
				titleFontSize: { value: 20, unit: 'px' },
			} );

			fireEvent.change( await openTypography(), {
				target: { value: '14' },
			} );

			expect( setAttributes ).toHaveBeenLastCalledWith( {
				titleFontSize: {
					value: 20,
					unit: 'px',
					[ key ]: { value: 14 },
				},
			} );
		}
	);

	it( 'EDJS-18 color controls are not wired to any other attribute', async () => {
		const user = userEvent.setup();
		const setAttributes = renderStyles();
		const panel = screen
			.getByRole( 'button', { name: 'Colors' } )
			.closest( '.components-panel__body' );

		for ( const [ label ] of COLOR_CONTROLS ) {
			const toggle = within( panel ).getByRole( 'button', {
				name: label,
			} );
			await user.click( toggle );
			await user.click(
				within(
					toggle.closest(
						'.block-editor-tools-panel-color-gradient-settings__dropdown'
					)
				).getByRole( 'option', {
					name: new RegExp( PALETTE[ 0 ].name ),
				} )
			);
		}

		expect(
			setAttributes.mock.calls.map( ( [ attrs ] ) =>
				Object.keys( attrs )
			)
		).toEqual( COLOR_CONTROLS.map( ( [ , attribute ] ) => [ attribute ] ) );
	} );
} );
