/**
 * Helpers for the "Search & sharing" panel.
 * Pure functions (no React, no WordPress), so they're easy to test.
 */

/**
 * Meta keys, matching includes/Seo/Fields.php.
 */
export const KEYS = {
	title: 'dsg_seo_title',
	description: 'dsg_seo_description',
	image: 'dsg_seo_image',
	noindex: 'dsg_seo_noindex',
};

/**
 * About where search results cut text off.
 */
export const LIMITS = { title: 60, description: 155 };

/**
 * The post types that have the panel, matching Fields::POST_TYPES.
 */
export const POST_TYPES = [ 'page', 'dsg_project' ];

/**
 * Plain text on one line: tags removed, spaces collapsed.
 *
 * @param {string} text Text that may contain HTML.
 * @return {string} Plain text.
 */
export function plainText( text ) {
	return String( text ?? '' )
		.replace( /<[^>]*>/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/**
 * What a field will show when it's left empty: the first value that has text.
 * Follows the same order as includes/Seo/Context.php.
 *
 * @param {...string} candidates Fallbacks, best first.
 * @return {string} The fallback, or "".
 */
export function fallback( ...candidates ) {
	return candidates.map( plainText ).find( ( text ) => text !== '' ) ?? '';
}

/**
 * Character count and whether it's too long for search results.
 *
 * @param {string} text  The text.
 * @param {number} limit About where search results cut it off.
 * @return {{count: number, tooLong: boolean}} The count and verdict.
 */
export function lengthStatus( text, limit ) {
	const count = [ ...plainText( text ) ].length;

	return { count, tooLong: count > limit };
}
