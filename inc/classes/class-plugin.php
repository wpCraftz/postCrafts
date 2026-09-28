<?php
/**
 * Plugin manifest class.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		// Load plugin classes.
		Assets::get_instance();
		Blocks::get_instance();
		Media::get_instance();
		Api::get_instance();
		Style_Loader::get_instance();

		register_activation_hook( POST_CRAFTS_FILE, array( $this, 'activate' ) );
		$this->setup_hooks();
	}

	/**
	 * Do stuff on plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		if ( ! get_option( 'post_crafts_installed' ) ) {
			update_option( 'post_crafts_installed', time() );
		}

		update_option( 'post_crafts_version', POST_CRAFTS_VERSION );
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		/**
		 * Filters
		 */
		add_filter( 'rest_prepare_post', array( $this, 'add_post_class_in_rest_response' ), 10, 3 );
		add_action( 'init', array( $this, 'localize_scripts' ), 1 );
		add_action( 'init', array( $this, 'load_textdomain' ), 9999 );

		// AJAX pagination.
		add_action( 'wp_ajax_paginate_posts', array( $this, 'post_crafts_pagination' ) );
		add_action( 'wp_ajax_nopriv_paginate_posts', array( $this, 'post_crafts_pagination' ) );
	}

	/**
	 * Ajax pagination related stuffs.
	 */
	public function post_crafts_pagination() {
		if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ), 'post-crafts' ) ) {
			return;
		}

		$post_id  = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
		$block_id = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$template = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : '';
		$query    = isset( $_POST['query'] ) && is_array( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by post_crafts_sanitize_query_args().

		// Only the loop templates can be requested.
		if ( ! in_array( $template, array( 'post-grid', 'post-list' ), true ) ) {
			wp_send_json_error( array( __( 'Invalid template', 'post-crafts' ) ), 400 );
		}

		$attributes = post_crafts_get_block_attributes( $post_id, $block_id );

		// The block may live outside the post content (e.g. a template part); use block.json defaults.
		if ( empty( $attributes ) ) {
			$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'post-crafts/' . $template );
			$attributes = $block_type ? $block_type->prepare_attributes_for_render( array() ) : array();
		}

		$fetched_posts = new \WP_Query( post_crafts_sanitize_query_args( $query ) );

		if ( $fetched_posts->have_posts() ) {
			$new_posts = array();
			while ( $fetched_posts->have_posts() ) {
				$fetched_posts->the_post();
				$new_posts[] = post_crafts_template(
					'block-templates/' . $template,
					array(
						'excerpt'        => $attributes['excerpt'],
						'excerpt_length' => $attributes['excerptLength'],
					),
				);
			}
			wp_send_json_success( $new_posts );
			wp_reset_postdata();
		} else {
			wp_send_json_error(
				array(
					__( 'No more posts found', 'post-crafts' ),
				)
			);
		}
		wp_die();
	}

	/**
	 * To localize scripts.
	 */
	public function localize_scripts() {

		$local_script_handle = 'post-crafts-localized-script';

		$localized_data = array(
			'urls'  => array(
				'restBase' => home_url( '/wp-json/' . POST_CRAFTS_REST_NAMESPACE ),
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			),
			'nonce' => wp_create_nonce( 'post-crafts' ),
		);
		wp_register_script( $local_script_handle, '', array( 'wp-i18n' ), POST_CRAFTS_VERSION, true );
		wp_enqueue_script( $local_script_handle );
		wp_localize_script( $local_script_handle, 'POSTCRAFTS', $localized_data );
	}

	/**
	 * Load all translations for our plugin from the MO file.
	 */
	public function load_textdomain() {

		load_plugin_textdomain( 'post-crafts', false, dirname( plugin_basename( POST_CRAFTS_FILE ) ) . '/languages' );
	}

	/**
	 * Filters the post data for a REST API response.
	 *
	 * This data is being used for the post loop gutenberg block in editor.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     Post object.
	 */
	public function add_post_class_in_rest_response( $response, $post ) {

		$response->data['post_class'] = implode( ' ', get_post_class( '', $post->ID ) );

		$featured_image_id  = $response->data['featured_media'];
		$featured_image_src = wp_get_attachment_image_src( $featured_image_id, 'thumb-330x185' );

		$featured_image             = array();
		$featured_image['src']      = ! empty( $featured_image_src[0] ) ? $featured_image_src[0] : '';
		$featured_image['width']    = ! empty( $featured_image_src[1] ) ? $featured_image_src[1] : '';
		$featured_image['height']   = ! empty( $featured_image_src[2] ) ? $featured_image_src[2] : '';
		$featured_image['loading']  = 'lazy';
		$featured_image['decoding'] = 'async';
		$featured_image['class']    = 'attachment-thumb-330x185 size-thumb-330x185 wp-post-image';
		$featured_image['alt']      = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );
		$featured_image['srcset']   = wp_get_attachment_image_srcset( $featured_image_id, 'thumb-330x185' );
		$featured_image['sizes']    = wp_get_attachment_image_sizes( $featured_image_id, 'thumb-330x185' );

		if ( $featured_image_id ) {
			$response->data['featured_image'] = $featured_image;
		}

		/*
		 * Untrimmed card excerpt, built by the same helper as the front end. The editor
		 * trims it to excerptLength words. It's texturized here because the front end
		 * runs the trimmed text through the `the_excerpt` filters.
		 */
		$response->data['pcrafts_excerpt'] = wptexturize( post_crafts_get_excerpt_source( $post ) );

		return $response;
	}
}
