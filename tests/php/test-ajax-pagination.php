<?php
/**
 * Smoke test: the paginate_posts AJAX handler can be driven from tests.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * AJAX pagination smoke test.
 *
 * WP_Ajax_UnitTestCase turns wp_die() into exceptions and captures the output.
 * Don't tag these tests `@group ajax`: the WP test bootstrap skips that group unless asked for.
 */
class Test_Ajax_Pagination extends \WP_Ajax_UnitTestCase {

	/**
	 * A valid request returns the rendered loop items for the requested page.
	 */
	public function test_returns_rendered_posts() {
		self::factory()->post->create_many( 3 );

		$_POST = array(
			'_ajax_nonce' => wp_create_nonce( 'post-crafts' ),
			'postId'      => 0,
			'blockId'     => '',
			'template'    => 'post-grid',
			'query'       => array(
				'posts_per_page' => 2,
				'paged'          => 2,
			),
		);

		try {
			$this->_handleAjax( 'paginate_posts' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e ); // wp_send_json_*() ends the request with wp_die().
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data'] );
		$this->assertStringContainsString( '<article', $response['data'][0] );
	}
}
