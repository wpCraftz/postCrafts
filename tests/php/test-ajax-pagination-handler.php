<?php
/**
 * End-to-end tests for the paginate_posts AJAX handler (Plugin::post_crafts_pagination()).
 *
 * Acceptance criteria: tests/acceptance/pagination-php.md (PAGP-17 to PAGP-27).
 * Query sanitization in isolation is covered by test-sanitize-query-args.php.
 *
 * @package post-crafts
 */

// phpcs:disable WordPress.WP.PostsPerPage, WordPress.DB.SlowDBQuery -- Deliberately hostile query arguments.
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Builds the request the handler verifies.
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting, WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Raises the error level for PAGP-27.

namespace PostCrafts\Blocks\Tests;

/**
 * AJAX pagination handler.
 *
 * Not tagged `@group ajax`: the WP test bootstrap skips that group unless asked for.
 */
class Test_Ajax_Pagination_Handler extends \WP_Ajax_UnitTestCase {

	/**
	 * Words in the post content, so the excerpt length is measurable.
	 */
	const CONTENT_WORDS = 50;

	/**
	 * error_reporting() level while the handler ran (null until a request reached it).
	 *
	 * @var int|null
	 */
	private $handler_error_level;

	/**
	 * Published post IDs, newest first.
	 *
	 * @var int[]
	 */
	private $posts = array();

	/**
	 * Creates five published posts with distinct dates and reports every PHP notice/warning from the handler.
	 */
	public function set_up() {
		parent::set_up();

		/*
		 * WP_Ajax_UnitTestCase hides E_WARNING because core's admin_init sends headers. PAGP-27 wants
		 * every notice and warning from the handler to fail the test, so report everything while it runs.
		 */
		add_action( 'wp_ajax_paginate_posts', array( $this, 'report_all_errors' ), 0 );
		add_action( 'wp_ajax_nopriv_paginate_posts', array( $this, 'report_all_errors' ), 0 );

		$words = implode( ' ', array_fill( 0, self::CONTENT_WORDS, 'word' ) );

		for ( $i = 0; $i < 5; $i++ ) {
			$this->posts[] = self::factory()->post->create(
				array(
					'post_date'    => sprintf( '2024-01-%02d 10:00:00', 20 - $i ),
					'post_content' => $words,
					'post_excerpt' => '',
				)
			);
		}
	}

	/**
	 * Removes the error-level hooks.
	 */
	public function tear_down() {
		remove_action( 'wp_ajax_paginate_posts', array( $this, 'report_all_errors' ), 0 );
		remove_action( 'wp_ajax_nopriv_paginate_posts', array( $this, 'report_all_errors' ), 0 );
		wp_reset_postdata();
		parent::tear_down();
	}

	/**
	 * Reports every PHP error level for the rest of the request (runs before the handler).
	 */
	public function report_all_errors() {
		error_reporting( E_ALL );
		$this->handler_error_level = error_reporting();
	}

	/**
	 * Sends a paginate_posts request and returns the outcome.
	 *
	 * @param array $post   $_POST values.
	 * @param bool  $nopriv Whether to run the logged-out (nopriv) action.
	 * @return array { @type array|null $json, @type string $raw, @type bool $died }
	 */
	private function request( array $post, $nopriv = false ) {
		$_POST       = $post;
		$died        = false;
		$error_level = error_reporting();

		try {
			if ( $nopriv ) {
				// Same as _handleAjax(), but for the action admin-ajax.php fires for logged-out users.
				$_POST['action'] = 'paginate_posts';
				$_REQUEST        = $_POST;
				ob_start();
				do_action( 'wp_ajax_nopriv_paginate_posts' );
				$buffer = ob_get_clean();
				if ( '' !== $buffer ) {
					$this->_last_response = $buffer;
				}
			} else {
				$this->_handleAjax( 'paginate_posts' );
			}
		} catch ( \WPAjaxDieContinueException $e ) {
			$died = true; // wp_send_json_*() ends the request with wp_die().
		} finally {
			error_reporting( $error_level );
		}

		return array(
			'json' => json_decode( $this->_last_response, true ),
			'raw'  => $this->_last_response,
			'died' => $died,
		);
	}

