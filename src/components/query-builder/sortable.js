/**
 * WordPress dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * External dependencies
 */
import { sortableContainer, SortableElement } from 'react-sortable-hoc';
import { arrayMoveImmutable } from 'array-move';
import classnames from 'classnames';

const SortableItem = SortableElement( ( { value, itemIndex } ) => {
	/**
	 * Get post status.
	 *
	 * @param {string} postStatus
	 *
	 * @return {string} Post Status.
	 */
	const getPresentablePostStatus = ( postStatus ) => {
		if ( ! postStatus ) {
			return '';
		}

		if ( 'publish' === postStatus ) {
			return 'Published';
		}

		return '';
	};

	const className = classnames( 'sortable-item', 'items-list--item item', {
		'first-item': itemIndex === 0,
	} );

	return (
		<li className={ className }>
			<span className="item__post-id">{ value.id }</span>
			<span className="item__post-title">
				{ decodeEntities( value.title ) ||
					__( '(No Title)', 'post-crafts' ) }
			</span>
			<span className="item__post-status">
				{ getPresentablePostStatus( value.status ) }
			</span>
		</li>
	);
} );

const SortableContainer = sortableContainer( ( { children, customClass } ) => {
	return (
		<div
			className={ classnames( 'kbb-sortable-container', customClass ) }
			id="kbb-sortable-container"
		>
			{ children }
		</div>
	);
} );

/**
 * Sortable component.
 *
 * @param {Object}   props
 * @param {Function} props.onChange     On post select function.
 * @param {Array}    props.items        Items need to be sorted.
 * @param {string}   props.customClass  Custom class name to add to the wrapper.
 * @param {Object}   props.customLabels Object with keys equal to items index before which custom label need to be inserted.
 * @param {Function} props.closeModal   Function to close the Modal.
 *
 * @return {JSX} Sortable component
 */
const Sortable = ( {
	items,
	onChange,
	customClass,
	customLabels = {},
	closeModal,
} ) => {
	const onSortEnd = ( { oldIndex, newIndex } ) => {
		onChange(
			arrayMoveImmutable( items, oldIndex, newIndex ).map(
				( item ) => item.id
			)
		);
	};
	return (
		<div className="post-sorter">
			<Button
				variant="primary"
				className="close-sorting-modal"
				onClick={ () => closeModal() }
			>
				{ __( 'Finish Sorting', 'post-crafts' ) }
			</Button>
			<SortableContainer
				onSortEnd={ onSortEnd }
				customClass={ customClass }
			>
				<ul className="post-sotring__items-wrapper items-list">
					<li className="items-list--item item item--headers">
						<span className="item__post-id">
							{ __( 'Post ID', 'post-crafts' ) }
						</span>
						<span className="item__post-title">
							{ __( 'Post Title', 'post-crafts' ) }
						</span>
						<span className="item__post-status">
							{ __( 'Post Status', 'post-crafts' ) }
						</span>
					</li>
				</ul>
				{ items.map( ( item, index ) => (
					<Fragment key={ item }>
						{ Object.keys( customLabels ).length > 0 && (
							<h4 className="custom-label">
								{ customLabels[ index ] }
							</h4>
						) }
						<SortableItem
							key={ `item-${ item.title }` }
							index={ index }
							itemIndex={ index }
							value={ item }
						/>
					</Fragment>
				) ) }
			</SortableContainer>
		</div>
	);
};

export default Sortable;
