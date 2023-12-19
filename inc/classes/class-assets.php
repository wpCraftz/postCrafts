<?php
/**
 * Assets class.
 *
 * @package pc-blocks
 */

namespace PostCraft\Blocks\Inc;

use PostCraft\Blocks\Inc\Traits\Singleton;

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

	}
	
	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		wp_enqueue_style( 'pc-blocks', PC_URL . '/build/src/styles/main.css', array(), '1.0.0' );
		// wp_enqueue_style( 'pc-blocks', PC_URL . '/build/src/styles/main.css', array(), filemtime( PC_PATH . '/build/src/styles/main.css' ) );
	}
	
	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {

		wp_enqueue_style( 'pc-blocks', PC_URL . '/build/src/styles/editor.css', array(), '1.0.0' );
		// wp_enqueue_style( 'pc-blocks', PC_URL . '/build/src/styles/editor.css', array(), filemtime( PC_PATH . '/build/src/styles/editor.css' ) );
	}

}
