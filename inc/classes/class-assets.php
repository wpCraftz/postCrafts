<?php
/**
 * Assets class.
 *
 * @package pcrafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

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
		wp_enqueue_style( 'pcrafts-admin', PCRAFTS_URL . '/build/src/styles/admin.css', array(), filemtime( PCRAFTS_PATH . '/build/src/styles/admin.css' ) );
	}

	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {

		if ( is_admin() ) {
			wp_enqueue_style( 'pcrafts-editor', PCRAFTS_URL . '/build/src/styles/editor.css', array(), filemtime( PCRAFTS_PATH . '/build/src/styles/editor.css' ) );
		} else {
			wp_enqueue_style( 'pcrafts', PCRAFTS_URL . '/build/src/styles/main.css', array(), filemtime( PCRAFTS_PATH . '/build/src/styles/main.css' ) );
		}

	}

}
