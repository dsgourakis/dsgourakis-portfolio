<?php
/**
 * Structured data (JSON-LD) that tells search engines what each page is.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

use DSG\Core\PostTypes\Project;
use WP_Post;

/**
 * Prints one <script type="application/ld+json"> per page with an @graph:
 *
 * - Every page:    WebSite and Person (the site's owner, defined once).
 * - Home:          WebPage about the Person.
 * - /portfolio/:   CollectionPage listing every Project.
 * - A Project:     WebPage + CreativeWork, made by the Person.
 * - /about/:       ProfilePage about the Person.
 * - /contact/:     ContactPage.
 * - Other pages:   WebPage.
 * - All but Home:  BreadcrumbList (Home › Work › Project).
 *
 * Nodes point at each other by @id (e.g. "https://…/#person") instead of
 * repeating themselves.
 */
class Schema {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_head', array( $this, 'print_script' ), 3 );
	}

	/**
	 * Print the JSON-LD script.
	 *
	 * @return void
	 */
	public function print_script(): void {
		if ( ! Context::is_supported() || Context::is_filtered() ) {
			return;
		}

		$graph = self::graph();
		if ( ! $graph ) {
			return;
		}

		wp_print_inline_script_tag(
			// JSON_HEX_TAG turns "<" into <, so text can't close the script tag early.
			(string) wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			),
			array( 'type' => 'application/ld+json' )
		);
	}

	/**
	 * The nodes for the current page.
	 *
	 * WordPress hands out titles HTML-encoded ("&#8211;" for "–"). JSON
	 * isn't HTML, so every string is decoded back to plain text here.
	 *
	 * @return array[]
	 */
	public static function graph(): array {
		$graph = self::build();

		array_walk_recursive(
			$graph,
			static function ( &$value ) {
				if ( is_string( $value ) ) {
					$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				}
			}
		);

		return $graph;
	}

	/**
	 * The nodes for the current page, before decoding.
	 *
	 * @return array[]
	 */
	private static function build(): array {
		$url = Context::url();
		if ( ! $url ) {
			return array();
		}

		$graph = array( self::website(), self::person() );
		$post  = Context::post();

		if ( is_front_page() ) {
			$graph[] = self::web_page( 'WebPage', $url ) + array( 'about' => array( '@id' => self::id( 'person' ) ) );
			return $graph;
		}

		if ( is_post_type_archive( Project::POST_TYPE ) ) {
			$graph[] = self::web_page( 'CollectionPage', $url ) + array( 'mainEntity' => self::project_list() );
			$graph[] = self::breadcrumbs( array( array( Context::work_title(), $url ) ) );
			return $graph;
		}

		if ( ! $post ) {
			return $graph;
		}

		$title = wp_strip_all_tags( get_the_title( $post ) );

		if ( Project::POST_TYPE === $post->post_type ) {
			$graph[] = self::web_page( 'WebPage', $url ) + array( 'mainEntity' => array( '@id' => $url . '#project' ) );
			$graph[] = self::project( $post, $url );
			$graph[] = self::breadcrumbs(
				array(
					array( Context::work_title(), (string) get_post_type_archive_link( Project::POST_TYPE ) ),
					array( $title, $url ),
				)
			);
			return $graph;
		}

		$page = self::web_page( self::page_type( $post ), $url );
		if ( 'ProfilePage' === $page['@type'] ) {
			$page['mainEntity'] = array( '@id' => self::id( 'person' ) );
		}
		$graph[] = $page;
		$graph[] = self::breadcrumbs( array( array( $title, $url ) ) );

		return $graph;
	}

	/**
	 * Schema.org type for a page: About and Contact are special.
	 * Matched by address, like the page-about and page-contact templates.
	 *
	 * @param WP_Post $post The page.
	 * @return string
	 */
	public static function page_type( WP_Post $post ): string {
		$types = array(
			'about'   => 'ProfilePage',
			'contact' => 'ContactPage',
		);

		return ( 'page' === $post->post_type && isset( $types[ $post->post_name ] ) ) ? $types[ $post->post_name ] : 'WebPage';
	}

	/**
	 * A node's @id: the home URL plus a fragment, e.g. https://…/#person.
	 *
	 * @param string $name Fragment.
	 * @return string
	 */
	public static function id( string $name ): string {
		return home_url( '/#' . $name );
	}

	/**
	 * The site.
	 *
	 * @return array
	 */
	private static function website(): array {
		return self::filled(
			array(
				'@type'       => 'WebSite',
				'@id'         => self::id( 'website' ),
				'url'         => home_url( '/' ),
				'name'        => get_bloginfo( 'name' ),
				'description' => get_bloginfo( 'description' ),
				'inLanguage'  => get_bloginfo( 'language' ),
				'publisher'   => array( '@id' => self::id( 'person' ) ),
			)
		);
	}

	/**
	 * The site's owner. The Site Title is the name.
	 *
	 * @return array
	 */
	private static function person(): array {
		$icon = get_site_icon_url( 512 );

		return self::filled(
			array(
				'@type'    => 'Person',
				'@id'      => self::id( 'person' ),
				'name'     => get_bloginfo( 'name' ),
				'url'      => home_url( '/' ),
				'jobTitle' => (string) get_option( Settings::JOB_TITLE, '' ),
				'image'    => $icon ? $icon : '',
				'sameAs'   => Settings::profiles(),
			)
		);
	}

	/**
	 * The page itself.
	 *
	 * @param string $type WebPage, CollectionPage, ProfilePage or ContactPage.
	 * @param string $url  The page's address.
	 * @return array
	 */
	private static function web_page( string $type, string $url ): array {
		$image = Context::image();

		return self::filled(
			array(
				'@type'        => $type,
				'@id'          => $url . '#webpage',
				'url'          => $url,
				'name'         => wp_get_document_title(),
				'description'  => Context::description(),
				'isPartOf'     => array( '@id' => self::id( 'website' ) ),
				'inLanguage'   => get_bloginfo( 'language' ),
				'primaryImage' => $image ? $image['url'] : '',
			)
		);
	}

	/**
	 * A Project as a piece of work by the Person.
	 *
	 * @param WP_Post $post The Project.
	 * @param string  $url  Its address.
	 * @return array
	 */
	private static function project( WP_Post $post, string $url ): array {
		$image_id     = Context::post_image_id( $post );
		$image        = $image_id ? Context::image_data( $image_id ) : null;
		$technologies = wp_get_post_terms( $post->ID, Project::TAXONOMY, array( 'fields' => 'names' ) );
		$live_url     = (string) get_post_meta( $post->ID, 'dsg_live_url', true );

		return self::filled(
			array(
				'@type'            => 'CreativeWork',
				'@id'              => $url . '#project',
				'name'             => wp_strip_all_tags( get_the_title( $post ) ),
				'description'      => Context::post_description( $post ),
				'url'              => $url,
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'image'            => $image ? $image['url'] : '',
				'author'           => array( '@id' => self::id( 'person' ) ),
				'dateCreated'      => trim( (string) get_post_meta( $post->ID, 'dsg_year', true ) ),
				'keywords'         => is_wp_error( $technologies ) ? '' : implode( ', ', $technologies ),
				// The live website the Project is about.
				'sameAs'           => $live_url,
			)
		);
	}

	/**
	 * Every published Project, in the order the Work page shows them.
	 *
	 * @return array
	 */
	private static function project_list(): array {
		$projects = get_posts(
			array(
				'post_type'           => Project::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => 100, // Plenty for a portfolio; keeps the query bounded.
				'orderby'             => Project::ORDERBY,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$items = array();
		foreach ( $projects as $index => $project ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'url'      => get_permalink( $project ),
				'name'     => wp_strip_all_tags( get_the_title( $project ) ),
			);
		}

		return array(
			'@type'           => 'ItemList',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}

	/**
	 * Home › … › current page.
	 *
	 * @param array[] $trail Pairs of name and URL, after Home.
	 * @return array
	 */
	private static function breadcrumbs( array $trail ): array {
		array_unshift( $trail, array( __( 'Home', 'dsg-core' ), home_url( '/' ) ) );

		$items = array();
		foreach ( $trail as $index => list( $name, $url ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $name,
				'item'     => $url,
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => end( $items )['item'] . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/**
	 * Drop empty values, so the output has no "jobTitle": "".
	 *
	 * @param array $node A node.
	 * @return array
	 */
	private static function filled( array $node ): array {
		return array_filter(
			$node,
			static function ( $value ) {
				return '' !== $value && array() !== $value && null !== $value;
			}
		);
	}
}
