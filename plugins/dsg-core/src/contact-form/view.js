/**
 * Contact form: send without leaving the page.
 *
 * The form already works without this file: it posts to admin-post.php
 * and the server redirects back. This script takes over when it loads:
 * - checks the fields first, and shows every error at once;
 * - sends the form to the REST endpoint (POST /wp-json/dsg/v1/contact);
 * - shows the result in the status line, which screen readers announce;
 * - moves focus to the first field with an error.
 *
 * State lives in the form's data-wp-context (see render.php). The error
 * texts come from the server, so they match its checks and translations.
 */
import {
	store,
	getContext,
	getElement,
	withSyncEvent,
} from '@wordpress/interactivity';

import { validate, allFields, firstInvalid, FIELDS } from './utils.js';

const { state } = store( 'dsg/contact-form', {
	actions: {
		/**
		 * Check the fields, then send them. A generator, so the
		 * Interactivity API keeps this form's context across `yield`.
		 *
		 * withSyncEvent: preventDefault() has to run straight away,
		 * before the browser submits the form the normal way.
		 *
		 * @param {SubmitEvent} event The submit event.
		 */
		submit: withSyncEvent( function* ( event ) {
			event.preventDefault();

			const context = getContext();
			const form = event.currentTarget;

			if ( context.isSending ) {
				return;
			}

			const data = Object.fromEntries( new FormData( form ) );

			const errors = validate( data, state.messages );
			if ( firstInvalid( errors ) ) {
				showErrors( context, form, errors, state.messages.checkFields );
				return;
			}

			setStatus( context, state.messages.sending );
			context.isSending = true;

			try {
				const response = yield fetch( state.endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( data ),
				} );
				const body = yield response.json().catch( () => ( {} ) );

				if ( response.ok ) {
					form.reset();
					showErrors( context, form, {} );
					setStatus( context, context.successMessage, 'sent' );
				} else if ( body.data?.fields ) {
					showErrors( context, form, body.data.fields, body.message );
				} else {
					setStatus(
						context,
						body.message || state.messages.failed,
						'error'
					);
				}
			} catch {
				// Offline, or the server didn't answer.
				setStatus( context, state.messages.failed, 'error' );
			} finally {
				context.isSending = false;
			}
		} ),

		/**
		 * Clear a field's error as soon as it's edited.
		 *
		 * @param {InputEvent} event The input event, from any field.
		 */
		clearError( event ) {
			const context = getContext();
			const key = event.target.name;

			if ( FIELDS.includes( key ) && context.errors[ key ] ) {
				context.errors[ key ] = '';
				context.invalid[ key ] = false;
			}
		},
	},

	callbacks: {
		/**
		 * With JS running, this script checks the fields and shows the
		 * errors in the page. Turn off the browser's own error bubbles,
		 * which disappear and don't reach every screen reader.
		 */
		init() {
			getElement().ref.noValidate = true;
		},
	},
} );

/**
 * Show an error per field (or clear them all), and focus the first one.
 *
 * @param {Object}                 context The form's context.
 * @param {HTMLFormElement}        form    The form.
 * @param {Object<string, string>} errors  Errors keyed by field.
 * @param {string}                 [text]  Status line text.
 */
function showErrors( context, form, errors, text = '' ) {
	const all = allFields( errors );
	context.errors = all;
	context.invalid = Object.fromEntries(
		Object.entries( all ).map( ( [ key, value ] ) => [ key, !! value ] )
	);

	const first = firstInvalid( all );
	setStatus( context, text, first ? 'error' : undefined );

	if ( first ) {
		form.elements.namedItem( first )?.focus();
	}
}

/**
 * Set the status line's text and look.
 *
 * @param {Object} context The form's context.
 * @param {string} text    What to say.
 * @param {string} [kind]  'sent', 'error', or nothing for neutral text.
 */
function setStatus( context, text, kind ) {
	context.statusText = text;
	context.isSent = 'sent' === kind;
	context.isError = 'error' === kind;
}
