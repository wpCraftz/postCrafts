/**
 * External dependencies
 */
const path = require( 'path' );
const { sync: glob } = require( 'fast-glob' );

/**
 * WordPress dependencies
 */
// Import the original config from the @wordpress/scripts package.
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Import the helper to find and generate the entry points in the src directory
const { getWebpackEntryPoints } = require( '@wordpress/scripts/utils/config' );

const prepare = ( props = [], depth = 3 ) => {
	return Object.fromEntries(
		props.map( ( entry ) => [
			entry
				.split( path.sep )
				.slice( 1 )
				.slice( -depth )
				.join( path.sep )
				.replace( /\.[^/.]+$/, '' ),
			entry,
		] )
	);
};

const editorStyles = glob(
	path.resolve( __dirname, 'src/styles/editor.scss' )
);
const frontendStyles = glob(
	path.resolve( __dirname, 'src/styles/main.scss' )
);

const styles = prepare( [ ...editorStyles, ...frontendStyles ] );

// Add any a new entry point by extending the webpack config.
module.exports = {
	...defaultConfig,
	entry: {
		...getWebpackEntryPoints(),
		...styles,
	},
};
