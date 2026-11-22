<?php
/**
 * The contact form's submission handling, shared by both ways in.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Contact;

use WP_Error;

/**
 * Checks, saves and emails one contact form submission.
 *
 * Two routes call handle(): the REST endpoint (ContactEndpoint, used by
 * the block's JS) and a plain form post (FallbackHandler, when JS is off).
 * Both get the same checks and the same results.
 *
 * Spam protection, cheapest first:
 * - Honeypot: a field people can't see. Bots that fill it are told the
 *   message was sent, so they learn nothing.
 * - Timing: the form carries a signed timestamp. A form sent back within
 *   a couple of seconds of loading was filled in by a script.
 * - Rate limit: a few messages per visitor per hour.
 *
 * Every message is saved as a private post before it's emailed, so a
 * failed email never loses one.
 */
class ContactForm {

	/**
	 * Name of the honeypot field. Something a bot would want to fill in.
	 */
	public const HONEYPOT = 'website';

	/**
	 * Name of the signed timestamp field.
	 */
	public const TOKEN = 'dsg_token';

	/**
	 * A form sent back faster than this (seconds) was not filled in by a person.
	 */
	public const MIN_SECONDS = 2;

	/**
	 * How many messages one visitor can send per window.
	 */
	public const RATE_LIMIT = 5;

	/**
	 * Length of the rate-limit window, in seconds.
	 */
	public const RATE_WINDOW = HOUR_IN_SECONDS;

	/**
	 * Longest allowed value per field, in characters.
	 */
	public const MAX_LENGTH = array(
		'name'    => 100,
		'email'   => 254,
		'message' => 5000,
	);

	/**
	 * Handle one submission.
	 *
	 * @param array  $input     Raw submitted fields (unslashed).
	 * @param string $client_ip The sender's IP, used only for the rate limit.
	 * @return true|WP_Error True when the message was accepted.
	 */
	public function handle( array $input, string $client_ip ) {
		// A filled honeypot or an instant reply: pretend it worked.
		if ( '' !== trim( (string) ( $input[ self::HONEYPOT ] ?? '' ) ) ) {
			return true;
		}

		$age = self::token_age( (string) ( $input[ self::TOKEN ] ?? '' ) );
		if ( null === $age ) {
			return new WP_Error(
				'dsg_contact_expired',
				__( 'This form has expired. Please reload the page and try again.', 'dsg-core' ),
				array( 'status' => 400 )
			);
		}
		if ( $age < self::MIN_SECONDS ) {
			return true;
		}

		if ( self::is_rate_limited( $client_ip ) ) {
			return new WP_Error(
				'dsg_contact_rate_limited',
				__( 'You’ve sent several messages in a short time. Please try again later, or email me directly.', 'dsg-core' ),
				array( 'status' => 429 )
			);
		}

		$fields = self::validate( $input );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}

		self::count_submission( $client_ip );

		$post_id = Messages::save( $fields );
		$sent    = Mail::send_notification( $fields );

		if ( $post_id ) {
			update_post_meta( $post_id, Messages::META_MAIL_SENT, $sent ? 1 : 0 );
		}

