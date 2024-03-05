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

$post_query               = new WP_Query( post_crafts_query_builder( $attributes ) );
$block_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'pcrafts-postlist-wrapper',
	)
);

$current_post_id = get_the_ID();
?>
<div <?php echo wp_kses_data( $block_wrapper_attributes ); ?>>
	<?php
	if ( $post_query->have_posts() ) {
		$posts_count = 0;
		while ( $post_query->have_posts() && $posts_count < $attributes['postsPerPage'] ) {
			$post_query->the_post();
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

wp_reset_postdata();

