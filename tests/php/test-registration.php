<?php
/**
 * Block registration, new attributes, backward compatibility and the removed admin page.
 *
 * Criteria: tests/acceptance/rendering-php.md (RNDP-60 to RNDP-66).
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Registration and admin.
 */
class Test_Registration extends TestCase {

	/**
	 * Both blocks with their name-specific keyword.
	 *
	 * @return array
	 */
	public function data_blocks() {
		return array(
			'post grid' => array( 'post-grid', 'post grid' ),
			'post list' => array( 'post-list', 'post list' ),
		);
	}

	/**
	 * Registered block type.
	 *
	 * @param string $block Block name without namespace.
	 * @return \WP_Block_Type
	 */
	private function block_type( $block ) {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'post-crafts/' . $block );
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );

		return $block_type;
	}

	/**
	 * RNDP-60: no "post-crafts" admin page is registered.
	 */
	public function test_no_admin_menu_page() {
		global $menu, $submenu, $_registered_pages, $_parent_pages;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Fresh admin menu globals for this test.
		$menu              = array();
		$submenu           = array();
		$_registered_pages = array();
		$_parent_pages     = array();
		// phpcs:enable

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'dashboard' );

		do_action( 'admin_menu' );

		$slugs = wp_list_pluck( $menu, 2 );
		foreach ( $submenu as $items ) {
			$slugs = array_merge( $slugs, wp_list_pluck( $items, 2 ) );
		}

		$this->assertNotContains( 'post-crafts', $slugs );
		$this->assertSame( '', menu_page_url( 'post-crafts', false ) );
		$this->assertArrayNotHasKey( get_plugin_page_hookname( 'post-crafts', '' ), $_registered_pages );
		$this->assertArrayNotHasKey( 'post-crafts', $_parent_pages );

		set_current_screen( 'front' );
	}

	/**
	 * RNDP-61: the Admin class is gone.
	 */
	public function test_admin_class_removed() {
		$this->assertFalse( class_exists( '\PostCrafts\Blocks\Inc\Admin' ) );
	}

	/**
	 * RNDP-62: new optional attributes exist with the right type and no default.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_new_attributes_have_no_defaults( $block ) {
		$block_type = $this->block_type( $block );
		$expected   = array(
			'paginationBeforeSelection' => 'boolean',
			'categoryColor'             => 'string',
			'titleColor'                => 'string',
			'titleHoverColor'           => 'string',
			'metaColor'                 => 'string',
			'excerptColor'              => 'string',
			'titleFontSize'             => 'object',
		);

		$prepared = $block_type->prepare_attributes_for_render( array() );

		foreach ( $expected as $name => $type ) {
			$this->assertArrayHasKey( $name, $block_type->attributes, "$name is declared" );
			$this->assertSame( $type, $block_type->attributes[ $name ]['type'], "$name type" );
			$this->assertArrayNotHasKey( 'default', $block_type->attributes[ $name ], "$name has no default" );
			$this->assertArrayNotHasKey( $name, $prepared, "$name is not set on render" );
		}
	}

	/**
	 * RNDP-63: excerpt defaults to boolean true.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_excerpt_default_is_boolean_true( $block ) {
		$block_type = $this->block_type( $block );

		$this->assertSame( 'boolean', $block_type->attributes['excerpt']['type'] );
		$this->assertTrue( $block_type->attributes['excerpt']['default'] );
		$this->assertTrue( $block_type->prepare_attributes_for_render( array() )['excerpt'] );
	}

	/**
	 * RNDP-64: keywords include the plugin name and the block name.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block   Block name without namespace.
	 * @param string $keyword Block-specific keyword.
	 */
	public function test_keywords( $block, $keyword ) {
		$keywords = $this->block_type( $block )->keywords;

		$this->assertContains( 'postcrafts', $keywords );
		$this->assertContains( $keyword, $keywords );
	}

	/**
	 * RNDP-65: a block saved before the new attributes existed renders as before.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_block_with_old_attributes_renders( $block ) {
		$ids = self::factory()->post->create_many(
			3,
			array(
				'post_content' => '<p>Legacy body words here</p>',
				'post_excerpt' => '',
			)
		);

		$html = $this->render_block(
			$block,
			array(
				'blockId'       => 'legacy01',
				'postsPerPage'  => 2,
				'pagination'    => false,
				'excerptLength' => 2,
				'sorting'       => array(
					'order'   => 'asc',
					'orderBy' => 'date',
				),
			)
		);

		$this->assertStringContainsString( 'pcrafts-block-legacy01', $html );
		$this->assertSame( 2, substr_count( $html, '<article' ) );
		$this->assertSame( 2, substr_count( $html, '<div class="entry-summary"><p>Legacy body&hellip;</p>' ) );
		$this->assertStringNotContainsString( 'pcrafts-pagination', $html );
		$this->assertMatchesRegularExpression( '/id="post-(' . implode( '|', $ids ) . ')"/', $html );
	}

	/**
	 * RNDP-66: a legacy string "true" excerpt value still shows excerpts.
	 *
	 * @dataProvider data_blocks
	 *
	 * @param string $block Block name without namespace.
	 */
	public function test_legacy_string_excerpt_shows_excerpt( $block ) {
		self::factory()->post->create(
			array(
				'post_content' => '<p>Legacy string excerpt</p>',
				'post_excerpt' => '',
			)
		);

		$html = $this->render_block(
			$block,
			array(
				'blockId'    => 'legacy02',
				'pagination' => false,
				'excerpt'    => 'true',
			)
		);

		$this->assertStringContainsString( '<div class="entry-summary"><p>Legacy string excerpt</p>', $html );
	}
}
