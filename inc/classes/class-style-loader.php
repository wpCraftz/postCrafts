<?php
/**
 * Style loader class.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Style_Loader
 */
class Style_Loader {

	use Singleton;

	/**
	 * Post CSS.
	 *
	 * @var String
	 */
	public $css = '';

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

		add_action( 'wp', array( $this, 'load_css' ) );
	}

	/**
	 * Load the saved block CSS for the current post.
	 *
	 * @return void
	 */
	public function load_css() {

		if ( ! is_singular() || is_attachment() ) {
			return;
		}

		$post_id   = get_the_ID();
		$this->css = get_post_meta( $post_id, is_preview() ? 'post-crafts-preview-style' : 'post-crafts-style', true );

		if ( ! empty( $this->css ) ) {
			add_action( 'wp_head', array( $this, 'print_css' ) );
		}
	}

	/**
	 * Prints the optimized CSS in the head.
	 *
	 * @return void
	 */
	public function print_css() {

		if ( ! empty( $this->css ) ) {
			echo "\n";
			echo '<style class="pcrafts-dynamic-styles">';
			echo wp_strip_all_tags( $this->css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS can't be HTML-escaped; stripping tags stops it closing the style element.
			echo '</style>';
		}
	}
}
