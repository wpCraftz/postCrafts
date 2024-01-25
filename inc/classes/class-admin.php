<?php
/**
 * Admin class.
 *
 * @package pcraftz
 */

namespace PostCraftz\Blocks\Inc;

use PostCraftz\Blocks\Inc\Traits\Singleton;

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
		add_menu_page( __( 'PostCrafts', 'pcraftz' ), __( 'PostCrafts', 'pcraftz' ), 'manage_options', 'pcraftz', [ $this, 'postcrafts_settings' ], 'dashicons-grid-view' );
	}

	/**
	 * Settings markup.
	 */
	public function postcrafts_settings() {
		ob_start();
		?>
		<div>
			<h1><?php esc_html_e( 'PostCrafts Settings', 'pcraftz' );?></h1>
		</dvi>
		<?php
		$menu = ob_get_clean();
		echo $menu;
	}
	

}
