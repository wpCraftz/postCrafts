/**
 * Internal dependencies
 */
import styleGenerator from './style-generator';

/**
 * Collect styles for PostCrafts blocks, including ones nested in other blocks.
 *
 * @param {Array} blocks Blocks to walk.
 *
 * @return {Array} Generated CSS per block.
 */
const collectStyles = ( blocks ) =>
	blocks.flatMap( ( { name, attributes, innerBlocks } ) => [
		...( name.includes( 'post-crafts' )
			? [ styleGenerator( name, attributes ) ]
			: [] ),
		...collectStyles( innerBlocks || [] ),
	] );

const parseStyle = () => {
	const { getBlocks } = wp.data.select( 'core/editor' );

	return collectStyles( getBlocks() ).join( '' );
};

export default parseStyle;
