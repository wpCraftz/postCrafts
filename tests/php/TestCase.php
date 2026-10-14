<?php
/**
 * Base test case for PostCrafts integration tests.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Extends WP_UnitTestCase: every test runs in a DB transaction that is rolled back,
 * and self::factory() creates posts, terms, users and attachments.
 */
abstract class TestCase extends \WP_UnitTestCase {

	/**
	 * Serialized block comment for a PostCrafts block, ready for post_content.
	 *
	 * @param string $block      Block name without namespace: 'post-grid' or 'post-list'.
	 * @param array  $attributes Block attributes. Set a fixed 'blockId' when a test needs to look the block up.
	 * @return string
	 */
	protected function block_markup( $block, array $attributes = array() ) {
		return serialize_block(
			array(
				'blockName'    => 'post-crafts/' . $block,
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Creates a published page whose content holds one PostCrafts block.
	 *
	 * @param string $block      Block name without namespace: 'post-grid' or 'post-list'.
	 * @param array  $attributes Block attributes.
	 * @return int Post ID.
	 */
	protected function create_post_with_block( $block, array $attributes = array() ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $this->block_markup( $block, $attributes ),
			)
		);
	}

	/**
	 * Renders a PostCrafts block the way the front end does (render.php).
	 *
	 * @param string $block      Block name without namespace: 'post-grid' or 'post-list'.
	 * @param array  $attributes Block attributes.
	 * @return string HTML.
	 */
	protected function render_block( $block, array $attributes = array() ) {
		return do_blocks( $this->block_markup( $block, $attributes ) );
	}
}
