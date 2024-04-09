/**
 * WordPress dependencies
 */
import { useRef, useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

const usePreview = () => {
	const [ isPostPreviewing, setIsPostPreviewing ] = useState( false );
	const isPostPreviewingnProgress = useRef( false );
	const { isPreviewingPost } = useSelect( ( __select ) => {
		return {
			isPreviewingPost: __select( 'core/editor' ).isPreviewingPost(),
		};
	} );

	useEffect( () => {
		if ( isPreviewingPost && ! isPostPreviewingnProgress.current ) {
			setIsPostPreviewing( false );
			isPostPreviewingnProgress.current = true;
		}
		if ( ! isPreviewingPost && isPostPreviewingnProgress.current ) {
			setIsPostPreviewing( true );
			isPostPreviewingnProgress.current = false;
		}
	}, [ isPreviewingPost ] );

	return isPostPreviewing;
};

export default usePreview;
