#!/usr/bin/env bash
#
# Installs WordPress core, the WordPress PHPUnit test library and the SQLite
# Database Integration drop-in, so the PHP integration tests can run without MySQL.
#
# Usage: tests/bin/install-wp-tests.sh [wp-version] [--force]
#   wp-version  "latest" (default), "nightly"/"trunk", or an exact version like 7.1.3.
#   --force     Re-download everything for that version.
#
# Environment:
#   POSTCRAFTS_WP_TESTS_CACHE  Cache root (default: ${TMPDIR:-/tmp}/postcrafts-wp-tests).
#   WP_CORE_DIR                WordPress core dir (default: <cache>/<version>/wordpress).
#   WP_TESTS_DIR               Test library dir   (default: <cache>/<version>/wordpress-tests-lib).
#
# Everything is downloaded once per version and shared by all checkouts/worktrees.
# The SQLite database file is NOT stored here: wp-tests-config.php reads
# POSTCRAFTS_TESTS_DB_DIR, which tests/php/bootstrap.php sets per checkout.

set -euo pipefail

WP_VERSION="latest"
FORCE=0
for arg in "$@"; do
	case "$arg" in
		--force) FORCE=1 ;;
		-h|--help) sed -n '2,19p' "$0"; exit 0 ;;
		*) WP_VERSION="$arg" ;;
	esac
done

download() {
	curl --fail --silent --show-error --location --retry 3 "$1" --output "$2"
}

CACHE_ROOT="${POSTCRAFTS_WP_TESTS_CACHE:-${TMPDIR:-/tmp}}"
CACHE_ROOT="${CACHE_ROOT%/}"
if [ -z "${POSTCRAFTS_WP_TESTS_CACHE:-}" ]; then
	CACHE_ROOT="$CACHE_ROOT/postcrafts-wp-tests"
fi
mkdir -p "$CACHE_ROOT"

