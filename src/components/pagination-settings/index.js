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

const PaginationSettings = ( { clientId, attributes, setAttributes } ) => {
	const {
		paginationType = 'pagination',
		pagination,
		paginationAlignment,
	} = attributes;

	return (
		<PanelBody
			title={ __( 'Pagination', 'post-crafts' ) }
			initialOpen={ false }
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
				</>
			) }
		</PanelBody>
	);
};

export default PaginationSettings;
