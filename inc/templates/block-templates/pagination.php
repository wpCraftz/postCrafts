<?php
/**
 * Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query   = ! empty( $post_query ) ? $post_query : array();
$max_page     = ! empty( $max_page ) ? $max_page : 1;
$current_page = isset( $current_page ) ? $current_page : 1;

?>

<div class="pcrafts-pagination"
	data-query="<?php echo esc_attr( json_encode( $post_query ) ); ?>"
	data-page="<?php echo esc_attr( $current_page ); ?>"
	data-max-page="<?php echo esc_attr( $max_page ); ?>"
	>
	<?php
		echo post_crafts_pagination( $max_page, $current_page );
	?>
</div>