# Resolve "latest" to an exact version so the cache is keyed by it. The
# <cache>/latest symlink (read by tests/php/bootstrap.php) points at that version.
REQUESTED_VERSION="$WP_VERSION"
if [ "$WP_VERSION" = "latest" ]; then
	WP_VERSION="$(curl --fail --silent --retry 3 https://api.wordpress.org/core/version-check/1.7/ \
		| grep -o '"version":"[^"]*"' | head -1 | sed 's/"version":"//; s/"//' || true)"
	if [ -z "$WP_VERSION" ]; then
		if [ -L "$CACHE_ROOT/latest" ]; then
			WP_VERSION="$(basename "$(readlink "$CACHE_ROOT/latest")")"
			echo "Offline: reusing the cached latest version ($WP_VERSION)."
		else
			echo "Could not resolve the latest WordPress version." >&2
			exit 1
		fi
	fi
fi
if [ "$WP_VERSION" = "trunk" ]; then
	WP_VERSION="nightly"
fi

CACHE_DIR="$CACHE_ROOT/$WP_VERSION"
WP_CORE_DIR="${WP_CORE_DIR:-$CACHE_DIR/wordpress}"
WP_TESTS_DIR="${WP_TESTS_DIR:-$CACHE_DIR/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR%/}"
WP_TESTS_DIR="${WP_TESTS_DIR%/}"

TMP_WORK="$(mktemp -d)"
trap 'rm -rf "$TMP_WORK"' EXIT

if [ "$FORCE" = 1 ]; then
	rm -rf "$WP_CORE_DIR" "$WP_TESTS_DIR"
fi

echo "WordPress version: $WP_VERSION"
echo "WP_CORE_DIR:       $WP_CORE_DIR"
echo "WP_TESTS_DIR:      $WP_TESTS_DIR"

# 1. WordPress core.
if [ ! -f "$WP_CORE_DIR/wp-settings.php" ]; then
	echo "Downloading WordPress core..."
	if [ "$WP_VERSION" = "nightly" ]; then
		download https://wordpress.org/nightly-builds/wordpress-latest.zip "$TMP_WORK/wordpress.zip"
		unzip -q "$TMP_WORK/wordpress.zip" -d "$TMP_WORK"
	else
		download "https://wordpress.org/wordpress-$WP_VERSION.tar.gz" "$TMP_WORK/wordpress.tar.gz"
		tar -xzf "$TMP_WORK/wordpress.tar.gz" -C "$TMP_WORK"
	fi
	mkdir -p "$(dirname "$WP_CORE_DIR")"
	rm -rf "$WP_CORE_DIR"
	mv "$TMP_WORK/wordpress" "$WP_CORE_DIR"
fi

# 2. SQLite Database Integration plugin + db.php drop-in.
SQLITE_DIR="$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration"
if [ ! -f "$SQLITE_DIR/wp-includes/sqlite/db.php" ]; then
	echo "Downloading SQLite Database Integration..."
	download https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip "$TMP_WORK/sqlite.zip"
	rm -rf "$SQLITE_DIR"
	unzip -q "$TMP_WORK/sqlite.zip" -d "$WP_CORE_DIR/wp-content/plugins"
fi
if [ ! -f "$WP_CORE_DIR/wp-content/db.php" ] || [ "$FORCE" = 1 ]; then
	sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$SQLITE_DIR#" \
		-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
		"$SQLITE_DIR/db.copy" > "$WP_CORE_DIR/wp-content/db.php"
fi

# The test framework scans wp-content/uploads before every test; the core download doesn't include it.
mkdir -p "$WP_CORE_DIR/wp-content/uploads"

# 3. WordPress PHPUnit test library (tests/phpunit/includes + data from wordpress-develop).
if [ ! -f "$WP_TESTS_DIR/includes/bootstrap.php" ] || [ ! -d "$WP_TESTS_DIR/data" ]; then
	echo "Downloading the WordPress test library..."
	if [ "$WP_VERSION" = "nightly" ]; then
		SVN_REF="trunk"
		GIT_REF="heads/trunk"
	else
		# Tags drop a trailing ".0" (7.1.0 is tagged "7.1").
		TAG="${WP_VERSION%.0}"
		SVN_REF="tags/$TAG"
		GIT_REF="tags/$TAG"
	fi
	rm -rf "$WP_TESTS_DIR"
	mkdir -p "$WP_TESTS_DIR"
	if command -v svn > /dev/null 2>&1; then
		svn export --quiet --force "https://develop.svn.wordpress.org/$SVN_REF/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
		svn export --quiet --force "https://develop.svn.wordpress.org/$SVN_REF/tests/phpunit/data/" "$WP_TESTS_DIR/data"
		svn export --quiet --force "https://develop.svn.wordpress.org/$SVN_REF/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config-sample.php"
	else
		download "https://codeload.github.com/WordPress/wordpress-develop/tar.gz/refs/$GIT_REF" "$TMP_WORK/develop.tar.gz"
		tar -xzf "$TMP_WORK/develop.tar.gz" -C "$TMP_WORK" \
			--wildcards '*/tests/phpunit/includes/*' '*/tests/phpunit/data/*' '*/wp-tests-config-sample.php' 2> /dev/null \
			|| tar -xzf "$TMP_WORK/develop.tar.gz" -C "$TMP_WORK"
		SRC="$(find "$TMP_WORK" -maxdepth 1 -type d -name 'wordpress-develop-*' | head -1)"
		mv "$SRC/tests/phpunit/includes" "$WP_TESTS_DIR/includes"
		mv "$SRC/tests/phpunit/data" "$WP_TESTS_DIR/data"
		mv "$SRC/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config-sample.php"
	fi
fi

# 4. wp-tests-config.php for SQLite. The DB directory comes from the environment at
#    runtime, so one shared install serves every checkout with its own database.
cat > "$WP_TESTS_DIR/wp-tests-config.php" << PHP
<?php
/**
 * Generated by postcrafts tests/bin/install-wp-tests.sh. Do not edit; re-run the script.
 */

define( 'ABSPATH', '$WP_CORE_DIR/' );

define( 'WP_DEFAULT_THEME', 'default' );

// SQLite (via the db.php drop-in). Each checkout sets POSTCRAFTS_TESTS_DB_DIR.
\$postcrafts_db_dir = getenv( 'POSTCRAFTS_TESTS_DB_DIR' );
if ( ! \$postcrafts_db_dir ) {
	\$postcrafts_db_dir = sys_get_temp_dir() . '/postcrafts-wp-tests-db';
}
define( 'DB_ENGINE', 'sqlite' );
define( 'DB_DIR', rtrim( \$postcrafts_db_dir, '/' ) . '/' );
define( 'DB_FILE', getenv( 'POSTCRAFTS_TESTS_DB_FILE' ) ?: 'wordpress-tests.sqlite' );

// Unused by SQLite but expected by the test library.
define( 'DB_NAME', 'wordpress_tests' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'postcrafts-tests' );
define( 'SECURE_AUTH_KEY',  'postcrafts-tests' );
define( 'LOGGED_IN_KEY',    'postcrafts-tests' );
define( 'NONCE_KEY',        'postcrafts-tests' );
define( 'AUTH_SALT',        'postcrafts-tests' );
define( 'SECURE_AUTH_SALT', 'postcrafts-tests' );
define( 'LOGGED_IN_SALT',   'postcrafts-tests' );
define( 'NONCE_SALT',       'postcrafts-tests' );

\$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', PHP_BINARY );

define( 'WPLANG', '' );
PHP

if [ "$REQUESTED_VERSION" = "latest" ]; then
	ln -sfn "$WP_VERSION" "$CACHE_ROOT/latest"
fi

echo "Done. Run the PHP tests with: composer test"
if [ "$REQUESTED_VERSION" != "latest" ]; then
	echo "(set WP_VERSION=$WP_VERSION or WP_TESTS_DIR=$WP_TESTS_DIR when running them)"
fi
