<?php
/**
 * Tests for post_crafts_sanitize_query_args(), which cleans the AJAX pagination query.
 *
 * Acceptance criteria: tests/acceptance/pagination-php.md (PAGP-21).
 *
 * @package post-crafts
 */

// phpcs:disable WordPress.WP.PostsPerPage, WordPress.DB.SlowDBQuery, WordPressVIPMinimum.Performance.WPQueryParams, WordPressVIPMinimum.Performance.NoPaging -- Deliberately hostile query arguments; none reach WP_Query unsanitized.

namespace PostCrafts\Blocks\Tests;

/**
 * Query sanitization.
 */
class Test_Sanitize_Query_Args extends TestCase {

	/**
	 * Arguments every sanitized query starts from.
	 *
	 * @var array
	 */
	private $defaults = array(
		'post_status'            => 'publish',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'posts_per_page'         => 6,
		'paged'                  => 1,
	);

	/**
	 * Non-array or empty input gives the defaults.
	 *
	 * @covers-ac PAGP-21
	 * @dataProvider data_not_a_query
	 *
	 * @param mixed $query Input.
	 */
	public function test_defaults( $query ) {
		$this->assertSame( $this->defaults, post_crafts_sanitize_query_args( $query ) );
	}

	/**
	 * Inputs that aren't a query.
	 *
	 * @return array
	 */
	public function data_not_a_query() {
		return array(
			'null'        => array( null ),
			'string'      => array( 'posts_per_page=500&post_status=draft' ),
			'number'      => array( 5 ),
			'empty array' => array( array() ),
		);
	}

