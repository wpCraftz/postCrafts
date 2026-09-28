/* eslint-disable no-undef */

/**
 * Pagination related scripts.
 *
 * Each `.pcrafts-pagination` wrapper handles its own clicks through event
 * delegation. A wrapper runs one request at a time: clicks made while a request
 * is in flight are ignored, so a double click can't load the same page twice.
 */
class Pagination {
	constructor() {
		this.paginationWrappers = document.querySelectorAll(
			'.pcrafts-pagination'
		);
		this.busyWrappers = new WeakSet();
		this.init();
	}

	/**
	 * Get the posts container of a pagination wrapper.
	 *
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 *
	 * @return {HTMLElement|null} Posts container.
	 */
	getPostsContainer( paginationWrapper ) {
		const block = paginationWrapper.closest( '.pcrafts-block' );

		return block ? block.querySelector( '.pcrafts-posts-wrapper' ) : null;
	}

	/**
	 * Update markup
	 *
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 * @param {string}      newPosts          New posts markup
	 * @param {boolean}     loadmore          Loadmore flag
	 */
	updateMarkup( paginationWrapper, newPosts, loadmore = false ) {
		const postsContainer = this.getPostsContainer( paginationWrapper );

		if ( loadmore ) {
			jQuery( postsContainer ).append( newPosts );
		} else {
			jQuery( postsContainer ).html( newPosts );
		}
	}

	/**
	 * Toggle the loading state of a wrapper and its posts container.
	 *
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 * @param {boolean}     loading           Loading flag
	 */
	setLoading( paginationWrapper, loading ) {
		const postsContainer = this.getPostsContainer( paginationWrapper );

		if ( loading ) {
			this.busyWrappers.add( paginationWrapper );
		} else {
			this.busyWrappers.delete( paginationWrapper );
		}

		paginationWrapper.classList.toggle( 'is-loading', loading );

		if ( postsContainer ) {
			postsContainer.classList.toggle( 'is-loading', loading );
			if ( loading ) {
				postsContainer.setAttribute( 'aria-busy', 'true' );
			} else {
				postsContainer.removeAttribute( 'aria-busy' );
			}
		}
	}

