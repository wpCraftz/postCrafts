/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Get the current device type
 *
 * @return {string} device
 */
const useDevice = () => {
	const [ device, setDevice ] = useState( 'Desktop' );
	const { deviceType } = useSelect( ( __select ) => {
		return {
			deviceType: __select( 'core/editor' ).getDeviceType(),
		};
	} );

	useEffect( () => {
		setDevice( deviceType );
	}, [ deviceType ] );

	return device;
};

export default useDevice;
