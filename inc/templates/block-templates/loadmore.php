<?php
/**
 * Loadmore Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query = ! empty( $post_query ) ? $post_query : array();
$max_page   = ! empty( $max_page ) ? $max_page : 1;
?>

<div class="pcrafts-pagination pcrafts-loadmore" 
	data-query="<?php echo esc_attr( json_encode( $post_query ) ); ?>"
	data-page="1"
	data-max-page="<?php echo esc_attr( $max_page ); ?>"
>
	<button class="pcrafts-loadmore-btn"><?php echo __( 'Load More', 'post-crafts' ); ?></button>
</div>
