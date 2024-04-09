/**
 * Internal dependencies
 */
import styleGenerator from './style-generator';

const parseStyle = () => {
	const { getBlocks } = wp.data.select( 'core/editor' );
	const allBlocks = getBlocks();

	const blockStyles = [];
	if ( allBlocks.length > 0 ) {
		allBlocks
			.filter( ( block ) => block.name.includes( 'post-crafts' ) )
			.forEach( ( { name, attributes } ) => {
				blockStyles.push( styleGenerator( name, attributes ) );
			} );
	}

	return blockStyles.join( '' );
};

export default parseStyle;
