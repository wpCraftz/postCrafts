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

/**
 * Silence Dart Sass "legacy JS API" deprecation warnings.
 * sass-loader@12 (bundled with @wordpress/scripts@27) only supports the legacy API.
 */
defaultConfig.module.rules.forEach( ( rule ) => {
	rule.use?.forEach?.( ( loader ) => {
		if ( loader?.loader?.includes( 'sass-loader' ) ) {
			loader.options = {
				...loader.options,
				sassOptions: {
					...loader.options?.sassOptions,
					silenceDeprecations: [ 'legacy-js-api' ],
				},
			};
		}
	} );
} );

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
