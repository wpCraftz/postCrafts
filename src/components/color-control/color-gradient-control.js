/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { ColorPalette, GradientPicker } from '@wordpress/components';

/**
 * External dependencies
 */
import classnames from 'classnames';

const TAB_IDS = { color: 'color', gradient: 'gradient' };
const TABS = [
	{
		id: 'color',
		label: 'Solid',
	},
	{
		id: 'gradient',
		label: 'Gradient',
	},
];

const ColorGradientControl = ( {
	colors,
	gradients,
	className,
	disableCustomColors,
	disableCustomGradients,
	onColorChange,
	onGradientChange,
	colorValue,
	gradientValue,
} ) => {
	const canChooseAColor =
		onColorChange &&
		( ( colors && colors.length > 0 ) || ! disableCustomColors );

	const canChooseAGradient =
		onGradientChange &&
		( ( gradients && gradients.length > 0 ) || ! disableCustomGradients );

	const [ activeTab, setActiveTab ] = useState(
		gradientValue ? TAB_IDS.gradient : !! canChooseAColor && TAB_IDS.color
	);

	if ( ! canChooseAColor && ! canChooseAGradient ) {
		return null;
	}

	const tabPanels = {
		[ TAB_IDS.color ]: (
			<ColorPalette
				value={ colorValue }
				onChange={
					canChooseAGradient
						? ( newColor ) => {
								onColorChange( newColor );
								onGradientChange();
						  }
						: onColorChange
				}
				{ ...{ colors, disableCustomColors } }
				__experimentalIsRenderedInSidebar
				clearable
				enableAlpha
			/>
		),
		[ TAB_IDS.gradient ]: (
			<GradientPicker
				value={ gradientValue }
				onChange={
					canChooseAColor
						? ( newGradient ) => {
								onGradientChange( newGradient );
								onColorChange();
						  }
						: onGradientChange
				}
				{ ...{ gradients, disableCustomGradients } }
				__experimentalIsRenderedInSidebar
				clearable
			/>
		),
	};

	const renderPanelType = ( type ) => (
		<div className="block-editor-color-gradient-control__panel">
			{ tabPanels[ type ] }
		</div>
	);

	return (
		<div className={ classnames( 'popover-inner-content', className ) }>
			{ canChooseAColor && canChooseAGradient && (
				<div role="tablist" className="tabs">
					{ TABS.map( ( { id, label } ) => (
						<button
							className={ classnames( 'tab-title', [
								{ active: id === activeTab },
							] ) }
							key={ id }
							onClick={ () => setActiveTab( id ) }
						>
							{ label }
						</button>
					) ) }
					{ renderPanelType(
						activeTab === 'gradient' ? 'gradient' : 'color'
					) }
				</div>
			) }

			{ ! canChooseAGradient && renderPanelType( TAB_IDS.color ) }
			{ ! canChooseAColor && renderPanelType( TAB_IDS.gradient ) }
		</div>
	);
};

export default ColorGradientControl;
