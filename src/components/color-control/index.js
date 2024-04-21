/**
 * WordPress dependencies
 */
import {
	BaseControl,
	ColorIndicator,
	Dropdown,
	Button,
	FlexItem,
	Flex,
} from '@wordpress/components';

/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import ColorGradientControl from './color-gradient-control';

const renderToggle =
	( settings ) =>
	( { onToggle, isOpen } ) => {
		const { colorValue, label } = settings;

		const toggleProps = {
			onClick: onToggle,
			className: classNames(
				'block-editor-panel-color-gradient-settings__dropdown',
				{ 'is-open': isOpen }
			),
			'aria-expanded': isOpen,
		};

		return (
			<Button { ...toggleProps }>
				<Flex justify="flex-start">
					<FlexItem>
						<ColorIndicator
							className="block-editor-panel-color-gradient-settings__color-indicator"
							colorValue={ colorValue }
						/>
					</FlexItem>
					<FlexItem
						className="block-editor-panel-color-gradient-settings__color-name"
						title={ label }
					>
						{ label }
					</FlexItem>
				</Flex>
			</Button>
		);
	};

const ColorControl = ( { settings, label, className } ) => {
	const getControlProps = ( {
		colors,
		gradients,
		colorValue,
		gradientValue,
		onColorChange,
		onGradientChange,
		disableCustomColors,
		disableCustomGradients,
		enableAlpha = true,
		...rest
	} ) => ( {
		clearable: true,
		colors,
		gradients,
		colorValue,
		gradientValue,
		disableCustomColors,
		disableCustomGradients,
		enableAlpha,
		onColorChange,
		onGradientChange,
		showTitle: false,
		...rest,
	} );

	const getToggleSettings = ( {
		gradientValue,
		colorValue,
		label: fieldLabel,
	} ) => ( {
		colorValue: gradientValue ?? colorValue,
		label: fieldLabel,
	} );

	const popoverProps = {
		placement: 'left-start',
		offset: 36,
		shift: true,
	};

	return (
		<BaseControl
			__nextHasNoMarginBottom
			className={ classNames( 'pcrafts-color-control', className ) }
		>
			<BaseControl.VisualLabel>{ label }</BaseControl.VisualLabel>
			{ settings.map( ( setting, index ) => {
				return (
					<div
						className={ classNames(
							'components-tools-panel-item block-editor-tools-panel-color-gradient-settings__item',
							[ { 'first-item': index === 0 } ]
						) }
						key={ index }
					>
						<Dropdown
							popoverProps={ popoverProps }
							contentClassName="pcrafts-color-control-drop-down"
							className="block-editor-tools-panel-color-gradient-settings__dropdown"
							renderToggle={ renderToggle(
								getToggleSettings( setting )
							) }
							renderContent={ () => (
								<div className="block-editor-panel-color-gradient-settings__dropdown-content">
									<ColorGradientControl
										{ ...getControlProps( setting ) }
									/>
								</div>
							) }
						/>
					</div>
				);
			} ) }
		</BaseControl>
	);
};

export default ColorControl;