	/**
	 * post_status is always publish.
	 *
	 * @covers-ac PAGP-21
	 * @dataProvider data_statuses
	 *
	 * @param mixed $status Requested status.
	 */
	public function test_status_is_always_publish( $status ) {
		$args = post_crafts_sanitize_query_args( array( 'post_status' => $status ) );

		$this->assertSame( 'publish', $args['post_status'] );
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
			'trash'   => array( 'trash' ),
			'list'    => array( array( 'draft', 'publish' ) ),
		);
	}

	/**
	 * posts_per_page is clamped to 1..100 and paged is at least 1.
	 *
	 * @covers-ac PAGP-21
	 * @dataProvider data_numbers
	 *
	 * @param mixed $input    Value sent for both posts_per_page and paged.
	 * @param int   $per_page Expected posts_per_page.
	 * @param int   $paged    Expected paged.
	 */
	public function test_clamps_numbers( $input, $per_page, $paged ) {
		$args = post_crafts_sanitize_query_args(
			array(
				'posts_per_page' => $input,
				'paged'          => $input,
			)
		);

		$this->assertSame( $per_page, $args['posts_per_page'] );
		$this->assertSame( $paged, $args['paged'] );
	}

	/**
	 * Numbers and their clamped values.
	 *
	 * @return array
	 */
	public function data_numbers() {
		return array(
			'normal'       => array( '3', 3, 3 ),
			'int'          => array( 12, 12, 12 ),
			'zero'         => array( '0', 1, 1 ),
			'minus one'    => array( '-1', 1, 1 ),
			'huge'         => array( '100000', 100, 100000 ),
			'upper bound'  => array( '100', 100, 100 ),
			'not a number' => array( 'abc', 1, 1 ),
		);
	}

	/**
	 * Keys outside the allow-list are dropped.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_drops_unknown_keys() {
		$args = post_crafts_sanitize_query_args(
			array(
				'meta_query'       => array( array( 'key' => 'secret' ) ),
				'meta_key'         => 'secret',
				's'                => 'search',
				'author'           => 1,
				'fields'           => 'ids',
				'perm'             => 'readable',
				'suppress_filters' => true,
				'has_password'     => true,
				'post_password'    => 'x',
				'post_parent'      => 3,
				'nopaging'         => true,
				'offset'           => 5,
			)
		);

		$this->assertSame( $this->defaults, $args );
	}

	/**
	 * orderby and order are whitelisted.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_order_and_orderby() {
		foreach ( array( 'date', 'title', 'post__in' ) as $orderby ) {
			$this->assertSame( $orderby, post_crafts_sanitize_query_args( array( 'orderby' => $orderby ) )['orderby'] );
		}

		foreach ( array( 'rand', 'meta_value', 'ID DESC, post_title', array( 'date' => 'ASC' ) ) as $orderby ) {
			$this->assertArrayNotHasKey( 'orderby', post_crafts_sanitize_query_args( array( 'orderby' => $orderby ) ) );
		}

		$this->assertSame( 'ASC', post_crafts_sanitize_query_args( array( 'order' => 'asc' ) )['order'] );
		$this->assertSame( 'DESC', post_crafts_sanitize_query_args( array( 'order' => 'desc' ) )['order'] );
		$this->assertSame( 'DESC', post_crafts_sanitize_query_args( array( 'order' => 'ASC; DROP TABLE' ) )['order'] );
	}

	/**
	 * post_type is kept only when viewable.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_post_type() {
		$this->assertSame( 'page', post_crafts_sanitize_query_args( array( 'post_type' => 'page' ) )['post_type'] );
		$this->assertArrayNotHasKey( 'post_type', post_crafts_sanitize_query_args( array( 'post_type' => 'wp_template' ) ) );
		$this->assertArrayNotHasKey( 'post_type', post_crafts_sanitize_query_args( array( 'post_type' => 'nope' ) ) );
		$this->assertArrayNotHasKey( 'post_type', post_crafts_sanitize_query_args( array( 'post_type' => array( 'post', 'page' ) ) ) );
	}

	/**
	 * ignore_sticky_posts is a boolean, and the string "false" (from jQuery) is off.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_ignore_sticky() {
		$this->assertTrue( post_crafts_sanitize_query_args( array( 'ignore_sticky_posts' => 'true' ) )['ignore_sticky_posts'] );
		$this->assertTrue( post_crafts_sanitize_query_args( array( 'ignore_sticky_posts' => '1' ) )['ignore_sticky_posts'] );
		$this->assertArrayNotHasKey( 'ignore_sticky_posts', post_crafts_sanitize_query_args( array( 'ignore_sticky_posts' => 'false' ) ) );
		$this->assertArrayNotHasKey( 'ignore_sticky_posts', post_crafts_sanitize_query_args( array( 'ignore_sticky_posts' => '' ) ) );
	}

	/**
	 * ID lists are absint'ed, zeros dropped; non-arrays ignored.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_id_lists() {
		$keys  = array( 'post__in', 'post__not_in', 'category__in', 'category__not_in', 'category__and', 'tag__in', 'tag__not_in', 'tag__and' );
		$query = array();

		foreach ( $keys as $key ) {
			$query[ $key ] = array( '5', 'abc', '0', '-7', '3 OR 1=1' );
		}

		$args = post_crafts_sanitize_query_args( $query );

		foreach ( $keys as $key ) {
			$this->assertSame( array( 5, 7, 3 ), array_values( $args[ $key ] ), $key );
		}

		$this->assertArrayNotHasKey( 'post__in', post_crafts_sanitize_query_args( array( 'post__in' => '1,2' ) ) );
	}

	/**
	 * tax_query keeps only category/post_tag clauses with safe values, nested groups included.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_tax_query() {
		$args = post_crafts_sanitize_query_args(
			array(
				'tax_query' => array(
					array(
						'relation' => 'or',
						array(
							'taxonomy' => 'category',
							'field'    => 'slug',
							'terms'    => array( '4', 'x', '9' ),
							'operator' => 'not in',
						),
						array(
							'taxonomy' => 'post_tag',
							'terms'    => '12',
							'operator' => 'EXISTS',
						),
						array(
							'taxonomy' => 'nav_menu',
							'terms'    => array( 1 ),
						),
						array(
							'taxonomy' => 'category',
							'terms'    => array(),
						),
					),
					'relation' => 'XOR',
					'junk',
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => array(
							0 => 4,
							2 => 9,
						),
						'operator' => 'NOT IN',
					),
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'term_id',
						'terms'    => array( 12 ),
						'operator' => 'IN',
					),
				),
				'relation' => 'AND',
			),
			$args['tax_query']
		);
	}

	/**
	 * A tax_query with nothing valid left is dropped.
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_empty_tax_query_is_dropped() {
		$args = post_crafts_sanitize_query_args(
			array(
				'tax_query' => array(
					array(
						'taxonomy' => 'author',
						'terms'    => array( 1 ),
					),
				),
			)
		);

		$this->assertArrayNotHasKey( 'tax_query', $args );
	}

	/**
	 * The args the block builds for its first page survive sanitization unchanged (apart from paged).
	 *
	 * @covers-ac PAGP-21
	 */
	public function test_builder_output_round_trips() {
		$category   = self::factory()->category->create();
		$tag        = self::factory()->tag->create();
		$attributes = \WP_Block_Type_Registry::get_instance()
			->get_registered( 'post-crafts/post-grid' )
			->prepare_attributes_for_render(
				array(
					'postsPerPage' => 4,
					'ignoreSticky' => true,
					'taxQuery'     => array(
						'category' => array( $category ),
						'post_tag' => array( $tag ),
					),
					'taxRelation'  => 'OR',
				)
			);

		$built = post_crafts_query_builder( $attributes, 2 );
		$args  = post_crafts_sanitize_query_args( $built );

		ksort( $built );
		ksort( $args );
		$built['order'] = strtoupper( $built['order'] );

		$this->assertSame( $built, $args );
	}
}
