<?php
/**
 * Registers all custom gutenberg blocks.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Blocks
 */
class Blocks {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 11, 2 );
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {

		$block_files = glob( POST_CRAFTS_BUILD . '/blocks/**' );

		if ( ! empty( $block_files ) && is_array( $block_files ) ) {

			foreach ( $block_files as $block ) {
				register_block_type( $block );
			}
		}
	}

	/**
	 * To add custom block category
	 *
	 * @param array $block_categories Array of block categories.
	 * @return array
	 */
	public function register_block_categories( $block_categories ) {

		return array_merge(
			array(
				array(
					'slug'  => 'post-crafts',
					'title' => __( 'Advanced Post Blocks', 'post-crafts' ),
					'icon'  => null,
				),
			),
			$block_categories
		);
	}
}
