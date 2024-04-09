/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { Range } from '../components';

const ListSetttings = ( {
	attributes,
	setAttributes,
	initialOpen = false,
} ) => {
	const { rowGap } = attributes;
	return (
		<PanelBody
			title={ __( 'Layout Settings', 'post-crafts' ) }
			initialOpen={ initialOpen }
		>
			<Range
				isResponsive
				value={ rowGap }
				label={ __( 'Row Gap', 'post-crafts' ) }
				onChange={ ( value ) => {
					setAttributes( { rowGap: value } );
				} }
			/>
		</PanelBody>
	);
};
export default ListSetttings;
