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

	/**
	 * Upload the canola.jpg test image and attach it to a post.
	 *
	 * Fails with WordPress's upload error instead of an undefined-index notice from the factory.
	 *
	 * @param int $parent_post_id Post to attach the image to.
	 * @return int Attachment ID.
	 */
	protected function create_image_attachment( $parent_post_id = 0 ) {
		$file   = DIR_TESTDATA . '/images/canola.jpg';
		$upload = wp_upload_bits( wp_basename( $file ), null, file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Local test fixture.
		$this->assertFalse( $upload['error'], 'Upload failed: ' . $upload['error'] );

		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => $upload['file'],
				'post_parent'    => $parent_post_id,
				'post_mime_type' => $upload['type'],
				'post_title'     => wp_basename( $upload['file'] ),
			)
		);
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		return $attachment_id;
	}
}
