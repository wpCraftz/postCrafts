/**
 * WordPress dependencies
 */
import { useRef, useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

const useSave = () => {
	const [ isPostSaved, setIsPostSaved ] = useState( false );
	const isPostSavingInProgress = useRef( false );
	const { isSavingPost, isAutosavingPost, isPublishingPost } = useSelect(
		( __select ) => {
			return {
				isSavingPost: __select( 'core/editor' ).isSavingPost(),
				isAutosavingPost: __select( 'core/editor' ).isAutosavingPost(),
				isPublishingPost: __select( 'core/editor' ).isPublishingPost(),
			};
		}
	);

	useEffect( () => {
		if (
			( isSavingPost || isAutosavingPost || isPublishingPost ) &&
			! isPostSavingInProgress.current
		) {
			setIsPostSaved( false );
			isPostSavingInProgress.current = true;
		}
		if (
			! ( isSavingPost || isAutosavingPost || isPublishingPost ) &&
			isPostSavingInProgress.current
		) {
			setIsPostSaved( true );
			isPostSavingInProgress.current = false;
		}
	}, [ isSavingPost, isAutosavingPost, isPublishingPost ] );

	return isPostSaved;
};

export default useSave;
