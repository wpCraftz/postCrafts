/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { useMemo, useEffect, useRef } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { dateI18n } from '@wordpress/date';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useFetchPosts, trimWords } from '../../libs';

import {
	QueryBuilder,
	ListSetttings,
	PaginationEdit,
	PaginationSettings,
	ExcerptSettings,
} from '../../components';
import StyleSettings from '../../components/style-settings';

import styleGenerator from '../../editor/style-generator';

/**
 * Module Constants
 */
const CATEGORIES_LIST_QUERY = {
	per_page: -1,
	context: 'view',
};
const AUTHORS_QUERY = {
	who: 'authors',
	per_page: -1,
	_fields: 'id,name,link',
	context: 'view',
};

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object} props               Block props.
 * @param {string} props.name          Block name.
 * @param {Object} props.attributes    Block's attributes.
 * @param {Object} props.setAttributes Function to set block's attributes.
 * @param {string} props.clientId      Block unique identifier.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {JSX} Element to render.
 */
export default function Edit( { name, attributes, setAttributes, clientId } ) {
	const {
		blockId,
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerpt: showExcerpt,
		excerptLength,
		pagination,
		paginationType,
		paginationAlignment,
		excludeCurrentPost,
	} = attributes;

	const customQuery = {
		per_page: postsPerPage,
		order: sorting.order,
		orderby: sorting.orderBy,
	};

	const dynamicStyleRef = useRef( null );
	/**
	 * Add blockId
	 */
	useEffect( () => {
		if ( clientId && ! blockId ) {
			setAttributes( {
				blockId: clientId.substring( 0, 8 ),
			} );
		}
	}, [ clientId, setAttributes, blockId ] );

	dynamicStyleRef.current = useMemo(
		() => styleGenerator( name, attributes ),
		[ name, attributes ]
	);

	/**
	 * Fetch or Reorder posts
	 */
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
		excludeCurrentPost,
		taxRelation,
		catOperator,
		tagOperator,
	} );

	/**
	 * Fetch authors
	 */
	const authors = useSelect( ( select ) => {
		const { getUsers } = select( coreStore );
		return getUsers( AUTHORS_QUERY );
	}, [] );

	/**
	 * Fetching taxonomies & tags
	 */
	const { categoriesList } = useSelect( ( select ) => {
		const { getEntityRecords } = select( coreStore );

		return {
			categoriesList: getEntityRecords(
				'taxonomy',
				'category',
				CATEGORIES_LIST_QUERY
			),
		};
	}, [] );

	/**
	 * Preparing data to render in backend
	 */
	const blockContexts = useMemo(
		() =>
			posts?.map( ( post ) => ( {
				postType: post.type,
				postId: post.id,
				postClass: post.post_class,
				status: post.status,
				postLink: post.link,
				title: post.title.rendered,
				// Untrimmed text from post_crafts_get_excerpt_source(), shared with the front end.
				excerpt: post.pcrafts_excerpt || '',
				// Same format as post_crafts_posted_on( 'F d, Y' ); post.date is in the site timezone.
				date: dateI18n( 'F d, Y', post.date ),
				featuredImgSrc: post.featured_image?.src,
				featuredImgAlt: post.featured_image?.alt,
				featuredImgWidth: post.featured_image?.width,
				featuredImgHeight: post.featured_image?.height,
				featuredImgClass: post.featured_image?.class,
				featuredImgSrcset: post.featured_image?.srcset,
				featuredImgSizes: post.featured_image?.sizes,
				featuredImgLoading: post.featured_image?.loading,
				featuredImgDecoding: post.featured_image?.decoding,
				// First category, like post_crafts_get_primary_category() without Yoast.
				category: categoriesList?.find(
					( { id } ) => id === post.categories?.[ 0 ]
				),
				author: authors?.find( ( author ) => author.id === post.author ),
			} ) ),
		[ posts, categoriesList, authors ]
	);

	const blockProps = useBlockProps( {
		className: classNames(
			'pcrafts-block',
			'pcrafts-postlist-wrapper',
			`pcrafts-block-${ blockId }`
		),
	} );

	/**
	 * Card preview. Mirrors inc/templates/block-templates/post-list.php, which is
	 * canonical: keep the element order and class names in sync with it.
	 *
	 * @param {Object} post Entry of blockContexts.
	 * @return {JSX} Card markup.
	 */
	const renderPost = ( post ) => {
		const {
			postId,
			featuredImgSrc,
			featuredImgAlt,
			featuredImgWidth,
			featuredImgHeight,
			featuredImgClass,
			featuredImgSrcset,
			featuredImgSizes,
			featuredImgLoading,
			featuredImgDecoding,
			title,
			category,
			excerpt,
			author,
			date,
			postLink,
		} = post;
		const decodedTitle = decodeEntities( title );
		const summary = showExcerpt
			? decodeEntities( trimWords( excerpt, excerptLength ) )
			: '';

		return (
			<article id={ `post-${ postId }` } className="post-list" key={ postId }>
				<figure className="post-thumbnail">
					<a
						href={ postLink }
						rel="bookmark"
						title={ decodedTitle }
						aria-label={ decodedTitle }
					>
						{ featuredImgSrc ? (
							<img
								src={ featuredImgSrc }
								alt={ featuredImgAlt }
								width={ featuredImgWidth }
								height={ featuredImgHeight }
								className={ featuredImgClass }
								srcSet={ featuredImgSrcset || undefined }
								sizes={ featuredImgSizes || undefined }
								loading={ featuredImgLoading }
								decoding={ featuredImgDecoding }
							/>
						) : (
							<span className="image-placeholder"></span>
						) }
					</a>
				</figure>
				<div className="post-list-content post-content">
					{ category?.name && (
						<span className="cat-links">
							<a href={ category.link } rel="category tag">
								{ category.name }
							</a>
						</span>
					) }
					<h2 className="entry-title">
						<a href={ postLink } rel="bookmark">
							{ decodedTitle }
						</a>
					</h2>
					<div className="entry-meta">
						<span className="byline">
							<span className="author vcard">
								<a className="url fn n" href={ author?.link }>
									{ author?.name }
								</a>
							</span>
						</span>
						<span className="separator">-</span>
						<span className="posted-on">{ date }</span>
					</div>
					{ summary && (
						<div className="entry-summary post-entry-summary">
							<p>{ summary }</p>
						</div>
					) }
				</div>
			</article>
		);
	};

	let preview;

	if ( ! posts ) {
		// Only the preview waits for posts; the inspector stays mounted so panels keep their state.
		preview = <Spinner />;
	} else if ( ! posts.length ) {
		preview = <p>{ __( 'No results found.', 'post-crafts' ) }</p>;
	} else {
		preview = (
			<>
				<div className="pcrafts-list-items-wrapper">
					{ blockContexts.map( renderPost ) }
				</div>
				{ pagination && (
					<PaginationEdit
						type={ paginationType }
						alignment={ paginationAlignment }
					/>
				) }
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<ListSetttings
					initialOpen
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<QueryBuilder
					enableRelation
					attributes={ attributes }
					setAttributes={ setAttributes }
					maxNumberOfPost={ 40 }
					sorting={ sorting }
					label={ __( 'Query Builder', 'post-crafts' ) }
					postMeta={
						!! blockContexts
							? blockContexts.map( ( post ) => ( {
									title: post.title,
									id: post.postId,
									status: post.status,
							  } ) )
							: []
					}
				/>
				<PaginationSettings
					clientId={ clientId }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<ExcerptSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<InspectorControls group="styles">
				<StyleSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<style>{ dynamicStyleRef.current }</style>
			<div { ...blockProps }>{ preview }</div>
		</>
	);
}
