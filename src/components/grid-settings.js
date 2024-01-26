/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl } from '@wordpress/components';

const GridSetttings = ( {
	attributes,
	setAttributes,
	initialOpen = false,
} ) => {
	const { columns } = attributes;
	return (
		<PanelBody
			title={ __( 'Layout Settings', 'post-crafts' ) }
			initialOpen={ initialOpen }
		>
			<RangeControl
				min={ 1 }
				max={ 6 }
				value={ columns }
				label={ __( 'Columns', 'post-crafts' ) }
				onChange={ ( value ) => {
					setAttributes( { columns: value } );
				} }
			/>
		</PanelBody>
	);
};
export default GridSetttings;
