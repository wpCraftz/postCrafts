/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const orderOptions = [
	{
		label: __( 'Newest to oldest', 'pcrafts' ),
		value: 'date/desc',
	},
	{
		label: __( 'Oldest to newest', 'pcrafts' ),
		value: 'date/asc',
	},
	{
		/* translators: label for ordering posts by title in ascending order */
		label: __( 'A → Z', 'pcrafts' ),
		value: 'title/asc',
	},
	{
		/* translators: label for ordering posts by title in descending order */
		label: __( 'Z → A', 'pcrafts' ),
		value: 'title/desc',
	},
];
function OrderControl( { order, orderBy, onChange } ) {
	return (
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Order by', 'pcrafts' ) }
			value={ `${ orderBy }/${ order }` }
			options={ orderOptions }
			onChange={ ( value ) => {
				const [ newOrderBy, newOrder ] = value.split( '/' );
				onChange( { order: newOrder, orderBy: newOrderBy } );
			} }
		/>
	);
}

export default OrderControl;
