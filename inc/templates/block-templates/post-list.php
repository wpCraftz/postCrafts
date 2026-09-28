<?php
/**
 * Post List block template.
 *
 * The editor preview in src/blocks/post-list/edit.js mirrors this markup.
 *
 * @package post-crafts
 */

$excerpt        = isset( $excerpt ) ? $excerpt : true;
$excerpt_length = isset( $excerpt_length ) ? $excerpt_length : '55';

// Empty when there is no thumbnail or its attachment is missing.
$thumbnail = get_the_post_thumbnail( null, 'thumb-330x185' );
$summary   = $excerpt ? post_crafts_excerpt_length( get_the_ID(), $excerpt_length ) : '';

?>

<article id="post-<?php the_ID(); ?>" class="post-list">
	<figure class="post-thumbnail">
		<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>" aria-label="<?php the_title_attribute(); ?>">
			<?php if ( $thumbnail ) : ?>
				<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core image markup; kses would strip srcset/sizes. ?>
			<?php else : ?>
				<span class="image-placeholder"></span>
			<?php endif; ?>
		</a>
	</figure>
	<div class="post-list-content post-content">
		<?php post_crafts_get_primary_category(); ?>
		<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
		<div class="entry-meta">
			<?php post_crafts_get_author(); ?><span class="separator">-</span><?php post_crafts_posted_on( 'F d, Y' ); ?>
		</div>
		<?php if ( $summary ) : ?>
			<div class="entry-summary"><?php echo wp_kses_post( $summary ); ?></div>
		<?php endif; ?>
	</div>
</article>
