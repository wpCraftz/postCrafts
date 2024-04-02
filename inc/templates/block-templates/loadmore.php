<?php
/**
 * Loadmore Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query = ! empty( $post_query ) ? $post_query : array();
$max_page   = isset( $max_page ) ? $max_page : 1;
$alignment  = isset( $alignment ) ? $alignment : 'left';
$block_id   = isset( $block_id ) ? $block_id : null;
$posts_per_page   = isset( $posts_per_page ) ? $posts_per_page : 4;

?>

<div class="pcrafts-pagination pcrafts-loadmore <?php echo esc_attr( $alignment ); ?>" 
	data-page="1"
	data-max-page="<?php echo esc_attr( $max_page ); ?>"
	data-posts-per-page="<?php echo esc_attr( $posts_per_page ); ?>"
	data-post-id="<?php echo esc_attr( get_the_ID() ); ?>"
	data-block-id="<?php echo esc_attr( $block_id ); ?>"
>
	<button class="pcrafts-loadmore-btn"><?php echo __( 'Load More', 'post-crafts' ); ?></button>
</div>
