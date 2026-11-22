<?php
/**
 * Tests for the structured data (JSON-LD).
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;
use DSG\Core\Seo\Fields;
use DSG\Core\Seo\Schema;
use DSG\Core\Seo\Settings;

/**
 * The @graph for each kind of page.
 */
class Test_Seo_Schema extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		( new Fields() )->register_meta();
		update_option( 'blogname', 'Site Name' );
	}

	/**
	 * The graph's nodes by @type.
	 *
	 * @return array
	 */
	private function nodes(): array {
		$nodes = array();
		foreach ( Schema::graph() as $node ) {
			$nodes[ $node['@type'] ] = $node;
		}
		return $nodes;
	}

	public function test_every_page_has_the_site_and_its_owner() {
		update_option( Settings::JOB_TITLE, 'WordPress Developer' );
		update_option( Settings::PROFILES, "https://github.com/example\nhttps://www.linkedin.com/in/example" );
		$this->go_to( home_url( '/' ) );

		$nodes = $this->nodes();

		$this->assertSame( 'Site Name', $nodes['WebSite']['name'] );
		$this->assertSame( Schema::id( 'person' ), $nodes['Person']['@id'] );
		$this->assertSame( 'WordPress Developer', $nodes['Person']['jobTitle'] );
		$this->assertCount( 2, $nodes['Person']['sameAs'] );
		$this->assertSame( array( '@id' => Schema::id( 'person' ) ), $nodes['WebPage']['about'] );
	}

	public function test_empty_values_are_left_out() {
		$this->go_to( home_url( '/' ) );

		$this->assertArrayNotHasKey( 'jobTitle', $this->nodes()['Person'] );
	}

	public function test_project_is_a_creative_work_by_the_person() {
		$project_id = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Red Cross',
			)
		);
		wp_set_object_terms( $project_id, array( 'PHP', 'React' ), Project::TAXONOMY );
		update_post_meta( $project_id, 'dsg_year', '2024' );
		update_post_meta( $project_id, 'dsg_live_url', 'https://www.redcross.gr/' );
		$this->go_to( get_permalink( $project_id ) );

		$nodes = $this->nodes();
		$work  = $nodes['CreativeWork'];

		$this->assertSame( 'Red Cross', $work['name'] );
		$this->assertSame( array( '@id' => Schema::id( 'person' ) ), $work['author'] );
		$this->assertSame( '2024', $work['dateCreated'] );
		$this->assertStringContainsString( 'PHP', $work['keywords'] );
		$this->assertSame( 'https://www.redcross.gr/', $work['sameAs'] );

		$crumbs = $nodes['BreadcrumbList']['itemListElement'];
		$this->assertSame( array( 'Home', 'Work', 'Red Cross' ), wp_list_pluck( $crumbs, 'name' ) );
		$this->assertSame( array( 1, 2, 3 ), wp_list_pluck( $crumbs, 'position' ) );
	}

	public function test_work_page_lists_every_project_in_order() {
		$second = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 2,
			)
		);
		$first  = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 1,
			)
		);
		$this->go_to( get_post_type_archive_link( Project::POST_TYPE ) );

		$list = $this->nodes()['CollectionPage']['mainEntity'];

		$this->assertSame( 2, $list['numberOfItems'] );
		$this->assertSame( array( get_permalink( $first ), get_permalink( $second ) ), wp_list_pluck( $list['itemListElement'], 'url' ) );
	}

	public function test_about_and_contact_pages_get_their_types() {
		$about   = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);
		$contact = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'post_name' => 'contact',
			)
		);
		$other   = self::factory()->post->create_and_get( array( 'post_type' => 'page' ) );

		$this->assertSame( 'ProfilePage', Schema::page_type( $about ) );
		$this->assertSame( 'ContactPage', Schema::page_type( $contact ) );
		$this->assertSame( 'WebPage', Schema::page_type( $other ) );

		$this->go_to( get_permalink( $about ) );
		$this->assertSame( array( '@id' => Schema::id( 'person' ) ), $this->nodes()['ProfilePage']['mainEntity'] );
	}

	public function test_titles_are_plain_text_not_html_entities() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => "Tom's work - notes",
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		$name = $this->nodes()['WebPage']['name'];

		$this->assertStringNotContainsString( '&#', $name );
		$this->assertStringContainsString( 'Site Name', $name );
	}

	public function test_script_is_valid_json_that_cannot_break_out_of_the_tag() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => '</script><script>alert(1)</script>',
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		ob_start();
		( new Schema() )->print_script();
		$html = (string) ob_get_clean();

		$this->assertSame( 1, substr_count( $html, '</script>' ) );
		$this->assertMatchesRegularExpression( '#<script type="application/ld\+json">(.+)</script>#s', $html );

		preg_match( '#<script[^>]*>(.+)</script>#s', $html, $matches );
		$data = json_decode( $matches[1], true );
		$this->assertSame( 'https://schema.org', $data['@context'] );
		$this->assertNotEmpty( $data['@graph'] );
	}

	public function test_nothing_on_404() {
		$this->go_to( home_url( '/?p=999999' ) );

		ob_start();
		( new Schema() )->print_script();
		$this->assertSame( '', ob_get_clean() );
	}
}
