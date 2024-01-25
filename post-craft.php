<?php
/*
 * Plugin Name:       PostCraftz - Gutenberg Post Layout Blocks
 * Plugin URI:        https://wpcraftz.com/postcraft/
 * Description:       Advanced Gutenberg blocks to highlight, summarize and beautifully organize your posts.
 * Version:           0.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            wpCraftz
 * Author URI:        https://wpcraftz.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       pcraftz
 * Domain Path:       /languages
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PCRAFTZ_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PCRAFTZ_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PCRAFTZ_BLOCK_SRC', PCRAFTZ_PATH . '/src/blocks' );
define( 'PCRAFTZ_BUILD', PCRAFTZ_PATH . '/build' );
define( 'PCRAFTZ_POST_SRC_PATH', PCRAFTZ_PATH . 'assets/src/blocks' );

const PCRAFTZ_VERSION = 1.0;

if ( file_exists( PCRAFTZ_PATH . '/inc/helpers/autoloader.php' ) ) {
	require_once PCRAFTZ_PATH . '/inc/helpers/autoloader.php';
}

if ( file_exists( PCRAFTZ_PATH . '/inc/helpers/custom-functions.php' ) ) {
	require_once PCRAFTZ_PATH . '/inc/helpers/custom-functions.php';
}



/**
 * To load plugin manifest class.
 *
 * @return void
 */
function postCraftz_blocks() {
	\PostCraftz\Blocks\Inc\Plugin::get_instance();
}

postCraftz_blocks();
