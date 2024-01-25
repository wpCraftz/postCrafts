<?php
/**
 * Plugin manifest class.
 *
 * @package pcrafts
 */

namespace PostCrafts\Blocks\Inc;

use \PostCrafts\Blocks\Inc\Traits\Singleton;

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
		Admin::get_instance();
		Media::get_instance();

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		$this->setup_hooks();
	}

	/**
	 * Do stuff on plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$installed = get_option( 'PCRAFTS_post_blocks_installed' );

		if ( ! $installed ) {
			update_option( 'PCRAFTS_post_blocks_installed', time() );
		}

		update_option( 'PCRAFTS_post_blocks_installed', PCRAFTS_VERSION );
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
		add_filter( 'excerpt_more', [ $this, 'PCRAFTS_add_read_more_link' ] );
		add_filter( 'excerpt_length', [ $this, 'PCRAFTS_excerpt_length' ] );
		add_filter( 'rest_prepare_post', [ $this, 'postcrafts_add_post_class_in_rest_response' ], 10, 3 );
		add_action( 'init', [ $this, 'PCRAFTS_localize_scripts' ], 1 );
		add_action( 'init', [ $this, 'PCRAFTS_load_textdomain' ], 9999 );

	}

	/**
	 * To localize scripts
	 */
	public function PCRAFTS_localize_scripts() {

		$local_script_handle = 'postcrafts-localized-script';
		$localized_data = array(
			'urls' => array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			),
		);
		wp_register_script( $local_script_handle, '', [ 'wp-i18n' ], PCRAFTS_VERSION, true );
		wp_enqueue_script( $local_script_handle );
		wp_localize_script( $local_script_handle, 'postCrafts', $localized_data );
	}

	/**
	 * Load all translations for our plugin from the MO file.
	 */
	function PCRAFTS_load_textdomain() {

		load_plugin_textdomain( 'pcrafts', false, plugin_dir_path( __FILE__ ) . 'languages' );
	
	}

	/**
	 * Filter the excerpt length
	 *
	 * @return string
	 */
	public function PCRAFTS_excerpt_length() {
		return 20;
	}

	/**
	 * Filters the post data for a REST API response.
	 *
	 * This data is being used for the post loop gutenberg block in editor.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     Post object.
	 * @param \WP_REST_Request  $request  Request object.
	 */
	public function postcrafts_add_post_class_in_rest_response( $response, $post, $request ) {

		$response->data['post_class'] = implode( ' ', get_post_class( '', $post->ID ) );

		$featured_image_id  = $response->data['featured_media'];
		$featured_image_src = wp_get_attachment_image_src( $featured_image_id, 'thumb-330x185' );

		$featured_image             = [];
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
		return $response;
	}

	/**
	 * Add read more link
	 *
	 * @filter excerpt_more
	 *
	 * @return string
	 */
	public function PCRAFTS_add_read_more_link() {
		return '&hellip;';
	}

}
