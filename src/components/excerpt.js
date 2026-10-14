/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

const ExcerptSettings = ( { attributes, setAttributes } ) => {
	const { excerpt, excerptLength } = attributes;
	return (
		<PanelBody
			title={ __( 'Excerpt', 'postcrafts' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Show Excerpt ', 'postcrafts' ) }
				checked={ excerpt }
				onChange={ ( newValue ) => {
					setAttributes( { excerpt: newValue } );
				} }
			/>
			{ excerpt && (
				<RangeControl
					label={ __( 'Excerpt Length', 'postcrafts' ) }
					value={ excerptLength }
					onChange={ ( newValue ) => {
						setAttributes( { excerptLength: newValue } );
					} }
					min={ 5 }
					max={ 200 }
				/>
			) }
		</PanelBody>
	);
};

export default ExcerptSettings;