		/*
		 * Accepted even if both failed: the visitor did nothing wrong, and
		 * telling them to try again would only send duplicates.
		 */
		return true;
	}

	/**
	 * Clean the fields and check them.
	 *
	 * Every field is checked, so all problems can be shown at once.
	 *
	 * @param array $input Raw submitted fields (unslashed).
	 * @return array|WP_Error The clean fields (name, email, message), or a
	 *                        WP_Error whose data has a message per field.
	 */
	public static function validate( array $input ) {
		$fields = array(
			'name'    => sanitize_text_field( (string) ( $input['name'] ?? '' ) ),
			'email'   => sanitize_email( (string) ( $input['email'] ?? '' ) ),
			'message' => sanitize_textarea_field( (string) ( $input['message'] ?? '' ) ),
		);

		$messages = self::messages();
		$errors   = array();

		if ( '' === $fields['name'] ) {
			$errors['name'] = $messages['nameRequired'];
		}

		if ( '' === trim( (string) ( $input['email'] ?? '' ) ) ) {
			$errors['email'] = $messages['emailRequired'];
		} elseif ( ! is_email( $fields['email'] ) ) {
			$errors['email'] = $messages['emailInvalid'];
		}

		if ( '' === $fields['message'] ) {
			$errors['message'] = $messages['messageRequired'];
		}

		foreach ( self::MAX_LENGTH as $key => $max ) {
			if ( ! isset( $errors[ $key ] ) && mb_strlen( $fields[ $key ] ) > $max ) {
				$errors[ $key ] = sprintf( $messages['tooLong'], $max );
			}
		}

		if ( $errors ) {
			return new WP_Error(
				'dsg_contact_invalid',
				$messages['checkFields'],
				array(
					'status' => 400,
					'fields' => $errors,
				)
			);
		}

		return $fields;
	}

	/**
	 * Every message the form can show, keyed by name.
	 *
	 * Used here and handed to the block's script, so checks in the browser
	 * show exactly the same (translated) text as checks on the server.
	 *
	 * @return array<string, string>
	 */
	public static function messages(): array {
		return array(
			'nameRequired'    => __( 'Please enter your name.', 'dsg-core' ),
			'emailRequired'   => __( 'Please enter your email address.', 'dsg-core' ),
			'emailInvalid'    => __( 'Please enter a valid email address, like name@example.com.', 'dsg-core' ),
			'messageRequired' => __( 'Please enter a message.', 'dsg-core' ),
			/* translators: %d: maximum number of characters. */
			'tooLong'         => __( 'Please keep this under %d characters.', 'dsg-core' ),
			'checkFields'     => __( 'Please check the highlighted fields.', 'dsg-core' ),
			'sending'         => __( 'Sending…', 'dsg-core' ),
			'failed'          => __( 'Your message couldn’t be sent. Please check your connection and try again, or email me directly.', 'dsg-core' ),
		);
	}

	/**
	 * A signed timestamp for the form's hidden field.
	 *
	 * @param int|null $time Unix time. Defaults to now.
	 * @return string
	 */
	public static function token( ?int $time = null ): string {
		$time = $time ?? time();

		return $time . '.' . self::sign( $time );
	}

	/**
	 * How many seconds ago a token was made, or null if it's not genuine.
	 *
	 * @param string $token Token from the form.
	 * @return int|null
	 */
	public static function token_age( string $token ): ?int {
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
			return null;
		}

		$time = (int) $parts[0];
		if ( ! hash_equals( self::sign( $time ), $parts[1] ) ) {
			return null;
		}

		return time() - $time;
	}

	/**
	 * The signature for a timestamp. Uses the site's secret salts.
	 *
	 * @param int $time Unix time.
	 * @return string
	 */
	private static function sign( int $time ): string {
		return substr( wp_hash( 'dsg-contact|' . $time, 'nonce' ), 0, 20 );
	}

	/**
	 * Whether this visitor has used up their messages for now.
	 *
	 * @param string $client_ip The sender's IP.
	 * @return bool
	 */
	public static function is_rate_limited( string $client_ip ): bool {
		return (int) get_transient( self::rate_key( $client_ip ) ) >= self::RATE_LIMIT;
	}

	/**
	 * Count one accepted message against this visitor's limit.
	 *
	 * @param string $client_ip The sender's IP.
	 * @return void
	 */
	private static function count_submission( string $client_ip ): void {
		$key = self::rate_key( $client_ip );
		set_transient( $key, (int) get_transient( $key ) + 1, self::RATE_WINDOW );
	}

	/**
	 * Transient name for a visitor. The IP is hashed, so it's never stored.
	 *
	 * @param string $client_ip The sender's IP.
	 * @return string
	 */
	private static function rate_key( string $client_ip ): string {
		return 'dsg_contact_' . substr( wp_hash( $client_ip ), 0, 24 );
	}

	/**
	 * The sender's IP address.
	 *
	 * Behind Cloudflare, REMOTE_ADDR is only the visitor's address if the
	 * server restores it (e.g. mod_remoteip). If it doesn't, every visitor
	 * would share Cloudflare's few addresses and the rate limit with them.
	 * The filter lets the server's setup decide, without trusting headers
	 * that anyone could send.
	 *
	 * @return string
	 */
	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the IP address the contact form's rate limit uses.
		 *
		 * @param string $ip The address from REMOTE_ADDR.
		 */
		return (string) apply_filters( 'dsg_core_contact_client_ip', $ip );
	}
}
