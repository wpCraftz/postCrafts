<?php
/**
 * Excerpt helpers: post_crafts_get_excerpt_source() and post_crafts_excerpt_length().
 *
 * Criteria: tests/acceptance/rendering-php.md (RNDP-01 to RNDP-13).
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Excerpt source and trimming.
 */
class Test_Excerpt extends TestCase {

	/**
	 * Restores state changed by individual tests.
	 */
	public function tear_down() {
		remove_shortcode( 'pcrafts_test' );
		parent::tear_down();
	}

	/**
	 * Creates a published post with the given content.
	 *
	 * @param string $content Post content.
	 * @param array  $args    Extra post fields.
	 * @return int Post ID.
	 */
	private function post_with_content( $content, array $args = array() ) {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_content' => $content,
					'post_excerpt' => '',
				),
				$args
			)
		);
	}

	/**
	 * A numbered word list: "w1 w2 … wN".
	 *
	 * @param int $count Number of words.
	 * @return string
	 */
	private function words( $count ) {
		return implode(
			' ',
			array_map(
				function ( $i ) {
					return 'w' . $i;
				},
				range( 1, $count )
			)
		);
	}

	/**
	 * RNDP-01: a hand-written excerpt is used instead of the content.
	 */
	public function test_manual_excerpt_wins_over_content() {
		$post_id = $this->post_with_content(
			'<p>Content words only</p>',
			array( 'post_excerpt' => 'Manual summary here' )
		);

		$source = post_crafts_get_excerpt_source( $post_id );

		$this->assertSame( 'Manual summary here', $source );
		$this->assertStringNotContainsString( 'Content', $source );
	}

	/**
	 * RNDP-02: words touching across block-level tags are separated by one space.
	 *
	 * @dataProvider data_block_level_boundaries
	 *
	 * @param string $content  Post content.
	 * @param string $expected Expected source text.
	 */
	public function test_block_level_tags_separate_words( $content, $expected ) {
		$post_id = $this->post_with_content( $content );

		$this->assertSame( $expected, post_crafts_get_excerpt_source( $post_id ) );
	}

	/**
	 * Content with block-level boundaries and the expected plain text.
	 *
	 * @return array
	 */
	public function data_block_level_boundaries() {
		return array(
			'paragraphs'         => array( '<p>totam</p><p>Porro</p>', 'totam Porro' ),
			'divs'               => array( '<div>alpha</div><div>beta</div>', 'alpha beta' ),
			'list items'         => array( '<ul><li>one</li><li>two</li></ul>three', 'one two three' ),
			'headings'           => array( '<h2>Title</h2><p>Body</p><h3>Sub</h3>text', 'Title Body Sub text' ),
			'line breaks'        => array( 'first<br>second<br />third', 'first second third' ),
			'figure and caption' => array( 'before<figure>inside<figcaption>caption</figcaption></figure>after', 'before inside caption after' ),
			'blockquote'         => array( 'said<blockquote>quoted</blockquote>done', 'said quoted done' ),
			'table cells'        => array( '<table><tr><td>a1</td><td>b1</td></tr><tr><th>a2</th><td>b2</td></tr></table>', 'a1 b1 a2 b2' ),
			'paragraph classes'  => array( '<p class="x">end.</p><p class="y">Start</p>', 'end. Start' ),
		);
	}

	/**
	 * RNDP-03: paragraphs inside group and columns blocks keep their text, separated.
	 */
	public function test_nested_layout_blocks_keep_text_separated() {
		$content = '<!-- wp:group --><div class="wp-block-group">'
			. '<!-- wp:paragraph --><p>groupone</p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph --><p>grouptwo</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:columns --><div class="wp-block-columns">'
			. '<!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>colone</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
			. '<!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>coltwo</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
			. '</div><!-- /wp:columns -->';

		$post_id = $this->post_with_content( $content );

		$this->assertSame( 'groupone grouptwo colone coltwo', post_crafts_get_excerpt_source( $post_id ) );
	}

	/**
	 * RNDP-04: inline tags inside a word don't split it.
	 */
	public function test_inline_tags_do_not_split_words() {
		$post_id = $this->post_with_content( '<p>al<strong>pha</strong> <em>be</em>ta <a href="https://example.org">gam</a>ma <span>del</span>ta</p>' );

		$this->assertSame( 'alpha beta gamma delta', post_crafts_get_excerpt_source( $post_id ) );
	}

	/**
	 * RNDP-05: registered shortcodes and their output are stripped.
	 */
	public function test_shortcodes_are_stripped() {
		add_shortcode(
			'pcrafts_test',
			function () {
				return 'SHORTCODEOUTPUT';
			}
		);

		$post_id = $this->post_with_content( '<p>Hello [pcrafts_test /] world [pcrafts_test foo="bar"]inner[/pcrafts_test] end</p>' );
		$source  = post_crafts_get_excerpt_source( $post_id );

		$this->assertSame( 'Hello world end', $source );
		$this->assertStringNotContainsString( 'SHORTCODEOUTPUT', $source );
		$this->assertStringNotContainsString( '[', $source );
	}

	/**
	 * RNDP-06: an image block (and its caption) is removed; paragraphs remain.
	 */
	public function test_image_block_is_removed() {
		$content = '<!-- wp:paragraph --><p>Before</p><!-- /wp:paragraph -->'
			. '<!-- wp:image {"id":123} --><figure class="wp-block-image"><img src="https://example.org/a.jpg" alt="alttext"/><figcaption class="wp-element-caption">Caption words</figcaption></figure><!-- /wp:image -->'
			. '<!-- wp:paragraph --><p>After</p><!-- /wp:paragraph -->';

		$post_id = $this->post_with_content( $content );
		$source  = post_crafts_get_excerpt_source( $post_id );

		$this->assertSame( 'Before After', $source );
		$this->assertStringNotContainsString( 'Caption', $source );
		$this->assertStringNotContainsString( 'alttext', $source );
	}

	/**
	 * RNDP-07: a PostCrafts block in the content isn't rendered into the excerpt.
	 */
	public function test_postcrafts_block_in_content_is_not_rendered() {
		self::factory()->post->create(
			array(
				'post_title'   => 'OtherPostTitle',
				'post_content' => 'OtherPostBody',
			)
		);

		$content = $this->block_markup( 'post-grid', array( 'blockId' => 'nested01' ) )
			. '<!-- wp:paragraph --><p>Own text</p><!-- /wp:paragraph -->';
		$post_id = $this->post_with_content( $content );

		$source = post_crafts_get_excerpt_source( $post_id );

		$this->assertSame( 'Own text', $source );
		$this->assertStringNotContainsString( 'OtherPost', $source );
	}

	/**
	 * RNDP-08: whitespace runs collapse to single spaces and the ends are trimmed.
	 */
	public function test_whitespace_is_collapsed() {
		$post_id = $this->post_with_content( "  \n\tone\n\ntwo \t  three\r\n  " );

		$this->assertSame( 'one two three', post_crafts_get_excerpt_source( $post_id ) );
	}

	/**
	 * RNDP-09: a password-protected post exposes no text.
	 */
	public function test_password_protected_post_returns_empty() {
		$post_id = $this->post_with_content(
			'<p>Secret content words</p>',
			array(
				'post_password' => 'pass',
				'post_excerpt'  => 'Secret excerpt',
			)
		);

		$this->assertSame( '', post_crafts_get_excerpt_source( $post_id ) );
		$this->assertSame( '', post_crafts_excerpt_length( $post_id, 10 ) );
	}

	/**
	 * RNDP-10: the text is trimmed to N words followed by an ellipsis.
	 */
	public function test_trims_to_word_count_with_ellipsis() {
		$post_id = $this->post_with_content( '<p>' . $this->words( 20 ) . '</p>' );

		$excerpt = post_crafts_excerpt_length( $post_id, 5 );

		$this->assertSame( "<p>w1 w2 w3 w4 w5&hellip;</p>\n", $excerpt );
	}

	/**
	 * RNDP-10: text at or under the limit isn't given an ellipsis.
	 */
	public function test_short_text_has_no_ellipsis() {
		$post_id = $this->post_with_content( '<p>' . $this->words( 5 ) . '</p>' );

		$excerpt = post_crafts_excerpt_length( $post_id, 5 );

		$this->assertStringContainsString( 'w1 w2 w3 w4 w5', $excerpt );
		$this->assertStringNotContainsString( '&hellip;', $excerpt );
		$this->assertStringNotContainsString( '…', $excerpt );
	}

	/**
	 * RNDP-11: posts without text give an empty string, not an empty paragraph.
	 *
	 * @dataProvider data_textless_content
	 *
	 * @param string $content Post content.
	 */
	public function test_empty_text_returns_empty_string( $content ) {
		$post_id = $this->post_with_content( $content );

		$this->assertSame( '', post_crafts_get_excerpt_source( $post_id ) );
		$this->assertSame( '', post_crafts_excerpt_length( $post_id, 10 ) );
	}

	/**
	 * Content that has no excerpt text.
	 *
	 * @return array
	 */
	public function data_textless_content() {
		return array(
			'empty'            => array( '' ),
			'whitespace'       => array( "  \n\t " ),
			'image block only' => array( '<!-- wp:image --><figure class="wp-block-image"><img src="https://example.org/a.jpg" alt=""/></figure><!-- /wp:image -->' ),
			'empty paragraph'  => array( '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->' ),
		);
	}

	/**
	 * Missing posts give an empty string.
	 */
	public function test_missing_post_returns_empty_string() {
		$this->assertSame( '', post_crafts_get_excerpt_source( PHP_INT_MAX ) );
		$this->assertSame( '', post_crafts_excerpt_length( PHP_INT_MAX, 10 ) );
	}

	/**
	 * RNDP-12: the given post is used, not the global post, and output is the_excerpt markup.
	 */
	public function test_uses_given_post_not_global_post() {
		$global_id = $this->post_with_content( '<p>Global post text</p>' );
		$target_id = $this->post_with_content( '<p>Target post text</p>' );

		$GLOBALS['post'] = get_post( $global_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test sets up a different global post.
		setup_postdata( $GLOBALS['post'] );

		$excerpt = post_crafts_excerpt_length( $target_id, 10 );

		$this->assertSame( "<p>Target post text</p>\n", $excerpt );
		$this->assertSame( 'Target post text', post_crafts_get_excerpt_source( $target_id ) );
		$this->assertSame( 'Target post text', post_crafts_get_excerpt_source( get_post( $target_id ) ) );

		wp_reset_postdata();
	}

	/**
	 * RNDP-13: the plugin doesn't change the site-wide excerpt length or "more" string.
	 */
	public function test_no_site_wide_excerpt_filters() {
		$this->assertSame( 55, apply_filters( 'excerpt_length', 55 ) );
		$this->assertSame( ' [&hellip;]', apply_filters( 'excerpt_more', ' [&hellip;]' ) );

		$post_id = $this->post_with_content( '<p>' . $this->words( 80 ) . '</p>' );
		$core    = get_the_excerpt( $post_id );

		$this->assertStringContainsString( 'w55', $core );
		$this->assertStringNotContainsString( 'w56', $core );
		$this->assertStringEndsWith( '[&hellip;]', $core );
	}
}
