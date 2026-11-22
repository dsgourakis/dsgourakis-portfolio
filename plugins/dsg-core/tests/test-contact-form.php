<?php
/**
 * Tests for the contact form's submission handling.
 *
 * @package DSG\Core
 */

use DSG\Core\Contact\ContactForm;
use DSG\Core\Contact\Mail;
use DSG\Core\Contact\Messages;

/**
 * Checks validation, the spam checks, and that an accepted message is
 * saved and emailed.
 */
class Test_Contact_Form extends WP_UnitTestCase {

	private const IP = '203.0.113.7';

	public function set_up() {
		parent::set_up();
		reset_phpmailer_instance();

		// The test site runs on "localhost", and "wordpress@localhost" isn't a valid sender.
		add_filter( 'wp_mail_from', fn() => 'site@example.org' );
	}

	/**
	 * A valid submission, as if the form was loaded 10 seconds ago.
	 *
	 * @param array $overrides Fields to change.
	 * @return array
	 */
	private function input( array $overrides = array() ): array {
		return array_merge(
			array(
				'name'                => 'Ada Lovelace',
				'email'               => 'ada@example.com',
				'message'             => 'Hello, I have a project for you.',
				ContactForm::HONEYPOT => '',
				ContactForm::TOKEN    => ContactForm::token( time() - 10 ),
			),
			$overrides
		);
	}

	/**
	 * All saved messages.
	 *
	 * @return WP_Post[]
	 */
	private function saved_messages(): array {
		return get_posts(
			array(
				'post_type'   => Messages::POST_TYPE,
				'post_status' => 'any',
			)
		);
	}

	public function test_valid_fields_are_cleaned_and_returned() {
		$fields = ContactForm::validate(
			array(
				'name'    => '  <b>Ada</b> ',
				'email'   => ' ada@example.com ',
				'message' => "Line one\nLine two <script>x</script>",
			)
		);

		$this->assertSame(
			array(
				'name'    => 'Ada',
				'email'   => 'ada@example.com',
				'message' => "Line one\nLine two",
			),
			$fields
		);
	}

	public function test_every_missing_field_is_reported_at_once() {
		$error = ContactForm::validate( array() );

		$this->assertWPError( $error );
		$this->assertSame( 400, $error->get_error_data()['status'] );
		$this->assertSame( array( 'name', 'email', 'message' ), array_keys( $error->get_error_data()['fields'] ) );
	}

	public function test_invalid_email_is_rejected() {
		$error = ContactForm::validate( $this->input( array( 'email' => 'not-an-email' ) ) );

		$this->assertWPError( $error );
		$this->assertSame( array( 'email' ), array_keys( $error->get_error_data()['fields'] ) );
	}

	public function test_overlong_message_is_rejected() {
		$error = ContactForm::validate( $this->input( array( 'message' => str_repeat( 'a', 5001 ) ) ) );

		$this->assertWPError( $error );
		$this->assertSame( array( 'message' ), array_keys( $error->get_error_data()['fields'] ) );
	}

	public function test_token_reports_its_age() {
		$this->assertSame( 30, ContactForm::token_age( ContactForm::token( time() - 30 ) ) );
	}

	public function test_tampered_or_garbage_tokens_are_not_genuine() {
		$token  = ContactForm::token( time() - 30 );
		$forged = ( time() - 3600 ) . substr( $token, strpos( $token, '.' ) );

		$this->assertNull( ContactForm::token_age( $forged ) );
		$this->assertNull( ContactForm::token_age( '' ) );
		$this->assertNull( ContactForm::token_age( 'abc.def' ) );
	}

	public function test_accepted_message_is_saved_and_emailed() {
		$this->assertTrue( ( new ContactForm() )->handle( $this->input(), self::IP ) );

		$messages = $this->saved_messages();
		$this->assertCount( 1, $messages );
		$this->assertSame( 'private', $messages[0]->post_status );
		$this->assertSame( 'Hello, I have a project for you.', $messages[0]->post_content );
		$this->assertSame( 'ada@example.com', get_post_meta( $messages[0]->ID, Messages::META_EMAIL, true ) );
		$this->assertSame( '1', get_post_meta( $messages[0]->ID, Messages::META_MAIL_SENT, true ) );

		$mail = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertSame( Mail::recipient(), $mail->to[0][0] );
		$this->assertStringContainsString( 'Ada Lovelace', $mail->subject );
		$this->assertStringContainsString( 'Hello, I have a project for you.', $mail->body );
		$this->assertStringContainsString( 'Reply-To: Ada Lovelace <ada@example.com>', $mail->header );
	}

	public function test_filled_honeypot_pretends_to_succeed() {
		$result = ( new ContactForm() )->handle( $this->input( array( ContactForm::HONEYPOT => 'https://spam.example' ) ), self::IP );

		$this->assertTrue( $result );
		$this->assertCount( 0, $this->saved_messages() );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_instant_submission_pretends_to_succeed() {
		$result = ( new ContactForm() )->handle( $this->input( array( ContactForm::TOKEN => ContactForm::token() ) ), self::IP );

		$this->assertTrue( $result );
		$this->assertCount( 0, $this->saved_messages() );
	}

	public function test_invalid_token_asks_for_a_reload() {
		$result = ( new ContactForm() )->handle( $this->input( array( ContactForm::TOKEN => 'nope' ) ), self::IP );

		$this->assertWPError( $result );
		$this->assertSame( 'dsg_contact_expired', $result->get_error_code() );
		$this->assertCount( 0, $this->saved_messages() );
	}

	public function test_invalid_fields_are_not_saved() {
		$result = ( new ContactForm() )->handle( $this->input( array( 'email' => '' ) ), self::IP );

		$this->assertWPError( $result );
		$this->assertCount( 0, $this->saved_messages() );
	}

	public function test_too_many_messages_are_rate_limited_per_visitor() {
		$form = new ContactForm();
		for ( $i = 0; $i < ContactForm::RATE_LIMIT; $i++ ) {
			$this->assertTrue( $form->handle( $this->input(), self::IP ) );
		}

		$result = $form->handle( $this->input(), self::IP );
		$this->assertWPError( $result );
		$this->assertSame( 429, $result->get_error_data()['status'] );

		// Someone else can still send.
		$this->assertTrue( $form->handle( $this->input(), '198.51.100.1' ) );
	}

	public function test_invalid_attempts_do_not_use_up_the_limit() {
		$form = new ContactForm();
		for ( $i = 0; $i < ContactForm::RATE_LIMIT + 2; $i++ ) {
			$form->handle( $this->input( array( 'email' => 'typo' ) ), self::IP );
		}

		$this->assertTrue( $form->handle( $this->input(), self::IP ) );
	}

	public function test_recipient_defaults_to_the_admin_email() {
		$this->assertSame( get_option( 'admin_email' ), Mail::recipient() );
	}
}
