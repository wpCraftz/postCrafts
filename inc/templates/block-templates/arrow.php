<?php
/**
 * Arrow Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="pcrafts-pagination pcrafts-arrow"
	data-query="<?php echo esc_attr( json_encode( $post_query ) ); ?>"
	data-page="1"
	data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
>
	<button class="pcrafts-prev disabled"><</button>
	<button class="pcrafts-next">></button>
</div>
