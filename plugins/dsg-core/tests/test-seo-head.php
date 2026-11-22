<?php
/**
 * Tests for the <head> tags, titles and sitemap.
 *
 * @package DSG\Core
 */

use DSG\Core\Plugin;
use DSG\Core\PostTypes\Project;
use DSG\Core\Seo\Fields;
use DSG\Core\Seo\Head;
use DSG\Core\Seo\Settings;
use DSG\Core\Seo\Sitemap;

/**
 * Meta tags, document titles and sitemap tidy-up.
 */
class Test_Seo_Head extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		( new Fields() )->register_meta();
		update_option( 'blogname', 'Site Name' );
		update_option( 'blogdescription', 'Tagline text' );
	}

	/**
	 * The tags Head prints for the current page.
	 *
	 * @return string
	 */
	private function head(): string {
		ob_start();
		( new Head() )->print_tags();
		return (string) ob_get_clean();
	}

	public function test_project_gets_description_and_open_graph_tags() {
		$project_id = self::factory()->post->create(
			array(
				'post_type'    => Project::POST_TYPE,
				'post_title'   => 'Red Cross',
				'post_excerpt' => 'A site for the Red Cross.',
			)
		);
		$this->go_to( get_permalink( $project_id ) );

		$head = $this->head();

		$this->assertStringContainsString( '<meta name="description" content="A site for the Red Cross." />', $head );
		$this->assertStringContainsString( '<meta property="og:title" content="Red Cross" />', $head );
		$this->assertStringContainsString( '<meta property="og:type" content="article" />', $head );
		$this->assertStringContainsString( 'property="og:url" content="' . get_permalink( $project_id ) . '"', $head );
		$this->assertStringNotContainsString( 'rel="canonical"', $head, 'WordPress prints the canonical on single pages itself.' );
	}

	public function test_share_image_uses_the_large_card() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		$image_id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		set_post_thumbnail( $project_id, $image_id );
		$this->go_to( get_permalink( $project_id ) );

		$head = $this->head();

		$this->assertStringContainsString( 'property="og:image" content="' . wp_get_attachment_url( $image_id ) . '"', $head );
		$this->assertStringContainsString( 'property="og:image:width"', $head );
		$this->assertStringContainsString( 'name="twitter:card" content="summary_large_image"', $head );
	}

	public function test_work_page_gets_canonical_and_its_own_description() {
		update_option( Settings::WORK_DESCRIPTION, 'Selected projects.' );
		$this->go_to( get_post_type_archive_link( Project::POST_TYPE ) );

		$head = $this->head();

		$this->assertStringContainsString( '<link rel="canonical" href="' . esc_url( get_post_type_archive_link( Project::POST_TYPE ) ) . '" />', $head );
		$this->assertStringContainsString( 'content="Selected projects."', $head );
	}

	public function test_nothing_is_printed_on_404() {
		$this->go_to( home_url( '/?p=999999' ) );

		$this->assertSame( '', $this->head() );
	}

	public function test_home_title_is_site_name_and_tagline() {
		$this->go_to( home_url( '/' ) );

		$parts = ( new Head() )->title_parts( array( 'title' => 'Ignored' ) );

		$this->assertSame(
			array(
				'title'   => 'Site Name',
				'tagline' => 'Tagline text',
			),
			$parts
		);
	}

	public function test_static_home_page_with_seo_title_uses_it_alone() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		update_post_meta( $page_id, Fields::TITLE, 'Name – WordPress Developer' );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( array( 'title' => 'Name – WordPress Developer' ), ( new Head() )->title_parts( array( 'title' => 'Home' ) ) );
	}

	public function test_seo_title_replaces_page_title() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'About',
			)
		);
		update_post_meta( $page_id, Fields::TITLE, 'About me' );
		$this->go_to( get_permalink( $page_id ) );

		$parts = ( new Head() )->title_parts(
			array(
				'title' => 'About',
				'site'  => 'Site Name',
			)
		);

		$this->assertSame( 'About me', $parts['title'] );
		$this->assertSame( 'Site Name', $parts['site'] );
	}

	public function test_work_page_title() {
		$this->go_to( get_post_type_archive_link( Project::POST_TYPE ) );

		$parts = ( new Head() )->title_parts( array( 'title' => 'Projects' ) );

		$this->assertSame( 'Work', $parts['title'] );
	}

	public function test_sitemap_has_no_author_pages() {
		$sitemap = new Sitemap();

		$posts = new WP_Sitemaps_Posts();

		$this->assertFalse( $sitemap->remove_users( new WP_Sitemaps_Users(), 'users' ) );
		$this->assertSame( $posts, $sitemap->remove_users( $posts, 'posts' ), 'Other sitemaps stay.' );
	}

	public function test_sitemap_drops_posts_while_the_blog_is_empty() {
		$sitemap    = new Sitemap();
		$post_types = array(
			'post' => get_post_type_object( 'post' ),
			'page' => get_post_type_object( 'page' ),
		);

		$this->assertSame( array( 'page' ), array_keys( $sitemap->remove_empty_blog( $post_types ) ) );

		self::factory()->post->create();
		$this->assertSame( array( 'post', 'page' ), array_keys( $sitemap->remove_empty_blog( $post_types ) ) );
	}

	public function test_sitemap_skips_hidden_pages() {
		$shown  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$hidden = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $hidden, Fields::NOINDEX, true );

		$args = ( new Sitemap() )->skip_noindex(
			array(
				'post_type' => 'page',
				'fields'    => 'ids',
			)
		);
		$ids  = get_posts( $args );

		$this->assertContains( $shown, $ids );
		$this->assertNotContains( $hidden, $ids );
	}

	public function test_seo_plugins_are_detected() {
		$this->assertFalse( Plugin::has_seo_plugin() );

		update_option( 'active_plugins', array( 'wordpress-seo/wp-seo.php' ) );
		$this->assertTrue( Plugin::has_seo_plugin() );
	}
}
