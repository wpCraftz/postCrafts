<?php
/**
 * Smoke tests: the plugin boots and registers its blocks.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Plugin bootstrap smoke tests.
 */
class Test_Plugin extends TestCase {

	/**
	 * The main file defines its constants and helpers.
	 */
	public function test_constants_and_helpers_are_defined() {
		$this->assertTrue( defined( 'POST_CRAFTS_PATH' ) );
		$this->assertSame( 'post-crafts/v1', POST_CRAFTS_REST_NAMESPACE );
		$this->assertSame( POST_CRAFTS_PATH . '/build', POST_CRAFTS_BUILD );

		$this->assertTrue( function_exists( 'post_crafts_query_builder' ) );
		$this->assertTrue( function_exists( 'post_crafts_get_block_attributes' ) );
		$this->assertTrue( class_exists( '\PostCrafts\Blocks\Inc\Plugin' ) );
	}

	/**
	 * Both dynamic blocks are registered from build/blocks with a render callback.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block_name Block name.
	 */
	public function test_block_is_registered( $block_name ) {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
		$this->assertTrue( $block_type->is_dynamic() );
		$this->assertSame( 'post-crafts', $block_type->category );
	}

	/**
	 * Block names.
	 *
	 * @return array
	 */
	public function data_blocks() {
		return array(
			'post grid' => array( 'post-crafts/post-grid' ),
			'post list' => array( 'post-crafts/post-list' ),
		);
	}

	/**
	 * The AJAX pagination actions are hooked for logged-in and logged-out users.
	 */
	public function test_ajax_pagination_is_hooked() {
		$this->assertNotFalse( has_action( 'wp_ajax_paginate_posts' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_paginate_posts' ) );
	}
}
