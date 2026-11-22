<?php
/**
 * The contact form without JavaScript: a normal form post and redirect.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Contact;

/**
 * Receives the form when it's posted the old way, to admin-post.php.
 *
 * With JS on, the block's script sends the form to the REST endpoint and
 * this never runs. With JS off (or failed), the browser posts the form
 * here. It's handled by the same ContactForm, then the browser is sent
 * back to the page it came from, at #contact:
 *
 * - Sent:   ?dsg_contact=sent
 * - Failed: ?dsg_contact=<key>. The key points to a short-lived transient
 *   holding the errors and what was typed, so the form can show the
 *   errors and keep the visitor's text. No personal data goes in the URL.
 */
class FallbackHandler {

	/**
	 * The admin-post.php action, sent as a hidden "action" field.
	 */
	public const ACTION = 'dsg_contact';

	/**
	 * Query argument that carries the result back to the page.
	 */
	public const QUERY_ARG = 'dsg_contact';

	/**
	 * How long a failed attempt's details are kept, in seconds.
	 */
	public const RESULT_LIFETIME = 10 * MINUTE_IN_SECONDS;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle_request' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_request' ) );
	}

	/**
	 * Handle the posted form, then redirect back.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		// No nonce on purpose: see ContactEndpoint for why.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$input = array();
		foreach ( array( 'name', 'email', 'message', ContactForm::HONEYPOT, ContactForm::TOKEN ) as $key ) {
			// Each field is sanitized by ContactForm::validate().
			$input[ $key ] = isset( $_POST[ $key ] ) ? (string) wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		// phpcs:enable

		$referer = (string) wp_get_referer();

		wp_safe_redirect( self::process( $input, ContactForm::client_ip(), $referer ), 303 );
		exit;
	}

	/**
	 * Handle a submission and return where to send the browser.
	 *
	 * @param array  $input     Raw submitted fields (unslashed).
	 * @param string $client_ip The sender's IP.
	 * @param string $referer   The page the form was on. Empty for the homepage.
	 * @return string
	 */
	public static function process( array $input, string $client_ip, string $referer ): string {
		$result = ( new ContactForm() )->handle( $input, $client_ip );

		if ( true === $result ) {
			return self::back_to_form( $referer, 'sent' );
		}

		// Lowercase hex, so it survives sanitize_key() when read back.
		$key = bin2hex( random_bytes( 8 ) );
		set_transient(
			self::transient_name( $key ),
			array(
				'message' => $result->get_error_message(),
				'fields'  => $result->get_error_data()['fields'] ?? array(),
				'values'  => array(
					'name'    => sanitize_text_field( $input['name'] ?? '' ),
					'email'   => sanitize_text_field( $input['email'] ?? '' ),
					'message' => sanitize_textarea_field( $input['message'] ?? '' ),
				),
			),
			self::RESULT_LIFETIME
		);

		return self::back_to_form( $referer, $key );
	}

	/**
	 * The result of the last fallback post, read from the current URL.
	 *
	 * @return array|null Null when there's none. Otherwise an array with
	 *                    'status' ('sent' or 'error') and, for errors,
	 *                    'message', 'fields' and 'values'.
	 */
	public static function current_result(): ?array {
		// Only reads a result; nothing is changed.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = isset( $_GET[ self::QUERY_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_ARG ] ) ) : '';

		return self::result_for( $value );
	}

	/**
	 * The result a query value points to.
	 *
	 * @param string $value The query argument's value.
	 * @return array|null
	 */
	public static function result_for( string $value ): ?array {
		if ( 'sent' === $value ) {
			return array( 'status' => 'sent' );
		}

		if ( '' === $value ) {
			return null;
		}

		$stored = get_transient( self::transient_name( $value ) );
		if ( ! is_array( $stored ) ) {
			return null;
		}

		return array( 'status' => 'error' ) + $stored;
	}

	/**
	 * The form's page with the result added, scrolled to the form.
	 *
	 * @param string $referer The page the form was on.
	 * @param string $value   'sent' or a result key.
	 * @return string
	 */
	private static function back_to_form( string $referer, string $value ): string {
		$page = '' !== $referer ? $referer : home_url( '/' );
		$page = remove_query_arg( self::QUERY_ARG, strtok( $page, '#' ) );

		return add_query_arg( self::QUERY_ARG, $value, $page ) . '#contact';
	}

	/**
	 * Transient name for a result key.
	 *
	 * @param string $key Result key.
	 * @return string
	 */
	private static function transient_name( string $key ): string {
		return 'dsg_contact_result_' . $key;
	}
}
