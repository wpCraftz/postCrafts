/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Update Block style
 *
 * @param {string}  postID       Post Id.
 * @param {string}  blockId      Block ID.
 * @param {string}  style        Block style.
 * @param {boolean} isPreviewing Post is previewing or not.
 */
const updateStyle = async ( postID, blockId, style, isPreviewing = false ) => {
	try {
		apiFetch( {
			path: `/post-crafts/v1/style`,
			method: 'POST',
			data: {
				post_id: postID,
				block_id: blockId,
				style,
				is_previewing: isPreviewing,
			},
		} ).then( ( res ) => {
			return res;
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error:', error );
	}
};

/**
 * Get Block style
 *
 * @param {string} postID Post Id.
 */
const getStyle = async ( postID ) => {
	const queryParams = { post_id: postID };
	try {
		apiFetch( {
			path: addQueryArgs( '/post-crafts/v1/style', queryParams ),
			method: 'GET',
		} ).then( ( res ) => {
			return res;
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error:', error );
	}
};

export { updateStyle, getStyle };
