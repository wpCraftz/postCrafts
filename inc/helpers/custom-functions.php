<?php
/**
 * PostCrafts custom functions.
 *
 * @package post-crafts
 */

/**
 * Prints HTML with meta information for the current author.
 */
function post_crafts_get_author() {

	$byline = '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>';

	printf( '<span class="byline">%s</span>', wp_kses_post( $byline ) );
}

/**
 * Display post posted date.
 *
 * @param string $format Optional. The date format.
 *
 * @return void
 */
function post_crafts_posted_on( $format = 'm/d/Y g:ia' ) {

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
		array(
			'span' => array(
				'class' => true,
			),
		)
	);
}

/**
 * Prints HTML with meta information for primary post category.
 *
 * @param boolean $return_id  Flag what to return.
 *
 * @return void|int
 */
function post_crafts_get_primary_category( $return_id = false ) {
	$primary_category = array(
		'name'    => '',
		'url'     => '',
		'term_id' => '',
	);

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
 * Build Query Object
 *
 * @param object $attributes  Block attributes.
 * @param number $paged       Current page.
 *
 * @return object
 */
function post_crafts_query_builder( $attributes, $paged = null ) {
	$args = array(
		'posts_per_page'         => $attributes['postsPerPage'],
		'post_status'            => 'publish',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'paged'                  => isset( $attributes['paged'] ) ? $attributes['paged'] : 1,
		'order'                  => $attributes['sorting']['order'],
		'orderby'                => $attributes['sorting']['orderBy'],
	);

	if ( null !== $paged ) {
		$args['paged'] = $paged;
	}

	if ( isset( $attributes['post_type'] ) ) {
		$args['post_type'] = $attributes['post_type'];
	}

	if ( isset( $attributes['ignoreSticky'] ) && true === $attributes['ignoreSticky'] ) {
		$args['ignore_sticky_posts'] = true;
	}

	if ( isset( $attributes['excludeCurrentPost'] ) && true === $attributes['excludeCurrentPost'] ) {
		$args['post__not_in'] = array( get_the_ID() );
	}

	if ( ! empty( $attributes['postIds'] ) ) {

		$args['post__in'] = $attributes['postIds'];
		$args['orderby']  = 'post__in';

	} else {
		$tax_query    = array();
		$cat_operator = $attributes['catOperator'];
		$tag_operator = $attributes['tagOperator'];
		if ( ( isset( $attributes['taxQuery']['category'] ) && ! empty( $attributes['taxQuery']['category'] ) ) && ( isset( $attributes['taxQuery']['post_tag'] ) && ! empty( $attributes['taxQuery']['post_tag'] ) ) ) {

			$tax_relation          = $attributes['taxRelation'];
			$tax_query['relation'] = $tax_relation;

			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $attributes['taxQuery']['category'],
				'operator' => $cat_operator,
			);

			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $attributes['taxQuery']['post_tag'],
				'operator' => $tag_operator,
			);

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
			$args['tax_query'] = array( $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
	}

	return $args;
}

/**
 * Get plugin template.
 *
 * @param string $template  Name or path of the template within /templates folder without php extension.
 * @param array  $variables pass an array of variables you want to use in template.
 * @param bool   $echo      Whether to echo out the template content or not.
 *
 * @return string|void Template markup.
 */
function post_crafts_template( $template, $variables = array(), $echo = false ) {

	$template_file = sprintf( '%1$s/inc/templates/%2$s.php', POST_CRAFTS_PATH, $template );

	if ( ! file_exists( $template_file ) ) {
		return '';
	}

	if ( ! empty( $variables ) && is_array( $variables ) ) {
		extract( $variables, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Used as an exception as there is no better alternative.
	}

	ob_start();

	include $template_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	// include $template_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

	$markup = ob_get_clean();

	if ( ! $echo ) {
		return $markup;
	}

	echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped already in template.
}

/**
 * Post Crafts Pagination.
 *
 * @param number $max_page Total number of pages.
 * @param number $current_page Current page number.
 *
 * @return string|void Template markup.
 */
function post_crafts_pagination( $max_page, $current_page ) {

	if ( $max_page <= 1 ) {
		return;
	}

	if ( ! isset( $current_page ) || 0 === $current_page ) {
		$current_page = 1;
	}

	$hide_class   = ' hide';
	$active_class = ' current';

	$pages = '<ul class="pcrafts-pages">';

	$pages .= sprintf( '<li class="prev page-numbers%1$s" data-page="%2$s">%3$s</li>', $current_page === 1 ? $hide_class : '', $current_page - 1, __( 'Prev', 'post-crafts' ) );

	if ( $max_page > 4 ) {

		if ( $current_page > 3 ) {
			$extra_class = '';
		}

		$pages .= sprintf( '<li class="page-numbers first-page%s" data-page="1">1</li>', $current_page < 3 ? $hide_class : '' );
		$pages .= sprintf( '<li class="page-dots first%s">...</li>', $current_page < 4 ? $hide_class : '' );

	}

	$middle_pages = array();

	if ( $max_page >= 3 ) {
		$middle_pages = array( 1, 2, 3 );
		if ( $current_page >= 3 && $current_page === $max_page ) {
			$middle_pages = array( $current_page - 2, $current_page - 1, $current_page );
		} elseif ( $current_page >= 3 ) {
			$middle_pages = array( $current_page - 1, $current_page, $current_page + 1 );
		}
	} elseif ( $max_page == 2 ) {
		$middle_pages = array( 1, 2 );
	}

	foreach ( $middle_pages as $page ) {
		$pages .= sprintf( '<li class="page-numbers middle-pages%s" data-page="' . $page . '">' . $page . '</li>', $current_page === $page ? $active_class : '' );
	}

	$pages .= sprintf( '<li class="page-dots last%s">...</li>', $max_page <= $current_page + 2 ? $hide_class : '' );

	if ( $max_page > 3 ) {
		$pages .= sprintf( '<li class="page-numbers last-page%s" data-page="' . $max_page . '">' . $max_page . '</li>', $max_page <= $current_page + 1 ? $hide_class : '' );
	}

	$pages .= sprintf( '<li class="next page-numbers%1$s" data-page="%2$s">%3$s</li>', $current_page === $max_page ? $hide_class : '', $current_page + 1, __( 'Next', 'post-crafts' ) );

	$pages .= '</ul>';
	return $pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped already in template.
}

/**
 * Post Crafts Excerpt Length.
 *
 * @param number $post_id ID of current post.
 * @param number $length Limit of excerpt length.
 *
 * @return string|void Template markup.
 */
function post_crafts_excerpt_length( $post_id, $length = 40 ) {
	$post_content = get_the_content( $post_id );
	return apply_filters( 'the_excerpt', wp_trim_words( $post_content, $length ) );
}

/**
 * Get block attributes.
 *
 * @param number $block_id Block ID.
 *
 * @return array Block attributes.
 */
function post_crafts_get_block_attributes( $post_id, $block_id ) {
	$post       = get_post( $post_id );
	$attributes = array();

	if ( has_blocks( $post->post_content ) ) {
		$blocks = parse_blocks( $post->post_content );

		foreach ( $blocks as $block ) {
			if ( $block_id === $block['attrs']['blockId'] ) {
				$attributes = $block['attrs'];
				break;
			}
		}
	}

	return $attributes;
}
