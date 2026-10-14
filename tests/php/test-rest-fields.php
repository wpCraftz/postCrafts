<?php
/**
 * Fields added to /wp/v2/posts for the editor preview: pcrafts_excerpt and featured_image.
 *
 * Criteria: tests/acceptance/rendering-php.md (RNDP-40 to RNDP-44).
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * REST response fields.
 */
class Test_Rest_Fields extends TestCase {

	/**
	 * Removes files uploaded by thumbnail tests.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
	}

	/**
	 * Fetches one post through the REST API.
	 *
	 * @param int $post_id Post ID.
	 * @return array Response data.
	 */
	private function get_post_data( $post_id ) {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id ) );
		$this->assertSame( 200, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * RNDP-40, RNDP-41: every listed post has pcrafts_excerpt matching the texturized source.
	 */
	public function test_collection_items_have_excerpt_source() {
		$ids = array(
			self::factory()->post->create(
				array(
					'post_content' => "<p>It's \"quoted\"</p><p>next</p>",
					'post_excerpt' => '',
				)
			),
			self::factory()->post->create(
				array(
					'post_content' => '<p>Body</p>',
					'post_excerpt' => 'Hand written -- excerpt',
				)
			),
			self::factory()->post->create(
				array(
					'post_content' => '',
					'post_excerpt' => '',
				)
			),
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/posts' ) );
		$this->assertSame( 200, $response->get_status() );

		$seen = array();
		foreach ( $response->get_data() as $item ) {
			$this->assertArrayHasKey( 'pcrafts_excerpt', $item );
			$this->assertIsString( $item['pcrafts_excerpt'] );
			$this->assertSame( wptexturize( post_crafts_get_excerpt_source( $item['id'] ) ), $item['pcrafts_excerpt'] );
			$seen[ $item['id'] ] = $item['pcrafts_excerpt'];
		}

		$this->assertEqualsCanonicalizing( $ids, array_keys( $seen ) );
		$this->assertSame( 'It&#8217;s &#8220;quoted&#8221; next', $seen[ $ids[0] ] );
		$this->assertSame( 'Hand written &#8212; excerpt', $seen[ $ids[1] ] );
		$this->assertSame( '', $seen[ $ids[2] ] );
	}

	/**
	 * RNDP-42: the field is not trimmed.
	 */
	public function test_excerpt_is_untrimmed() {
		$words   = implode( ' ', array_map( 'strval', range( 1000, 1099 ) ) );
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<p>' . $words . '</p>',
				'post_excerpt' => '',
			)
		);

		$data = $this->get_post_data( $post_id );

		$this->assertSame( $words, $data['pcrafts_excerpt'] );
		$this->assertStringNotContainsString( '&hellip;', $data['pcrafts_excerpt'] );
	}

	/**
	 * RNDP-43: a password-protected post doesn't leak its text.
	 */
	public function test_password_protected_post_does_not_leak() {
		$post_id = self::factory()->post->create(
			array(
				'post_content'  => '<p>Classified content words</p>',
				'post_excerpt'  => 'Classified excerpt',
				'post_password' => 'secret',
			)
		);

		$data = $this->get_post_data( $post_id );

		$this->assertSame( '', $data['pcrafts_excerpt'] );
		$this->assertStringNotContainsString( 'Classified', wp_json_encode( $data['pcrafts_excerpt'] ) );
	}

	/**
	 * RNDP-44: featured_image is present only when the post has a thumbnail.
	 */
	public function test_featured_image_only_with_thumbnail() {
		$without = self::factory()->post->create();
		$with    = self::factory()->post->create();
		$image   = $this->create_image_attachment( $with );
		set_post_thumbnail( $with, $image );

		$this->assertArrayNotHasKey( 'featured_image', $this->get_post_data( $without ) );

		$data = $this->get_post_data( $with );
		$this->assertArrayHasKey( 'featured_image', $data );
		$this->assertSame( wp_get_attachment_image_url( $image, 'thumb-330x185' ), $data['featured_image']['src'] );
		$this->assertNotEmpty( $data['featured_image']['src'] );
	}
}
