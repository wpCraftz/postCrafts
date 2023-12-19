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
