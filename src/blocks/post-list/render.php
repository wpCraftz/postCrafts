<?php
/**
 * Render.php
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package post-crafts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_list_query          = post_crafts_query_builder( $attributes );
$fetched_posts            = new WP_Query( $post_list_query );
$block_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'pcrafts-postlist-wrapper',
	)
);

$current_post_id = get_the_ID();
$pagination      = $attributes['pagination'];
$paginationType  = $attributes['paginationType'];
?>
<div <?php echo wp_kses_data( $block_wrapper_attributes ); ?>>
	<div class="pcrafts-list-items-wrapper">
		<?php
		if ( $fetched_posts->have_posts() ) {
			$posts_count = 0;
			while ( $fetched_posts->have_posts() && $posts_count < $attributes['postsPerPage'] ) {
				$fetched_posts->the_post();
				$current = get_the_ID();

				// Skip the current post.
				if ( $current_post_id === $current ) {
					continue;
				}
				++$posts_count;
				?>
				<article id="post-<?php the_ID(); ?>" class="post-list">
					<figure class="post-thumbnail">
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
					<div class="post-list-content post-content">
						<?php post_crafts_get_primary_category(); ?>
						<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
						<div class="entry-meta">
							<?php post_crafts_get_author(); ?><span class="separator">-</span><?php post_crafts_posted_on( 'F d, Y' ); ?>
						</div>
						<div class="entry-summary"><?php the_excerpt(); ?></div>
					</div>
				</article>
				<?php
			}
		}
		?>
	</div>
	<?php
	if ( $pagination ) {
		post_crafts_template(
			$paginationType === 'loadmore' ?
			'block-templates/loadmore' : (
			$paginationType === 'arrow' ?
			'block-templates/arrow' :
			'block-templates/pagination' ),
			array(
				'post_query' => $fetched_posts,
			),
			true
		);
	}
	?>
</div>
<?php

wp_reset_postdata();

