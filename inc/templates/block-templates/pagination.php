<?php
/**
 * Pagination Template.
 *
 * @package post-crafts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query    = ! empty( $post_query ) ? $post_query : array();
$max_page      = ! empty( $max_page ) ? $max_page : 1;
$current_page  = isset( $current_page ) ? $current_page : 1;
$alignment     = isset( $alignment ) ? $alignment : 'left';
$block_id      = isset( $block_id ) ? $block_id : null;
$loop_template = isset( $loop_template ) ? $loop_template : 'post-grid';
$pages_markup  = post_crafts_pagination( $max_page, $current_page );

?>

<div class="pcrafts-pagination pcrafts-numberic <?php echo esc_attr( $alignment ); ?>"
	<?php if ( ! empty( $pages_markup ) ) : ?>
	role="navigation"
	aria-label="<?php esc_attr_e( 'Posts pagination', 'post-crafts' ); ?>"
	<?php endif; ?>
	data-query="<?php echo esc_attr( wp_json_encode( $post_query ) ); ?>"
	data-page="<?php echo esc_attr( $current_page ); ?>"
	data-max-page="<?php echo esc_attr( $max_page ); ?>"
	data-post-id="<?php echo esc_attr( get_the_ID() ); ?>"
	data-block-id="<?php echo esc_attr( $block_id ); ?>"
	data-template="<?php echo esc_attr( $loop_template ); ?>"
	>
	<?php
	echo $pages_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped in post_crafts_pagination().
	?>
</div>
