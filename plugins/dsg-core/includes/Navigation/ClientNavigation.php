<?php
/**
 * Client-side navigation: moving between pages without a full reload.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Navigation;

use WP_HTML_Tag_Processor;

/**
 * Uses the router that ships with WordPress (Interactivity API) to swap only
 * the page's <main> when an internal link is clicked. The header and footer
 * stay on screen, so the menu's pill can slide to the new link.
 *
 * - PHP (this class): marks <main> as the area the router replaces, marks the
 *   current menu link, and loads the script.
 * - JS (assets/client-navigation/): catches link clicks, asks the router for
 *   the new page, then scrolls, moves focus and updates the menu.
 *
 * Without JavaScript, or in a browser that fails, links work as normal links.
 */
class ClientNavigation {

	/**
	 * Script module ID.
	 */
	public const SCRIPT_ID = 'dsg-core/client-navigation';

	/**
	 * Class that turns a Navigation block into the pill menu.
	 */
	public const PILL_CLASS = 'dsg-pill-nav';

	/**
	 * Admin bar items that change from page to page ("Edit Page", "Edit Site").
	 */
	public const ADMIN_BAR_NODES = array( 'edit', 'site-editor' );

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_filter( 'render_block_core/group', array( $this, 'mark_main_as_router_region' ), 10, 2 );
		add_filter( 'render_block_core/navigation-link', array( $this, 'mark_current_link' ), 10, 2 );
		add_filter( 'render_block_core/navigation', array( $this, 'add_pill' ), 10, 2 );
	}

	/**
	 * Load the script module on the frontend.
	 *
	 * The router is a dynamic import: it is only downloaded once someone
	 * hovers or clicks an internal link.
	 *
	 * @return void
	 */
	public function enqueue_script(): void {
		wp_enqueue_script_module(
			self::SCRIPT_ID,
			plugins_url( 'assets/client-navigation/index.js', DSG_CORE_FILE ),
			array(
				'@wordpress/interactivity',
				array(
					'id'     => '@wordpress/interactivity-router',
					'import' => 'dynamic',
				),
			),
			DSG_CORE_VERSION
		);
	}

	/**
	 * Mark every template's <main> as the area the router swaps.
	 *
	 * Done here rather than in the template files, so the Site Editor
	 * doesn't see unknown attributes and flag the Group block as invalid.
	 *
	 * @param string $block_content The Group block's HTML.
	 * @param array  $block         The block's data.
	 * @return string
	 */
	public function mark_main_as_router_region( string $block_content, array $block ): string {
		if ( 'main' !== ( $block['attrs']['tagName'] ?? '' ) ) {
			return $block_content;
		}

		$tags = new WP_HTML_Tag_Processor( $block_content );
		if ( $tags->next_tag( 'main' ) ) {
			$tags->set_attribute( 'data-wp-interactive', 'dsg-core/client-navigation' );
			$tags->set_attribute( 'data-wp-router-region', 'dsg-core/main' );

			// Lets CSS style the rest of the page per page, e.g. hiding the
			// footer's contact card on /contact/. <main> is swapped on every
			// client-side page change, so this stays correct without a reload.
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Post && is_page() ) {
				$tags->set_attribute( 'data-page', $queried->post_name );
			}

			$admin_bar = self::admin_bar_links();
			if ( null !== $admin_bar ) {
				$tags->set_attribute( 'data-dsg-admin-bar', wp_json_encode( $admin_bar ) );
			}
		}

		return $tags->get_updated_html();
	}

	/**
	 * The admin bar links that depend on the page being shown, for the JS
	 * to copy into the admin bar after a client-side page change.
	 *
	 * The admin bar is outside <main>, so the router never updates it:
	 * without this, "Edit Page" would keep pointing to the first page
	 * loaded. The links are built by WordPress's own admin bar functions,
	 * on a separate admin bar, so they match what a full page load shows.
	 *
	 * @return array|null Each node's title and href (null when the page has
	 *                    no such link), or null when the admin bar is hidden.
	 */
	public static function admin_bar_links(): ?array {
		if ( ! is_admin_bar_showing() || ! class_exists( 'WP_Admin_Bar' ) ) {
			return null;
		}

		$bar = new \WP_Admin_Bar();
		wp_admin_bar_edit_menu( $bar );
		wp_admin_bar_edit_site_menu( $bar );

		$links = array();
		foreach ( self::ADMIN_BAR_NODES as $id ) {
			$node         = $bar->get_node( $id );
			$links[ $id ] = $node ? array(
				'title' => wp_strip_all_tags( $node->title ),
				'href'  => $node->href,
			) : null;
		}

		return $links;
	}

	/**
	 * Mark a menu link as current when it points to the page being shown.
	 *
	 * WordPress only does this for links to a specific page or post. This
	 * also covers custom links, like "/portfolio/", and keeps "Work" current
	 * on a single project too. The JS applies the same rule after each
	 * client-side page change (see isCurrentPath in utils.js).
	 *
	 * @param string $block_content The Navigation Link block's HTML.
	 * @param array  $block         The block's data.
	 * @return string
	 */
	public function mark_current_link( string $block_content, array $block ): string {
		$link_path = self::path_of( $block['attrs']['url'] ?? '' );

		if ( null === $link_path || ! self::is_current_path( $link_path, self::current_path() ) ) {
			return $block_content;
		}

		$tags = new WP_HTML_Tag_Processor( $block_content );
		if ( $tags->next_tag( 'li' ) ) {
			$tags->add_class( 'current-menu-item' );
		}
		if ( $tags->next_tag( 'a' ) ) {
			$tags->set_attribute( 'aria-current', 'page' );
		}

		return $tags->get_updated_html();
	}

	/**
	 * Add the sliding pill to Navigation blocks that have the pill class.
	 *
	 * It is printed by PHP (not created by JS) because the Navigation block
	 * is controlled by the Interactivity API, which could remove elements
	 * that JS adds later. It is decoration only, hidden from screen readers.
	 *
	 * @param string $block_content The Navigation block's HTML.
	 * @param array  $block         The block's data.
	 * @return string
	 */
	public function add_pill( string $block_content, array $block ): string {
		$classes = explode( ' ', $block['attrs']['className'] ?? '' );
		if ( ! in_array( self::PILL_CLASS, $classes, true ) ) {
			return $block_content;
		}

		$list = '<ul class="wp-block-navigation__container';
		$pos  = strpos( $block_content, $list );
		if ( false === $pos ) {
			return $block_content;
		}

		return substr_replace( $block_content, '<span class="dsg-nav-pill" aria-hidden="true"></span>', $pos, 0 );
	}

	/**
	 * Whether a menu link counts as current for a page.
	 *
	 * "/" is only current on the homepage; any other link is also current
	 * on pages below it ("/portfolio/" on "/portfolio/some-project/").
	 *
	 * @param string $link_path Path the link points to.
	 * @param string $page_path Path of the page being shown.
	 * @return bool
	 */
	public static function is_current_path( string $link_path, string $page_path ): bool {
		$link_path = trailingslashit( $link_path );
		$page_path = trailingslashit( $page_path );

		if ( '/' === $link_path ) {
			return '/' === $page_path;
		}

		return str_starts_with( $page_path, $link_path );
	}

	/**
	 * The path of a URL on this site, or null for other sites and hash-only links.
	 *
	 * @param string $url A full or relative URL.
	 * @return string|null
	 */
	public static function path_of( string $url ): ?string {
		if ( '' === $url || str_starts_with( $url, '#' ) ) {
			return null;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
			return null;
		}

		return wp_parse_url( $url, PHP_URL_PATH ) ?? '/';
	}

	/**
	 * The path of the page being shown.
	 *
	 * @return string
	 */
	public static function current_path(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return wp_parse_url( $uri, PHP_URL_PATH ) ?? '/';
	}
}
