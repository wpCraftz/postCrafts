<?php
/**
 * Registers all custom gutenberg blocks.
 *
 * @package pcraftz
 */

namespace PostCraftz\Blocks\Inc;


use \PostCraftz\Blocks\Inc\Traits\Singleton;

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

		$block_files = glob( PCRAFTZ_BUILD . '/blocks/**' );

		if ( ! empty( $block_files ) && is_array( $block_files ) ) {

			foreach ( $block_files as $pcraftz_block ) {
				register_block_type( $pcraftz_block );
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
					'slug'  => 'pcraftz',
					'title' => __( 'Advanced Post Blocks', 'pcraftz' ),
					'icon'  => null,
				),
			),
			$block_categories
		);
	}
}
