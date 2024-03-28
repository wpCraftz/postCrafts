/* eslint-disable no-undef */

/**
 * Pagination related scripts.
 */
class Pagination {
	constructor() {
		this.paginationWrappers = document.querySelectorAll(
			'.pcrafts-pagination'
		);
		this.init();
	}

	/**
	 * Update markup
	 *
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 * @param {string}      newPosts          New posts markup
	 * @param {boolean}     loadmore          Loadmore flag
	 */
	updateMarkup( paginationWrapper, newPosts, loadmore = false ) {
		const postsContainer = paginationWrapper
			.closest( '.pcrafts-postgrid-wrapper' )
			.querySelector( '.pcrafts-grid-items-wrapper' );

		if ( loadmore ) {
			jQuery( postsContainer ).append( newPosts );
		}
	}

	/**
	 * fetch posts
	 *
	 * @param {Object}      query             Query string
	 * @param {number}      page              Page number
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 */
	async fetchPosts( query, page, paginationWrapper ) {
		const data = {
			action: 'paginate_posts',
			_ajax_nonce: POSTCRAFTS.nonce,
			query: {
				...JSON.parse( query ),
				paged: parseInt( page ) + 1,
			},
		};

		const response = await jQuery.post(
			POSTCRAFTS.urls.ajaxUrl,
			data,
			( res ) => {
				return res;
			}
		);

		if ( response.success ) {
			paginationWrapper.setAttribute( 'data-page', parseInt( page ) + 1 );
		}

		return response;
	}

	/**
	 * Load more pagination
	 *
	 * @param {HTMLElement} paginationWrapper
	 */
	handleLoadMore( paginationWrapper ) {
		const loadmoreBtn = paginationWrapper.querySelector(
			'.pcrafts-loadmore-btn'
		);

		if ( loadmoreBtn ) {
			loadmoreBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const { query = {}, page } = paginationWrapper.dataset;

				const response = this.fetchPosts(
					query,
					page,
					paginationWrapper
				);

				const { posts_per_page: postsPerPage } = JSON.parse( query );

				response.then( ( res ) => {
					this.updateMarkup( paginationWrapper, res.data, true );
					if ( ! res.success || res.data.length < postsPerPage ) {
						loadmoreBtn.classList.add( 'disabled' );
					}
				} );
			} );
		}
	}

	/**
	 * Arrow Pagination
	 */
	handleArrow() {}

	init() {
		if ( ! this.paginationWrappers ) {
			return;
		}

		this.paginationWrappers.forEach( ( paginationWrapper ) => {
			if ( paginationWrapper.classList.contains( 'pcrafts-loadmore' ) ) {
				this.handleLoadMore( paginationWrapper );
			} else if (
				paginationWrapper.classList.contains( 'pcrafts-arrow' )
			) {
				this.handleArrow( paginationWrapper );
			} else {
			}
		} );
	}
}

new Pagination();
