<?php
/**
 * What search engines and link previews should say about the current page.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

use DSG\Core\PostTypes\Project;
use WP_Post;

/**
 * The one place that works out a page's title, description, image and
 * address. Head, Robots and Schema all read from here, so the meta tags,
 * the link preview and the structured data always agree.
 *
 * Each value has a fallback chain, so empty "Search & sharing" fields are fine:
 * - Title:       SEO title → post title.
 * - Description: SEO description → written excerpt → start of the content → Tagline.
 * - Image:       SEO image → featured image → screenshot (Projects) → default share image → Site Icon.
 */
class Context {

	/**
	 * Search results cut descriptions off at about this many characters.
	 */
	public const DESCRIPTION_LENGTH = 155;

	/**
	 * The query argument the Project Grid's technology filter uses.
	 */
	public const FILTER_ARG = 'tech';

	/**
	 * Whether this page gets meta tags and structured data at all.
	 * Not 404s, search results or date archives: they are noindex or unused.
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return is_front_page() || is_home() || is_singular() || is_post_type_archive( Project::POST_TYPE );
	}

	/**
	 * The post being shown, on single posts and pages.
	 *
	 * @return WP_Post|null
	 */
	public static function post(): ?WP_Post {
		$post = is_singular() ? get_queried_object() : null;

		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * The page's title, without the site name (for og:title).
	 *
	 * @return string
	 */
	public static function title(): string {
		$post = self::post();
		if ( $post ) {
			$custom = self::custom_title( $post );
			if ( '' !== $custom ) {
				return $custom;
			}
			return is_front_page() ? get_bloginfo( 'name' ) : wp_strip_all_tags( get_the_title( $post ) );
		}

		if ( is_post_type_archive( Project::POST_TYPE ) ) {
			return self::work_title();
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * The title the Work page (/portfolio/) uses in search results and the browser tab.
	 *
	 * @return string
	 */
	public static function work_title(): string {
		return __( 'Work', 'dsg-core' );
	}

	/**
	 * A post's own "SEO title" field, or "".
	 *
	 * @param WP_Post $post The post.
	 * @return string
	 */
	public static function custom_title( WP_Post $post ): string {
		return trim( (string) get_post_meta( $post->ID, Fields::TITLE, true ) );
	}

	/**
	 * The page's description.
	 *
	 * @return string
	 */
	public static function description(): string {
		$post = self::post();
		if ( $post ) {
			return self::post_description( $post );
		}

		if ( is_post_type_archive( Project::POST_TYPE ) ) {
			$work = trim( (string) get_option( Settings::WORK_DESCRIPTION, '' ) );
			if ( '' !== $work ) {
				return self::trim_text( $work );
			}
		}

		return self::trim_text( get_bloginfo( 'description' ) );
	}

	/**
	 * A post's description: SEO description → written excerpt → start of the content → Tagline.
	 *
	 * @param WP_Post $post The post.
	 * @return string
	 */
	public static function post_description( WP_Post $post ): string {
		$candidates = array(
			(string) get_post_meta( $post->ID, Fields::DESCRIPTION, true ),
			has_excerpt( $post ) ? $post->post_excerpt : '',
			// excerpt_remove_blocks keeps text blocks (paragraphs, headings…)
			// and drops dynamic ones like the hero, which aren't page text.
			excerpt_remove_blocks( strip_shortcodes( $post->post_content ) ),
			get_bloginfo( 'description' ),
		);

		foreach ( $candidates as $text ) {
			$text = self::trim_text( $text );
			if ( '' !== $text ) {
				return $text;
			}
		}

		return '';
	}

	/**
	 * The image for link previews, or null.
	 *
	 * @return array{url: string, width: int, height: int, alt: string}|null
	 */
	public static function image(): ?array {
		$post     = self::post();
		$image_id = $post ? self::post_image_id( $post ) : 0;

		if ( ! $image_id ) {
			$image_id = (int) get_option( Settings::DEFAULT_IMAGE, 0 );
		}
		if ( ! $image_id ) {
			$image_id = (int) get_option( 'site_icon', 0 );
		}

		return $image_id ? self::image_data( $image_id ) : null;
	}

	/**
	 * A post's own share image: SEO image → featured image → screenshot (Projects).
	 *
	 * @param WP_Post $post The post.
	 * @return int Attachment ID, or 0.
	 */
	public static function post_image_id( WP_Post $post ): int {
		$ids = array(
			(int) get_post_meta( $post->ID, Fields::IMAGE, true ),
			(int) get_post_thumbnail_id( $post ),
			Project::POST_TYPE === $post->post_type ? (int) get_post_meta( $post->ID, Project::SCREENSHOT_META, true ) : 0,
		);

		foreach ( $ids as $id ) {
			if ( $id && wp_attachment_is_image( $id ) ) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * URL, size and alt text of an image, at full size: link previews
	 * look best at 1200 px wide or more.
	 *
	 * @param int $image_id Attachment ID.
	 * @return array{url: string, width: int, height: int, alt: string}|null
	 */
	public static function image_data( int $image_id ): ?array {
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! $src ) {
			return null;
		}

		return array(
			'url'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'alt'    => trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ),
		);
	}

	/**
	 * The page's one true address: no ?tech= filter, no tracking arguments.
	 *
	 * @return string|null Null where there isn't one (404, search…).
	 */
	public static function url(): ?string {
		$post = self::post();
		if ( $post ) {
			$url = wp_get_canonical_url( $post );
			return $url ? $url : null;
		}

		if ( is_front_page() ) {
			return home_url( '/' );
		}

		if ( is_home() ) {
			$page_for_posts = (int) get_option( 'page_for_posts' );
			return $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/' );
		}

		if ( is_post_type_archive( Project::POST_TYPE ) ) {
			$url = get_post_type_archive_link( Project::POST_TYPE );
			return $url ? $url : null;
		}

		return null;
	}

	/**
	 * Open Graph type: "article" for a single Project or post, "website" for the rest.
	 *
	 * @return string
	 */
	public static function type(): string {
		$post = self::post();

		return ( $post && ! is_front_page() && in_array( $post->post_type, array( 'post', Project::POST_TYPE ), true ) ) ? 'article' : 'website';
	}

	/**
	 * Whether the visitor is looking at a filtered list (?tech=php).
	 * The same projects are on the unfiltered page, so it's not indexed.
	 *
	 * @return bool
	 */
	public static function is_filtered(): bool {
		return isset( $_GET[ self::FILTER_ARG ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check, the value isn't used.
	}

	/**
	 * Whether search engines should leave this page out.
	 *
	 * @return bool
	 */
	public static function is_noindex(): bool {
		if ( is_404() || is_search() || self::is_filtered() ) {
			return true;
		}

		$post = self::post();
		if ( $post && get_post_meta( $post->ID, Fields::NOINDEX, true ) ) {
			return true;
		}

		/**
		 * Keep the whole site out of search engines, e.g. on staging.
		 *
		 * @param bool $noindex True everywhere except production.
		 */
		return (bool) apply_filters( 'dsg_core_seo_noindex_site', 'production' !== wp_get_environment_type() );
	}

	/**
	 * Plain text, on one line, cut at a word to fit search results.
	 *
	 * @param string $text   Any text, may contain HTML.
	 * @param int    $length Most characters to keep, including the "…".
	 * @return string
	 */
	public static function trim_text( string $text, int $length = self::DESCRIPTION_LENGTH ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text, true ), ENT_QUOTES, 'UTF-8' );
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		}

		$cut   = mb_substr( $text, 0, $length - 1 );
		$space = mb_strrpos( $cut, ' ' );
		if ( false !== $space && $space > $length / 2 ) {
			$cut = mb_substr( $cut, 0, $space );
		}

		// Not rtrim(): it works on bytes and would break multibyte (e.g. Greek) letters.
		return preg_replace( '/[\s,.;:–-]+$/u', '', $cut ) . '…';
	}
}
