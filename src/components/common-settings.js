/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

const CommonSettings = ( {
	attributes,
	setAttributes,
	initialOpen = false,
	label,
} ) => {
	const { showExcerpt, showCategory, showPagination } = attributes;
	return (
		<PanelBody title={ label } initialOpen={ initialOpen }>
			{ typeof showCategory !== 'undefined' && (
				<ToggleControl
					label={ __( 'Show Category', 'pc-blocks' ) }
					checked={ showCategory }
					onChange={ ( value ) =>
						setAttributes( { showCategory: value } )
					}
				/>
			) }
			{ typeof showExcerpt !== 'undefined' && (
				<ToggleControl
					label={ __( 'Show Excerpt', 'pc-blocks' ) }
					checked={ showExcerpt }
					onChange={ ( value ) =>
						setAttributes( { showExcerpt: value } )
					}
				/>
			) }

			{ typeof showPagination !== 'undefined' && (
				<ToggleControl
					label={ __( 'Show Pagination', 'pc-blocks' ) }
					checked={ showPagination }
					onChange={ ( value ) =>
						setAttributes( { showPagination: value } )
					}
				/>
			) }
		</PanelBody>
	);
};
export default CommonSettings;
