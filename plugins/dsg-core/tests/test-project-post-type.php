<?php
/**
 * Tests for the Project post type and Technology taxonomy.
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;

/**
 * Each method starting with "test_" is one test.
 * WP_UnitTestCase resets the database after every test, so tests never affect each other.
 */
class Test_Project_Post_Type extends WP_UnitTestCase {

	/**
	 * The test framework unregisters all meta keys after each test, so
	 * register the Project details again before each one.
	 */
	public function set_up() {
		parent::set_up();
		( new Project() )->register_meta();
	}

	public function test_post_type_is_registered() {
		$this->assertTrue( post_type_exists( Project::POST_TYPE ) );
	}

	public function test_post_type_is_public_and_uses_the_block_editor() {
		$post_type = get_post_type_object( Project::POST_TYPE );

		$this->assertTrue( $post_type->public );
		$this->assertTrue( $post_type->show_in_rest, 'show_in_rest is required for the block editor.' );
	}

	public function test_archive_lives_at_portfolio() {
		$post_type = get_post_type_object( Project::POST_TYPE );

		$this->assertSame( 'portfolio', $post_type->has_archive );
		$this->assertSame( 'portfolio', $post_type->rewrite['slug'] );
	}

	public function test_projects_have_an_order_field() {
		$this->assertTrue( post_type_supports( Project::POST_TYPE, 'page-attributes' ), 'The Project Grid sorts by the "Order" field.' );
	}

	public function test_taxonomy_is_attached_to_projects() {
		$this->assertTrue( taxonomy_exists( Project::TAXONOMY ) );
		$this->assertContains( Project::TAXONOMY, get_object_taxonomies( Project::POST_TYPE ) );
	}

	public function test_technologies_have_no_public_pages() {
		$taxonomy = get_taxonomy( Project::TAXONOMY );

		$this->assertFalse( $taxonomy->publicly_queryable, 'Technologies are filters only, with no archive pages.' );
		$this->assertFalse( $taxonomy->rewrite );
		$this->assertTrue( $taxonomy->show_ui, 'Technologies are still managed in wp-admin.' );
		$this->assertTrue( $taxonomy->show_in_rest, 'The block editor needs the taxonomy in the REST API.' );
	}

	public function test_project_can_be_tagged_with_technologies() {
		// Factories create test content quickly.
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		wp_set_object_terms( $project_id, array( 'PHP', 'React' ), Project::TAXONOMY );

		$names = wp_get_object_terms( $project_id, Project::TAXONOMY, array( 'fields' => 'names' ) );
		sort( $names );

		$this->assertSame( array( 'PHP', 'React' ), $names );
	}

	public function test_project_details_are_registered_for_the_editor() {
		$registered = get_registered_meta_keys( 'post', Project::POST_TYPE );

		foreach ( array_keys( Project::META ) as $key ) {
			$this->assertArrayHasKey( $key, $registered, "$key is not registered." );
			$this->assertTrue( (bool) $registered[ $key ]['show_in_rest'], "$key must be in the REST API for the editor." );
		}
	}

	public function test_screenshot_is_registered_as_an_attachment_id() {
		$registered = get_registered_meta_keys( 'post', Project::POST_TYPE );

		$this->assertArrayHasKey( Project::SCREENSHOT_META, $registered );
		$this->assertSame( 'integer', $registered[ Project::SCREENSHOT_META ]['type'] );
		$this->assertTrue( (bool) $registered[ Project::SCREENSHOT_META ]['show_in_rest'] );

		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		update_post_meta( $project_id, Project::SCREENSHOT_META, '-42' );
		$this->assertSame( '42', get_post_meta( $project_id, Project::SCREENSHOT_META, true ) );
	}

	public function test_featured_is_a_boolean_in_the_rest_api() {
		$registered = get_registered_meta_keys( 'post', Project::POST_TYPE );

		$this->assertArrayHasKey( Project::FEATURED_META, $registered );
		$this->assertSame( 'boolean', $registered[ Project::FEATURED_META ]['type'] );
		$this->assertTrue( (bool) $registered[ Project::FEATURED_META ]['show_in_rest'] );

		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		$this->assertFalse( Project::is_featured( $project_id ) );

		update_post_meta( $project_id, Project::FEATURED_META, true );
		$this->assertTrue( Project::is_featured( $project_id ) );
	}

	public function test_project_details_are_sanitized() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		update_post_meta( $project_id, 'dsg_agency', '<b>Acme Digital</b>' );
		update_post_meta( $project_id, 'dsg_live_url', 'javascript:alert(1)' );

		$this->assertSame( 'Acme Digital', get_post_meta( $project_id, 'dsg_agency', true ) );
		$this->assertSame( '', get_post_meta( $project_id, 'dsg_live_url', true ) );
	}

	public function test_only_editors_can_change_project_details_over_rest() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( current_user_can( 'edit_post_meta', $project_id, 'dsg_agency' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertTrue( current_user_can( 'edit_post_meta', $project_id, 'dsg_agency' ) );
	}

	public function test_next_after_follows_the_order_field_and_wraps_round() {
		$first  = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 1,
			)
		);
		$second = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 2,
			)
		);
		$third  = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 3,
			)
		);

		$this->assertSame( $second, Project::next_after( $first ) );
		$this->assertSame( $third, Project::next_after( $second ) );
		$this->assertSame( $first, Project::next_after( $third ), 'The last Project points back to the first.' );
	}

	public function test_next_after_is_zero_with_a_single_project() {
		$only = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		$this->assertSame( 0, Project::next_after( $only ) );
	}

	public function test_next_after_skips_drafts() {
		$first = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 1,
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => Project::POST_TYPE,
				'menu_order'  => 2,
				'post_status' => 'draft',
			)
		);
		$third = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'menu_order' => 3,
			)
		);

		$this->assertSame( $third, Project::next_after( $first ) );
	}
}
