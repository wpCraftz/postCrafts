/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

const ExcerptSettings = ( { attributes, setAttributes } ) => {
	const { excerpt, excerptLength } = attributes;
	return (
		<PanelBody
			title={ __( 'Excerpt', 'post-crafts' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Show Excerpt ', 'post-crafts' ) }
				checked={ excerpt }
				onChange={ ( newValue ) => {
					setAttributes( { excerpt: newValue } );
				} }
			/>
			{ excerpt && (
				<RangeControl
					label={ __( 'Excerpt Length', 'post-crafts' ) }
					value={ excerptLength }
					onChange={ ( newValue ) => {
						setAttributes( { excerptLength: newValue } );
					} }
					min={ 10 }
					max={ 100 }
				/>
			) }
		</PanelBody>
	);
};

export default ExcerptSettings;
