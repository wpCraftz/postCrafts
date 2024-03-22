/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	Button,
	ButtonGroup,
	ToggleControl,
} from '@wordpress/components';

const TYPES = [
	__( 'pagination', 'post-crafts' ),
	__( 'arrow', 'post-crafts' ),
	__( 'loadmore', 'post-crafts' ),
];

const Pagination = ( { attributes, setAttributes } ) => {
	const { paginationType = 'pagination', pagination } = attributes;

	return (
		<PanelBody
			title={ __( 'Pagination', 'post-crafts' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Enable Pagination', 'post-crafts' ) }
				checked={ pagination }
				onChange={ ( newValue ) => {
					setAttributes( { enablePagination: newValue } );
				} }
			/>
			{ pagination && (
				<>
					<ButtonGroup className="post-crafts-pagination-settings">
						{ TYPES.map( ( type ) => (
							<Button
								key={ type }
								size="small"
								{ ...( type === paginationType && {
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
				</>
			) }
		</PanelBody>
	);
};

export default Pagination;
