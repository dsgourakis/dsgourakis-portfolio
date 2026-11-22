<?php
/**
 * Tests for the upload image formats.
 *
 * @package DSG\Core
 */

use DSG\Core\Media\ImageFormats;

/**
 * JPEG uploads get WebP sizes.
 */
class Test_Image_Formats extends WP_UnitTestCase {

	public function test_jpeg_becomes_webp() {
		$this->assertSame( 'image/webp', wp_get_image_editor_output_format( 'photo.jpg', 'image/jpeg' )['image/jpeg'] );
	}

	public function test_png_is_left_alone() {
		$this->assertArrayNotHasKey( 'image/png', ImageFormats::output_formats( array() ) );
	}

	public function test_existing_mapping_wins() {
		$formats = ImageFormats::output_formats( array( 'image/jpeg' => 'image/avif' ) );

		$this->assertSame( 'image/avif', $formats['image/jpeg'] );
	}
}