	/**
	 * Fetch a page, ignoring the call while the wrapper is already loading.
	 *
	 * @param {number}      page              Page number
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 *
	 * @return {Promise<Object|null>} AJAX response, or null when skipped or failed.
	 */
	async request( page, paginationWrapper ) {
		if ( this.busyWrappers.has( paginationWrapper ) ) {
			return null;
		}

		this.setLoading( paginationWrapper, true );

		try {
			return await this.fetchPosts( page, paginationWrapper );
		} catch ( error ) {
			return null;
		} finally {
			this.setLoading( paginationWrapper, false );
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

		const response = await jQuery.post( POSTCRAFTS.urls.ajaxUrl, data );

		if ( response && response.success ) {
			paginationWrapper.setAttribute( 'data-page', page );
		}

		return response;
	}

	/**
	 * Bring replaced posts into view and move focus to them.
	 *
	 * Scrolls only when the block top is above the viewport, so a short block
	 * that is already visible doesn't jump.
	 *
	 * @param {HTMLElement} paginationWrapper Pagination wrapper
	 */
	revealPosts( paginationWrapper ) {
		const block = paginationWrapper.closest( '.pcrafts-block' );
		const postsContainer = this.getPostsContainer( paginationWrapper );

		if ( postsContainer ) {
			if ( ! postsContainer.hasAttribute( 'tabindex' ) ) {
				postsContainer.setAttribute( 'tabindex', '-1' );
			}
			postsContainer.focus( { preventScroll: true } );
		}

		if ( block && block.getBoundingClientRect().top < 0 ) {
			const reduceMotion =
				window.matchMedia &&
				window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			block.scrollIntoView( {
				behavior: reduceMotion ? 'auto' : 'smooth',
				block: 'start',
			} );
		}
	}

	/**
	 * Enable or disable a button with both the attribute and the legacy class.
	 *
	 * @param {HTMLElement|null} button   Button element
	 * @param {boolean}          disabled Flag
	 */
	setDisabled( button, disabled ) {
		if ( ! button ) {
			return;
		}

		button.disabled = disabled;
		button.classList.toggle( 'disabled', disabled );
	}

	/**
	 * Load more pagination
	 *
	 * @param {HTMLElement} paginationWrapper
	 */
	handleLoadMore( paginationWrapper ) {
		paginationWrapper.addEventListener( 'click', ( e ) => {
			const loadmoreBtn = e.target.closest( '.pcrafts-loadmore-btn' );

			if ( ! loadmoreBtn || ! paginationWrapper.contains( loadmoreBtn ) ) {
				return;
			}

			e.preventDefault();

			if (
				loadmoreBtn.disabled ||
				loadmoreBtn.classList.contains( 'disabled' )
			) {
				return;
			}

			const { postsPerPage, maxPage } = paginationWrapper.dataset;
			const nextPage = parseInt( paginationWrapper.dataset.page, 10 ) + 1;
			const postsContainer = this.getPostsContainer( paginationWrapper );
			const countBefore = postsContainer
				? postsContainer.children.length
				: 0;

			this.request( nextPage, paginationWrapper ).then( ( res ) => {
				if ( ! res ) {
					return;
				}

				if ( ! res.success ) {
					this.setDisabled( loadmoreBtn, true );
					return;
				}

				this.updateMarkup( paginationWrapper, res.data, true );

				const max = parseInt( maxPage, 10 );
				const isLastPage =
					( ! isNaN( max ) && nextPage >= max ) ||
					( Array.isArray( res.data ) &&
						res.data.length < parseInt( postsPerPage, 10 ) );

				if ( isLastPage ) {
					this.setDisabled( loadmoreBtn, true );

					// The focused button just became disabled; hand focus to the first new post.
					const firstNew = postsContainer
						? postsContainer.children[ countBefore ]
						: null;
					if ( firstNew ) {
						if ( ! firstNew.hasAttribute( 'tabindex' ) ) {
							firstNew.setAttribute( 'tabindex', '-1' );
						}
						firstNew.focus( { preventScroll: true } );
					}
				}
			} );
		} );
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

		paginationWrapper.addEventListener( 'click', ( e ) => {
			const button = e.target.closest( 'button' );

			if ( ! button || ( button !== prevBtn && button !== nextBtn ) ) {
				return;
			}

			e.preventDefault();

			if ( button.disabled || button.classList.contains( 'disabled' ) ) {
				return;
			}

			const page = parseInt( paginationWrapper.dataset.page, 10 );
			const maxPage = parseInt( paginationWrapper.dataset.maxPage, 10 );
			const nextPage = button === prevBtn ? page - 1 : page + 1;

			this.request( nextPage, paginationWrapper ).then( ( res ) => {
				if ( ! res || ! res.success ) {
					return;
				}

				this.updateMarkup( paginationWrapper, res.data, false );
				this.setDisabled( prevBtn, nextPage <= 1 );
				this.setDisabled( nextBtn, nextPage >= maxPage );
				this.revealPosts( paginationWrapper );
			} );
		} );
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
	 * Set a page item's number, keeping the label inside its button.
	 *
	 * @param {HTMLElement} item Page item (li)
	 * @param {number}      page Page number
	 */
	setPageNumber( item, page ) {
		const target = item.querySelector( 'button' ) || item;

		target.textContent = page;
		item.setAttribute( 'data-page', page );
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

		paginationWrapper
			.querySelectorAll( '.page-numbers.current' )
			.forEach( ( item ) => item.classList.remove( 'current' ) );
		paginationWrapper
			.querySelectorAll( '[aria-current]' )
			.forEach( ( item ) => item.removeAttribute( 'aria-current' ) );

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

		prevBtn?.setAttribute( 'data-page', currentPage - 1 );
		nextBtn?.setAttribute( 'data-page', currentPage + 1 );

		paginationWrapper
			.querySelectorAll( '.middle-pages' )
			.forEach( ( page, index ) => {
				this.setPageNumber( page, middlePages[ index ] );

				if ( middlePages[ index ] === currentPage ) {
					page.classList.add( 'current' );
					( page.querySelector( 'button' ) || page ).setAttribute(
						'aria-current',
						'page'
					);
				}
			} );
	}

	/**
	 * Handle Pagination
	 *
	 * @param {HTMLElement} paginationWrapper
	 */
	handlePagination( paginationWrapper ) {
		paginationWrapper.addEventListener( 'click', ( e ) => {
			const item = e.target.closest( 'li.page-numbers' );

			if ( ! item || ! paginationWrapper.contains( item ) ) {
				return;
			}

			e.preventDefault();

			if (
				item.classList.contains( 'current' ) ||
				item.classList.contains( 'hide' )
			) {
				return;
			}

			const nextPage = parseInt( item.dataset.page, 10 );
			const maxPage = parseInt( paginationWrapper.dataset.maxPage, 10 );

			if ( isNaN( nextPage ) || nextPage < 1 || nextPage > maxPage ) {
				return;
			}

			this.request( nextPage, paginationWrapper ).then( ( res ) => {
				if ( ! res || ! res.success ) {
					return;
				}

				this.updatePages( paginationWrapper, maxPage, nextPage );
				this.updateMarkup( paginationWrapper, res.data, false );
				this.revealPosts( paginationWrapper );
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
