<?php
/**
 * Card templates: inc/templates/block-templates/post-grid.php and post-list.php.
 *
 * Criteria: tests/acceptance/rendering-php.md (RNDP-20 to RNDP-30).
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Loop item markup rendered by both blocks.
 */
class Test_Card_Templates extends TestCase {

	/**
	 * Removes files uploaded by thumbnail tests.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
	}

	/**
	 * Both blocks.
	 *
	 * @return array
	 */
	public function data_blocks() {
		return array(
			'post grid' => array( 'post-grid' ),
			'post list' => array( 'post-list' ),
		);
	}

	/**
	 * Renders a block that shows only the given post.
	 *
	 * @param string $block      Block name without namespace.
	 * @param int    $post_id    Post to show.
	 * @param array  $attributes Extra block attributes.
	 * @return string HTML.
	 */
	private function render_card( $block, $post_id, array $attributes = array() ) {
		return $this->render_block(
			$block,
			array_merge(
				array(
					'blockId'    => 'card0001',
					'postIds'    => array( $post_id ),
					'pagination' => false,
				),
				$attributes
			)
		);
	}

	/**
	 * Parses HTML for XPath queries.
	 *
	 * @param string $html HTML fragment.
	 * @return \DOMXPath
	 */
	private function xpath( $html ) {
		$previous = libxml_use_internal_errors( true );
		$doc      = new \DOMDocument();
		$doc->loadHTML( '<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return new \DOMXPath( $doc );
	}

	/**
	 * XPath predicate matching a class name.
	 *
	 * @param string $class_name Class name.
	 * @return string
	 */
	private function has_class( $class_name ) {
		return sprintf( 'contains(concat(" ", normalize-space(@class), " "), " %s ")', $class_name );
	}

	/**
	 * Creates a post with text and a category.
	 *
	 * @param array $args Extra post fields.
	 * @return int Post ID.
	 */
	private function create_card_post( array $args = array() ) {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_title'   => 'Card title',
					'post_content' => '<p>Card body text for the summary.</p>',
					'post_excerpt' => '',
				),
				$args
			)
		);
	}

	/**
	 * RNDP-20: the card is an article with the post ID and the block's item class.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_article_wrapper( $block ) {
		$post_id = $this->create_card_post();
		$xpath   = $this->xpath( $this->render_card( $block, $post_id ) );

		$articles = $xpath->query( '//article' );
		$this->assertSame( 1, $articles->length );
		$this->assertSame( 'post-' . $post_id, $articles->item( 0 )->getAttribute( 'id' ) );
		$this->assertSame( $block, $articles->item( 0 )->getAttribute( 'class' ) );
	}

	/**
	 * RNDP-21: category, title, meta, then summary inside .post-content.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_content_order( $block ) {
		$category = self::factory()->category->create( array( 'name' => 'News' ) );
		$post_id  = $this->create_card_post( array( 'post_category' => array( $category ) ) );
		$xpath    = $this->xpath( $this->render_card( $block, $post_id ) );

		$content = $xpath->query( '//article//div[' . $this->has_class( 'post-content' ) . ']' );
		$this->assertSame( 1, $content->length );
		$this->assertStringContainsString( $block . '-content', $content->item( 0 )->getAttribute( 'class' ) );

		$order = array();
		foreach ( $xpath->query( './*', $content->item( 0 ) ) as $child ) {
			$order[] = $child->getAttribute( 'class' );
		}

		$this->assertSame( array( 'cat-links', 'entry-title', 'entry-meta', 'entry-summary' ), $order );
	}

	/**
	 * RNDP-22: a real featured image renders an img inside figure.post-thumbnail > a.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_featured_image_renders_img( $block ) {
		$post_id       = $this->create_card_post();
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg', $post_id );
		set_post_thumbnail( $post_id, $attachment_id );

		$xpath = $this->xpath( $this->render_card( $block, $post_id ) );

		$images = $xpath->query( '//article/figure[' . $this->has_class( 'post-thumbnail' ) . ']/a/img' );
		$this->assertSame( 1, $images->length );
		$this->assertStringContainsString( 'size-thumb-330x185', $images->item( 0 )->getAttribute( 'class' ) );
		$this->assertSame( wp_get_attachment_image_url( $attachment_id, 'thumb-330x185' ), $images->item( 0 )->getAttribute( 'src' ) );
		$this->assertSame( 0, $xpath->query( '//*[' . $this->has_class( 'image-placeholder' ) . ']' )->length );
	}

	/**
	 * RNDP-23: without a featured image, a placeholder span is rendered instead.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_no_thumbnail_renders_placeholder( $block ) {
		$post_id = $this->create_card_post();
		$xpath   = $this->xpath( $this->render_card( $block, $post_id ) );

		$this->assertSame( 1, $xpath->query( '//article/figure[' . $this->has_class( 'post-thumbnail' ) . ']/a/span[' . $this->has_class( 'image-placeholder' ) . ']' )->length );
		$this->assertSame( 0, $xpath->query( '//img' )->length );
	}

	/**
	 * RNDP-24: a thumbnail ID with no image behind it renders the placeholder.
	 *
	 * @dataProvider data_broken_thumbnails
	 *
	 * @param string $block      Block name without namespace.
	 * @param string $meta_value Kind of broken _thumbnail_id value.
	 */
	public function test_broken_thumbnail_renders_placeholder( $block, $meta_value ) {
		$post_id = $this->create_card_post();

		switch ( $meta_value ) {
			case 'missing id':
				$value = PHP_INT_MAX;
				break;
			case 'non-attachment post':
				$value = self::factory()->post->create();
				break;
			default:
				$value = array( self::factory()->post->create(), 7 );
		}
		update_post_meta( $post_id, '_thumbnail_id', $value );

		$this->assertTrue( has_post_thumbnail( $post_id ), 'Precondition: core reports a thumbnail.' );

		$xpath = $this->xpath( $this->render_card( $block, $post_id ) );

		$this->assertSame( 1, $xpath->query( '//article/figure/a/span[' . $this->has_class( 'image-placeholder' ) . ']' )->length );
		$this->assertSame( 0, $xpath->query( '//img' )->length );
	}

	/**
	 * Blocks × broken _thumbnail_id values.
	 *
	 * @return array
	 */
	public function data_broken_thumbnails() {
		$data = array();
		foreach ( array( 'post-grid', 'post-list' ) as $block ) {
			foreach ( array( 'missing id', 'non-attachment post', 'serialized array' ) as $kind ) {
				$data[ "$block, $kind" ] = array( $block, $kind );
			}
		}
		return $data;
	}

	/**
	 * RNDP-25: excerpt off hides the summary.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_excerpt_off_hides_summary( $block ) {
		$post_id = $this->create_card_post();
		$html    = $this->render_card( $block, $post_id, array( 'excerpt' => false ) );

		$this->assertStringNotContainsString( 'entry-summary', $html );
		$this->assertStringNotContainsString( 'Card body text', $html );
	}

	/**
	 * RNDP-26: no empty summary element when the post has no text.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_no_empty_summary_for_textless_post( $block ) {
		$post_id = $this->create_card_post( array( 'post_content' => '' ) );
		$html    = $this->render_card( $block, $post_id );

		$this->assertStringContainsString( 'entry-title', $html, 'Precondition: the card rendered.' );
		$this->assertStringNotContainsString( 'entry-summary', $html );
	}

	/**
	 * RNDP-27: excerptLength sets the number of words.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_excerpt_length_is_respected( $block ) {
		$words   = implode( ' ', array_map( 'strval', range( 101, 160 ) ) );
		$post_id = $this->create_card_post( array( 'post_content' => '<p>' . $words . '</p>' ) );

		$xpath   = $this->xpath( $this->render_card( $block, $post_id, array( 'excerptLength' => 4 ) ) );
		$summary = $xpath->query( '//div[' . $this->has_class( 'entry-summary' ) . ']' );
		$this->assertSame( 1, $summary->length );
		$this->assertSame( '101 102 103 104…', trim( $summary->item( 0 )->textContent ) );

		// Unset: the block.json default of 30 words applies.
		$xpath   = $this->xpath( $this->render_card( $block, $post_id ) );
		$summary = trim( $xpath->query( '//div[' . $this->has_class( 'entry-summary' ) . ']' )->item( 0 )->textContent );
		$this->assertSame( implode( ' ', range( 101, 130 ) ) . '…', $summary );
	}

	/**
	 * RNDP-28: meta has the author link, a separator and the date.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_meta_line( $block ) {
		$author  = self::factory()->user->create(
			array(
				'role'         => 'author',
				'display_name' => 'Ada Author',
			)
		);
		$post_id = $this->create_card_post(
			array(
				'post_author' => $author,
				'post_date'   => '2024-03-05 10:00:00',
			)
		);

		$xpath = $this->xpath( $this->render_card( $block, $post_id ) );
		$meta  = $xpath->query( '//div[' . $this->has_class( 'entry-meta' ) . ']' );
		$this->assertSame( 1, $meta->length );

		$children = array();
		foreach ( $xpath->query( './*', $meta->item( 0 ) ) as $child ) {
			$children[] = $child->getAttribute( 'class' );
		}
		$this->assertSame( array( 'byline', 'separator', 'posted-on' ), $children );

		$link = $xpath->query( './/span[' . $this->has_class( 'byline' ) . ']//a', $meta->item( 0 ) );
		$this->assertSame( 1, $link->length );
		$this->assertSame( get_author_posts_url( $author ), $link->item( 0 )->getAttribute( 'href' ) );
		$this->assertSame( 'Ada Author', $link->item( 0 )->textContent );

		$time = $xpath->query( './/span[' . $this->has_class( 'posted-on' ) . ']/time[' . $this->has_class( 'entry-date' ) . ']', $meta->item( 0 ) );
		$this->assertSame( 1, $time->length );
		$this->assertSame( get_the_date( DATE_W3C, $post_id ), $time->item( 0 )->getAttribute( 'datetime' ) );
		$this->assertSame( 'March 05, 2024', $time->item( 0 )->textContent );
	}

	/**
	 * RNDP-29: the post's category links to its archive.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_category_link( $block ) {
		$category = self::factory()->category->create( array( 'name' => 'Science' ) );
		$post_id  = $this->create_card_post( array( 'post_category' => array( $category ) ) );

		$xpath = $this->xpath( $this->render_card( $block, $post_id ) );
		$link  = $xpath->query( '//span[' . $this->has_class( 'cat-links' ) . ']/a' );

		$this->assertSame( 1, $link->length );
		$this->assertSame( get_category_link( $category ), $link->item( 0 )->getAttribute( 'href' ) );
		$this->assertSame( 'Science', $link->item( 0 )->textContent );
	}

	/**
	 * RNDP-30: called without excerpt variables, the template shows the excerpt.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_template_defaults_show_excerpt( $block ) {
		$post_id = $this->create_card_post();

		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The template reads the global post.
		setup_postdata( $GLOBALS['post'] );

		$html = post_crafts_template( 'block-templates/' . $block );

		wp_reset_postdata();

		$this->assertStringContainsString( 'id="post-' . $post_id . '"', $html );
		$this->assertStringContainsString( '<div class="entry-summary"><p>Card body text for the summary.</p>', $html );
	}
}
