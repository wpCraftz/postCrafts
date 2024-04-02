/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */
import classNames from 'classnames';

const Pagination = ( { type, alignment } ) => {
	const classes = classNames(
		'pcrafts-pagination',
		{
			'pcrafts-loadmore': type === 'loadmore',
			'pcrafts-arrow': type === 'arrow',
			'pcrafts-numberic': type === 'pagination',
		},
		alignment
	);

	if ( type === 'loadmore' ) {
		return (
			<div className={ classes }>
				<button className="pcrafts-loadmore">
					{ __( 'Load More', 'post-crafts' ) }
				</button>
			</div>
		);
	}

	if ( type === 'arrow' ) {
		return (
			<div className={ classes }>
				<button className="pcrafts-prev disabled">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="24"
						height="24"
					>
						<path d="M15.293 3.293 6.586 12l8.707 8.707 1.414-1.414L9.414 12l7.293-7.293-1.414-1.414z" />
					</svg>
				</button>
				<button className="ppcrafts-next">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="24"
						height="24"
					>
						<path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" />
					</svg>
				</button>
			</div>
		);
	}

	return (
		<div className={ classes }>
			<ul className="pcrafts-pages">
				{ [ 1, 2, 3, 'Next' ].map( ( page ) => (
					<li
						key={ page }
						className={ classNames( 'page-numbers', {
							current: page === 1,
						} ) }
						data-page={ page }
					>
						{ page }
					</li>
				) ) }
			</ul>
		</div>
	);
};

export default Pagination;
