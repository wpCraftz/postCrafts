/**
 * WordPress dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * External dependencies
 */
import { arrayMoveImmutable } from 'array-move';
import isEqual from 'lodash.isequal';

/**
 * Custom hook to fetch/reorder Posts in combination with custom Sortable component
 *
 * @param {Object} props
 * @param {Array}  props.postIds     Ids of selected posts.
 * @param {Object} props.customQuery Any custom query to be added.
 * @param {Object} props.taxQuery    Selected taxonomies.
 * @param {string} props.postType    Post type.
 *
 * @return {Object} Fetched Posts
 */
const useFetchPosts = ( props ) => {
	const {
		postIds,
		postsPerPage,
		customQuery = {},
		postType = 'post',
		taxQuery = {},
		taxRelation,
		catOperator,
		tagOperator,
		withTaxRelation,
		currentPostId,
		excludeCurrentPost,
	} = props;

	const postsRef = useRef( [] );
	const postIdsRef = useRef( [] );

	/**
	 * Fetch posts
	 */
	const posts = useSelect(
		( select ) => {
			/**
			 * Resize PostsRef,postIdsRef, if postsPerPage is decreased.
			 */
			if (
				postIds.length &&
				postIdsRef.current &&
				postsPerPage < postIdsRef.current.length &&
				postsRef.current !== null
			) {
				postsRef.current = postsRef.current.slice( 0, postsPerPage );
				postIdsRef.current = postsRef.current.map(
					( post ) => post.id
				);
				return postsRef.current;
			}

			/* eslint @wordpress/no-unused-vars-before-return: 0 */
			const { getEntityRecords, getTaxonomies } = select( coreStore );

			const taxonomies = getTaxonomies( {
				per_page: -1,
				context: 'view',
			} );

			const excludeArgs =
				excludeCurrentPost && currentPostId
					? { exclude: [ currentPostId ] }
					: {};

			let query = { ...excludeArgs };

			if ( Object.keys( customQuery ).length > 0 ) {
				query = {
					...customQuery,
					...excludeArgs,
				};
			}

			if ( postIds.length ) {
				query.include = postIds;
				query.orderby = 'include';
			}

			const tempTaxQuery = {};

			if ( Object.keys( taxQuery ).length ) {
				if ( ! postIds.length && taxQuery ) {
					// We have to build the tax query for the REST API and use as
					// keys the taxonomies `rest_base` with the `term ids` as values.
					const builtTaxQuery = Object.entries( taxQuery ).reduce(
						( accumulator, [ taxonomySlug, terms ] ) => {
							const taxonomy = taxonomies?.find(
								( { slug } ) => slug === taxonomySlug
							);

							if ( taxonomy?.rest_base ) {
								accumulator[ taxonomy?.rest_base ] = terms;
							}

							return accumulator;
						},
						{}
					);
					if ( !! Object.keys( builtTaxQuery ).length ) {
						Object.assign( tempTaxQuery, builtTaxQuery );
					}
				}
			}

			if (
				postIdsRef.current?.length &&
				isEqual( postIdsRef.current, postIds )
			) {
				return postsRef.current;
			}

			/**
			 * Check if new postIds array is just reordered, If reordered, reorder postsRef and return.
			 */
			let isReordered = true;
			if (
				postIdsRef.current?.length &&
				postIdsRef.current.length === postIds.length
			) {
				postIdsRef.current.forEach( ( id ) => {
					if ( ! postIds.includes( id ) ) {
						isReordered = false;
					}
				} );
			} else {
				isReordered = false;
			}

			if ( postIdsRef.current?.length && isReordered ) {
				let oldIndex, newIndex;
				postIdsRef.current.forEach( ( id, index ) => {
					if ( postIds.indexOf( id ) !== index ) {
						oldIndex = index;
						newIndex = postIds.indexOf( id );
					}
				} );
				postsRef.current = arrayMoveImmutable(
					postsRef.current,
					oldIndex,
					newIndex
				);
			} else if ( withTaxRelation ) {
				let catArgument = 'categories';
				let tagArgument = 'tags';
				if ( catOperator === 'NOT IN' ) {
					catArgument = 'categories_exclude';
				}
				if ( tagOperator === 'NOT IN' ) {
					tagArgument = 'categories_exclude';
				}

				postsRef.current = getEntityRecords( 'postType', postType, {
					...query,
					...( tempTaxQuery.categories?.length > 0 &&
						tempTaxQuery.tags?.length > 0 && {
							tax_relation: taxRelation,
						} ),
					...( tempTaxQuery.categories?.length > 0 && {
						[ catArgument ]: tempTaxQuery.categories,
					} ),
					...( tempTaxQuery.tags?.length > 0 && {
						[ tagArgument ]: tempTaxQuery.tags,
					} ),
				} );
			} else {
				if ( !! Object.keys( tempTaxQuery ).length ) {
					Object.assign( query, tempTaxQuery );
				}
				postsRef.current = getEntityRecords( 'postType', postType, {
					...query,
				} );
			}

			/**
			 * Store Post ids in ref to compare with passed postIds in subsequent calls
			 */
			if ( postsRef.current ) {
				postIdsRef.current = postsRef.current.map(
					( post ) => post.id
				);
			}

			return postsRef.current;
		},
		[
			taxQuery,
			postIds,
			customQuery,
			postsPerPage,
			postType,
			taxRelation,
			catOperator,
			tagOperator,
			withTaxRelation,
			currentPostId,
			excludeCurrentPost,
		]
	);

	return posts;
};

export default useFetchPosts;
