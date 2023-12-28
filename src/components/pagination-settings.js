/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl } from '@wordpress/components';

const PaginationSettings = ( {
	attributes,
	setAttributes,
	label,
	initialOpen,
} ) => {
	const { pages } = attributes;
	return (
		<PanelBody title={ label } initialOpen={ initialOpen }>
			{ /* <RangeControl
				label={ __( 'Number of links' ) }
				help={ __(
					'Specify how many links can appear before and after the current page number. Links to the first, current and last page are always visible.'
				) }
				value={ midSize }
				onChange={ ( value ) => {
					setAttributes( {
						midSize: parseInt( value, 10 ),
					} );
				} }
				min={ 0 }
				max={ 5 }
			/> */ }
			<RangeControl
				label={ __( 'Max page to show' ) }
				help={ __(
					'Limit the pages you want to show, even if the query has more results. To show all pages use 0 (zero).'
				) }
				value={ pages }
				onChange={ ( value ) => {
					setAttributes( {
						pages: parseInt( value, 10 ),
					} );
				} }
				min={ 0 }
				max={ 5 }
			/>
		</PanelBody>
	);
};

export default PaginationSettings;
