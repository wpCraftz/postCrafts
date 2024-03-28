const Pagination = ( { type } ) => {
	return (
		<div className="pcrafts-pagination">
			{ type === 'loadmore' && (
				<button className="pcrafts-loadmore">Load More</button>
			) }
			{ type === 'arrow' && (
				<button className="pcrafts-loadmore">Arrow</button>
			) }
			{ type === 'pagination' && (
				<button className="pcrafts-loadmore">Pagination</button>
			) }
		</div>
	);
};

export default Pagination;
