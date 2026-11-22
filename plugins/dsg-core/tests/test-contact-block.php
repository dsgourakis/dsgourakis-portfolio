<?php
/**
 * Tests for the Contact Form block's markup and the copyright binding.
 *
 * @package DSG\Core
 */

use DSG\Core\Blocks\Copyright;
use DSG\Core\Contact\ContactForm;
use DSG\Core\Contact\FallbackHandler;

/**
 * Checks the form works without JS: it posts to admin-post.php, and after
 * the redirect it shows the result. Run `npm run build` first.
 */
class Test_Contact_Block extends WP_UnitTestCase {

	private const BLOCK = '<!-- wp:dsg/contact-form /-->';

	public function tear_down() {
		unset( $_GET[ FallbackHandler::QUERY_ARG ] );
		parent::tear_down();
	}

	public function test_form_posts_to_admin_post_with_its_hidden_fields() {
		$html = do_blocks( self::BLOCK );

		$this->assertStringContainsString( 'action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"', $html );
		$this->assertStringContainsString( 'method="post"', $html );
		$this->assertStringContainsString( 'name="action" value="' . FallbackHandler::ACTION . '"', $html );
		$this->assertMatchesRegularExpression( '/name="' . ContactForm::TOKEN . '" value="\d+\.\w+"/', $html );
		$this->assertStringContainsString( 'name="' . ContactForm::HONEYPOT . '" tabindex="-1"', $html );
	}

	public function test_every_field_has_a_label_and_an_error_slot() {
		$html = do_blocks( self::BLOCK );

		foreach ( array( 'name', 'email', 'message' ) as $key ) {
			$this->assertMatchesRegularExpression( '/<label for="(dsg-contact-\d+-' . $key . ')">.+?id="\1"/s', $html );
			$this->assertMatchesRegularExpression( '/aria-describedby="(dsg-contact-\d+-' . $key . '-error)".+?<p[^>]*\sid="\1"/s', $html );
		}
		$this->assertStringContainsString( 'role="status"', $html );
		$this->assertStringNotContainsString( 'aria-invalid="true"', $html );
	}

	public function test_shows_success_after_a_fallback_post() {
		$_GET[ FallbackHandler::QUERY_ARG ] = 'sent';

		$html = do_blocks( '<!-- wp:dsg/contact-form {"successMessage":"Got it, thanks."} /-->' );

		$this->assertMatchesRegularExpression( '/role="status"[^>]*>Got it, thanks\.</s', $html );
	}

	public function test_shows_errors_and_keeps_the_text_after_a_failed_fallback_post() {
		$url = FallbackHandler::process(
			array(
				'name'             => 'Ada',
				'email'            => 'nope',
				'message'          => 'My <b>message</b>',
				ContactForm::TOKEN => ContactForm::token( time() - 10 ),
			),
			'203.0.113.7',
			''
		);
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$_GET[ FallbackHandler::QUERY_ARG ] = $query[ FallbackHandler::QUERY_ARG ];

		$html = do_blocks( self::BLOCK );

		$this->assertStringContainsString( 'value="Ada"', $html );
		$this->assertStringContainsString( 'My message</textarea>', $html );
		// Server-side directive processing may put attributes in any order.
		$this->assertMatchesRegularExpression( '/<input[^>]*\saria-invalid="true"[^>]*\sname="email"|<input[^>]*\sname="email"[^>]*\saria-invalid="true"/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]*\saria-invalid="false"[^>]*\sname="name"|<input[^>]*\sname="name"[^>]*\saria-invalid="false"/', $html );
		$this->assertStringContainsString( ContactForm::messages()['emailInvalid'], $html );
		$this->assertStringContainsString( ContactForm::messages()['checkFields'], $html );
	}

	public function test_copyright_shows_this_year_and_the_name() {
		$this->assertSame( '© ' . wp_date( 'Y' ) . ' Ada', Copyright::text( array( 'name' => 'Ada' ) ) );
		$this->assertSame( '© ' . wp_date( 'Y' ) . ' ' . get_bloginfo( 'name' ), Copyright::text() );
	}

	public function test_bound_paragraph_gets_the_current_year() {
		$html = do_blocks( '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"dsg-core/copyright","args":{"name":"Ada"}}}}} --><p>© 1999 Ada</p><!-- /wp:paragraph -->' );

		$this->assertStringContainsString( '© ' . wp_date( 'Y' ) . ' Ada', $html );
		$this->assertStringNotContainsString( '1999', $html );
	}
}
