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
				const data = {
					action: 'paginate_posts',
					// eslint-disable-next-line no-undef
					_ajax_nonce: POSTCRAFTS.nonce,
				};
				// eslint-disable-next-line no-undef
				$ = jQuery;
				// eslint-disable-next-line no-undef
				$.post( POSTCRAFTS.urls.ajaxUrl, data, ( response ) => {
					const nextItems = response;
					const postsContainer = paginationWrapper
						.closest( '.pcrafts-postgrid-wrapper' )
						.querySelector( '.pcrafts-grid-items-wrapper' );

					// eslint-disable-next-line no-undef
					$( postsContainer ).append( nextItems );
				} );
			} );
		}
	}

	fetchNextItems() {
		return '<article class="pcrafts-grid-item">New Item</article><article class="pcrafts-grid-item">New Item2</article>';
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
