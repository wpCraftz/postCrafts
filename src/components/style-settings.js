/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ColorControl from './color-control';
import Range from './range';

// Fallback for blocks saved before `titleFontSize` existed (no default, so no CSS).
const EMPTY_FONT_SIZE = { unit: 'px' };

/**
 * Card colors and typography, shown in the block's Styles tab.
 *
 * The generated CSS comes from `src/editor/dynamic-attributes/<block>.js`.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update the attributes.
 *
 * @return {JSX.Element} Style settings panels.
 */
const StyleSettings = ( { attributes, setAttributes } ) => {
	const { titleFontSize = EMPTY_FONT_SIZE } = attributes;

	const colorSetting = ( key, label ) => ( {
		colorValue: attributes[ key ],
		label,
		onColorChange: ( newValue ) => setAttributes( { [ key ]: newValue } ),
	} );

	return (
		<>
			<PanelBody title={ __( 'Colors', 'postcrafts' ) } initialOpen>
				<ColorControl
					label={ __( 'Card Colors', 'postcrafts' ) }
					settings={ [
						colorSetting(
							'categoryColor',
							__( 'Category', 'postcrafts' )
						),
						colorSetting(
							'titleColor',
							__( 'Title', 'postcrafts' )
						),
						colorSetting(
							'titleHoverColor',
							__( 'Title Hover', 'postcrafts' )
						),
						colorSetting( 'metaColor', __( 'Meta', 'postcrafts' ) ),
						colorSetting(
							'excerptColor',
							__( 'Excerpt', 'postcrafts' )
						),
					] }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Typography', 'postcrafts' ) }
				initialOpen={ false }
			>
				<Range
					isResponsive
					value={ titleFontSize }
					min={ 10 }
					max={ 80 }
					units={ [ 'px' ] }
					label={ __( 'Title Font Size', 'postcrafts' ) }
					onChange={ ( value ) =>
						setAttributes( { titleFontSize: value } )
					}
				/>
			</PanelBody>
		</>
	);
};

export default StyleSettings;
