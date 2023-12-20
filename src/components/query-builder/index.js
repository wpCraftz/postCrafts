/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

import {
	PanelBody,
	Button,
	RangeControl,
	Modal,
	SelectControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import PostSelector from './post-selection';
import TaxonomyControls from './taxonomoy-controls';
import Sortable from './sortable';
import OrderControl from './order-control';

/**
 * Component to display post selection settings inside Inspector Control.
 *
 * @param {Object}   props                      Object containing related params to display Post Block settings.
 * @param {string}   props.label                Component Label.
 * @param {Object}   props.attributes           Attributes of current block.
 * @param {Function} props.setAttributes        Function to update the attributes.
 * @param {Object}   props.postMeta             Object entries Post title & Id.
 * @param {Object}   props.sorting              Sorting related attributes.
 * @param {Object}   props.sortableCustomLabels Custom class name to add to the Sortable component.
 * @param {number}   props.maxNumberOfPost      Limit of max post can be selected
 * @param {boolean}  props.enableRelation       Relation between Categories & Tags
 * @param {boolean}  props.initialOpen          Determines wheather Panel will be open initially
 *
 * @return {JSX.Element}  Post Block Settings
 */
const PostBlockSettings = ( {
	label = 'Settings',
	attributes,
	setAttributes,
	postMeta = [],
	sorting = {},
	sortableCustomLabels,
	maxNumberOfPost = 25,
	enableRelation = false,
	initialOpen = false,
} ) => {
	const {
		postIds,
		taxQuery = {
			category: [],
			post_tag: [],
		},
		taxRelation,
		tagOperator,
		catOperator,
		postType = 'post',
		pagination,
		postsPerPage,
	} = attributes;

	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ isSortingModalOpen, setIsSortingModalOpen ] = useState( false );
	const [ selectedPosts, setSelectedPosts ] = useState( postIds );

	const paginationRef = useRef( pagination );

	useEffect( () => {
		setSelectedPosts( postIds );
	}, [ postIds ] );

	/**
	 * Save Selected Post.
	 *
	 * @param {Object}  post  Selected post data.
	 * @param {boolean} close Flag to close the modal.
	 */
	const onPostSelection = ( post, close = false ) => {
		if ( close ) {
			setAttributes( { postIds: selectedPosts } );
			setIsModalOpen( false );
			return;
		}

		if ( selectedPosts.includes( post.id ) ) {
			setSelectedPosts(
				selectedPosts.filter(
					( currentPostId ) => currentPostId !== post.id
				)
			);
		} else {
			setSelectedPosts( [ ...selectedPosts, post.id ] );
		}
		if ( typeof pagination !== 'undefined' ) {
			setAttributes( { pagination: false } );
		}
	};

	const clearAll = () => {
		setAttributes( { postIds: [] } );
		setSelectedPosts( [] );
		if ( typeof pagination !== 'undefined' ) {
			setAttributes( {
				pagination: paginationRef.current ?? pagination,
			} );
			paginationRef.current = null;
		}
	};

	/**
	 * Show Post Selection modal.
	 *
	 */
	const displayPostSelectModal = () => {
		return (
			<Modal
				title={ __( 'Select Posts', 'pc-blocks' ) }
				onRequestClose={ () => {
					setAttributes( { postIds: selectedPosts } );
					setIsModalOpen( false );
				} }
			>
				<PostSelector
					onPostSelect={ onPostSelection }
					postIds={ selectedPosts }
					limit={ postsPerPage }
				/>
			</Modal>
		);
	};

	/**
	 * Show Post Selection modal.
	 *
	 */
	const displayPostSortingModal = () => {
		return (
			<Modal
				title={ __( 'Sort Selected Posts', 'pc-blocks' ) }
				onRequestClose={ () => {
					setIsSortingModalOpen( false );
				} }
			>
				<Sortable
					items={ postMeta }
					onChange={ ( newValue ) => {
						paginationRef.current = pagination;
						setAttributes( { postIds: newValue } );
					} }
					customLabels={ sortableCustomLabels }
					closeModal={ () => setIsSortingModalOpen( false ) }
				/>
			</Modal>
		);
	};

	return (
		<>
			{ isModalOpen && displayPostSelectModal() }
			{ isSortingModalOpen && displayPostSortingModal() }
			<PanelBody title={ label } initialOpen={ initialOpen }>
				<div className="sort-container">
					<Button
						variant="primary"
						icon="search"
						iconPosition="right"
						className="post-selector-trigger"
						onClick={ () => setIsModalOpen( true ) }
					>
						{ __( 'Select Posts', 'pc-blocks' ) }
					</Button>
					{ !! postIds.length && (
						<>
							<Button
								variant="secondary"
								onClick={ clearAll }
								className="clear-selected-list"
								showTooltip
								label={ __( 'Clear Selection', 'pc-blocks' ) }
							>
								{ __( 'Clear', 'pc-blocks' ) }
							</Button>
						</>
					) }
				</div>
				{ !! ( postIds.length && postIds.length < postsPerPage ) && (
					<p
						id="inspector-range-control-0__help"
						className="components-base-control__help sortable-help"
					>
						{ __( 'You can select ', 'pc-blocks' ) }
						{ postsPerPage - postIds.length }
						{ __( ' more ', 'pc-blocks' ) }
						{ postsPerPage - postIds.length > 1
							? __( 'posts', 'pc-blocks' )
							: __( 'post', 'pc-blocks' ) }
					</p>
				) }

				{ !! postIds.length && (
					<Button
						variant="primary"
						icon="sort"
						iconPosition="right"
						className="post-sorting-modal-trigger"
						onClick={ () => setIsSortingModalOpen( true ) }
					>
						{ __( 'Sort Posts', 'pc-blocks' ) }
					</Button>
				) }

				<RangeControl
					min={ 1 }
					max={ maxNumberOfPost }
					value={ postsPerPage }
					className="number-of-posts"
					label={ __( 'Max number of Posts ', 'pc-blocks' ) }
					onChange={ ( postsPerPage ) => {
						const updatedAttrs = {
							postsPerPage,
						};
						if ( postIds.length > postsPerPage ) {
							updatedAttrs.postIds = [ ...postIds ].splice(
								0,
								postsPerPage
							);
						}
						setAttributes( updatedAttrs );
					} } /*eslint  no-shadow:0 */
				/>

				{ Object.keys( sorting ).length > 0 && (
					<OrderControl
						{ ...{
							order: sorting.order,
							orderBy: sorting.orderBy,
						} }
						onChange={ ( newQuery ) => {
							setAttributes( { sorting: newQuery } );
						} }
					/>
				) }

				<TaxonomyControls
					onChange={ ( newQuery ) => setAttributes( newQuery ) }
					taxQuery={ taxQuery }
					postType={ postType }
					catOperator={ catOperator }
					tagOperator={ tagOperator }
					enableRelation={ enableRelation }
					setAttributes={ setAttributes }
				/>
				{ enableRelation &&
					taxQuery.category?.length > 0 &&
					taxQuery.post_tag?.length > 0 && (
						<SelectControl
							label={ __( 'Relation', 'pc-blocks' ) }
							value={ taxRelation }
							options={ [
								{
									label: __( 'AND', 'pc-blocks' ),
									value: 'AND',
								},
								{ label: __( 'OR', 'pc-blocks' ), value: 'OR' },
							] }
							onChange={ ( newRelation ) =>
								setAttributes( { taxRelation: newRelation } )
							}
							help={ __(
								'The logical relationship between above taxonomies.',
								'pc-blocks'
							) }
						/>
					) }
			</PanelBody>
		</>
	);
};

export default PostBlockSettings;
