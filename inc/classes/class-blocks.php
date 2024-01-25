<?php
/**
 * Registers all custom gutenberg blocks.
 *
 * @package pcrafts
 */

namespace PostCrafts\Blocks\Inc;


use \PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Blocks
 */
class Blocks {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ], 11, 2 );
		add_action( 'init', [ $this, 'register_blocks' ] );

	}

	/**
	 * Register all blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {

		$block_files = glob( PCRAFTS_BUILD . '/blocks/**' );

		if ( ! empty( $block_files ) && is_array( $block_files ) ) {

			foreach ( $block_files as $PCRAFTS_block ) {
				register_block_type( $PCRAFTS_block );
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
					'slug'  => 'pcrafts',
					'title' => __( 'Advanced Post Blocks', 'pcrafts' ),
					'icon'  => null,
				),
			),
			$block_categories
		);
	}
}
