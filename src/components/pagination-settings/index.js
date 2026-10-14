/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Alignment from '../alignment';
import Type from './type';
import Range from '../range';
import ColorControl from '../color-control';

const PaginationSettings = ( { clientId, attributes, setAttributes } ) => {
	const {
		paginationType = 'pagination',
		pagination,
		paginationAlignment,
		paginationColor,
		paginationBg,
		paginationGradient,
		paginationBorderRadius,
	} = attributes;

	return (
		<PanelBody
			title={ __( 'Pagination', 'postcrafts' ) }
			initialOpen={ true }
		>
			<ToggleControl
				label={ __( 'Enable Pagination', 'postcrafts' ) }
				checked={ pagination }
				onChange={ ( newValue ) => {
					setAttributes( { pagination: newValue } );
				} }
			/>
			{ pagination && (
				<>
					<Type
						clientId={ clientId }
						value={ paginationType }
						setAttributes={ setAttributes }
					/>
					<Alignment
						clientId={ clientId }
						value={ paginationAlignment }
						name="paginationAlignment"
						setAttributes={ setAttributes }
					/>
					<ColorControl
						settings={ [
							{
								colorValue: paginationColor,
								label: __( 'Text', 'postcrafts' ),
								onColorChange: ( newValue ) =>
									setAttributes( {
										paginationColor: newValue,
									} ),
							},
							{
								colorValue: paginationBg,
								gradientValue: paginationGradient,
								label: __( 'Background', 'postcrafts' ),
								onColorChange: ( newValue ) =>
									setAttributes( {
										paginationBg: newValue,
									} ),
								onGradientChange: ( newValue ) =>
									setAttributes( {
										paginationGradient: newValue,
									} ),
							},
						] }
						label={ __( 'Color', 'postcrafts' ) }
					/>
					<Range
						isResponsive
						value={ paginationBorderRadius }
						max={ 50 }
						label={ __( 'Border Radius', 'postcrafts' ) }
						onChange={ ( value ) => {
							setAttributes( { paginationBorderRadius: value } );
						} }
					/>
				</>
			) }
		</PanelBody>
	);
};

export default PaginationSettings;
