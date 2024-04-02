/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { useMemo, useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { dateI18n } from '@wordpress/date';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useFetchPosts, getSubString } from '../../libs';
import {
	QueryBuilder,
	PaginationEdit,
	PaginationSettings,
	ExcerptSettings,
} from '../../components';

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
	_fields: 'id,name',
	context: 'view',
};

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object} props               Block props.
 * @param {Object} props.attributes    Block's attributes.
 * @param {Object} props.setAttributes Function to set block's attributes.
 * @param {string} props.clientId      Block unique identifier.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {JSX} Element to render.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		postsPerPage,
		postIds,
		taxQuery,
		taxRelation,
		catOperator,
		tagOperator,
		sorting,
		excerptLength,
		pagination,
		paginationType,
	} = attributes;

	const customQuery = {
		per_page: postsPerPage,
		order: sorting.order,
		orderby: sorting.orderBy,
	};

	/**
	 * Update blockId with clientId
	 */
	useEffect( () => {
		if ( clientId ) {
			setAttributes( {
				blockId: clientId,
			} );
		}
	}, [ clientId, setAttributes ] );

	/**
	 * Fetch or Reorder posts
	 */
	const posts = useFetchPosts( {
		postIds,
		customQuery,
		postsPerPage,
		taxQuery,
		withTaxRelation: true,
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
				excerpt: post.content.raw
					.replace( /<[^>]+>|[\n]/gi, ' ' )
					.replace( /\s+/g, ' ' ),
				date: dateI18n( 'F j, Y', post.date_gmt ),
				dateTime: dateI18n( 'Y-m-dTH:i:sP', post.date_gmt ),
				featuredImgSrc: post.featured_image?.src,
				featuredImgAlt: post.featured_image?.alt,
				featuredImgWidth: post.featured_image?.width,
				featuredImgHeight: post.featured_image?.height,
				featuredImgClass: post.featured_image?.class,
				featuredImgSrcset: post.featured_image?.srcset,
				featuredImgSizes: post.featured_image?.sizes,
				featuredImgLoading: post.featured_image?.loading,
				featuredImgDecoding: post.featured_image?.decoding,
				categories: post.categories.map( ( catId ) => {
					if (
						'undefined' === typeof categoriesList ||
						null === categoriesList
					) {
						return [];
					}

					return categoriesList.find( ( category ) => {
						if ( category.id === catId ) {
							return category;
						}

						return false;
					} );
				} ),
				author: authors
					? authors.find( ( author ) => author.id === post.author )
							?.name
					: [],
			} ) ),
		[ posts, categoriesList, authors ]
	);

	const blockProps = useBlockProps( {
		className: 'pcrafts-postlist-wrapper',
	} );

	if ( ! posts ) {
		return (
			<p { ...blockProps }>
				<Spinner />
			</p>
		);
	}

	return (
		<>
			<InspectorControls>
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

			{ ! posts?.length ? (
				<p { ...blockProps }>
					{ __( 'No results found.', 'post-crafts' ) }
				</p>
			) : (
				<div { ...blockProps }>
					<div className="pcrafts-list-items-wrapper">
						{ blockContexts.map( ( post ) => {
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
								categories,
								excerpt,
								author,
								date,
								postLink,
							} = post;

							return (
								<article
									id={ postId }
									className="post-list"
									key={ postId }
								>
									<figure className="post-thumbnail">
										{ featuredImgSrc ? (
											<img
												src={ featuredImgSrc }
												alt={ featuredImgAlt }
												width={ featuredImgWidth }
												height={ featuredImgHeight }
												className={ featuredImgClass }
												srcSet={ featuredImgSrcset }
												sizes={ featuredImgSizes }
												loading={ featuredImgLoading }
												decoding={ featuredImgDecoding }
											/>
										) : (
											<span className="image-placeholder"></span>
										) }
									</figure>

									<div className="post-list-content post-content">
										<span className="cat-links has-tiny-font-size text-bold">
											{ categories &&
												categories
													.filter(
														( _, index ) =>
															index === 0
													)
													.map(
														( { name, link } ) => (
															<span
																rel="category tag"
																key={ name }
																className="cat-links"
															>
																<a
																	href={
																		link
																	}
																>
																	{ name }
																</a>
															</span>
														)
													) }
										</span>
										<h2 className="entry-title">
											<a href={ postLink } rel="bookmark">
												{ decodeEntities( title ) }
											</a>
										</h2>
										{ excerpt && (
											<div className="post-entry-summary">
												{ getSubString(
													excerpt,
													excerptLength
												) }
											</div>
										) }
										<div className="entry-meta">
											<span className="byline">
												<span className="author vcard text-bold">
													{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
													<a
														className="url fn n"
														href="#"
													>
														{ author }
													</a>
												</span>
											</span>
											<span className="posted-on has-tiny-font-size">
												<span className="meta-separator">
													-
												</span>
												<span className="posted-on">
													{ date }
												</span>
											</span>
										</div>
									</div>
								</article>
							);
						} ) }
					</div>
					{ pagination && <PaginationEdit type={ paginationType } /> }
				</div>
			) }
		</>
	);
}
