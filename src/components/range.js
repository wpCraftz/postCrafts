/**
 * WordPress dependencies
 */
import { RangeControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { useDevice } from '../hooks';
import { deepClone } from '../libs';
import ExtraControls from './extra-controls';
import UnitPicker from '../components/extra-controls/unit-picker';

const Range = ( {
	label,
	value: initialValue,
	onChange,
	min = 1,
	max = 100,
	step = 1,
	className,
	units = [ 'px', 'em', '%' ],
	isResponsive = false,
} ) => {
	const device = useDevice();
	const deviceKey = device.toLowerCase();
	const [ value, setValue ] = useState( initialValue.value );
	const [ activeUnit, setActiveUnit ] = useState( initialValue.unit );

	useEffect( () => {
		if ( isResponsive ) {
			if ( device && device !== 'Desktop' ) {
				const responsiveValue = initialValue[ deviceKey ] ?? {};
				setValue( responsiveValue.value );
				setActiveUnit( responsiveValue.unit ?? 'px' );
			} else {
				setValue( initialValue.value );
				setActiveUnit( initialValue.unit );
			}
		}
	}, [ deviceKey, device, isResponsive, initialValue ] );

	const updateAttribute = ( newValue, type = 'value' ) => {
		if ( isResponsive ) {
			const newValues = deepClone( initialValue );

			if ( device && device !== 'Desktop' ) {
				newValues[ deviceKey ] = {
					...( initialValue[ deviceKey ]
						? initialValue[ deviceKey ]
						: {} ),
					[ type ]: newValue,
				};
			} else {
				newValues[ type ] = newValue;
			}
			onChange( newValues );
		} else {
			onChange( newValue );
		}

		if ( type === 'unit' ) {
			setActiveUnit( newValue );
		}
	};

	const classes = classNames( [
		'pcrafts-range',
		'pcrafts-field',
		className,
		{ 'responsive-field': isResponsive },
	] );

	return (
		<div className={ classes }>
			{ ( isResponsive || units.length > 1 ) && (
				<ExtraControls
					isResponsive={ isResponsive }
					{ ...( units.length > 1 && {
						units: false,
						activeUnit,
						onUnitChange: ( newValue ) =>
							updateAttribute( newValue, 'unit' ),
					} ) }
				/>
			) }

			<div className="pcrafts-range-input">
				<RangeControl
					min={ min }
					max={ max }
					step={ step }
					value={ value }
					className={ classNames( [
						'pcrafts-range',
						{
							'with-extra-controls':
								isResponsive || units.length > 1,
						},
					] ) }
					label={ label }
					onChange={ ( newValue ) => {
						updateAttribute( newValue );
					} }
				/>
				{ !! units && (
					<UnitPicker
						units={ units }
						activeUnit={ activeUnit }
						onChange={ ( newValue ) => {
							updateAttribute( newValue, 'unit' );
						} }
					/>
				) }
			</div>
		</div>
	);
};
export default Range;
