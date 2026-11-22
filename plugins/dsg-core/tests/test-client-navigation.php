<?php
/**
 * Tests for client-side navigation (the server-side half).
 *
 * @package DSG\Core
 */

use DSG\Core\Navigation\ClientNavigation;

/**
 * Checks the markup ClientNavigation adds: the router region, the current
 * menu link and the pill.
 */
class Test_Client_Navigation extends WP_UnitTestCase {

	/**
	 * REQUEST_URI before the test, so tests that change it can put it back.
	 *
	 * @var string|null
	 */
	private ?string $request_uri = null;

	public function set_up() {
		parent::set_up();
		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only stored and restored.
	}

	public function tear_down() {
		if ( null === $this->request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->request_uri; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- restoring the original value.
		}
		parent::tear_down();
	}

	/**
	 * Data for test_is_current_path: link path, page path, expected result.
	 *
	 * @return array
	 */
	public function current_path_cases(): array {
		return array(
			'home link on home'         => array( '/', '/', true ),
			'home link elsewhere'       => array( '/', '/about/', false ),
			'section link on itself'    => array( '/portfolio/', '/portfolio/', true ),
			'section link on a child'   => array( '/portfolio', '/portfolio/golden-star/', true ),
			'similar name is not child' => array( '/about/', '/about-me/', false ),
		);
	}

	/**
	 * Same rule as isCurrentPath() in assets/client-navigation/utils.js.
	 *
	 * @dataProvider current_path_cases
	 *
	 * @param string $link_path Link path.
	 * @param string $page_path Page path.
	 * @param bool   $expected  Expected result.
	 */
	public function test_is_current_path( string $link_path, string $page_path, bool $expected ) {
		$this->assertSame( $expected, ClientNavigation::is_current_path( $link_path, $page_path ) );
	}

	public function test_path_of_ignores_hash_links_and_other_sites() {
		$this->assertNull( ClientNavigation::path_of( '#work' ) );
		$this->assertNull( ClientNavigation::path_of( 'https://github.com/dsgourakis' ) );
		$this->assertSame( '/about/', ClientNavigation::path_of( '/about/' ) );
		$this->assertSame( '/about/', ClientNavigation::path_of( home_url( '/about/' ) ) );
	}

	public function test_main_group_becomes_the_router_region() {
		$html = do_blocks( '<!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><p>Hi</p></main><!-- /wp:group -->' );

		$this->assertMatchesRegularExpression( '/<main [^>]*data-wp-router-region="dsg-core\/main"/', $html );
		$this->assertMatchesRegularExpression( '/<main [^>]*data-wp-interactive="dsg-core\/client-navigation"/', $html );
	}

	public function test_other_groups_are_left_alone() {
		$html = do_blocks( '<!-- wp:group --><div class="wp-block-group"><p>Hi</p></div><!-- /wp:group -->' );

		$this->assertStringNotContainsString( 'data-wp-router-region', $html );
	}

	public function test_custom_menu_link_is_marked_current_on_its_section() {
		$_SERVER['REQUEST_URI'] = '/portfolio/golden-star/';
		$navigation             = new ClientNavigation();

		$work = $navigation->mark_current_link(
			'<li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="/portfolio/">Work</a></li>',
			array( 'attrs' => array( 'url' => '/portfolio/' ) )
		);
		$home = $navigation->mark_current_link(
			'<li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="/">Home</a></li>',
			array( 'attrs' => array( 'url' => '/' ) )
		);

		$this->assertStringContainsString( 'current-menu-item', $work );
		$this->assertStringContainsString( 'aria-current="page"', $work );
		$this->assertStringNotContainsString( 'current-menu-item', $home );
	}

	public function test_pill_is_added_only_to_pill_menus() {
		$navigation = new ClientNavigation();
		$markup     = '<nav class="wp-block-navigation"><div class="wp-block-navigation__responsive-container-content"><ul class="wp-block-navigation__container"></ul></div></nav>';

		$pill_menu  = $navigation->add_pill( $markup, array( 'attrs' => array( 'className' => 'dsg-pill-nav' ) ) );
		$plain_menu = $navigation->add_pill( $markup, array( 'attrs' => array() ) );

		$this->assertStringContainsString( '<span class="dsg-nav-pill" aria-hidden="true"></span><ul', $pill_menu );
		$this->assertStringNotContainsString( 'dsg-nav-pill', $plain_menu );
	}

	public function test_admin_bar_links_are_left_out_without_the_admin_bar() {
		add_filter( 'show_admin_bar', '__return_false' );

		$this->assertNull( ClientNavigation::admin_bar_links() );

		$html = do_blocks( '<!-- wp:group {"tagName":"main"} --><main class="wp-block-group"></main><!-- /wp:group -->' );
		$this->assertStringNotContainsString( 'data-dsg-admin-bar', $html );
	}

	public function test_admin_bar_links_point_to_the_page_shown() {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		add_filter( 'show_admin_bar', '__return_true' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page ) );

		$links = ClientNavigation::admin_bar_links();

		$this->assertSame( get_edit_post_link( $page ), $links['edit']['href'] );
		$this->assertSame( get_post_type_object( 'page' )->labels->edit_item, $links['edit']['title'] );
		$this->assertArrayHasKey( 'site-editor', $links );

		$html = do_blocks( '<!-- wp:group {"tagName":"main"} --><main class="wp-block-group"></main><!-- /wp:group -->' );
		$this->assertStringContainsString( 'data-dsg-admin-bar=', $html );
	}

	public function test_admin_bar_has_no_edit_link_where_there_is_nothing_to_edit() {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		add_filter( 'show_admin_bar', '__return_true' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->go_to( home_url( '/?s=nothing' ) );

		$this->assertNull( ClientNavigation::admin_bar_links()['edit'] );
	}
}
