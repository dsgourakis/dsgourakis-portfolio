/**
 * Pure helpers for the contact form, kept apart from the store so they
 * can be unit tested without a browser or the Interactivity API.
 */

export const FIELDS = [ 'name', 'email', 'message' ];

// Same idea as WordPress's is_email(): something@something.something,
// no spaces. The server makes the final call.
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Check the fields the way the server will, so most mistakes are caught
 * without a round trip. Lengths are capped by maxlength in the markup.
 *
 * @param {Object<string, string>} values   Field values, keyed by name.
 * @param {Object<string, string>} messages Error texts from the server (ContactForm::messages()).
 * @return {Object<string, string>} An error per invalid field; empty when all are fine.
 */
export function validate( values, messages ) {
	const errors = {};
	const value = ( key ) => ( values[ key ] ?? '' ).trim();

	if ( ! value( 'name' ) ) {
		errors.name = messages.nameRequired;
	}

	if ( ! value( 'email' ) ) {
		errors.email = messages.emailRequired;
	} else if ( ! EMAIL.test( value( 'email' ) ) ) {
		errors.email = messages.emailInvalid;
	}

	if ( ! value( 'message' ) ) {
		errors.message = messages.messageRequired;
	}

	return errors;
}

/**
 * An error for every field: the given ones, and '' for the rest.
 *
 * The store keeps one entry per field, so the markup can bind to each.
 *
 * @param {Object<string, string>} errors Errors for some fields.
 * @return {Object<string, string>} One entry per field.
 */
export function allFields( errors = {} ) {
	return Object.fromEntries(
		FIELDS.map( ( key ) => [ key, errors[ key ] ?? '' ] )
	);
}

/**
 * The first field (in form order) that has an error.
 *
 * @param {Object<string, string>} errors Errors keyed by field.
 * @return {string|undefined} The field's name, or undefined when all are fine.
 */
export function firstInvalid( errors ) {
	return FIELDS.find( ( key ) => errors[ key ] );
}
