<?php
/**
 * Tests for the Resume link setting and its block binding.
 *
 * @package DSG\Core
 */

use DSG\Core\Settings\ResumeLink;

/**
 * Checks the setting only accepts PDFs, and that a bound button gets the
 * PDF's URL, or disappears when there is none.
 */
class Test_Resume_Link extends WP_UnitTestCase {

	/**
	 * A Buttons group with one button bound to the resume, like the header's.
	 */
	private const MARKUP = '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"metadata":{"bindings":{"url":{"source":"dsg-core/resume"}}}} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" target="_blank" rel="noreferrer noopener">Resume</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';

	/**
	 * Create an attachment with the given MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @param string $file      File name.
	 * @return int Attachment ID.
	 */
	private function attachment( string $mime_type, string $file ): int {
		return self::factory()->attachment->create(
			array(
				'post_mime_type' => $mime_type,
				'file'           => $file,
			)
		);
	}

	public function test_sanitize_only_keeps_pdfs() {
		$pdf   = $this->attachment( 'application/pdf', 'resume.pdf' );
		$image = $this->attachment( 'image/jpeg', 'photo.jpg' );

		$this->assertSame( $pdf, ResumeLink::sanitize( (string) $pdf ) );
		$this->assertSame( 0, ResumeLink::sanitize( $image ) );
		$this->assertSame( 0, ResumeLink::sanitize( 'nonsense' ) );
	}

	public function test_bound_button_links_to_the_resume() {
		$pdf = $this->attachment( 'application/pdf', 'resume.pdf' );
		update_option( ResumeLink::OPTION, $pdf );

		$html = do_blocks( self::MARKUP );

		// The permanent URL, not the Media Library one that changes per upload.
		$this->assertStringContainsString( 'href="' . home_url( '/cv.pdf' ) . '"', $html );
		$this->assertStringNotContainsString( wp_get_attachment_url( $pdf ), $html );
	}

	public function test_url_is_null_without_a_resume() {
		delete_option( ResumeLink::OPTION );

		$this->assertNull( ResumeLink::url() );
	}

	/**
	 * Serving the PDF ends the request (exit), so only the cases that must
	 * NOT serve it can be tested here: they have to return normally.
	 */
	public function test_serve_ignores_other_paths() {
		$pdf = $this->attachment( 'application/pdf', 'resume.pdf' );
		update_option( ResumeLink::OPTION, $pdf );

		$wp          = new WP();
		$wp->request = 'about';
		( new ResumeLink() )->serve( $wp );

		$this->assertSame( 'about', $wp->request );
	}

	public function test_serve_falls_through_to_404_without_a_resume() {
		delete_option( ResumeLink::OPTION );

		$wp          = new WP();
		$wp->request = ResumeLink::PATH;
		( new ResumeLink() )->serve( $wp );

		$this->assertSame( ResumeLink::PATH, $wp->request );
	}

	public function test_bound_button_is_hidden_without_a_resume() {
		delete_option( ResumeLink::OPTION );

		$this->assertSame( '', trim( do_blocks( self::MARKUP ) ) );
	}

	public function test_unbound_buttons_are_left_alone() {
		delete_option( ResumeLink::OPTION );

		$html = do_blocks( '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Contact</a></div><!-- /wp:button --></div><!-- /wp:buttons -->' );

		$this->assertStringContainsString( 'href="/contact/"', $html );
	}
}
