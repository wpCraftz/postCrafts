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

$post_grid_query          = post_crafts_query_builder( $attributes );
$fetched_posts            = new WP_Query( $post_grid_query );
$block_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'pcrafts-postgrid-wrapper columns-' . esc_attr( $attributes['columns'] . '' ),
	)
);

$current_post_id = get_the_ID();
$pagination      = $attributes['pagination'];
$paginationType  = $attributes['paginationType'];
$max_page        = $fetched_posts->max_num_pages;



?>
<div <?php echo wp_kses_data( $block_wrapper_attributes ); ?>>
	<div class="pcrafts-grid-items-wrapper">
		<?php
		if ( $fetched_posts->have_posts() ) {
			while ( $fetched_posts->have_posts() ) {
				$fetched_posts->the_post();
				post_crafts_template(
					'block-templates/post-grid',
					array(),
					true
				);
			}
			// wp_reset_postdata();
		}
		?>
	</div>
	<?php
	if ( $pagination ) {

		post_crafts_template(
			$paginationType === 'loadmore' ?
			'block-templates/loadmore' : (
			$paginationType === 'arrow' ?
			'block-templates/arrow' :
			'block-templates/pagination' ),
			array(
				'post_query' => $post_grid_query,
				'max_page'  => $max_page,
			),
			true
		);

	}
	wp_reset_query();
	?>
</div>
<?php

wp_reset_postdata();

