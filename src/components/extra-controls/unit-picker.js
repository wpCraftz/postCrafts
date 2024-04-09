/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * External dependencies
 */
import classNames from 'classnames';

const UnitPicker = ( { units, activeUnit, onChange } ) => {
	const [ isOpen, setIsOpen ] = useState( false );

	const handleMouseEnter = () => {
		setIsOpen( true );
	};
	const handleMouseLeave = () => {
		setIsOpen( false );
	};

	return (
		<div
			className={ classNames( [
				'pcrafts-unit-picker',
				{ 'is-open': isOpen },
			] ) }
			onMouseEnter={ () => handleMouseEnter() }
			onMouseLeave={ () => handleMouseLeave() }
		>
			<div className="unit active-unit">{ activeUnit }</div>
			{ isOpen && (
				<div className="pcrafts-units">
					{ units.map( ( unit ) => (
						<button
							className={ classNames( [
								'unit',
								{ 'is-active': unit === activeUnit },
							] ) }
							key={ unit }
							onClick={ () => onChange( unit ) }
						>
							{ unit }
						</button>
					) ) }
				</div>
			) }
		</div>
	);
};

export default UnitPicker;
