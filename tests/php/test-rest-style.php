<?php
/**
 * REST route /post-crafts/v1/style: permissions and storage.
 *
 * Criteria: tests/acceptance/rendering-php.md (RNDP-50 to RNDP-56).
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Style save/get endpoint.
 */
class Test_Rest_Style extends TestCase {

	/**
	 * Author user ID.
	 *
	 * @var int
	 */
	private $author;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin;

	/**
	 * Creates users.
	 */
	public function set_up() {
		parent::set_up();
		$this->author = self::factory()->user->create( array( 'role' => 'author' ) );
		$this->admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * POSTs a style.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $extra   Extra body params.
	 * @return \WP_REST_Response
	 */
	private function save_style( $post_id, array $extra = array() ) {
		$request = new \WP_REST_Request( 'POST', '/post-crafts/v1/style' );
		$request->set_body_params(
			array_merge(
				array(
					'post_id'  => $post_id,
					'block_id' => 'all',
					'style'    => '.pcrafts-block-abc{color:red;}',
				),
				$extra
			)
		);

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * RNDP-50: an author can save style for their own post.
	 */
	public function test_author_can_save_own_post() {
		$post_id = self::factory()->post->create( array( 'post_author' => $this->author ) );
		wp_set_current_user( $this->author );

		$response = $this->save_style( $post_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '.pcrafts-block-abc{color:red;}', get_post_meta( $post_id, 'post-crafts-style', true ) );
	}

	/**
	 * RNDP-51: an author can't save style for another user's post.
	 */
	public function test_author_cannot_save_others_post() {
		$post_id = self::factory()->post->create( array( 'post_author' => $this->admin ) );
		update_post_meta( $post_id, 'post-crafts-style', 'original' );
		wp_set_current_user( $this->author );

		$response = $this->save_style( $post_id );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'original', get_post_meta( $post_id, 'post-crafts-style', true ) );
	}

	/**
	 * RNDP-52: an administrator can save style for any post.
	 */
	public function test_admin_can_save_any_post() {
		$post_id = self::factory()->post->create( array( 'post_author' => $this->author ) );
		wp_set_current_user( $this->admin );

		$response = $this->save_style( $post_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '.pcrafts-block-abc{color:red;}', get_post_meta( $post_id, 'post-crafts-style', true ) );
	}

	/**
	 * RNDP-53: a post that doesn't exist is refused, even for an administrator.
	 */
	public function test_missing_post_is_forbidden() {
		wp_set_current_user( $this->admin );

		$response = $this->save_style( PHP_INT_MAX );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * RNDP-54: logged-out requests are refused and store nothing.
	 */
	public function test_logged_out_is_refused() {
		$post_id = self::factory()->post->create();
		wp_set_current_user( 0 );

		$response = $this->save_style( $post_id );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
		$this->assertSame( '', get_post_meta( $post_id, 'post-crafts-style', true ) );
	}

	/**
	 * RNDP-55: previews are stored separately.
	 */
	public function test_preview_style_is_stored_separately() {
		$post_id = self::factory()->post->create( array( 'post_author' => $this->author ) );
		update_post_meta( $post_id, 'post-crafts-style', 'published-css' );
		wp_set_current_user( $this->author );

		$response = $this->save_style(
			$post_id,
			array(
				'style'         => 'preview-css',
				'is_previewing' => true,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'preview-css', get_post_meta( $post_id, 'post-crafts-preview-style', true ) );
		$this->assertSame( 'published-css', get_post_meta( $post_id, 'post-crafts-style', true ) );
	}

	/**
	 * RNDP-56: GET returns the saved style, or null when there is none.
	 */
	public function test_get_returns_saved_style() {
		$post_id = self::factory()->post->create( array( 'post_author' => $this->author ) );
		$empty   = self::factory()->post->create();

		wp_set_current_user( $this->author );
		$this->assertSame( 200, $this->save_style( $post_id )->get_status() );
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/post-crafts/v1/style' );
		$request->set_query_params( array( 'post_id' => $post_id ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '.pcrafts-block-abc{color:red;}', $response->get_data() );

		$request = new \WP_REST_Request( 'GET', '/post-crafts/v1/style' );
		$request->set_query_params( array( 'post_id' => $empty ) );
		$this->assertNull( rest_get_server()->dispatch( $request )->get_data() );
	}
}
