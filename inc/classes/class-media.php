<?php
/**
 * Handles Media.
 *
 * @package post-crafts
 */

namespace PostCrafts\Blocks\Inc;

use PostCrafts\Blocks\Inc\Traits\Singleton;

/**
 * Class Blocks
 */
class Media {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		add_action( 'after_setup_theme', array( $this, 'add_image_sizes' ) );
	}

	/**
	 * Add custom image sizes.
	 *
	 * @return void
	 */
	public function add_image_sizes() {

		add_image_size( 'thumb-179x134', 179, 134, true ); // 4:3 Aspect Ratio.
		add_image_size( 'thumb-358x268', 358, 268, true ); // 2x of thumb-179x134.

		add_image_size( 'thumb-330x185', 330, 185, true ); // 16:9 Aspect Ratio.
		add_image_size( 'thumb-660x370', 660, 370, true ); // 2x of thumb-330x185.

		add_image_size( 'thumb-1200x800', 1200, 800, true );
		add_image_size( 'thumb-870x570', 870, 570, true );
		add_image_size( 'thumb-600x600', 600, 600, true );
		add_image_size( 'thumb-600x900', 600, 900, true );
	}
}
