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
			title={ __( 'Pagination', 'post-crafts' ) }
			initialOpen={ true }
		>
			<ToggleControl
				label={ __( 'Enable Pagination', 'post-crafts' ) }
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
								label: __( 'Text', 'post-crafts' ),
								onColorChange: ( newValue ) =>
									setAttributes( {
										paginationColor: newValue,
									} ),
							},
							{
								colorValue: paginationBg,
								gradientValue: paginationGradient,
								label: __( 'Background', 'post-crafts' ),
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
						label={ __( 'Color', 'post-crafts' ) }
					/>
					<Range
						isResponsive
						value={ paginationBorderRadius }
						max={ 50 }
						label={ __( 'Border Radius', 'post-crafts' ) }
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