	/**
	 * A request body like the one src/scripts/pagination.js sends.
	 *
	 * @param array $overrides Values to change.
	 * @return array
	 */
	private function payload( array $overrides = array() ) {
		return array_merge(
			array(
				'_ajax_nonce' => wp_create_nonce( 'post-crafts' ),
				'postId'      => 0,
				'blockId'     => '',
				'paged'       => 1,
				'template'    => 'post-grid',
				'query'       => array(
					'posts_per_page' => 2,
					'order'          => 'desc',
					'orderby'        => 'date',
					'paged'          => 1,
				),
			),
			$overrides
		);
	}

	/**
	 * Post IDs of the rendered items, in order.
	 *
	 * @param string[] $items Rendered items.
	 * @return int[]
	 */
	private function item_ids( array $items ) {
		$ids = array();

		foreach ( $items as $html ) {
			$this->assertSame( 1, preg_match( '/<article id="post-(\d+)"/', $html, $match ), 'Each item is one article.' );
			$ids[] = (int) $match[1];
		}

		return $ids;
	}

	/**
	 * Words in an item's excerpt, or null when it has none.
	 *
	 * @param string $html Rendered item.
	 * @return int|null
	 */
	private function excerpt_words( $html ) {
		if ( ! preg_match( '#<div class="entry-summary">(.*?)</div>#s', $html, $match ) ) {
			return null;
		}

		$text = trim( str_replace( array( '&hellip;', '…' ), '', wp_strip_all_tags( $match[1] ) ) );

		return str_word_count( $text );
	}

