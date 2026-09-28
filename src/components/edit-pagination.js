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
				<button type="button" className="pcrafts-loadmore-btn">
					{ __( 'Load More', 'post-crafts' ) }
				</button>
			</div>
		);
	}

	if ( type === 'arrow' ) {
		return (
			<div className={ classes }>
				<button
					type="button"
					className="pcrafts-prev disabled"
					aria-label={ __( 'Previous page', 'post-crafts' ) }
					disabled
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="24"
						height="24"
						aria-hidden="true"
						focusable="false"
					>
						<path d="M15.293 3.293 6.586 12l8.707 8.707 1.414-1.414L9.414 12l7.293-7.293-1.414-1.414z" />
					</svg>
				</button>
				<button
					type="button"
					className="pcrafts-next"
					aria-label={ __( 'Next page', 'post-crafts' ) }
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="24"
						height="24"
						aria-hidden="true"
						focusable="false"
					>
						<path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" />
					</svg>
				</button>
			</div>
		);
	}

	return (
		<div
			className={ classes }
			role="navigation"
			aria-label={ __( 'Posts pagination', 'post-crafts' ) }
		>
			<ul className="pcrafts-pages">
				{ [ 1, 2, 3 ].map( ( page ) => (
					<li
						key={ page }
						className={ classNames( 'page-numbers middle-pages', {
							current: page === 1,
						} ) }
						data-page={ page }
					>
						<button
							type="button"
							className="pcrafts-page-btn"
							aria-current={ page === 1 ? 'page' : undefined }
						>
							{ page }
						</button>
					</li>
				) ) }
				<li className="next page-numbers" data-page={ 2 }>
					<button
						type="button"
						className="pcrafts-page-btn"
						aria-label={ __( 'Next page', 'post-crafts' ) }
					>
						{ __( 'Next', 'post-crafts' ) }
					</button>
				</li>
			</ul>
		</div>
	);
};

export default Pagination;
