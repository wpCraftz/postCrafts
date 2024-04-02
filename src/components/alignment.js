/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup, Icon, BaseControl } from '@wordpress/components';
/**
 * External dependencies
 */
import classNames from 'classnames';

const Alignment = ( {
	clientId,
	name,
	value,
	className,
	enableJustify,
	label,
	hideLabel = false,
	setAttributes,
} ) => {
	const classes = classNames( [
		'pcrafts-alignment',
		'pcrafts-field',
		className,
	] );

	let options = [
		{ value: 'left', icon: 'editor-alignleft' },
		{ value: 'center', icon: 'editor-aligncenter' },
		{ value: 'right', icon: 'editor-alignright' },
	];

	if ( enableJustify ) {
		options = options.push( { value: 'justify', icon: 'editor-justify' } );
	}

	return (
		<BaseControl
			className={ classes }
			id={ `${ clientId }-pagination-alignment` }
			{ ...( ! hideLabel && {
				label: label ? label : __( 'Alignment', 'post-crafts' ),
			} ) }
		>
			<ButtonGroup className="pcrafts-field-options">
				{ options.map( ( { value: optionValue, icon } ) => (
					<Button
						key={ optionValue }
						variant={
							value === optionValue ? 'primary' : 'secondary'
						}
						onClick={ () =>
							setAttributes( {
								[ name ]: optionValue,
							} )
						}
					>
						<Icon icon={ icon } />
					</Button>
				) ) }
			</ButtonGroup>
		</BaseControl>
	);
};

export default Alignment;
