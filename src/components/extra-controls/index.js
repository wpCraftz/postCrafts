/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { Icon } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { useDevice } from '../../hooks';
import UnitPicker from './unit-picker';

/**
 * External dependencies
 */
import classNames from 'classnames';

const DEVICES = [
	{ device: 'Desktop', icon: 'desktop' },
	{ device: 'Tablet', icon: 'tablet' },
	{ device: 'Mobile', icon: 'smartphone' },
];

const ExtraControls = ( {
	isResponsive,
	className,
	units,
	activeUnit,
	onUnitChange,
} ) => {
	const device = useDevice();
	const { setDeviceType } = useDispatch( editorStore );
	const classes = classNames( [ 'pcrafts-field-extra-controls', className ] );
	return (
		<div className={ classes }>
			<div className="pcrafts-devices">
				{ isResponsive &&
					DEVICES.map( ( { device: deviceName, icon } ) => (
						<button
							key={ deviceName }
							className={ classNames( 'pcrafts-device-btn', {
								'is-active': deviceName === device,
							} ) }
							onClick={ () => {
								setDeviceType( deviceName );
							} }
						>
							<Icon icon={ icon } />
						</button>
					) ) }
			</div>
			{ units && units.length > 1 && (
				<UnitPicker
					units={ units }
					activeUnit={ activeUnit }
					onChange={ onUnitChange }
				/>
			) }
		</div>
	);
};

export default ExtraControls;
