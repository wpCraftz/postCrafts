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

	if ( ! empty( $attributes['postType'] ) && is_post_type_viewable( $attributes['postType'] ) ) {
		$args['post_type'] = $attributes['postType'];
	}

	if ( isset( $attributes['ignoreSticky'] ) && true === $attributes['ignoreSticky'] ) {
		$args['ignore_sticky_posts'] = true;
	}

	if ( isset( $attributes['excludeCurrentPost'] ) && true === $attributes['excludeCurrentPost'] ) {
		$args['post__not_in'] = array( get_the_ID() ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Excludes only the current post.
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
 * Sanitize query arguments received from the front end.
 *
 * Only the keys produced by post_crafts_query_builder() are kept, so an
 * untrusted request can't pass arbitrary arguments to WP_Query.
 *
 * @param array $query Raw query arguments.
 *
 * @return array Sanitized query arguments.
 */
function post_crafts_sanitize_query_args( $query ) {
	$query = is_array( $query ) ? $query : array();
	$args  = array(
		'post_status'            => 'publish',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'posts_per_page'         => 6,
		'paged'                  => 1,
	);

	if ( isset( $query['posts_per_page'] ) ) {
		$args['posts_per_page'] = min( max( absint( $query['posts_per_page'] ), 1 ), 100 );
	}

	if ( isset( $query['paged'] ) ) {
		$args['paged'] = max( absint( $query['paged'] ), 1 );
	}

	if ( isset( $query['order'] ) ) {
		$args['order'] = 'ASC' === strtoupper( sanitize_text_field( $query['order'] ) ) ? 'ASC' : 'DESC';
	}

	if ( isset( $query['orderby'] ) && in_array( $query['orderby'], array( 'date', 'title', 'post__in' ), true ) ) {
		$args['orderby'] = $query['orderby'];
	}

	if ( isset( $query['post_type'] ) && is_string( $query['post_type'] ) && is_post_type_viewable( $query['post_type'] ) ) {
		$args['post_type'] = $query['post_type'];
	}

	if ( ! empty( $query['ignore_sticky_posts'] ) && 'false' !== $query['ignore_sticky_posts'] ) {
		$args['ignore_sticky_posts'] = true;
	}

	$id_keys = array( 'post__in', 'post__not_in', 'category__in', 'category__not_in', 'category__and', 'tag__in', 'tag__not_in', 'tag__and' );

	foreach ( $id_keys as $key ) {
		if ( ! empty( $query[ $key ] ) && is_array( $query[ $key ] ) ) {
			$args[ $key ] = array_filter( array_map( 'absint', $query[ $key ] ) );
		}
	}

	if ( ! empty( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
		$tax_query = post_crafts_sanitize_tax_query( $query['tax_query'] );

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
	}

	return $args;
}

/**
 * Sanitize a tax query, including nested groups.
 *
 * @param array $tax_query Raw tax query.
 *
 * @return array Sanitized tax query.
 */
function post_crafts_sanitize_tax_query( $tax_query ) {
	$sanitized = array();

	foreach ( $tax_query as $key => $clause ) {
		if ( 'relation' === $key ) {
			$sanitized['relation'] = 'OR' === strtoupper( sanitize_text_field( $clause ) ) ? 'OR' : 'AND';
			continue;
		}

		if ( ! is_array( $clause ) ) {
			continue;
		}

		// Nested group of clauses.
		if ( ! isset( $clause['taxonomy'] ) ) {
			$group = post_crafts_sanitize_tax_query( $clause );

			if ( ! empty( $group ) ) {
				$sanitized[] = $group;
			}
			continue;
		}

		if ( ! in_array( $clause['taxonomy'], array( 'category', 'post_tag' ), true ) || empty( $clause['terms'] ) ) {
			continue;
		}

		$operator = isset( $clause['operator'] ) ? strtoupper( sanitize_text_field( $clause['operator'] ) ) : 'IN';

		$sanitized[] = array(
			'taxonomy' => $clause['taxonomy'],
			'field'    => 'term_id',
			'terms'    => array_filter( array_map( 'absint', (array) $clause['terms'] ) ),
			'operator' => in_array( $operator, array( 'IN', 'NOT IN', 'AND' ), true ) ? $operator : 'IN',
		);
	}

	return $sanitized;
}

/**
 * Get plugin template.
 *
 * @param string $template  Name or path of the template within /templates folder without php extension.
 * @param array  $variables pass an array of variables you want to use in template.
 * @param bool   $should_echo Whether to echo out the template content or not.
 *
 * @return string|void Template markup.
 */
function post_crafts_template( $template, $variables = array(), $should_echo = false ) {

	$template_file = sprintf( '%1$s/inc/templates/%2$s.php', POST_CRAFTS_PATH, $template );

	if ( ! file_exists( $template_file ) ) {
		return '';
	}

	if ( ! empty( $variables ) && is_array( $variables ) ) {
		extract( $variables, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Used as an exception as there is no better alternative.
	}

	ob_start();

	include $template_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

	$markup = ob_get_clean();

	if ( ! $should_echo ) {
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

	$pages .= sprintf( '<li class="prev page-numbers%1$s" data-page="%2$s">%3$s</li>', 1 === $current_page ? $hide_class : '', esc_attr( $current_page - 1 ), esc_html__( 'Prev', 'post-crafts' ) );

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
	} elseif ( 2 == $max_page ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Value may be a numeric string.
		$middle_pages = array( 1, 2 );
	}

	foreach ( $middle_pages as $page ) {
		$pages .= sprintf( '<li class="page-numbers middle-pages%1$s" data-page="%2$s">%3$s</li>', $current_page === $page ? $active_class : '', esc_attr( $page ), esc_html( $page ) );
	}

	$pages .= sprintf( '<li class="page-dots last%s">...</li>', $max_page <= $current_page + 2 ? $hide_class : '' );

	if ( $max_page > 3 ) {
		$pages .= sprintf( '<li class="page-numbers last-page%1$s" data-page="%2$s">%3$s</li>', $max_page <= $current_page + 1 ? $hide_class : '', esc_attr( $max_page ), esc_html( $max_page ) );
	}

	$pages .= sprintf( '<li class="next page-numbers%1$s" data-page="%2$s">%3$s</li>', $current_page === $max_page ? $hide_class : '', esc_attr( $current_page + 1 ), esc_html__( 'Next', 'post-crafts' ) );

	$pages .= '</ul>';
	return $pages;
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
 * @param number $post_id  ID of the post that contains the block.
 * @param string $block_id Block ID.
 *
 * @return array Block attributes.
 */
function post_crafts_get_block_attributes( $post_id, $block_id ) {
	$post       = get_post( $post_id );
	$attributes = array();

	if ( ! $post ) {
		return $attributes;
	}

	if ( ! has_blocks( $post->post_content ) ) {
		return $attributes;
	}

	$block = post_crafts_find_block( parse_blocks( $post->post_content ), $block_id );

	if ( ! $block ) {
		return $attributes;
	}

	// Saved markup omits default values, so fill them in from block.json.
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );

	return $block_type ? $block_type->prepare_attributes_for_render( $block['attrs'] ) : $block['attrs'];
}

/**
 * Find a PostCrafts block by its blockId, searching nested blocks too.
 *
 * @param array  $blocks   Parsed blocks.
 * @param string $block_id Block ID.
 *
 * @return array|null The block, or null if not found.
 */
function post_crafts_find_block( $blocks, $block_id ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['attrs']['blockId'] ) && $block_id === $block['attrs']['blockId'] ) {
			return $block;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = post_crafts_find_block( $block['innerBlocks'], $block_id );

			if ( $found ) {
				return $found;
			}
		}
	}

	return null;
}
