<?php
/**
 * The Resume link: one site-wide setting that any button can use.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Settings;

use WP_Block;

/**
 * Stores which PDF is the resume, and feeds its URL to blocks.
 *
 * - Setting: Settings > General > Resume.
 * - Permanent URL: /cv.pdf always serves the chosen PDF.
 * - Block binding: a Button bound to the "dsg-core/resume" source gets
 *   /cv.pdf as its link.
 */
class ResumeLink {

	/**
	 * Option name. Holds an attachment ID, 0 when none is set.
	 */
	public const OPTION = 'dsg_resume_id';

	/**
	 * Block bindings source name.
	 */
	public const SOURCE = 'dsg-core/resume';

	/**
	 * The permanent path, relative to the site's home URL.
	 */
	public const PATH = 'cv.pdf';

	/**
	 * How long browsers and CDNs may reuse the PDF without asking again.
	 * Short, so a new version shows up within the hour.
	 */
	private const MAX_AGE = HOUR_IN_SECONDS;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'parse_request', array( $this, 'serve' ) );
		add_action( 'init', array( $this, 'register_setting' ) );
		add_action( 'init', array( $this, 'register_binding_source' ) );
		add_action( 'admin_init', array( $this, 'add_settings_field' ) );
		add_filter( 'render_block_core/button', array( $this, 'hide_when_empty' ), 10, 2 );
		add_filter( 'render_block_core/buttons', array( $this, 'remove_empty_buttons_group' ) );
	}

	/**
	 * Register the option. On "init" (not "admin_init") so the REST API knows it too.
	 *
	 * @return void
	 */
	public function register_setting(): void {
		register_setting(
			'general',
			self::OPTION,
			array(
				'type'              => 'integer',
				'description'       => __( 'Attachment ID of the resume PDF.', 'dsg-core' ),
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);
	}

	/**
	 * Register the "dsg-core/resume" source for block bindings.
	 *
	 * @return void
	 */
	public function register_binding_source(): void {
		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Resume PDF', 'dsg-core' ),
				'get_value_callback' => array( self::class, 'url' ),
			)
		);
	}

	/**
	 * Add the field to Settings > General.
	 *
	 * @return void
	 */
	public function add_settings_field(): void {
		add_settings_field(
			self::OPTION,
			__( 'Resume', 'dsg-core' ),
			array( $this, 'render_field' ),
			'general',
			'default',
			array( 'label_for' => self::OPTION )
		);
	}

	/**
	 * A dropdown of the PDFs in the Media Library.
	 *
	 * @return void
	 */
	public function render_field(): void {
		$pdfs    = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'application/pdf',
				'post_status'    => 'inherit',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$current = (int) get_option( self::OPTION, 0 );

		printf( '<select id="%1$s" name="%1$s">', esc_attr( self::OPTION ) );
		printf( '<option value="0">%s</option>', esc_html__( '— None (hide the Resume button) —', 'dsg-core' ) );
		foreach ( $pdfs as $pdf ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $pdf->ID,
				selected( $current, $pdf->ID, false ),
				esc_html( sprintf( '%s (%s)', get_the_title( $pdf ), get_the_date( '', $pdf ) ) )
			);
		}
		echo '</select>';

		printf(
			'<p class="description">%s <a href="%s">%s</a></p>',
			esc_html__( 'The PDF the header’s Resume button opens. To use a new version,', 'dsg-core' ),
			esc_url( admin_url( 'media-new.php' ) ),
			esc_html__( 'upload it to the Media Library first.', 'dsg-core' )
		);

		printf(
			'<p class="description">%s <code>%s</code></p>',
			esc_html__( 'Permanent link, always the selected PDF:', 'dsg-core' ),
			esc_html( home_url( '/' . self::PATH ) )
		);
	}

	/**
	 * Keep the option only when it points at a PDF attachment.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	public static function sanitize( $value ): int {
		$id = absint( $value );

		return ( $id && 'application/pdf' === get_post_mime_type( $id ) ) ? $id : 0;
	}

	/**
	 * The resume's permanent URL (/cv.pdf), or null when none is set or the file is gone.
	 *
	 * Also the bindings callback, which is why it returns null rather than "":
	 * null tells WordPress to leave the block's own value alone.
	 *
	 * @return string|null
	 */
	public static function url(): ?string {
		return self::attachment_url() ? home_url( '/' . self::PATH ) : null;
	}

	/**
	 * The current PDF's real Media Library URL, or null.
	 *
	 * @return string|null
	 */
	public static function attachment_url(): ?string {
		$id = (int) get_option( self::OPTION, 0 );
		if ( ! $id ) {
			return null;
		}

		$url = wp_get_attachment_url( $id );

		return $url ? $url : null;
	}

	/**
	 * Answer requests for /cv.pdf with the current resume.
	 *
	 * Runs on "parse_request": WordPress has worked out the requested path
	 * but hasn't queried posts yet. No rewrite rule needed, so nothing to
	 * flush. Any other path, or no resume set, falls through untouched.
	 *
	 * @param \WP $wp The current request.
	 * @return void
	 */
	public function serve( \WP $wp ): void {
		if ( self::PATH !== $wp->request || ! self::attachment_url() ) {
			return;
		}

		$file = get_attached_file( (int) get_option( self::OPTION ) );

		// The file isn't on this server (e.g. media offloaded to a CDN): redirect.
		// 302, temporary, so search engines keep /cv.pdf as the address.
		if ( ! $file || ! is_readable( $file ) ) {
			wp_redirect( self::attachment_url(), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- our own upload URL.
			exit;
		}

		$modified = (int) filemtime( $file );

		header( 'Content-Type: application/pdf' );
		// "inline" opens it in the browser; the filename is used if the visitor saves it.
		header( sprintf( 'Content-Disposition: inline; filename="%s"', basename( $file ) ) );
		header( sprintf( 'Link: <%s>; rel="canonical"', self::url() ) );
		header( 'Cache-Control: public, max-age=' . self::MAX_AGE );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified ) . ' GMT' );

		// The browser already has this version: answer "not modified", send no file.
		$since = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? strtotime( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) ) : false;
		if ( $since && $since >= $modified ) {
			status_header( 304 );
			exit;
		}

		status_header( 200 );
		header( 'Content-Length: ' . filesize( $file ) );

		// HEAD requests (link checkers, crawlers) only want the headers.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'get';
		if ( 'head' !== $method ) {
			readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a PDF.
		}
		exit;
	}

	/**
	 * Don't show a button bound to the resume when there is no resume.
	 *
	 * Runs after WordPress has applied the binding, so a set resume is
	 * already in the button's href by now.
	 *
	 * @param string $block_content The Button block's HTML.
	 * @param array  $block         The block's data.
	 * @return string
	 */
	public function hide_when_empty( string $block_content, array $block ): string {
		$source = $block['attrs']['metadata']['bindings']['url']['source'] ?? '';

		if ( self::SOURCE === $source && null === self::url() ) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Drop a Buttons group left with no buttons, so it doesn't add a gap.
	 *
	 * @param string $block_content The Buttons block's HTML.
	 * @return string
	 */
	public function remove_empty_buttons_group( string $block_content ): string {
		return str_contains( $block_content, 'wp-block-button__link' ) ? $block_content : '';
	}
}
