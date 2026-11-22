/**
 * The Project's details, in the order they are shown.
 * Keys match the post meta registered in includes/PostTypes/Project.php.
 */
import { __ } from '@wordpress/i18n';

export const FIELDS = [
	{ key: 'dsg_agency', label: __( 'Agency', 'dsg-core' ) },
	{ key: 'dsg_year', label: __( 'Year', 'dsg-core' ) },
	{ key: 'dsg_role', label: __( 'Role', 'dsg-core' ) },
	{
		key: 'dsg_live_url',
		label: __( 'Live site', 'dsg-core' ),
		type: 'url',
	},
];

/**
 * The fields that have a value, ready to show.
 *
 * @param {Object} meta The post's meta object (from the REST API).
 * @return {Array<Object>} Filled-in fields, each with its value added.
 */
export function filledFields( meta = {} ) {
	return FIELDS.map( ( field ) => ( {
		...field,
		value: String( meta?.[ field.key ] ?? '' ).trim(),
	} ) ).filter( ( field ) => field.value !== '' );
}

/**
 * The live site's address without "https://", "www." or a trailing slash,
 * for display: "https://www.redcross.gr/" → "redcross.gr".
 * render.php does the same on the frontend.
 *
 * @param {string} url Full URL.
 * @return {string} Short address.
 */
export function displayUrl( url ) {
	return String( url )
		.replace( /^https?:\/\//i, '' )
		.replace( /^www\./i, '' )
		.replace( /\/$/, '' );
}
