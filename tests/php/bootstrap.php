<?php
/**
 * PHPUnit bootstrap: loads the WordPress test suite (on SQLite) and the plugin.
 *
 * Environment:
 *   WP_TESTS_DIR             Test library dir. Default: <cache>/<WP_VERSION|latest>/wordpress-tests-lib.
 *   WP_VERSION               Which cached install to use when WP_TESTS_DIR is unset. Default: latest.
 *   POSTCRAFTS_WP_TESTS_CACHE Cache root used by tests/bin/install-wp-tests.sh.
 *   POSTCRAFTS_TESTS_DB_DIR  Directory for the SQLite database file. Default: tests/.tmp in this checkout.
 *
 * @package post-crafts
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions, WordPress.Security.EscapeOutput, WordPressVIPMinimum.Functions.RestrictedFunctions -- CLI bootstrap that runs before WordPress loads.

$postcrafts_root = dirname( __DIR__, 2 );

// The plugin registers blocks from build/blocks/*, which only exists after a build.
if ( ! file_exists( $postcrafts_root . '/build/blocks/post-grid/block.json' ) || ! file_exists( $postcrafts_root . '/build/blocks/post-list/block.json' ) ) {
	fwrite( STDERR, 'Error: build/blocks is missing. Run `pnpm build` before the PHP tests.' . PHP_EOL );
	exit( 1 );
}

if ( ! file_exists( $postcrafts_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' ) ) {
	fwrite( STDERR, 'Error: Composer dev dependencies are missing. Run `composer install`.' . PHP_EOL );
	exit( 1 );
}

// Locate the WordPress test library installed by tests/bin/install-wp-tests.sh.
$postcrafts_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $postcrafts_tests_dir ) {
	$postcrafts_cache = getenv( 'POSTCRAFTS_WP_TESTS_CACHE' );
	if ( ! $postcrafts_cache ) {
		$postcrafts_cache = rtrim( sys_get_temp_dir(), '/\\' ) . '/postcrafts-wp-tests';
	}
	$postcrafts_version   = getenv( 'WP_VERSION' ) ? getenv( 'WP_VERSION' ) : 'latest';
	$postcrafts_tests_dir = rtrim( $postcrafts_cache, '/\\' ) . '/' . $postcrafts_version . '/wordpress-tests-lib';
}
$postcrafts_tests_dir = rtrim( $postcrafts_tests_dir, '/\\' );

if ( ! file_exists( $postcrafts_tests_dir . '/includes/functions.php' ) || ! file_exists( $postcrafts_tests_dir . '/wp-tests-config.php' ) ) {
	fwrite( STDERR, "Error: WordPress test library not found in {$postcrafts_tests_dir}." . PHP_EOL );
	fwrite( STDERR, 'Run `composer test:install` (or set WP_TESTS_DIR).' . PHP_EOL );
	exit( 1 );
}

// Per-checkout SQLite database, so parallel worktrees don't share one file.
// Exported with putenv() because the test library installs WordPress in a child process.
$postcrafts_db_dir = getenv( 'POSTCRAFTS_TESTS_DB_DIR' );
if ( ! $postcrafts_db_dir ) {
	$postcrafts_db_dir = $postcrafts_root . '/tests/.tmp';
}
if ( ! is_dir( $postcrafts_db_dir ) && ! mkdir( $postcrafts_db_dir, 0777, true ) && ! is_dir( $postcrafts_db_dir ) ) {
	fwrite( STDERR, "Error: could not create the SQLite directory {$postcrafts_db_dir}." . PHP_EOL );
	exit( 1 );
}
putenv( 'POSTCRAFTS_TESTS_DB_DIR=' . $postcrafts_db_dir );

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $postcrafts_root . '/vendor/yoast/phpunit-polyfills' );

require_once $postcrafts_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $postcrafts_root ) {
		require $postcrafts_root . '/post-crafts.php';
	}
);

require $postcrafts_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/TestCase.php';
