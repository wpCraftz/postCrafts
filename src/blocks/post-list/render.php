<?php
/**
 * Render.php
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package pc-blocks
 */

 $default_args = [
	'postsPerPage' => 9,
];

$attributes = wp_parse_args( $attributes, $default_args );

$args = [
	'post_type'              => 'post',
	'posts_per_page'         => $attributes['postsPerPage'],
	'post_status'            => 'publish',
	'ignore_sticky_posts'    => 1,
	'update_post_meta_cache' => false,
	'update_post_term_cache' => false,
	'no_found_rows'          => true,
	'order'                  => $attributes['sorting']['order'],
	'orderby'                => $attributes['sorting']['orderBy'],
	'post__not_in'           => array( get_the_ID() ),
];

if ( ! empty( $attributes['postIds'] ) ) {

	$args['post__in'] = $attributes['postIds'];
	$args['orderby']  = 'post__in';

} else {
	$tax_query    = [];
	$cat_operator = $attributes['catOperator'];
	$tag_operator = $attributes['tagOperator'];
	if ( ( isset( $attributes['taxQuery']['category'] ) && ! empty( $attributes['taxQuery']['category'] ) ) && ( isset( $attributes['taxQuery']['post_tag'] ) && ! empty( $attributes['taxQuery']['post_tag'] ) ) ) {

		$tax_relation          = $attributes['taxRelation'];
		$tax_query['relation'] = $tax_relation;

		$tax_query[] = [
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => $attributes['taxQuery']['category'],
			'operator' => $cat_operator,
		];

		$tax_query[] = [
			'taxonomy' => 'post_tag',
			'field'    => 'term_id',
			'terms'    => $attributes['taxQuery']['post_tag'],
			'operator' => $tag_operator,
		];

	} elseif ( isset( $attributes['taxQuery']['category'] ) && ! empty( $attributes['taxQuery']['category'] ) ) {

		$cat_relelation = 'category__and';

		if ( 'IN' === $cat_operator ) {
			$cat_relelation = 'category__in';
		} elseif ( 'NOT IN' === $cat_operator ) {
			$cat_relelation = 'category__not_in';
		}

		$args[ $cat_relelation ] = $attributes['taxQuery']['category'];

	} elseif ( isset( $attributes['taxQuery']['post_tag'] ) && ! empty( $attributes['taxQuery']['post_tag'] ) ) {

		$tag_relelation = 'tag__and';

		if ( 'IN' === $tag_operator ) {
			$tag_relelation = 'tag__in';
		} elseif ( 'NOT IN' === $tag_operator ) {
			$tag_relelation = 'tag__not_in';
		}

		$args[ $tag_relelation ] = $attributes['taxQuery']['post_tag'];
	}

	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = [ $tax_query ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
}

$content_view_query = new WP_Query( $args );

?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
<?php
if ( $content_view_query->have_posts() ) {

		while ( $content_view_query->have_posts() ) {
			$content_view_query->the_post();
			?>
			<article id="post-<?php the_ID(); ?>" class='post-list'>
				<figure class="post-list-thumbnail">
					<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>" aria-label="<?php the_title_attribute(); ?>">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'thumb-330x185' );
						} else {
							?>
					<span class="image-placeholder"></span>
					<?php } ?>
					</a>
				</figure>
				<div class="post-list-content">
					<?php pc_get_primary_category(); ?>
					<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
					<div class="entry-meta">
						<?php pc_posted_by(); ?><span class="separator">-</span><?php pc_posted_on( 'F d, Y' ); ?>
					</div>
					<div class="entry-summary"><?php the_excerpt(); ?></div>
				</div>
			</article>
			<?php
		}
		 } ?>
</div>
<?php

wp_reset_postdata();

