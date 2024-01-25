<?php
/**
 * Admin class.
 *
 * @package pcrafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Admin
 */
class Admin {

	use Singleton;


	/**
	 * Construct method.
	 */
	protected function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/**
	 * Add menu item.
	 */
	function admin_menu() {
		add_menu_page( __( 'PostCrafts', 'pcrafts' ), __( 'PostCrafts', 'pcrafts' ), 'manage_options', 'pcrafts', [ $this, 'postcrafts_settings' ], 'dashicons-grid-view' );
	}

	/**
	 * Settings markup.
	 */
	public function postcrafts_settings() {
		ob_start();
		?>
		<div>
			<h1><?php esc_html_e( 'PostCrafts Settings', 'pcrafts' );?></h1>
		</dvi>
		<?php
		$menu = ob_get_clean();
		echo $menu;
	}

}
