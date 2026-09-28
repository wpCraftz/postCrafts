/**
 * WordPress dependencies
 */
import { subscribe } from '@wordpress/data';

/**
 * Internal dependencies
 */
import parseStyle from './css-manager';
import { updateStyle } from '../libs';

let timeStamp;

subscribe( () => {
	const {
		isSavingPost,
		isAutosavingPost,
		isPreviewingPost,
		isPublishingPost,
		getCurrentPostId,
	} = wp.data.select( 'core/editor' );

	if (
		isPublishingPost() ||
		( isSavingPost() && ! isAutosavingPost() ) ||
		isPreviewingPost()
	) {
		if (
			typeof timeStamp !== 'undefined' &&
			Date.now() - timeStamp < 800
		) {
			return;
		}

		// Template IDs in the site editor aren't numeric, and the style
		// endpoint only stores CSS for posts.
		const postId = getCurrentPostId();
		if ( ! Number.isInteger( postId ) ) {
			return;
		}

		const style = parseStyle();
		updateStyle( postId, 'all', style, isPreviewingPost() );
		timeStamp = Date.now();
	}
} );
