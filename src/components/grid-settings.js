/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { Range } from '../components';

const GridSetttings = ( {
	attributes,
	setAttributes,
	initialOpen = false,
} ) => {
	const { columns, columnGap, rowGap } = attributes;
	return (
		<PanelBody
			title={ __( 'Layout Settings', 'postcrafts' ) }
			initialOpen={ initialOpen }
		>
			<Range
				isResponsive
				units={ false }
				value={ columns }
				max={ 10 }
				label={ __( 'Columns', 'postcrafts' ) }
				onChange={ ( value ) => {
					setAttributes( { columns: value } );
				} }
			/>
			<Range
				isResponsive
				value={ columnGap }
				label={ __( 'Column Gap', 'postcrafts' ) }
				onChange={ ( value ) => {
					setAttributes( { columnGap: value } );
				} }
			/>
			<Range
				isResponsive
				value={ rowGap }
				label={ __( 'Row Gap', 'postcrafts' ) }
				onChange={ ( value ) => {
					setAttributes( { rowGap: value } );
				} }
			/>
		</PanelBody>
	);
};
export default GridSetttings;
