<?php
/**
 * Plugin Name:       PostCrafts - Gutenberg Post Blocks
 * Plugin URI:        https://wpcraftz.com/postcrafts/
 * Description:       Advanced blocks to highlight, summarize and beautifully organize your posts.
 * Version:           0.1.2
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            wpCraftz
 * Author URI:        https://wpcraftz.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       post-crafts
 * Domain Path:       /languages
 *
 * @package post-crafts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'POST_CRAFTS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'POST_CRAFTS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'POST_CRAFTS_BUILD', POST_CRAFTS_PATH . '/build' );
define( 'POST_CRAFTS_REST_NAMESPACE', 'post-crafts/v1' );

const POST_CRAFTS_VERSION = '0.1.2';

if ( file_exists( POST_CRAFTS_PATH . '/inc/helpers/autoloader.php' ) ) {
	require_once POST_CRAFTS_PATH . '/inc/helpers/autoloader.php';
}

if ( file_exists( POST_CRAFTS_PATH . '/inc/helpers/custom-functions.php' ) ) {
	require_once POST_CRAFTS_PATH . '/inc/helpers/custom-functions.php';
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
