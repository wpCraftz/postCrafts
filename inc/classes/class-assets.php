<?php
/**
 * Assets class.
 *
 * @package pcraftz
 */

namespace PostCraftz\Blocks\Inc;

use PostCraftz\Blocks\Inc\Traits\Singleton;

/**
 * Class Assets
 */
class Assets {

	use Singleton;


	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();

	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

	}
	
	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		
	}
	
	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style( 'pcraftz-admin', PCRAFTZ_URL . '/build/src/styles/admin.css', array(), filemtime( PCRAFTZ_PATH . '/build/src/styles/admin.css' ) );
	}
	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {

		if ( is_admin() ) {
			wp_enqueue_style( 'pcraftz-editor', PCRAFTZ_URL . '/build/src/styles/editor.css', array(), filemtime( PCRAFTZ_PATH . '/build/src/styles/editor.css' ) );
		} else {
			wp_enqueue_style( 'pcraftz', PCRAFTZ_URL . '/build/src/styles/main.css', array(), filemtime( PCRAFTZ_PATH . '/build/src/styles/main.css' ) );
		}
		
	}

}
