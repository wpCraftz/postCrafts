<?php
/**
 * Loadmore Pagination Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_query = ! empty( $post_query ) ? $post_query : array();
?>

<div class="pcrafts-pagination pcrafts-loadmore">
	<button class="pcrafts-loadmore-btn">Load More!</button>
</div>
