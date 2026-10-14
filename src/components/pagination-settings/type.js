/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup, BaseControl } from '@wordpress/components';

/**
 * External dependencies
 */
import classNames from 'classnames';

const TYPES = [
	__( 'pagination', 'postcrafts' ),
	__( 'arrow', 'postcrafts' ),
	__( 'loadmore', 'postcrafts' ),
];

const Type = ( { clientId, value, setAttributes, className } ) => {
	const classes = classNames( [
		'pcrafts-pagination-type',
		'pcrafts-field',
		className,
	] );
	return (
		<BaseControl
			className={ classes }
			id={ `${ clientId }-pagination-type` }
			label={ __( 'Pagination Type', 'postcrafts' ) }
		>
			<ButtonGroup className="pcrafts-pagination-settings pcrafts-field-options">
				{ TYPES.map( ( type ) => (
					<Button
						key={ type }
						{ ...( type === value && {
							isPrimary: true,
						} ) }
						onClick={ () =>
							setAttributes( { paginationType: type } )
						}
					>
						{ type }
					</Button>
				) ) }
			</ButtonGroup>
		</BaseControl>
	);
};

export default Type;
