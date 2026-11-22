/**
 * Image helpers shared by every block with an image.
 * Pure functions (no React, no WordPress), so they're easy to test.
 */

/**
 * Attributes that mean "no image".
 */
export const EMPTY_IMAGE = { imageId: undefined, imageUrl: '', imageAlt: '' };

/**
 * Turn the media object the Media Library returns into block attributes.
 * Uses the "large" size when it exists, so cards don't load the full original.
 *
 * @param {Object} media Media object from MediaUpload's onSelect.
 * @return {Object} imageId, imageUrl and imageAlt attributes.
 */
export function imageAttributesFromMedia( media ) {
	return {
		imageId: media.id,
		imageUrl: media.sizes?.large?.url ?? media.url,
		imageAlt: media.alt ?? '',
	};
}
