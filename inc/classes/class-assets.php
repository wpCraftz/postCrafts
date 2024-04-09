<?php
/**
 * Assets class.
 *
 * @package post-crafts
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
	}

	/**
	 * To enqueue scripts and styles in block editor.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_style( 'post-crafts-editor', POST_CRAFTS_URL . '/build/src/styles/editor.css', array(), filemtime( POST_CRAFTS_PATH . '/build/src/styles/editor.css' ) );
		wp_enqueue_script( 'post-crafts-editor', POST_CRAFTS_URL . '/build/src/editor/index.js', array( 'wp-blocks', 'wp-element', 'wp-i18n' ), filemtime( POST_CRAFTS_PATH . '/build/src/editor/index.js' ), array( 'in_footer' => true ) );
	}

	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style( 'post-crafts-admin', POST_CRAFTS_URL . '/build/src/styles/admin.css', array(), filemtime( POST_CRAFTS_PATH . '/build/src/styles/admin.css' ) );
	}

	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {

		wp_enqueue_style( 'post-crafts', POST_CRAFTS_URL . '/build/src/styles/main.css', array(), filemtime( POST_CRAFTS_PATH . '/build/src/styles/main.css' ) );

		if ( has_block( 'post-crafts/post-grid' ) || has_block( 'post-crafts/post-list' ) ) {
			wp_enqueue_script(
				'post-crafts-pagination',
				POST_CRAFTS_URL . '/build/src/scripts/pagination.js',
				array( 'jquery' ),
				filemtime( POST_CRAFTS_PATH . '/build/src/scripts/pagination.js' ),
				array(
					'in_footer' => true,
				)
			);
		}
	}
}
