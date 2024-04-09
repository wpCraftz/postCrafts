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

		const style = parseStyle();
		updateStyle( getCurrentPostId(), 'all', style, isPreviewingPost() );
		timeStamp = Date.now();
	}
} );
