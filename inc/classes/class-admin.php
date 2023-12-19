<?php
/**
 * Admin class.
 *
 * @package pc-blocks
 */

namespace PostCraft\Blocks\Inc;

use PostCraft\Blocks\Inc\Traits\Singleton;

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
		add_menu_page( __( 'PostCrafts', 'pc-blocks' ), __( 'PostCrafts', 'pc-blocks' ), 'manage_options', 'pc-blocks', [ $this, 'postcrafts_settings' ], 'dashicons-grid-view' );
	}

	/**
	 * Settings markup.
	 */
	public function postcrafts_settings() {
		ob_start();
		?>
		<div>
			<h1><?php esc_html_e( 'PostCrafts Settings', 'pc-blocks' );?></h1>
		</dvi>
		<?php
		$menu = ob_get_clean();
		echo $menu;
	}
	

}
