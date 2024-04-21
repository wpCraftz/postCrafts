/**
 * Internal dependencies
 */
import * as helpers from './helpers';
import * as dynamicAttributes from '../dynamic-attributes';

const mappedBlocks = {
	'post-crafts/post-grid': 'postGrid',
	'post-crafts/post-list': 'postList',
};

const styleGenerator = ( blockName, attributes ) => {
	const style = [];
	const tabletStyle = [];
	const mobileStyle = [];

	const styleAttributes =
		dynamicAttributes[ mappedBlocks[ blockName ] ]( attributes );

	Object.keys( styleAttributes ).forEach( ( attribute ) => {
		const {
			function: func,
			selector,
			responsive = false,
			condition = true,
		} = styleAttributes[ attribute ];

		const value = attributes[ attribute ];

		if ( ! value || ! condition ) {
			return;
		}

		const { value: desktop, unit: desktopUnit, tablet, mobile } = value;
		let currentStyle;

		if ( responsive ) {
			if ( tablet ) {
				tabletStyle.push(
					helpers[ func ]( selector, value.tablet, desktopUnit )
				);
			}
			if ( mobile ) {
				mobileStyle.push(
					helpers[ func ]( selector, value.mobile, desktopUnit )
				);
			}

			currentStyle = helpers[ func ]( selector, {
				value: desktop,
				unit: desktopUnit,
			} );
		} else {
			currentStyle = helpers[ func ]( selector, value );
		}

		if ( currentStyle ) {
			style.push( currentStyle );
		}
	} );

	let dynamicStyle = style.join( ' ' ).trim();

	if ( tabletStyle.length > 0 ) {
		dynamicStyle += `@media (max-width: 991px) { ${ tabletStyle.join(
			' '
		) } }`.trim();
	}

	if ( mobileStyle.length > 0 ) {
		dynamicStyle += `@media (max-width: 767px) { ${ mobileStyle.join(
			' '
		) } }`;
	}

	return dynamicStyle;
};

export default styleGenerator;
