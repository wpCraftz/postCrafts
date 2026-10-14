/**
 * Jest config for `pnpm test:unit` (wp-scripts test-unit-js).
 *
 * Extends the wp-scripts default (jsdom, @wordpress/jest-preset-default, Babel) and
 * limits Jest to tests/js, so it never crawls build/, vendor/ or .claude/worktrees/.
 */

/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	rootDir: __dirname,
	roots: [ '<rootDir>/tests/js' ],
	testMatch: [ '**/*.test.[jt]s?(x)' ],
	// Babel-transpile ESM-only dependencies of @wordpress/* (pnpm layout:
	// node_modules/.pnpm/<pkg>@<ver>/node_modules/<pkg>). Add a package here if Jest
	// reports "Cannot use import statement outside a module" for it.
	transform: Object.fromEntries(
		Object.entries( defaultConfig.transform || {} ).map(
			( [ , transformer ] ) => [ '\\.m?[jt]sx?$', transformer ]
		)
	),
	transformIgnorePatterns: [
		'/node_modules/(?!\\.pnpm/|(array-move|uuid|marked|parsel-js|@wordpress/theme)/)',
	],
	setupFilesAfterEnv: [
		...( defaultConfig.setupFilesAfterEnv || [] ),
		'<rootDir>/tests/js/setup.js',
	],
};
