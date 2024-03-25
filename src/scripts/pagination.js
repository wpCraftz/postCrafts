/**
 * Pagination related scripts.
 */
class Pagination {
	constructor() {
		this.paginations = document.querySelectorAll( '.pcrafts-pagination' );
		this.init();
	}

	/**
	 * Load more pagination
	 */
	handleLoadMore() {}
	/**
	 * Arrow Pagination
	 */
	handleArrow() {}

	init() {
		if ( ! this.paginations ) {
			return;
		}

		this.paginations.forEach( ( pagination ) => {
			if ( pagination.classList.contains( 'pcrafts-loadmore' ) ) {
				this.handleLoadMore();
			} else if ( pagination.classList.contains( 'pcrafts-arrow' ) ) {
				this.handleArrow();
			} else {
			}
		} );
	}
}

new Pagination();