	/**
	 * Creates a published page with the given content.
	 *
	 * @param string $content Post content.
	 * @return int
	 */
	private function page_with( $content ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);
	}

	/**
	 * Serialized PostCrafts block.
	 *
	 * @param string $block      Block name without namespace.
	 * @param array  $attributes Attributes.
	 * @return string
	 */
	private function block( $block, array $attributes ) {
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
	 * A valid request returns the requested page rendered with the requested template.
	 *
	 * @covers-ac PAGP-17
	 * @dataProvider data_templates
	 *
	 * @param string $template Template.
	 */
	public function test_returns_requested_page( $template ) {
		$result = $this->request(
			$this->payload(
				array(
					'template' => $template,
					'paged'    => 2,
					'query'    => array(
						'posts_per_page' => 2,
						'order'          => 'desc',
						'orderby'        => 'date',
						'paged'          => 2,
					),
				)
			)
		);

		$this->assertTrue( $result['died'] );
		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[2], $this->posts[3] ), $this->item_ids( $result['json']['data'] ) );

		foreach ( $result['json']['data'] as $html ) {
			$this->assertStringContainsString( 'class="' . $template . '"', $html );
		}
	}

	/**
	 * Loop templates.
	 *
	 * @return array
	 */
	public function data_templates() {
		return array(
			'post grid' => array( 'post-grid' ),
			'post list' => array( 'post-list' ),
		);
	}

	/**
	 * The data-* attributes of a rendered block, sent back the way the script sends them, load the next page.
	 *
	 * @covers-ac PAGP-17
	 * @dataProvider data_templates
	 *
	 * @param string $template Template (and block) name.
	 */
	public function test_front_end_payload_round_trip( $template ) {
		$page_id = $this->page_with(
			$this->block(
				$template,
				array(
					'blockId'      => 'trip1234',
					'postsPerPage' => 2,
				) 
			) 
		);

		$this->go_to( get_permalink( $page_id ) );
		$GLOBALS['post'] = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulates the main loop on the page.
		$html            = do_blocks( get_post( $page_id )->post_content );

		// Back to the admin-ajax.php context that go_to() left (admin_init expects is_admin()).
		set_current_screen( 'ajax' );

		$this->assertSame( 1, preg_match( '/<div class="pcrafts-pagination[^>]*>/', $html, $wrapper ) );

		$data = array();
		preg_match_all( '/data-([a-z-]+)="([^"]*)"/', $wrapper[0], $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$data[ $match[1] ] = html_entity_decode( $match[2], ENT_QUOTES );
		}

		$this->assertSame( (string) $page_id, $data['post-id'] );
		$this->assertSame( 'trip1234', $data['block-id'] );
		$this->assertSame( $template, $data['template'] );
		$this->assertSame( '3', $data['max-page'] );

		// jQuery.post() form-encodes the body: scalars become strings, booleans "true"/"false", empty arrays vanish.
		$stringify = static function ( $value ) use ( &$stringify ) {
			if ( is_array( $value ) ) {
				return array_filter(
					array_map( $stringify, $value ),
					static function ( $item ) {
						return array() !== $item;
					} 
				);
			}

			return is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value;
		};

		$query          = $stringify( json_decode( $data['query'], true ) );
		$query['paged'] = '2';

		$result = $this->request(
			array(
				'action'      => 'paginate_posts',
				'_ajax_nonce' => wp_create_nonce( 'post-crafts' ),
				'postId'      => $data['post-id'],
				'blockId'     => $data['block-id'],
				'paged'       => '2',
				'template'    => $data['template'],
				'query'       => $query,
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[2], $this->posts[3] ), $this->item_ids( $result['json']['data'] ) );
	}

	/**
	 * Asking past the last page returns an error.
	 *
	 * @covers-ac PAGP-18
	 */
	public function test_page_past_the_end() {
		$result = $this->request(
			$this->payload(
				array(
					'paged' => 1,
					'query' => array(
						'posts_per_page' => 2,
						'paged'          => 4,
					),
				)
			)
		);

		$this->assertTrue( $result['died'] );
		$this->assertFalse( $result['json']['success'] );
		$this->assertSame( array( 'No more posts found' ), $result['json']['data'] );
	}

	/**
	 * The page comes from query[paged], not the top-level paged.
	 *
	 * @covers-ac PAGP-18
	 */
	public function test_page_comes_from_query() {
		$result = $this->request(
			$this->payload(
				array(
					'paged' => 1,
					'query' => array(
						'posts_per_page' => 2,
						'paged'          => 3,
					),
				)
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[4] ), $this->item_ids( $result['json']['data'] ) );
	}

	/**
	 * A missing or invalid nonce makes the handler return without output or wp_die().
	 *
	 * @covers-ac PAGP-19
	 * @dataProvider data_bad_nonces
	 *
	 * @param string|null $nonce Nonce, or null to leave it out.
	 */
	public function test_bad_nonce_returns_early( $nonce ) {
		$post = $this->payload();

		if ( null === $nonce ) {
			unset( $post['_ajax_nonce'] );
		} else {
			$post['_ajax_nonce'] = 'other-action' === $nonce ? wp_create_nonce( 'other-action' ) : $nonce;
		}

		$result = $this->request( $post );

		$this->assertFalse( $result['died'], 'No JSON response and no wp_die().' );
		$this->assertSame( '', $result['raw'] );
		$this->assertNull( $result['json'] );
	}

	/**
	 * Bad nonces.
	 *
	 * @return array
	 */
	public function data_bad_nonces() {
		return array(
			'missing'      => array( null ),
			'empty'        => array( '' ),
			'garbage'      => array( 'not-a-nonce' ),
			'other action' => array( 'other-action' ),
			'array-like'   => array( 'abc[]' ),
		);
	}

	/**
	 * A nonce created for another user (here: logged out) is rejected for the logged-in user.
	 *
	 * @covers-ac PAGP-19
	 */
	public function test_nonce_from_another_session_is_rejected() {
		wp_set_current_user( 0 );
		$nonce = wp_create_nonce( 'post-crafts' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = $this->request( $this->payload( array( '_ajax_nonce' => $nonce ) ) );

		$this->assertFalse( $result['died'] );
		$this->assertSame( '', $result['raw'] );
	}

	/**
	 * Only post-grid and post-list can be requested.
	 *
	 * @covers-ac PAGP-20
	 * @dataProvider data_invalid_templates
	 *
	 * @param mixed $template Template value.
	 */
	public function test_rejects_invalid_template( $template ) {
		$post = $this->payload();

		if ( null === $template ) {
			unset( $post['template'] );
		} else {
			$post['template'] = $template;
		}

		$result = $this->request( $post );

		$this->assertTrue( $result['died'] );
		$this->assertFalse( $result['json']['success'] );
		$this->assertSame( array( 'Invalid template' ), $result['json']['data'] );
		$this->assertStringNotContainsString( '<article', $result['raw'] );
		$this->assertStringNotContainsString( 'pcrafts', $result['raw'], 'No template was included.' );
	}

	/**
	 * Template values that must be rejected.
	 *
	 * @return array
	 */
	public function data_invalid_templates() {
		return array(
			'missing'           => array( null ),
			'empty'             => array( '' ),
			'traversal'         => array( '../../../wp-config' ),
			'traversal in name' => array( 'post-grid/../../../../wp-config' ),
			'absolute path'     => array( '/etc/passwd' ),
			'other template'    => array( 'pagination' ),
			'arrow template'    => array( 'arrow' ),
			'with extension'    => array( 'post-grid.php' ),
			'block name'        => array( 'post-crafts/post-grid' ),
			'array'             => array( array( 'post-grid' ) ),
		);
	}

	/**
	 * Drafts and private posts never leak, even when asked for (and even to an admin).
	 *
	 * @covers-ac PAGP-22
	 * @dataProvider data_statuses
	 *
	 * @param mixed $status Requested post_status.
	 */
	public function test_only_published_posts( $status ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$hidden = array(
			self::factory()->post->create(
				array(
					'post_status' => 'draft',
					'post_date'   => '2025-01-01 10:00:00',
				)
			),
			self::factory()->post->create(
				array(
					'post_status' => 'private',
					'post_date'   => '2025-01-02 10:00:00',
				)
			),
		);

		$result = $this->request(
			$this->payload(
				array(
					'query' => array(
						'posts_per_page' => 20,
						'post_status'    => $status,
					),
				)
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$ids = $this->item_ids( $result['json']['data'] );
		$this->assertEqualsCanonicalizing( $this->posts, $ids );
		$this->assertEmpty( array_intersect( $hidden, $ids ) );
	}

	/**
	 * Requested statuses.
	 *
	 * @return array
	 */
	public function data_statuses() {
		return array(
			'draft'   => array( 'draft' ),
			'private' => array( 'private' ),
			'any'     => array( 'any' ),
			'list'    => array( array( 'draft', 'private', 'publish' ) ),
		);
	}

	/**
	 * Unknown query keys don't reach WP_Query; out-of-range values are clamped.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_request_query_is_sanitized() {
		$result = $this->request(
			$this->payload(
				array(
					'query' => array(
						'posts_per_page' => '1000',
						'post__in'       => array( (string) $this->posts[1], 'abc', '0', (string) $this->posts[3] ),
						'orderby'        => 'post__in',
						'meta_query'     => array( array( 'key' => 'nope' ) ),
						's'              => 'no match anywhere',
						'author'         => '999999',
					),
				)
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[1], $this->posts[3] ), $this->item_ids( $result['json']['data'] ) );
	}

	/**
	 * Logged-out visitors get the same response through the nopriv action.
	 *
	 * @covers-ac PAGP-23
	 */
	public function test_logged_out_request() {
		wp_set_current_user( 0 );
		$this->assertFalse( is_user_logged_in() );

		$result = $this->request( $this->payload(), true );

		$this->assertTrue( $result['died'] );
		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[0], $this->posts[1] ), $this->item_ids( $result['json']['data'] ) );
	}

	/**
	 * Attributes come from a block nested in core/group (and deeper).
	 *
	 * @covers-ac PAGP-24
	 * @dataProvider data_templates
	 *
	 * @param string $template Template (and block) name.
	 */
	public function test_nested_block_attributes( $template ) {
		$short   = $this->block(
			$template,
			array(
				'blockId'       => 'short123',
				'excerptLength' => 5,
			) 
		);
		$hidden  = $this->block(
			$template,
			array(
				'blockId' => 'noexc123',
				'excerpt' => false,
			) 
		);
		$columns = "<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column -->\n<div class=\"wp-block-column\">{$hidden}</div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->";
		$content = "<!-- wp:group -->\n<div class=\"wp-block-group\">{$short}\n{$columns}</div>\n<!-- /wp:group -->";
		$page_id = $this->page_with( $content );

		$result = $this->request(
			$this->payload(
				array(
					'postId'   => $page_id,
					'blockId'  => 'short123',
					'template' => $template,
				)
			)
		);
		$this->assertTrue( $result['json']['success'] );
		foreach ( $result['json']['data'] as $html ) {
			$this->assertSame( 5, $this->excerpt_words( $html ) );
		}

		$this->_last_response = '';
		$result               = $this->request(
			$this->payload(
				array(
					'postId'   => $page_id,
					'blockId'  => 'noexc123',
					'template' => $template,
				)
			)
		);
		$this->assertTrue( $result['json']['success'] );
		foreach ( $result['json']['data'] as $html ) {
			$this->assertNull( $this->excerpt_words( $html ), 'excerpt: false hides the excerpt.' );
		}
	}

	/**
	 * Attributes the saved block omits fall back to block.json defaults (excerpt on, 30 words).
	 *
	 * @covers-ac PAGP-25
	 * @dataProvider data_templates
	 *
	 * @param string $template Template (and block) name.
	 */
	public function test_block_json_defaults( $template ) {
		$page_id = $this->page_with( $this->block( $template, array( 'blockId' => 'dflt1234' ) ) );

		$result = $this->request(
			$this->payload(
				array(
					'postId'   => $page_id,
					'blockId'  => 'dflt1234',
					'template' => $template,
				)
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$this->assertNotEmpty( $result['json']['data'] );
		foreach ( $result['json']['data'] as $html ) {
			$this->assertSame( 30, $this->excerpt_words( $html ) );
			$this->assertStringContainsString( '&hellip;', $html );
		}
	}

	/**
	 * A block that can't be found falls back to the template block's defaults.
	 *
	 * @covers-ac PAGP-26
	 * @dataProvider data_missing_blocks
	 *
	 * @param string $content  Content of the post sent as postId ('' for postId 0).
	 * @param string $block_id Block ID sent.
	 * @param string $template Template.
	 */
	public function test_missing_block_uses_defaults( $content, $block_id, $template ) {
		$post_id = '' === $content ? 0 : $this->page_with( $content );

		$result = $this->request(
			$this->payload(
				array(
					'postId'   => $post_id,
					'blockId'  => $block_id,
					'template' => $template,
				)
			)
		);

		$this->assertTrue( $result['json']['success'] );
		$this->assertSame( array( $this->posts[0], $this->posts[1] ), $this->item_ids( $result['json']['data'] ) );
		foreach ( $result['json']['data'] as $html ) {
			$this->assertStringContainsString( 'class="' . $template . '"', $html );
			$this->assertSame( 30, $this->excerpt_words( $html ) );
		}
	}

	/**
	 * Requests whose block isn't in the post content.
	 *
	 * @return array
	 */
	public function data_missing_blocks() {
		$other_block = serialize_block(
			array(
				'blockName'    => 'post-crafts/post-grid',
				'attrs'        => array(
					'blockId'       => 'other123',
					'excerptLength' => 3,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);

		return array(
			'wrong block id'    => array( $other_block, 'missing1', 'post-grid' ),
			'empty block id'    => array( $other_block, '', 'post-list' ),
			'no blocks in post' => array( '<p>Classic content</p>', 'abc', 'post-list' ),
			'no post id'        => array( '', 'abc', 'post-grid' ),
		);
	}

	/**
	 * The guard for PAGP-27: notices, warnings and deprecations are reported, so PHPUnit turns them into failures.
	 *
	 * @covers-ac PAGP-27
	 */
	public function test_php_errors_are_reported() {
		$this->request( $this->payload() );
		$this->assertSame( E_ALL, $this->handler_error_level, 'The handler runs with every error level reported.' );

		$this->request( $this->payload(), true );
		$this->assertSame( E_ALL, $this->handler_error_level, 'So does the nopriv handler.' );

		$converted = false;
		error_reporting( E_ALL );
		try {
			trigger_error( 'PAGP-27 guard', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Proves warnings fail tests.
		} catch ( \PHPUnit\Framework\Error\Warning $e ) {
			$converted = true;
		}
		$this->assertTrue( $converted, 'PHP warnings are converted to test failures.' );
	}
}
