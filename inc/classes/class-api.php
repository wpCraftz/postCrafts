<?php
/**
 * Rest API class.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Assets
 */
class Api {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {

		add_action( 'rest_api_init', array( $this, 'register_end_points' ) );
	}

	/**
	 * Register end points
	 *
	 * @return void
	 */
	public function register_end_points() {

		register_rest_route(
			POST_CRAFTS_REST_NAMESPACE,
			'/' . 'style',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_style' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'post_id' => array(
						'required'    => true,
						'type'        => 'integer',
						'description' => __( 'ID of the current post', 'post-crafts' ),
					),
					'style'   => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Style to be saved', 'post-crafts' ),
					),
				),
			)
		);

		register_rest_route(
			POST_CRAFTS_REST_NAMESPACE,
			'/' . 'style',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_style' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Save style
	 *
	 * @param WP_REST_Request $request
	 */
	function save_style( $request ) {
		$post_id       = $request->get_param( 'post_id' );
		$style         = $request->get_param( 'style' );
		$block_id      = $request->get_param( 'block_id' );
		$is_previewing = $request->get_param( 'is_previewing' );

		if ( $block_id === 'all' ) {
			update_post_meta( $post_id, true == $is_previewing ? 'post-crafts-preview-style' : 'post-crafts-style', $style );
		} else {
			$current_style = get_post_meta( $post_id, 'post-crafts-style', true );
			$new_style     = array();

			if ( ! empty( $current_style ) ) {
				$new_style              = json_decode( $current_style, true );
				$new_style[ $block_id ] = $style;
			} else {
				$new_style[ $block_id ] = $style;
			}

			update_post_meta( $post_id, 'post-crafts-style', json_encode( $new_style ) );
		}

		return rest_ensure_response( 'Style saved successfully' );
	}

	/**
	 * Get style
	 */
	function get_style( $request ) {
		$post_id = $request->get_param( 'post_id' );

		$style = get_post_meta( $post_id, 'post-crafts-style', true );

		return rest_ensure_response( $style ?: null );
	}
}
