<?php
/**
 * Tests for the contact form's REST endpoint and its no-JS fallback.
 *
 * @package DSG\Core
 */

use DSG\Core\Contact\ContactForm;
use DSG\Core\Contact\FallbackHandler;

/**
 * Checks both ways a message can arrive return the right result.
 */
class Test_Contact_Endpoint extends WP_UnitTestCase {

	private const ROUTE = '/dsg/v1/contact';

	public function set_up() {
		parent::set_up();
		reset_phpmailer_instance();
		do_action( 'rest_api_init' );
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
				'name'             => 'Ada Lovelace',
				'email'            => 'ada@example.com',
				'message'          => 'Hello!',
				ContactForm::TOKEN => ContactForm::token( time() - 10 ),
			),
			$overrides
		);
	}

	/**
	 * POST to the endpoint as JSON, like the block's script does.
	 *
	 * @param array $body Fields.
	 * @return WP_REST_Response
	 */
	private function post( array $body ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}

	public function test_valid_message_returns_200() {
		$response = $this->post( $this->input() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'message', $response->get_data() );
	}

	public function test_invalid_fields_return_400_with_a_message_per_field() {
		$response = $this->post( $this->input( array( 'email' => 'nope' ) ) );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'dsg_contact_invalid', $data['code'] );
		$this->assertArrayHasKey( 'email', $data['data']['fields'] );
	}

	public function test_rate_limited_visitor_gets_429() {
		add_filter( 'dsg_core_contact_client_ip', fn() => '203.0.113.9' );
		for ( $i = 0; $i < ContactForm::RATE_LIMIT; $i++ ) {
			$this->post( $this->input() );
		}

		$this->assertSame( 429, $this->post( $this->input() )->get_status() );
	}

	public function test_get_is_not_allowed() {
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_fallback_success_returns_to_the_form() {
		$url = FallbackHandler::process( $this->input(), '203.0.113.7', 'https://example.org/about/?dsg_contact=old#top' );

		$this->assertSame( 'https://example.org/about/?dsg_contact=sent#contact', $url );
		$this->assertSame( array( 'status' => 'sent' ), FallbackHandler::result_for( 'sent' ) );
	}

	public function test_fallback_error_keeps_the_errors_and_typed_values() {
		$url = FallbackHandler::process( $this->input( array( 'email' => 'nope' ) ), '203.0.113.7', '' );

		$this->assertStringStartsWith( home_url( '/?dsg_contact=' ), $url );
		$this->assertStringEndsWith( '#contact', $url );

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$result = FallbackHandler::result_for( $query['dsg_contact'] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertArrayHasKey( 'email', $result['fields'] );
		$this->assertSame( 'Ada Lovelace', $result['values']['name'] );
		$this->assertSame( 'Hello!', $result['values']['message'] );
	}

	public function test_unknown_result_key_is_ignored() {
		$this->assertNull( FallbackHandler::result_for( 'deadbeef' ) );
		$this->assertNull( FallbackHandler::result_for( '' ) );
	}
}
