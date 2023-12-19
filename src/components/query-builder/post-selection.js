/**
 * Post Selection Component
 * (will fetch posts from last 3 years, if searched then will ignore the time limit).
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { ExternalLink, Button } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { date, dateI18n } from '@wordpress/date';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * External dependencies
 */
import debounce from 'lodash.debounce';
import times from 'lodash.times';
import classNames from 'classnames';

/**
 * Class to post selection.
 *
 * @param {Object}   props
 * @param {Function} props.onPostSelect On post select function.
 * @param {Array}    props.postIds      Selected Post ids.
 * @param {number}   props.limit        Max number of posts to be selectd.
 * @param {number}   props.queryTime    Duration of query in months.
 * @param {string}   props.postType     Slug of the Post Type.
 */
const PostSelector = ( {
	onPostSelect,
	postIds,
	limit,
	queryTime,
	postType,
} ) => {
	const [ fetchedPosts, setFetchedPosts ] = useState( [] );
	const [ searchedQuery, setSearchedQuery ] = useState( '' );
	const [ isFetchingData, setIsFetchingData ] = useState( true );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 0 );

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const debouncedSearchPosts = useCallback(
		debounce( ( searchQuery, currPage ) => {
			let after = new Date( dateI18n( 'Y-m-d\\TH:i:s' ) );
			after.setMonth( after.getMonth() - queryTime );

			after = date( 'Y-m-d\\TH:i:s', after );

			const REST_BASE = postType
				? `/wp/v2/${ postType }`
				: '/wp/v2/posts';

			const query = {
				status: 'publish',
				search: searchQuery,
				per_page: 30,
				page: currPage,
				...( queryTime && {
					after,
				} ),
				_fields: 'id,link,title,status,excerpt,featured_media',
			};

			const path = addQueryArgs( REST_BASE, query );

			try {
				setIsFetchingData( true );

				apiFetch( {
					path,
					parse: false,
				} )
					.then( ( data ) => {
						setTotalPages(
							Number( data.headers.get( 'X-WP-TotalPages' ) )
						);

						return data.json();
					} )
					.then( ( data ) => {
						setFetchedPosts( data );
						setIsFetchingData( false );
					} );
			} catch ( err ) {
				setIsFetchingData( false );
			}
		}, 300 ),
		[ searchedQuery, currentPage ]
	);

	useEffect( () => {
		debouncedSearchPosts( searchedQuery, currentPage );

		return debouncedSearchPosts.cancel;
	}, [ searchedQuery, debouncedSearchPosts, currentPage ] );

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

	return (
		<div className="post-selector">
			<label
				className="post-selector__search-field-label"
				htmlFor="post-selector-search-field"
			>
				{ __( 'Look for a Post', 'pc-blocks' ) }
			</label>
			<input
				type="text"
				id="post-selector-search-field"
				className="post-selector__search-field"
				value={ searchedQuery }
				onChange={ ( e ) => {
					setSearchedQuery( e.target.value );
					setCurrentPage( 1 );
				} }
			/>
			{ limit && (
				<div className="post-selection-info">
					{
						/* eslint @wordpress/i18n-no-variables:0, @wordpress/i18n-no-collapsible-whitespace:0 */
						postIds.length !== limit ? (
							<span>
								{ postIds.length }
								{ __( ' items are selected', 'pc-blocks' ) }
							</span>
						) : (
							<span>
								{ __(
									`Max ${ limit } posts are already selected`,
									'pc-blocks'
								) }
							</span>
						)
					}

					<Button
						variant="primary"
						disabled={ postIds.length === 0 }
						onClick={ () => onPostSelect( {}, true ) }
					>
						{ __( 'Insert Selected', 'pc-blocks' ) }
					</Button>
				</div>
			) }
			{ limit && (
				<div className="post-navigation">
					<Button
						className="pagination-button"
						variant="secondary"
						icon="arrow-left-alt2"
						iconPosition="left"
						disabled={ currentPage === 1 }
						onClick={ () => setCurrentPage( currentPage - 1 ) }
					></Button>

					<span> { currentPage } </span>

					<Button
						className="pagination-button"
						variant="secondary"
						icon="arrow-right-alt2"
						iconPosition="right"
						disabled={ currentPage === totalPages }
						onClick={ () => setCurrentPage( currentPage + 1 ) }
					></Button>
				</div>
			) }

			<ul className="post-selector__items-wrapper items-list">
				<li className="items-list--item item item--headers">
					<span className="item__post-id">
						{ __( 'Post ID', 'pc-blocks' ) }
					</span>
					<span className="item__post-title">
						{ __( 'Post Title', 'pc-blocks' ) }
					</span>
					<span className="item__post-status">
						{ __( 'Post Status', 'pc-blocks' ) }
					</span>
					<span className="item__post-link">
						{ __( 'Post Link', 'pc-blocks' ) }
					</span>
				</li>

				{ isFetchingData &&
					times( 20, () => (
						<li className="items-list--item item--placeholder">
							<span></span>
							<span></span>
							<span></span>
							<span></span>
						</li>
					) ) }

				{ ! isFetchingData &&
					fetchedPosts &&
					fetchedPosts.length > 0 &&
					fetchedPosts.map( ( post ) => {
						return (
							/* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-noninteractive-element-interactions */
							<li
								key={ post.id }
								className={ classNames(
									'items-list--item item',
									{
										'is-selected': postIds.includes(
											post.id
										),
									},
									{ disabled: postIds.length === limit }
								) }
								onClick={ () => {
									onPostSelect( post );
								} }
							>
								<span className="item__post-id">
									{ post.id }
								</span>
								<span className="item__post-title">
									{ decodeEntities( post.title.rendered ) ||
										__( '(No Title)', 'pc-blocks' ) }
								</span>
								<span className="item__post-status">
									{ getPresentablePostStatus( post.status ) }
								</span>
								<ExternalLink
									href={ post.link }
									className="item__post-link"
								></ExternalLink>
							</li>
						);
					} ) }

				{ ! isFetchingData &&
					fetchedPosts &&
					fetchedPosts.length === 0 && (
						<p>{ __( 'No Post Found', 'pc-blocks' ) }</p>
					) }
			</ul>
		</div>
	);
};

export default PostSelector;
