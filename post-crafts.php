<?php
/*
 * Plugin Name:       PostCrafts - Advanced PostGrid Blocks
 * Plugin URI:        https://wpcraftz.com/postcraft/
 * Description:       Ultimate plugin to highlight, summarize and beautifully organize your posts.
 * Version:           0.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            wpCraftz
 * Author URI:        https://wpcraftz.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       post-crafts
 * Domain Path:       /languages
 * 
 */

define( 'PC_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PC_BLOCK_SRC', PC_PATH . '/src/blocks' );
define( 'PC_BUILD', PC_PATH . '/build' );
define( 'PC_POST_SRC_PATH', PC_PATH . 'assets/src/blocks' );

const PC_VERSION = 1.0;

if ( file_exists( PC_PATH . '/inc/helpers/autoloader.php' ) ) {
	require_once PC_PATH . '/inc/helpers/autoloader.php';
}

if ( file_exists( PC_PATH . '/inc/helpers/custom-functions.php' ) ) {
	require_once PC_PATH . '/inc/helpers/custom-functions.php';
}



/**
 * To load plugin manifest class.
 *
 * @return void
 */
function postcrafts_blocks() {
	\PostCrafts\Blocks\Inc\Plugin::get_instance();
}

postcrafts_blocks();
