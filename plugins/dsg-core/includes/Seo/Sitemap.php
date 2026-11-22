<?php
/**
 * Tidies the sitemap WordPress builds at /wp-sitemap.xml.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

/**
 * The sitemap should list only the pages worth finding:
 * - no author pages (a one-person site, and they'd show the admin's username),
 * - no Posts section while the blog has no posts,
 * - no pages with "Hide from search engines" ticked.
 *
 * Technologies have no pages (the taxonomy isn't public), so WordPress
 * already leaves them out.
 */
class Sitemap {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'remove_users' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'remove_empty_blog' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'skip_noindex' ) );
	}

	/**
	 * Drop the users (author pages) sitemap.
	 *
	 * @param \WP_Sitemaps_Provider|false $provider The provider.
	 * @param string                      $name     Its name: posts, taxonomies or users.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public function remove_users( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * Drop the Posts sitemap while there are no published posts.
	 *
	 * @param \WP_Post_Type[] $post_types Post types in the sitemap, by name.
	 * @return \WP_Post_Type[]
	 */
	public function remove_empty_blog( array $post_types ): array {
		if ( isset( $post_types['post'] ) && ! (int) wp_count_posts( 'post' )->publish ) {
			unset( $post_types['post'] );
		}

		return $post_types;
	}

	/**
	 * Leave out posts with "Hide from search engines" ticked.
	 *
	 * @param array $args WP_Query arguments for one sitemap page.
	 * @return array
	 */
	public function skip_noindex( array $args ): array {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a portfolio has a handful of posts.
		$args['meta_query']   = $args['meta_query'] ?? array();
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'     => Fields::NOINDEX,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => Fields::NOINDEX,
				'value'   => '1',
				'compare' => '!=',
			),
		);

		return $args;
	}
}
