<?php
/**
 * REST endpoint for the contact form: POST /wp-json/dsg/v1/contact.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Contact;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The route the contact form block's JS posts to.
 *
 * Open to everyone, with no nonce: visitors aren't logged in, so there's
 * no account to protect from forged requests, and a nonce printed into a
 * cached page goes stale and would block real messages. ContactForm's
 * honeypot, timing check and rate limit do the protecting.
 *
 * Replies:
 * - 200 { message }                  Accepted.
 * - 400 { code, message, data.fields } A field is missing or invalid.
 * - 400 { code, message }            The form expired.
 * - 429 { code, message }            Too many messages.
 */
class ContactEndpoint {

	public const NAMESPACE = 'dsg/v1';
	public const ROUTE     = '/contact';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Register the route.
	 *
	 * Fields are declared as strings but not required here: ContactForm
	 * checks them all at once, so the form can show every problem together.
	 *
	 * @return void
	 */
	public function register_route(): void {
		$string = array(
			'type'    => 'string',
			'default' => '',
		);

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'name'                => $string,
					'email'               => $string,
					'message'             => $string,
					ContactForm::HONEYPOT => $string,
					ContactForm::TOKEN    => $string,
				),
			)
		);
	}

	/**
	 * Handle a submission.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function submit( WP_REST_Request $request ) {
		$result = ( new ContactForm() )->handle(
			array(
				'name'                => $request['name'],
				'email'               => $request['email'],
				'message'             => $request['message'],
				ContactForm::HONEYPOT => $request[ ContactForm::HONEYPOT ],
				ContactForm::TOKEN    => $request[ ContactForm::TOKEN ],
			),
			ContactForm::client_ip()
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'message' => self::success_message() ), 200 );
	}

	/**
	 * What the visitor sees after sending.
	 *
	 * @return string
	 */
	public static function success_message(): string {
		return __( 'Thanks! Your message is on its way. I’ll get back to you soon.', 'dsg-core' );
	}
}
