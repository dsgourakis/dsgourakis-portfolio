<?php
/**
 * Tests for the SEO fallback chains and noindex rules.
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;
use DSG\Core\Seo\Context;
use DSG\Core\Seo\Fields;
use DSG\Core\Seo\Robots;

/**
 * Context decides every value the SEO classes print.
 */
class Test_Seo_Context extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		( new Fields() )->register_meta();
		update_option( 'blogdescription', 'Tagline text' );
		// The test site isn't "production"; treat it as if it were.
		add_filter( 'dsg_core_seo_noindex_site', '__return_false' );
	}

	public function tear_down() {
		unset( $_GET[ Context::FILTER_ARG ] );
		parent::tear_down();
	}

	public function test_short_text_is_kept_as_is() {
		$this->assertSame( 'Hello world', Context::trim_text( "<p>Hello\n   world</p>" ) );
	}

	public function test_long_text_is_cut_at_a_word() {
		$text    = str_repeat( 'word ', 60 );
		$trimmed = Context::trim_text( $text );

		$this->assertLessThanOrEqual( Context::DESCRIPTION_LENGTH, mb_strlen( $trimmed ) );
		$this->assertStringEndsWith( 'word…', $trimmed );
	}

	public function test_trimming_keeps_greek_letters_whole() {
		$trimmed = Context::trim_text( str_repeat( 'Καλημέρα κόσμε ', 20 ), 40 );

		$this->assertTrue( mb_check_encoding( $trimmed, 'UTF-8' ) );
		$this->assertStringEndsWith( '…', $trimmed );
	}

	public function test_description_prefers_the_seo_field() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'page',
				'post_excerpt' => 'The excerpt',
				'post_content' => 'The content',
			)
		);
		update_post_meta( $post->ID, Fields::DESCRIPTION, 'The SEO description' );

		$this->assertSame( 'The SEO description', Context::post_description( $post ) );
	}

	public function test_description_falls_back_to_excerpt_then_content_then_tagline() {
		$with_excerpt = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'page',
				'post_excerpt' => 'The excerpt',
				'post_content' => 'The content',
			)
		);
		$with_content = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'page',
				'post_excerpt' => '',
				'post_content' => '<!-- wp:paragraph --><p>The content</p><!-- /wp:paragraph -->',
			)
		);
		$empty        = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'page',
				'post_excerpt' => '',
				'post_content' => '',
			)
		);

		$this->assertSame( 'The excerpt', Context::post_description( $with_excerpt ) );
		$this->assertSame( 'The content', Context::post_description( $with_content ) );
		$this->assertSame( 'Tagline text', Context::post_description( $empty ) );
	}

	public function test_image_falls_back_from_seo_image_to_featured_to_screenshot() {
		$project    = self::factory()->post->create_and_get( array( 'post_type' => Project::POST_TYPE ) );
		$screenshot = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$featured   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$seo_image  = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$this->assertSame( 0, Context::post_image_id( $project ) );

		update_post_meta( $project->ID, Project::SCREENSHOT_META, $screenshot );
		$this->assertSame( $screenshot, Context::post_image_id( $project ) );

		set_post_thumbnail( $project, $featured );
		$this->assertSame( $featured, Context::post_image_id( $project ) );

		update_post_meta( $project->ID, Fields::IMAGE, $seo_image );
		$this->assertSame( $seo_image, Context::post_image_id( $project ) );
	}

	public function test_normal_pages_are_indexed() {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$this->assertFalse( Context::is_noindex() );
	}

	public function test_404_and_search_are_noindex() {
		$this->go_to( home_url( '/?p=999999' ) );
		$this->assertTrue( is_404() );
		$this->assertTrue( Context::is_noindex() );

		$this->go_to( home_url( '/?s=test' ) );
		$this->assertTrue( Context::is_noindex() );
	}

	public function test_filtered_lists_are_noindex() {
		$this->go_to( add_query_arg( Context::FILTER_ARG, 'php', get_post_type_archive_link( Project::POST_TYPE ) ) );

		$this->assertTrue( Context::is_noindex() );
	}

	public function test_hide_from_search_engines_box() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page_id, Fields::NOINDEX, true );
		$this->go_to( get_permalink( $page_id ) );

		$this->assertTrue( Context::is_noindex() );
	}

	public function test_the_whole_site_is_noindex_outside_production() {
		remove_filter( 'dsg_core_seo_noindex_site', '__return_false' );
		add_filter( 'dsg_core_seo_noindex_site', '__return_true' );
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( Context::is_noindex() );
	}

	public function test_robots_adds_noindex_follow() {
		$this->go_to( home_url( '/?s=test' ) );

		$robots = ( new Robots() )->filter( array( 'max-image-preview' => 'large' ) );

		$this->assertTrue( $robots['noindex'] );
		$this->assertTrue( $robots['follow'] );
		$this->assertArrayNotHasKey( 'max-image-preview', $robots );
	}

	public function test_robots_leaves_indexed_pages_alone() {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$this->assertSame( array( 'max-image-preview' => 'large' ), ( new Robots() )->filter( array( 'max-image-preview' => 'large' ) ) );
	}
}
