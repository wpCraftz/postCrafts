<?php
/**
 * Loadmore Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query = ! empty( $post_query ) ? $post_query : array();
?>

<div class="pcrafts-pagination pcrafts-loadmore" 
	data-query="<?php echo esc_attr( json_encode( $post_query ) ); ?>"
	data-page="1"
	data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
>
	<button class="pcrafts-loadmore-btn"><?php echo __( 'Load More', 'post-crafts' ); ?></button>
</div>
