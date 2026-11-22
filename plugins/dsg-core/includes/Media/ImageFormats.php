<?php
/**
 * Image formats for uploads.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Media;

/**
 * Makes WordPress save the resized copies of uploaded JPEGs as WebP.
 *
 * WebP is usually 25–35% smaller than JPEG at the same quality, and every
 * current browser supports it. WordPress keeps the uploaded original as
 * it is; the sizes it generates (thumbnail, medium, large…) become WebP,
 * and so does the "-scaled" copy of a very large original.
 *
 * PNGs are left alone: they're usually screenshots or graphics with sharp
 * text, where lossy WebP can blur edges.
 *
 * If the server's image library (GD or Imagick) can't write WebP,
 * WordPress falls back to JPEG on its own.
 *
 * Applies to new uploads only. To convert existing images, regenerate
 * their sizes: `wp media regenerate --yes`.
 */
class ImageFormats {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'image_editor_output_format', array( self::class, 'output_formats' ) );
	}

	/**
	 * Map JPEG uploads to WebP output.
	 *
	 * @param array $formats Source MIME type => output MIME type.
	 * @return array
	 */
	public static function output_formats( $formats ): array {
		$formats = (array) $formats;

		// A mapping set elsewhere (another plugin, or the site's own code) wins.
		$formats['image/jpeg'] = $formats['image/jpeg'] ?? 'image/webp';

		return $formats;
	}
}
