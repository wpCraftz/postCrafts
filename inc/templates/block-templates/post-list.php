<?php
/**
 * Post List block template.
 *
 * @package post-crafts
 */

$excerpt        = isset( $excerpt ) ? $excerpt : true;
$excerpt_length = isset( $excerpt_length ) ? $excerpt_length : '55';
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
		<?php if ( $excerpt ) : ?>
			<div class="entry-summary"><?php echo post_crafts_excerpt_length( get_the_ID(), $excerpt_length ); ?></div>
		<?php endif; ?>
	</div>
</article>
