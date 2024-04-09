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
			.closest( '.pcrafts-block' )
			.querySelector( '.pcrafts-posts-wrapper' );

		if ( loadmore ) {
			jQuery( postsContainer ).append( newPosts );
		} else {
			jQuery( postsContainer ).html( newPosts );
		}
	}

	/**
	 * fetch posts
	 *
	 * @param {number}      page              Page number
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 */
	async fetchPosts( page, paginationWrapper ) {
		const { query, postId, blockId, template } = paginationWrapper.dataset;
		const data = {
			action: 'paginate_posts',
			_ajax_nonce: POSTCRAFTS.nonce,
			postId,
			blockId,
			paged: page,
			template,
			query: {
				...JSON.parse( query ),
				paged: page,
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
			paginationWrapper.setAttribute( 'data-page', page );
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
				const { postsPerPage, page } = paginationWrapper.dataset;

				const response = this.fetchPosts(
					parseInt( page ) + 1,
					paginationWrapper
				);

				response.then( ( res ) => {
					if ( res.success ) {
						this.updateMarkup( paginationWrapper, res.data, true );
					} else if (
						! res.success ||
						res.data.length < postsPerPage
					) {
						loadmoreBtn.classList.add( 'disabled' );
					}
				} );
			} );
		}
	}

	/**
	 * Arrow Pagination
	 *
	 * @param {HTMLElement} paginationWrapper
	 */
	handleArrow( paginationWrapper ) {
		const prevBtn = paginationWrapper.querySelector(
			'button.pcrafts-prev'
		);
		const nextBtn = paginationWrapper.querySelector(
			'button.pcrafts-next'
		);

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const { page } = paginationWrapper.dataset;

				const response = this.fetchPosts(
					parseInt( page ) - 1,
					paginationWrapper
				);

				response.then( ( res ) => {
					if ( res.success ) {
						this.updateMarkup( paginationWrapper, res.data, false );
						if ( parseInt( page ) - 1 === 1 ) {
							prevBtn.classList.add( 'disabled' );
						}

						if ( nextBtn.classList.contains( 'disabled' ) ) {
							nextBtn.classList.remove( 'disabled' );
						}
					}
				} );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const { page, maxPage } = paginationWrapper.dataset;

				const response = this.fetchPosts(
					parseInt( page ) + 1,
					paginationWrapper
				);

				response.then( ( res ) => {
					if ( res.success ) {
						this.updateMarkup( paginationWrapper, res.data, false );
						if ( parseInt( page ) + 1 >= maxPage ) {
							nextBtn.classList.add( 'disabled' );
						}

						if ( prevBtn.classList.contains( 'disabled' ) ) {
							prevBtn.classList.remove( 'disabled' );
						}
					}
				} );
			} );
		}
	}

	/**
	 * Toggle Element
	 *
	 * @param {HTMLElement} element Dom Element
	 * @param {boolean}     show    Flag
	 */
	toggleDisplay( element, show = true ) {
		if ( ! element ) {
			return;
		}

		if ( show ) {
			element.classList.remove( 'hide' );
		} else {
			element.classList.add( 'hide' );
		}
	}

	/**
	 * Update Pagination Pages
	 *
	 * @param {HTMLElement} paginationWrapper Wrapper element
	 * @param {number}      maxPage           Max Page
	 * @param {number}      currentPage       Current Page
	 */
	updatePages( paginationWrapper, maxPage, currentPage ) {
		const firstDots = paginationWrapper.querySelector( '.page-dots.first' );
		const lastDots = paginationWrapper.querySelector( '.page-dots.last' );
		const prevBtn = paginationWrapper.querySelector( '.page-numbers.prev' );
		const nextBtn = paginationWrapper.querySelector( '.page-numbers.next' );

		const firstPage = paginationWrapper.querySelector(
			'.page-numbers.first-page'
		);
		const lastPage = paginationWrapper.querySelector(
			'.page-numbers.last-page'
		);
		const currentActive = paginationWrapper.querySelector(
			'.page-numbers.current'
		);
		currentActive?.classList.remove( 'current' );

		let middlePages = [];

		if ( maxPage >= 3 ) {
			middlePages = [ 1, 2, 3 ];

			if ( currentPage >= 3 && currentPage === maxPage ) {
				middlePages = [ maxPage - 2, maxPage - 1, maxPage ];
			} else if ( currentPage >= 3 ) {
				middlePages = [ currentPage - 1, currentPage, currentPage + 1 ];
			}
		} else if ( maxPage === 2 ) {
			middlePages = [ 1, 2 ];
		}

		this.toggleDisplay( prevBtn, currentPage > 1 );
		this.toggleDisplay( firstDots, currentPage > 3 );
		this.toggleDisplay( firstPage, currentPage > 2 );
		this.toggleDisplay( lastDots, maxPage > currentPage + 2 );
		this.toggleDisplay( lastPage, maxPage > currentPage + 1 );
		this.toggleDisplay( nextBtn, maxPage !== currentPage );

		prevBtn.setAttribute( 'data-page', currentPage - 1 );
		nextBtn.setAttribute( 'data-page', currentPage + 1 );

		paginationWrapper
			.querySelectorAll( '.middle-pages' )
			.forEach( ( page, index ) => {
				page.innerHTML = middlePages[ index ];
				page.setAttribute( 'data-page', middlePages[ index ] );

				if ( middlePages[ index ] === currentPage ) {
					page.classList.add( 'current' );
				}
			} );
	}

	/**
	 * Handle Pagination
	 *
	 * @param {HTMLElement} paginationWrapper
	 */
	handlePagination( paginationWrapper ) {
		const pages = paginationWrapper.querySelectorAll( 'li.page-numbers' );
		const { maxPage } = paginationWrapper.dataset;

		pages.forEach( ( page ) => {
			page.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				const nextPage = parseInt( e.target.dataset.page );
				const response = this.fetchPosts( nextPage, paginationWrapper );

				response.then( ( res ) => {
					if ( res.success ) {
						this.updatePages(
							paginationWrapper,
							parseInt( maxPage ),
							nextPage
						);
						this.updateMarkup( paginationWrapper, res.data, false );
					}
				} );
			} );
		} );
	}

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
				this.handlePagination( paginationWrapper );
			}
		} );
	}
}

new Pagination();
