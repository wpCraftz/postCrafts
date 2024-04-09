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

const editorScripts = glob( path.resolve( __dirname, 'src/editor/index.js' ) );

const adminStyles = glob( path.resolve( __dirname, 'src/styles/admin.scss' ) );

const frontendStyles = glob(
	path.resolve( __dirname, 'src/styles/main.scss' )
);

const frontendScripts = glob( path.resolve( __dirname, 'src/scripts/*' ) );

const styles = {
	...defaultConfig,
	entry: prepare( [
		...editorStyles,
		...editorScripts,
		...frontendStyles,
		...adminStyles,
		...frontendScripts,
	] ),
	output: {
		path: path.resolve( __dirname, 'build/' ),
	},
};

// Add any a new entry point by extending the webpack config.

const blocks = {
	...defaultConfig,
};

module.exports = [ blocks, styles ];
