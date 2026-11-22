<?php
/**
 * Meta tags in <head>: description, canonical, Open Graph and Twitter.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

use DSG\Core\PostTypes\Project;

/**
 * Prints what search results and link previews show. The values come
 * from Context. WordPress itself still prints <title> (block themes get
 * it automatically) and the canonical link on single pages.
 */
class Head {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'document_title_parts', array( $this, 'title_parts' ) );
		// Right after <title> (priority 1), before styles and scripts.
		add_action( 'wp_head', array( $this, 'print_tags' ), 2 );
	}

	/**
	 * Set the browser tab and search result title.
	 *
	 * - Home: "Site name – Tagline", or the front page's SEO title on its own.
	 * - Pages and Projects: the SEO title, if set, instead of the post title.
	 * - /portfolio/: "Work" instead of the post type's name ("Projects").
	 *
	 * WordPress adds " – Site name" after the title on every page but the home page.
	 *
	 * @param array $parts Title parts: title, page, tagline, site.
	 * @return array
	 */
	public function title_parts( array $parts ): array {
		$post   = Context::post();
		$custom = $post ? Context::custom_title( $post ) : '';

		if ( is_front_page() ) {
			if ( '' !== $custom ) {
				return array( 'title' => $custom );
			}

			$parts = array( 'title' => get_bloginfo( 'name' ) );
			if ( get_bloginfo( 'description' ) ) {
				$parts['tagline'] = get_bloginfo( 'description' );
			}
			return $parts;
		}

		if ( '' !== $custom ) {
			$parts['title'] = $custom;
		} elseif ( is_post_type_archive( Project::POST_TYPE ) ) {
			$parts['title'] = Context::work_title();
		}

		return $parts;
	}

	/**
	 * Print the tags.
	 *
	 * @return void
	 */
	public function print_tags(): void {
		if ( ! Context::is_supported() ) {
			return;
		}

		$description = Context::description();
		$url         = Context::url();
		$image       = Context::image();

		if ( '' !== $description ) {
			self::meta( 'name', 'description', $description );
		}

		// WordPress prints the canonical link on single pages only.
		if ( $url && ! is_singular() ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
		}

		self::meta( 'property', 'og:type', Context::type() );
		self::meta( 'property', 'og:site_name', get_bloginfo( 'name' ) );
		self::meta( 'property', 'og:locale', get_locale() );
		self::meta( 'property', 'og:title', Context::title() );
		if ( '' !== $description ) {
			self::meta( 'property', 'og:description', $description );
		}
		if ( $url ) {
			self::meta( 'property', 'og:url', $url );
		}

		$post = Context::post();
		if ( $post && 'article' === Context::type() ) {
			self::meta( 'property', 'article:published_time', get_post_time( 'c', true, $post ) );
			self::meta( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $post ) );
		}

		if ( $image ) {
			self::meta( 'property', 'og:image', $image['url'] );
			self::meta( 'property', 'og:image:width', (string) $image['width'] );
			self::meta( 'property', 'og:image:height', (string) $image['height'] );
			if ( '' !== $image['alt'] ) {
				self::meta( 'property', 'og:image:alt', $image['alt'] );
			}
		}

		// The big preview needs a big image; a small one (the Site Icon) gets the compact card.
		$large = $image && $image['width'] >= 600;
		self::meta( 'name', 'twitter:card', $large ? 'summary_large_image' : 'summary' );
	}

	/**
	 * Print one <meta> tag.
	 *
	 * @param string $attribute "name" or "property" (Open Graph uses "property").
	 * @param string $key       The tag's name.
	 * @param string $content   The tag's value.
	 * @return void
	 */
	private static function meta( string $attribute, string $key, string $content ): void {
		printf(
			'<meta %s="%s" content="%s" />' . "\n",
			esc_attr( $attribute ),
			esc_attr( $key ),
			esc_attr( $content )
		);
	}
}
