<?php
/**
 * Which pages search engines should leave out.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

/**
 * Adds "noindex, follow" to the robots meta tag WordPress prints:
 * search engines skip the page but still follow its links.
 *
 * Used for 404s, search results, filtered lists (?tech=), pages with
 * "Hide from search engines" ticked, and every page outside production
 * (see Context::is_noindex()).
 */
class Robots {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'wp_robots', array( $this, 'filter' ) );
	}

	/**
	 * Add noindex where Context says so.
	 *
	 * @param array $robots Directives, e.g. array( 'max-image-preview' => 'large' ).
	 * @return array
	 */
	public function filter( array $robots ): array {
		if ( ! Context::is_noindex() ) {
			return $robots;
		}

		$robots['noindex'] = true;
		// "Discourage search engines" sets nofollow; keep that if it's there.
		if ( empty( $robots['nofollow'] ) ) {
			$robots['follow'] = true;
		}
		// Only matters for pages that are indexed.
		unset( $robots['max-image-preview'] );

		return $robots;
	}
}
