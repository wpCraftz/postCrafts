<?php
/**
 * Arrow Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query    = ! empty( $post_query ) ? $post_query : array();
$max_page      = ! empty( $max_page ) ? $max_page : 1;
$alignment     = isset( $alignment ) ? $alignment : 'left';
$block_id      = isset( $block_id ) ? $block_id : null;
$loop_template = isset( $loop_template ) ? $loop_template : 'post-grid';

?>

<div class="pcrafts-pagination pcrafts-arrow <?php echo esc_attr( $alignment ); ?>"
	data-query="<?php echo esc_attr( json_encode( $post_query ) ); ?>"
	data-page="1"
	data-max-page="<?php echo esc_attr( $max_page ); ?>"
	data-post-id="<?php echo esc_attr( get_the_ID() ); ?>"
	data-block-id="<?php echo esc_attr( $block_id ); ?>"
	data-template="<?php echo esc_attr( $loop_template ); ?>"
>
	<button class="pcrafts-prev disabled"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M15.293 3.293 6.586 12l8.707 8.707 1.414-1.414L9.414 12l7.293-7.293-1.414-1.414z"/></svg></button>
	<button class="pcrafts-next"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z"/></svg></button>
</div>
