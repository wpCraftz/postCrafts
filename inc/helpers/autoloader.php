<?php
/**
 * Autoloader file for plugin.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc\Helpers;

/**
 * Auto loader function.
 *
 * @param string $files Source namespace.
 *
 * @return void
 */
function autoloader( $files = '' ) {

	$file_path      = false;
	$namespace_root = 'PostCrafts\Blocks\\';
	$files          = trim( $files, '\\' );

	if ( empty( $files ) || strpos( $files, '\\' ) === false || strpos( $files, $namespace_root ) !== 0 ) {
		// Not our namespace, bail out.
		return;
	}

	// Remove our root namespace.
	$files = str_replace( $namespace_root, '', $files );

	$path = explode(
		'\\',
		str_replace( '_', '-', strtolower( $files ) )
	);

	/**
	 * Time to determine which type of file path it is,
	 * so that we can deduce the correct file path for it.
	 */
	if ( empty( $path[0] ) || empty( $path[1] ) ) {
		return;
	}

	$directory = '';
	$file_name = '';

	if ( 'inc' === $path[0] ) {

		switch ( $path[1] ) {
			case 'traits':
				$directory = 'traits';
				$file_name = sprintf( 'trait-%s', trim( strtolower( $path[2] ) ) );
				break;
			case 'post-types':
				// continue.
			case 'blocks':
				// continue.
			case 'plugin-configs':
				/**
				 * If there is class name provided for specific directory then load that.
				 * otherwise find in inc/ directory.
				 */
				if ( ! empty( $path[2] ) ) {
					$directory = sprintf( 'classes/%s', $path[1] );
					$file_name = sprintf( 'class-%s', trim( strtolower( $path[2] ) ) );
					break;
				}
				// continue.
			default:
				$directory = 'classes';
				$file_name = sprintf( 'class-%s', trim( strtolower( $path[1] ) ) );
				break;
		}

		$file_path = sprintf( '%s/inc/%s/%s.php', untrailingslashit( POST_CRAFTS_PATH ), $directory, $file_name );

	}

	$resource_path_valid = validate_file( $file_path );
	// For Windows platform, validate_file returns 2 so we've added this condition as well.
	if ( ! empty( $file_path ) && file_exists( $file_path ) && ( 0 === $resource_path_valid || 2 === $resource_path_valid ) ) {
		// We are already making sure that the file exists and it's valid.
		require_once( $file_path); // phpcs:ignore
	}
}

spl_autoload_register( '\PostCrafts\Blocks\Inc\Helpers\autoloader' );
