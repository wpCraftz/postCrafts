<?php
/**
 * Tests for the pagination templates (numeric, arrow, load more) and their output from the blocks.
 *
 * Acceptance criteria: tests/acceptance/pagination-php.md (PAGP-11 to PAGP-16).
 *
 * @package post-crafts
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMDocument API property names.

namespace PostCrafts\Blocks\Tests;

/**
 * Pagination template markup.
 */
class Test_Pagination_Templates extends TestCase {

	/**
	 * Page that "contains" the block while a template renders.
	 *
	 * @var int
	 */
	private $page_id;

	/**
	 * Views a page, so get_the_ID() inside the templates (and after wp_reset_postdata()) returns it.
	 */
	public function set_up() {
		parent::set_up();

		$this->page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $this->page_id ) );
		$GLOBALS['post'] = get_post( $this->page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulates the main loop on the page.
	}

	/**
	 * Resets the global post.
	 */
	public function tear_down() {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Loads markup into an XPath object.
	 *
	 * @param string $html HTML fragment.
	 * @return \DOMXPath
	 */
	private function xpath( $html ) {
		$previous = libxml_use_internal_errors( true );
		$dom      = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?><div id="root">' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return new \DOMXPath( $dom );
	}

	/**
	 * The single .pcrafts-pagination wrapper in the markup.
	 *
	 * @param \DOMXPath $xpath Parsed markup.
	 * @return \DOMElement
	 */
	private function wrapper( \DOMXPath $xpath ) {
		$wrappers = $xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " pcrafts-pagination ")]' );
		$this->assertSame( 1, $wrappers->length, 'Exactly one pagination wrapper.' );

		return $wrappers->item( 0 );
	}

	/**
	 * Class names of an element.
	 *
	 * @param \DOMElement $element Element.
	 * @return string[]
	 */
	private function classes( \DOMElement $element ) {
		return preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) );
	}

	/**
	 * Template variables as render.php passes them.
	 *
	 * @param array $overrides Values to change.
	 * @return array
	 */
	private function variables( array $overrides = array() ) {
		return array_merge(
			array(
				'post_query'     => array(
					'posts_per_page' => 3,
					'post_status'    => 'publish',
					'paged'          => 1,
					'order'          => 'desc',
					'orderby'        => 'date',
					'category__in'   => array( 4, 7 ),
				),
				'max_page'       => 5,
				'loop_template'  => 'post-list',
				'posts_per_page' => 3,
				'alignment'      => 'center',
				'block_id'       => 'abc12345',
			),
			$overrides
		);
	}

	/**
	 * Asserts the data-* attributes every pagination type shares.
	 *
	 * @param \DOMElement $wrapper   Wrapper element.
	 * @param array       $variables Template variables.
	 * @param string      $page      Expected data-page.
	 */
	private function assert_common_data( \DOMElement $wrapper, array $variables, $page ) {
		$this->assertSame( $variables['post_query'], json_decode( $wrapper->getAttribute( 'data-query' ), true ) );
		$this->assertSame( $page, $wrapper->getAttribute( 'data-page' ) );
		$this->assertSame( (string) $variables['max_page'], $wrapper->getAttribute( 'data-max-page' ) );
		$this->assertSame( (string) $this->page_id, $wrapper->getAttribute( 'data-post-id' ) );
		$this->assertSame( $variables['block_id'], $wrapper->getAttribute( 'data-block-id' ) );
		$this->assertSame( $variables['loop_template'], $wrapper->getAttribute( 'data-template' ) );
	}

	/**
	 * Numeric wrapper: classes, data-* attributes and the page list.
	 *
	 * @covers-ac PAGP-11
	 */
	public function test_numeric_wrapper() {
		$variables = $this->variables( array( 'current_page' => 2 ) );
		$xpath     = $this->xpath( post_crafts_template( 'block-templates/pagination', $variables ) );
		$wrapper   = $this->wrapper( $xpath );

		$this->assertSame( array( 'pcrafts-pagination', 'pcrafts-numberic', 'center' ), $this->classes( $wrapper ) );
		$this->assert_common_data( $wrapper, $variables, '2' );
		$this->assertFalse( $wrapper->hasAttribute( 'data-posts-per-page' ) );

		$list = $xpath->query( './ul[@class="pcrafts-pages"]', $wrapper );
		$this->assertSame( 1, $list->length );
		$this->assertSame( 1, $xpath->query( './ul/li[contains(@class, "current")][@data-page="2"]', $wrapper )->length );
		$this->assertSame( 1, $xpath->query( './ul/li[contains(@class, "last-page")][@data-page="5"]', $wrapper )->length );
	}

	/**
	 * Numeric wrapper is a navigation landmark only when it has pages.
	 *
	 * @covers-ac PAGP-12
	 */
	public function test_numeric_landmark() {
		$wrapper = $this->wrapper( $this->xpath( post_crafts_template( 'block-templates/pagination', $this->variables() ) ) );
		$this->assertSame( 'navigation', $wrapper->getAttribute( 'role' ) );
		$this->assertSame( 'Posts pagination', $wrapper->getAttribute( 'aria-label' ) );

		$xpath   = $this->xpath( post_crafts_template( 'block-templates/pagination', $this->variables( array( 'max_page' => 1 ) ) ) );
		$wrapper = $this->wrapper( $xpath );
		$this->assertFalse( $wrapper->hasAttribute( 'role' ) );
		$this->assertFalse( $wrapper->hasAttribute( 'aria-label' ) );
		$this->assertSame( 0, $xpath->query( '//ul|//button' )->length );
		$this->assertSame( '1', $wrapper->getAttribute( 'data-max-page' ), 'data-* attributes stay for the script.' );
	}

	/**
	 * Arrow wrapper and buttons.
	 *
	 * @covers-ac PAGP-13
	 */
	public function test_arrow_template() {
		$variables = $this->variables();
		$xpath     = $this->xpath( post_crafts_template( 'block-templates/arrow', $variables ) );
		$wrapper   = $this->wrapper( $xpath );

		$this->assertSame( array( 'pcrafts-pagination', 'pcrafts-arrow', 'center' ), $this->classes( $wrapper ) );
		$this->assertSame( 'navigation', $wrapper->getAttribute( 'role' ) );
		$this->assertSame( 'Posts pagination', $wrapper->getAttribute( 'aria-label' ) );
		$this->assert_common_data( $wrapper, $variables, '1' );

		$buttons = $xpath->query( './button', $wrapper );
		$this->assertSame( 2, $buttons->length );

		$prev = $buttons->item( 0 );
		$next = $buttons->item( 1 );

		$this->assertContains( 'pcrafts-prev', $this->classes( $prev ) );
		$this->assertSame( 'button', $prev->getAttribute( 'type' ) );
		$this->assertSame( 'Previous page', $prev->getAttribute( 'aria-label' ) );
		$this->assertTrue( $prev->hasAttribute( 'disabled' ) );
		$this->assertContains( 'disabled', $this->classes( $prev ) );

		$this->assertContains( 'pcrafts-next', $this->classes( $next ) );
		$this->assertSame( 'button', $next->getAttribute( 'type' ) );
		$this->assertSame( 'Next page', $next->getAttribute( 'aria-label' ) );
		$this->assertFalse( $next->hasAttribute( 'disabled' ) );
		$this->assertNotContains( 'disabled', $this->classes( $next ) );

		foreach ( $xpath->query( './button/svg', $wrapper ) as $svg ) {
			$this->assertSame( 'true', $svg->getAttribute( 'aria-hidden' ) );
		}
		$this->assertSame( 2, $xpath->query( './button/svg', $wrapper )->length );

		// A single page: Next is disabled too.
		$xpath = $this->xpath( post_crafts_template( 'block-templates/arrow', $this->variables( array( 'max_page' => 1 ) ) ) );
		$next  = $xpath->query( '//button[contains(@class, "pcrafts-next")]' )->item( 0 );
		$this->assertTrue( $next->hasAttribute( 'disabled' ) );
		$this->assertContains( 'disabled', $this->classes( $next ) );
	}

	/**
	 * Load More wrapper and button.
	 *
	 * @covers-ac PAGP-14
	 */
	public function test_loadmore_template() {
		$variables = $this->variables();
		$xpath     = $this->xpath( post_crafts_template( 'block-templates/loadmore', $variables ) );
		$wrapper   = $this->wrapper( $xpath );

		$this->assertSame( array( 'pcrafts-pagination', 'pcrafts-loadmore', 'center' ), $this->classes( $wrapper ) );
		$this->assert_common_data( $wrapper, $variables, '1' );
		$this->assertSame( '3', $wrapper->getAttribute( 'data-posts-per-page' ) );

		$buttons = $xpath->query( './button', $wrapper );
		$this->assertSame( 1, $buttons->length );

		$button = $buttons->item( 0 );
		$this->assertSame( array( 'pcrafts-loadmore-btn' ), $this->classes( $button ) );
		$this->assertSame( 'button', $button->getAttribute( 'type' ) );
		$this->assertSame( 'Load More', trim( $button->textContent ) );
		$this->assertFalse( $button->hasAttribute( 'disabled' ) );

		foreach ( array( 1, 0 ) as $max_page ) {
			$xpath  = $this->xpath( post_crafts_template( 'block-templates/loadmore', $this->variables( array( 'max_page' => $max_page ) ) ) );
			$button = $xpath->query( '//button' )->item( 0 );

			$this->assertTrue( $button->hasAttribute( 'disabled' ), "max_page $max_page" );
			$this->assertContains( 'disabled', $this->classes( $button ), "max_page $max_page" );
		}
	}

	/**
	 * Templates render with defaults when no variables are passed.
	 *
	 * @covers-ac PAGP-15
	 * @dataProvider data_templates
	 *
	 * @param string $template Template name.
	 * @param string $type     Type class.
	 */
	public function test_template_defaults( $template, $type ) {
		$wrapper = $this->wrapper( $this->xpath( post_crafts_template( 'block-templates/' . $template ) ) );

		$this->assertSame( array( 'pcrafts-pagination', $type, 'left' ), $this->classes( $wrapper ) );
		$this->assertSame( 'post-grid', $wrapper->getAttribute( 'data-template' ) );
		$this->assertSame( '1', $wrapper->getAttribute( 'data-max-page' ) );
		$this->assertSame( '1', $wrapper->getAttribute( 'data-page' ) );
		$this->assertSame( '[]', $wrapper->getAttribute( 'data-query' ) );
		$this->assertSame( '', $wrapper->getAttribute( 'data-block-id' ) );
	}

	/**
	 * Template names and their type classes.
	 *
	 * @return array
	 */
	public function data_templates() {
		return array(
			'numeric'   => array( 'pagination', 'pcrafts-numberic' ),
			'arrow'     => array( 'arrow', 'pcrafts-arrow' ),
			'load more' => array( 'loadmore', 'pcrafts-loadmore' ),
		);
	}

	/**
	 * Rendered blocks output the matching pagination template with the block's data.
	 *
	 * @covers-ac PAGP-16
	 * @dataProvider data_block_pagination
	 *
	 * @param string $block Block name without namespace.
	 * @param string $type  paginationType attribute.
	 * @param string $type_class Type class on the wrapper.
	 */
	public function test_block_outputs_pagination( $block, $type, $type_class ) {
		self::factory()->post->create_many( 7 );

		$attributes = array(
			'blockId'             => 'blk' . substr( md5( $block . $type ), 0, 5 ),
			'postsPerPage'        => 3,
			'pagination'          => true,
			'paginationType'      => $type,
			'paginationAlignment' => 'right',
		);

		$xpath   = $this->xpath( $this->render_block( $block, $attributes ) );
		$wrapper = $this->wrapper( $xpath );

		$this->assertSame( array( 'pcrafts-pagination', $type_class, 'right' ), $this->classes( $wrapper ) );
		$this->assertSame( $block, $wrapper->getAttribute( 'data-template' ) );
		$this->assertSame( $attributes['blockId'], $wrapper->getAttribute( 'data-block-id' ) );
		$this->assertSame( '3', $wrapper->getAttribute( 'data-max-page' ) );
		$this->assertSame( '1', $wrapper->getAttribute( 'data-page' ) );
		$this->assertSame( (string) $this->page_id, $wrapper->getAttribute( 'data-post-id' ) );

		$prepared = \WP_Block_Type_Registry::get_instance()
			->get_registered( 'post-crafts/' . $block )
			->prepare_attributes_for_render( $attributes );
		$this->assertSame( post_crafts_query_builder( $prepared ), json_decode( $wrapper->getAttribute( 'data-query' ), true ) );

		if ( 'loadmore' === $type ) {
			$this->assertSame( '3', $wrapper->getAttribute( 'data-posts-per-page' ) );
		} else {
			$this->assertFalse( $wrapper->hasAttribute( 'data-posts-per-page' ) );
		}

		// The pagination follows the posts wrapper inside the block wrapper.
		$block_wrapper = $wrapper->parentNode;
		$this->assertContains( 'pcrafts-block-' . $attributes['blockId'], $this->classes( $block_wrapper ) );
		$this->assertSame( 1, $xpath->query( 'preceding-sibling::div[contains(@class, "pcrafts-posts-wrapper")]', $wrapper )->length );
		$this->assertSame( 3, $xpath->query( '//article' )->length );

		// The backward-compatible `.pcrafts-pages li` structure for numeric pagination.
		if ( 'pagination' === $type ) {
			$this->assertGreaterThan( 0, $xpath->query( './ul[@class="pcrafts-pages"]/li', $wrapper )->length );
		}
	}

	/**
	 * Block × pagination type.
	 *
	 * @return array
	 */
	public function data_block_pagination() {
		$data = array();

		foreach ( array( 'post-grid', 'post-list' ) as $block ) {
			foreach ( array(
				'pagination' => 'pcrafts-numberic',
				'arrow'      => 'pcrafts-arrow',
				'loadmore'   => 'pcrafts-loadmore',
			) as $type => $class ) {
				$data[ "$block $type" ] = array( $block, $type, $class );
			}
		}

		return $data;
	}

	/**
	 * No pagination wrapper when the block has pagination off.
	 *
	 * @covers-ac PAGP-16
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_block_without_pagination( $block ) {
		self::factory()->post->create_many( 4 );

		$html = $this->render_block(
			$block,
			array(
				'blockId'      => 'nopag123',
				'postsPerPage' => 2,
				'pagination'   => false,
			)
		);

		$this->assertStringNotContainsString( 'pcrafts-pagination', $html );
		$this->assertSame( 2, substr_count( $html, '<article' ) );
	}

	/**
	 * Block names.
	 *
	 * @return array
	 */
	public function data_blocks() {
		return array(
			'post grid' => array( 'post-grid' ),
			'post list' => array( 'post-list' ),
		);
	}
}
