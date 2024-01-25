<?php
/**
 * Plugin Name:       PostCrafts - Gutenberg Post Layout Blocks
 * Plugin URI:        https://wpcraftz.com/postcrafts/
 * Description:       Advanced Gutenberg blocks to highlight, summarize and beautifully organize your posts.
 * Version:           0.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            wpCraftz
 * Author URI:        https://wpcraftz.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       pcrafts
 * Domain Path:       /languages
 *
 * @package pcrafts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PCRAFTS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PCRAFTS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PCRAFTS_BLOCK_SRC', PCRAFTS_PATH . '/src/blocks' );
define( 'PCRAFTS_BUILD', PCRAFTS_PATH . '/build' );
define( 'PCRAFTS_POST_SRC_PATH', PCRAFTS_PATH . 'assets/src/blocks' );

const PCRAFTS_VERSION = 1.0;

if ( file_exists( PCRAFTS_PATH . '/inc/helpers/autoloader.php' ) ) {
	require_once PCRAFTS_PATH . '/inc/helpers/autoloader.php';
}

if ( file_exists( PCRAFTS_PATH . '/inc/helpers/custom-functions.php' ) ) {
	require_once PCRAFTS_PATH . '/inc/helpers/custom-functions.php';
}



/**
 * To load plugin manifest class.
 *
 * @return void
 */
function post_crafts_blocks() {
	\PostCrafts\Blocks\Inc\Plugin::get_instance();
}

post_crafts_blocks();
