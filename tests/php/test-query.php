<?php
/**
 * Smoke tests: the database (SQLite) and the front-end query/render path work.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Tests;

/**
 * Query and render smoke tests.
 */
class Test_Query extends TestCase {

	/**
	 * Posts created with the factory are readable through WP_Query.
	 */
	public function test_factory_posts_are_queryable() {
		$post_ids = self::factory()->post->create_many( 3 );

		$query = new \WP_Query(
			array(
				'post_type' => 'post',
				'fields'    => 'ids',
				'orderby'   => 'ID',
				'order'     => 'ASC',
			)
		);

		$this->assertSame( $post_ids, array_map( 'intval', $query->posts ) );
	}

	/**
	 * The query builder turns block attributes into WP_Query args, including a category filter.
	 */
	public function test_query_builder_filters_by_category() {
		$category = self::factory()->category->create();
		$in_cat   = self::factory()->post->create_many( 2, array( 'post_category' => array( $category ) ) );
		self::factory()->post->create_many( 2 );

		$attributes = \WP_Block_Type_Registry::get_instance()
			->get_registered( 'post-crafts/post-grid' )
			->prepare_attributes_for_render( array( 'taxQuery' => array( 'category' => array( $category ) ) ) );

		$args = post_crafts_query_builder( $attributes );
		$this->assertSame( array( $category ), $args['category__in'] );

		$query = new \WP_Query( array_merge( $args, array( 'fields' => 'ids' ) ) );
		$this->assertEqualsCanonicalizing( $in_cat, array_map( 'intval', $query->posts ) );
	}

	/**
	 * The dynamic block renders one item per post, capped at postsPerPage.
	 *
	 * @dataProvider data_loop_markup
	 *
	 * @param string $block      Block name without namespace.
	 * @param string $item_class Class on each rendered item.
	 */
	public function test_block_renders_posts( $block, $item_class ) {
		self::factory()->post->create_many( 3 );

		$html = $this->render_block(
			$block,
			array(
				'blockId'      => 'smoke123',
				'postsPerPage' => 2,
				'pagination'   => false,
			)
		);

		$this->assertStringContainsString( 'pcrafts-block-smoke123', $html );
		$this->assertSame( 2, substr_count( $html, '<article' ) );
		$this->assertStringContainsString( $item_class, $html );
	}

	/**
	 * Blocks and the class each loop item gets.
	 *
	 * @return array
	 */
	public function data_loop_markup() {
		return array(
			'post grid' => array( 'post-grid', 'post-grid' ),
			'post list' => array( 'post-list', 'post-list' ),
		);
	}
}
