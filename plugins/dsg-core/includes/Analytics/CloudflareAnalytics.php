<?php
/**
 * Cloudflare Web Analytics.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Analytics;

/**
 * Prints Cloudflare's analytics beacon at the end of every frontend page.
 *
 * Cloudflare's "automatic setup" is supposed to inject this script at the
 * edge, but it doesn't for this site (most likely because the origin sends
 * pre-compressed HTML), so the snippet is added here instead. In the
 * Cloudflare dashboard the site must be set to "Enable with JS Snippet
 * installation", or it may be counted twice.
 *
 * The beacon sets no cookies, so it needs no consent banner. It also counts
 * client-side navigation (see ClientNavigation): the router changes the URL
 * with the History API, and the beacon records each change as a page view.
 *
 * Not printed for logged-in users, so your own visits don't count, or
 * outside production (WP_ENVIRONMENT_TYPE), so local and staging traffic
 * stays out of the numbers.
 */
class CloudflareAnalytics {

	/**
	 * Site token from the Cloudflare dashboard. It is public: it appears in
	 * every page's HTML.
	 */
	public const TOKEN = 'f961e2f6aab648e8b5fa30be13dbe7a8';

	/**
	 * Beacon script URL.
	 */
	public const SCRIPT_URL = 'https://static.cloudflareinsights.com/beacon.min.js';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_footer', array( $this, 'print_script' ), 99 );
	}

	/**
	 * Print the beacon script tag.
	 *
	 * @return void
	 */
	public function print_script(): void {
		if ( ! self::should_track() ) {
			return;
		}

		wp_print_script_tag(
			array(
				'type'           => 'module',
				'src'            => self::SCRIPT_URL,
				'data-cf-beacon' => wp_json_encode( array( 'token' => self::TOKEN ) ),
			)
		);
	}

	/**
	 * Whether this request should be counted.
	 *
	 * @return bool
	 */
	public static function should_track(): bool {
		return 'production' === wp_get_environment_type()
			&& ! is_user_logged_in()
			&& ! is_preview()
			&& ! is_customize_preview();
	}
}
