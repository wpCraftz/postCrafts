<?php
/**
 * Pc blocks custom functions.
 *
 * @package pc-blocks
 */


/**
 * Prints HTML with meta information for the current author.
 */
function pc_posted_by() {

	$byline = '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>';

	printf( '<span class="byline"> %s</span>', $byline ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

}

/**
 * Display post posted date.
 *
 * @param string $format Optional. The date format.
 *
 * @return void
 */
function pc_posted_on( $format = 'm/d/Y g:ia' ) {

	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
	}

	$time_string = sprintf(
		$time_string,
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date( $format ) )
	);

	echo wp_kses(
		sprintf( '<span class="posted-on">%s</span>', $time_string ),
		[
			'span' => [
				'class' => true,
			],
		]
	);
}

/**
 * Prints HTML with meta information for primary post category.
 *
 * @param boolean $return_id  Flag what to return.
 *
 * @return void|int
 */
function pc_get_primary_category( $return_id = false ) {
	$primary_category = [
		'name'    => '',
		'url'     => '',
		'term_id' => '',
	];

	// Check if Yoast SEO plugin is active.
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {

		// Get the primary term (category) set by Yoast SEO plugin.
		$wpseo_primary_term = new WPSEO_Primary_Term( 'category', get_the_ID() );
		$primary_term_id    = $wpseo_primary_term->get_primary_term();

		if ( $primary_term_id ) {
			$primary_category_obj = get_term( $primary_term_id );
			if ( ! is_wp_error( $primary_category_obj ) ) {
				$primary_category['name']    = $primary_category_obj->name;
				$primary_category['term_id'] = $primary_category_obj->term_id;
				$primary_category['url']     = get_term_link( $primary_category_obj );
			}
		}
	}

	// If no primary category is set or Yoast SEO is not active, use the first assigned category as primary.
	if ( ! $primary_category['name'] ) {
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$primary_category['name']    = $categories[0]->name;
			$primary_category['term_id'] = $categories[0]->term_id;
			$primary_category['url']     = get_category_link( $categories[0]->term_id );
		}
	}

	// return array if $return_data flag is true.
	if ( true === $return_id ) {
		return $primary_category;
	}

	if ( $primary_category['name'] ) {
		printf(
			'<span class="cat-links"><a href=%1$s rel="category tag">%2$s</a></span>',
			esc_url( $primary_category['url'] ),
			esc_html( $primary_category['name'] ),
		);
	}
}

/**
 * Build Query Objet
 *
 * @param object $attributes  Block attributes.
 *
 * @return object
 */
function pc_query_builder($attributes) {
	$args = [
		'post_type'              => 'post',
		'posts_per_page'         => $attributes['postsPerPage'],
		'post_status'            => 'publish',
		'ignore_sticky_posts'    => 1,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'no_found_rows'          => true,
		'order'                  => $attributes['sorting']['order'],
		'orderby'                => $attributes['sorting']['orderBy'],
		'post__not_in'           => array( get_the_ID() ),
	];

	if ( ! empty( $attributes['postIds'] ) ) {

		$args['post__in'] = $attributes['postIds'];
		$args['orderby']  = 'post__in';

	} else {
		$tax_query    = [];
		$cat_operator = $attributes['catOperator'];
		$tag_operator = $attributes['tagOperator'];
		if ( ( isset( $attributes['taxQuery']['category'] ) && ! empty( $attributes['taxQuery']['category'] ) ) && ( isset( $attributes['taxQuery']['post_tag'] ) && ! empty( $attributes['taxQuery']['post_tag'] ) ) ) {

			$tax_relation          = $attributes['taxRelation'];
			$tax_query['relation'] = $tax_relation;

			$tax_query[] = [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $attributes['taxQuery']['category'],
				'operator' => $cat_operator,
			];

			$tax_query[] = [
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $attributes['taxQuery']['post_tag'],
				'operator' => $tag_operator,
			];

		} elseif ( isset( $attributes['taxQuery']['category'] ) && ! empty( $attributes['taxQuery']['category'] ) ) {

			$cat_relelation = 'category__and';

			if ( 'IN' === $cat_operator ) {
				$cat_relelation = 'category__in';
			} elseif ( 'NOT IN' === $cat_operator ) {
				$cat_relelation = 'category__not_in';
			}

			$args[ $cat_relelation ] = $attributes['taxQuery']['category'];

		} elseif ( isset( $attributes['taxQuery']['post_tag'] ) && ! empty( $attributes['taxQuery']['post_tag'] ) ) {

			$tag_relelation = 'tag__and';

			if ( 'IN' === $tag_operator ) {
				$tag_relelation = 'tag__in';
			} elseif ( 'NOT IN' === $tag_operator ) {
				$tag_relelation = 'tag__not_in';
			}

			$args[ $tag_relelation ] = $attributes['taxQuery']['post_tag'];
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = [ $tax_query ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
	}

	return $args;
}
