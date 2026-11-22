<?php
/**
 * Tests for block registration and rendering.
 *
 * @package DSG\Core
 */

use DSG\Core\Blocks\Blocks;
use DSG\Core\PostTypes\Project;

/**
 * Checks that built blocks are found, registered and render correctly.
 * Run `npm run build` first, otherwise build/ is empty.
 *
 * Dynamic blocks are easy to test: give do_blocks() the comment WordPress
 * stores in the post, and check the HTML render.php returns.
 */
class Test_Blocks extends WP_UnitTestCase {

	public function test_block_folders_returns_empty_array_for_missing_folder() {
		$this->assertSame( array(), Blocks::block_folders( '/path/that/does/not/exist' ) );
	}

	public function test_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( array( 'dsg/contact-form', 'dsg/header-actions', 'dsg/hero', 'dsg/project-card', 'dsg/project-grid', 'dsg/next-project', 'dsg/project-meta', 'dsg/section-header', 'dsg/stat', 'dsg/stats', 'dsg/tech-stack' ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), "$name is not registered. Did you run \"npm run build\"?" );
		}
	}

	public function test_header_actions_renders_menu_resume_and_toggle() {
		$pdf = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'application/pdf',
				'file'           => 'resume.pdf',
			)
		);
		update_option( \DSG\Core\Settings\ResumeLink::OPTION, $pdf );

		$html = do_blocks( '<!-- wp:dsg/header-actions --><!-- wp:paragraph --><p>Menu here</p><!-- /wp:paragraph --><!-- /wp:dsg/header-actions -->' );

		$this->assertStringContainsString( 'site-header__actions', $html );
		$this->assertStringContainsString( 'Menu here', $html );
		$this->assertStringContainsString( 'class="site-header__resume wp-element-button" href="' . home_url( '/cv.pdf' ) . '"', $html );
		$this->assertStringContainsString( 'class="color-scheme-toggle"', $html );
	}

	public function test_header_actions_phone_menu_lists_the_navigation_links() {
		$this->go_to( '/about/' );
		$_SERVER['REQUEST_URI'] = '/about/';

		$html = do_blocks(
			'<!-- wp:dsg/header-actions {"email":"hello@example.com"} -->'
			. '<!-- wp:navigation {"overlayMenu":"never"} -->'
			. '<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->'
			. '<!-- wp:navigation-link {"label":"About","url":"/about/","kind":"custom"} /-->'
			. '<!-- /wp:navigation -->'
			. '<!-- /wp:dsg/header-actions -->'
		);

		$this->assertStringContainsString( '<dialog id="site-menu" class="site-menu"', $html );
		$this->assertStringContainsString( 'commandfor="site-menu" command="show-modal"', $html );
		$this->assertMatchesRegularExpression( '#<span class="site-menu__num" aria-hidden="true">01</span>\s*Home#', $html );
		$this->assertMatchesRegularExpression( '#class="site-menu__link" href="/about/" aria-current="page">\s*<span class="site-menu__num" aria-hidden="true">02</span>\s*About#', $html );
		$this->assertStringContainsString( 'href="mailto:hello@example.com"', $html );
	}

	public function test_header_actions_has_no_phone_menu_without_links() {
		$html = do_blocks( '<!-- wp:dsg/header-actions /-->' );

		$this->assertStringNotContainsString( '<dialog', $html );
		$this->assertStringNotContainsString( 'site-menu__open', $html );
	}

	public function test_header_actions_hides_resume_without_one() {
		delete_option( \DSG\Core\Settings\ResumeLink::OPTION );

		$html = do_blocks( '<!-- wp:dsg/header-actions /-->' );

		$this->assertStringNotContainsString( 'site-header__resume', $html );
		$this->assertStringContainsString( 'class="color-scheme-toggle"', $html );
	}

	public function test_project_card_renders_title_link_and_cta() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"Red Cross","summary":"A site.","url":"https://example.com"} /-->' );

		$this->assertStringContainsString( '<a href="https://example.com">Red Cross</a>', $html );
		$this->assertStringContainsString( 'A site.', $html );
		$this->assertStringContainsString( '<a class="wp-block-dsg-project-card__cta" href="https://example.com">', $html );
		$this->assertStringContainsString( 'View the project', $html );
	}

	public function test_project_card_without_link_has_no_link_or_cta() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"Red Cross"} /-->' );

		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringNotContainsString( 'View the project', $html );
	}

	public function test_project_card_escapes_unsafe_values() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"X","url":"javascript:alert(1)","technologies":["<script>bad</script>"]} /-->' );

		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_stat_renders_all_fields() {
		$html = do_blocks( '<!-- wp:dsg/stat {"pretitle":"build","title":"WordPress development","description":"Custom themes."} /-->' );

		$this->assertStringContainsString( '<p class="wp-block-dsg-stat__pretitle">build</p>', $html );
		$this->assertStringContainsString( '<h3 class="wp-block-dsg-stat__title">WordPress development</h3>', $html );
		$this->assertStringContainsString( 'Custom themes.', $html );
	}

	public function test_stats_wraps_its_stat_blocks() {
		$html = do_blocks(
			'<!-- wp:dsg/stats -->'
			. '<!-- wp:dsg/stat {"title":"4+"} /-->'
			. '<!-- wp:dsg/stat {"title":"Full-stack"} /-->'
			. '<!-- /wp:dsg/stats -->'
		);

		// Wrapper with the default "wide" alignment, and both Stats inside it.
		$this->assertMatchesRegularExpression( '/<div class="[^"]*\bwp-block-dsg-stats\b/', $html );
		$this->assertMatchesRegularExpression( '/<div class="[^"]*\balignwide\b/', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-stat__title' ) );
	}

	public function test_stat_keeps_empty_pretitle_for_the_counter() {
		$html = do_blocks( '<!-- wp:dsg/stat {"title":"4+"} /-->' );

		$this->assertStringContainsString( '<p class="wp-block-dsg-stat__pretitle"></p>', $html );
	}

	public function test_section_header_renders_heading_and_aside() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work","aside":"// 03 projects"} /-->' );

		$this->assertStringContainsString( '<h2 class="wp-block-dsg-section-header__heading">Selected work</h2>', $html );
		$this->assertStringContainsString( '<p class="wp-block-dsg-section-header__aside">// 03 projects</p>', $html );
		$this->assertMatchesRegularExpression( '/<div [^>]*class="[^"]*\balignwide\b/', $html );
	}

	public function test_section_header_has_a_default_bottom_margin() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work"} /-->' );

		// The block.json default for the Margin control, printed as an inline style.
		$this->assertStringContainsString( 'style="margin-bottom:var(--wp--preset--spacing--40)"', $html );
	}

	public function test_section_header_skips_empty_aside() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work"} /-->' );

		$this->assertStringNotContainsString( '__aside', $html );
	}

	public function test_section_header_renders_nothing_when_empty() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/section-header /-->' ) ) );
	}

	/**
	 * The block comment for a Hero. serialize_block_attributes() escapes the
	 * HTML in the attributes the same way the editor does when it saves.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private static function hero_comment( array $attributes ): string {
		return '<!-- wp:dsg/hero ' . serialize_block_attributes( $attributes ) . ' -->';
	}

	public function test_hero_renders_heading_tagline_buttons_and_monogram() {
		$html = do_blocks(
			self::hero_comment(
				array(
					'heading' => 'Hi,<br>I\'m <mark class="has-inline-color has-accent-color">D</mark>imitris',
					'tagline' => 'WordPress developer',
				)
			)
			. '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Contact me</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
			. '<!-- /wp:dsg/hero -->'
		);

		// Wrapper with the block class, the default "wide" alignment and the code tags on.
		$this->assertMatchesRegularExpression( '/<div class="(?=[^"]*\bwp-block-dsg-hero\b)(?=[^"]*\balignwide\b)(?=[^"]*\bhas-code-tags\b)/', $html );
		$this->assertStringContainsString( '<h1 class="wp-block-dsg-hero__title">Hi,<br>I\'m <mark class="has-inline-color has-accent-color">D</mark>imitris</h1>', $html );
		$this->assertStringContainsString( '<p class="wp-block-dsg-hero__tagline">WordPress developer</p>', $html );
		$this->assertStringContainsString( 'href="/contact/">Contact me</a>', $html );

		// Two copies of the default "D", hidden from screen readers.
		$this->assertStringContainsString( '<div class="wp-block-dsg-hero__monogram" aria-hidden="true">', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-hero__letter' ) );
	}

	public function test_hero_skips_empty_parts() {
		$html = do_blocks( '<!-- wp:dsg/hero {"heading":"Hi","monogram":"","showCodeTags":false} /-->' );

		$this->assertStringNotContainsString( '__tagline', $html );
		$this->assertStringNotContainsString( '__actions', $html );
		$this->assertStringNotContainsString( '__monogram', $html );
		$this->assertStringNotContainsString( 'has-code-tags', $html );
	}

	public function test_hero_escapes_unsafe_values() {
		$html = do_blocks(
			self::hero_comment(
				array(
					'heading'  => '<script>bad</script>Hi',
					'monogram' => '<b>',
				)
			) . '<!-- /wp:dsg/hero -->'
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringContainsString( '&lt;b&gt;', $html );
	}

	public function test_tech_stack_renders_a_labelled_list() {
		$html = do_blocks( '<!-- wp:dsg/tech-stack {"label":"Stack","items":["PHP","JavaScript"]} /-->' );

		$this->assertMatchesRegularExpression( '/<p class="wp-block-dsg-tech-stack__label" id="([^"]+)">Stack<\/p>\s*<ul class="wp-block-dsg-tech-stack__list" aria-labelledby="\1">/', $html );
		$this->assertStringContainsString( '<li>PHP</li>', $html );
		$this->assertStringContainsString( '<li>JavaScript</li>', $html );
	}

	public function test_tech_stack_escapes_items_and_skips_empty_ones() {
		$html = do_blocks( '<!-- wp:dsg/tech-stack {"items":["<script>bad</script>","  "]} /-->' );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertSame( 1, substr_count( $html, '<li>' ) );
	}

	public function test_tech_stack_without_items_renders_nothing() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/tech-stack {"label":"Stack"} /-->' ) ) );
	}

	public function test_project_grid_renders_projects_as_cards_in_order() {
		$first  = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Golden Star Ferries',
				'menu_order' => 0,
				'post_date'  => '2024-01-01 00:00:00',
			)
		);
		$second = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Hellenic Red Cross',
				'menu_order' => 1,
				'post_date'  => '2026-01-01 00:00:00',
			)
		);
		wp_set_object_terms( $first, array( 'PHP' ), Project::TAXONOMY );

		$html = do_blocks( '<!-- wp:dsg/project-grid /-->' );

		$this->assertMatchesRegularExpression( '/<div class="[^"]*\bwp-block-dsg-project-grid\b/', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-project-card__title' ) );
		$this->assertStringContainsString( get_permalink( $second ), $html );
		$this->assertStringContainsString( '<li>PHP</li>', $html );

		// Lower "Order" comes first, even though it's the older Project.
		$this->assertLessThan( strpos( $html, 'Hellenic Red Cross' ), strpos( $html, 'Golden Star Ferries' ) );
	}

	public function test_project_grid_marks_only_featured_projects() {
		$featured = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Hellenic Red Cross',
				'menu_order' => 0,
			)
		);
		$plain    = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Golden Star Ferries',
				'menu_order' => 1,
			)
		);
		update_post_meta( $featured, Project::FEATURED_META, true );
		wp_set_object_terms( $plain, array( 'Accessibility' ), Project::TAXONOMY );

		// All Projects: the featured one is first and marked.
		$html = do_blocks( '<!-- wp:dsg/project-grid /-->' );
		$this->assertSame( 1, substr_count( $html, 'is-featured' ) );

		// Filtered to a technology the featured Project doesn't have: the
		// first card shown is not marked as featured.
		$_GET['tech'] = 'accessibility';
		$html         = do_blocks( '<!-- wp:dsg/project-grid {"showFilter":true} /-->' );
		unset( $_GET['tech'] );

		$this->assertStringContainsString( 'Golden Star Ferries', $html );
		$this->assertStringNotContainsString( 'Hellenic Red Cross', $html );
		$this->assertStringNotContainsString( 'is-featured', $html );
	}

	public function test_project_grid_respects_number_and_technology() {
		$php   = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'PHP project',
			)
		);
		$react = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'React project',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Other project',
			)
		);

		$php_term = wp_set_object_terms( $php, array( 'PHP' ), Project::TAXONOMY );
		wp_set_object_terms( $react, array( 'React' ), Project::TAXONOMY );

		$html = do_blocks( '<!-- wp:dsg/project-grid {"technology":' . (int) $php_term[0] . '} /-->' );
		$this->assertStringContainsString( 'PHP project', $html );
		$this->assertStringNotContainsString( 'React project', $html );

		$html = do_blocks( '<!-- wp:dsg/project-grid {"numberOfItems":2} /-->' );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-project-card__title' ) );
	}

	/**
	 * Three Projects: two tagged PHP, one tagged React. "Unused" has none.
	 *
	 * @return void
	 */
	private function create_filter_projects(): void {
		foreach ( array(
			'PHP one'   => 'PHP',
			'PHP two'   => 'PHP',
			'React one' => 'React',
		) as $title => $tech ) {
			$id = self::factory()->post->create(
				array(
					'post_type'  => Project::POST_TYPE,
					'post_title' => $title,
				)
			);
			wp_set_object_terms( $id, array( $tech ), Project::TAXONOMY );
		}
		wp_insert_term( 'Unused', Project::TAXONOMY );
	}

	public function tear_down() {
		unset( $_GET['tech'] );
		parent::tear_down();
	}

	public function test_project_grid_filter_shows_all_projects_and_used_technologies() {
		$this->create_filter_projects();

		$html = do_blocks( '<!-- wp:dsg/project-grid {"showFilter":true,"numberOfItems":1} /-->' );

		$this->assertSame( 3, substr_count( $html, 'wp-block-dsg-project-card__title' ), 'The filter view ignores the number setting.' );
		$this->assertStringContainsString( 'aria-label="Filter projects by technology"', $html );
		$this->assertMatchesRegularExpression( '/data-dsg-filter aria-current="true">All</', $html );
		$this->assertStringContainsString( '?tech=php', $html );
		$this->assertStringContainsString( '?tech=react', $html );
		$this->assertStringNotContainsString( 'Unused', $html, 'Technologies without Projects get no chip.' );
		$this->assertStringContainsString( '// 3 projects', $html );
	}

	public function test_project_grid_filter_reads_the_tech_query_arg() {
		$this->create_filter_projects();
		$_GET['tech'] = 'php';

		$html = do_blocks( '<!-- wp:dsg/project-grid {"showFilter":true} /-->' );

		$this->assertStringContainsString( 'PHP one', $html );
		$this->assertStringContainsString( 'PHP two', $html );
		$this->assertStringNotContainsString( 'React one', $html );
		$this->assertMatchesRegularExpression( '/\?tech=php" data-dsg-filter aria-current="true">PHP</', $html );
		$this->assertStringContainsString( '// 2 projects built with PHP', $html );
	}

	public function test_project_grid_filter_ignores_unknown_technologies() {
		$this->create_filter_projects();
		$_GET['tech'] = 'cobol';

		$html = do_blocks( '<!-- wp:dsg/project-grid {"showFilter":true} /-->' );

		$this->assertSame( 3, substr_count( $html, 'wp-block-dsg-project-card__title' ) );
		$this->assertMatchesRegularExpression( '/data-dsg-filter aria-current="true">All</', $html );
	}

	public function test_project_grid_without_projects_renders_nothing() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/project-grid /-->' ) ) );
	}

	public function test_project_meta_lists_only_filled_details() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		update_post_meta( $project_id, 'dsg_agency', 'Acme Digital' );
		update_post_meta( $project_id, 'dsg_year', '2024' );

		$html = $this->render_project_meta( $project_id );

		$this->assertStringContainsString( '<dt>Agency</dt>', $html );
		$this->assertStringContainsString( 'Acme Digital', $html );
		$this->assertStringContainsString( '2024', $html );
		$this->assertStringNotContainsString( '<dt>Role</dt>', $html );
		$this->assertStringNotContainsString( '<dt>Live site</dt>', $html );
	}

	public function test_project_meta_links_the_live_site_in_a_new_tab() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		update_post_meta( $project_id, 'dsg_live_url', 'https://www.redcross.gr/' );

		$html = $this->render_project_meta( $project_id );

		$this->assertStringContainsString( 'href="https://www.redcross.gr/"', $html );
		$this->assertStringContainsString( 'target="_blank" rel="noopener"', $html );
		$this->assertStringContainsString( 'redcross.gr', $html );
		$this->assertStringContainsString( '(opens in a new tab)', $html );
	}

	public function test_project_meta_prints_nothing_without_details() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		$this->assertSame( '', trim( $this->render_project_meta( $project_id ) ) );
	}

	public function test_tech_stack_can_list_the_projects_technologies() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );
		wp_set_object_terms( $project_id, array( 'PHP', 'React' ), Project::TAXONOMY );

		$html = $this->render_with_project(
			'dsg/tech-stack',
			array(
				'label'       => 'Built with',
				'fromProject' => true,
				'items'       => array( 'Ignored' ),
			),
			$project_id
		);

		$this->assertStringContainsString( '<li>PHP</li>', $html );
		$this->assertStringContainsString( '<li>React</li>', $html );
		$this->assertStringNotContainsString( 'Ignored', $html );
	}

	public function test_tech_stack_from_project_prints_nothing_without_technologies() {
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		$html = $this->render_with_project( 'dsg/tech-stack', array( 'fromProject' => true ), $project_id );

		$this->assertSame( '', trim( $html ) );
	}

	public function test_next_project_shows_the_following_project_as_a_card() {
		$current = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Current',
				'menu_order' => 1,
			)
		);
		self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Ferries',
				'menu_order' => 2,
			)
		);

		$html = $this->render_with_project( 'dsg/next-project', array(), $current );

		$this->assertStringContainsString( '<nav', $html );
		$this->assertStringContainsString( 'aria-label="Next project"', $html );
		$this->assertStringContainsString( 'wp-block-dsg-project-card', $html );
		$this->assertStringContainsString( 'Ferries', $html );
		$this->assertStringNotContainsString( '>Current<', $html );
		// Always the large card, even though "Ferries" isn't a featured Project.
		$this->assertMatchesRegularExpression( '/<article class="[^"]*\bis-featured\b/', $html );
	}

	public function test_next_project_prints_nothing_with_a_single_project() {
		$only = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		$this->assertSame( '', trim( $this->render_with_project( 'dsg/next-project', array(), $only ) ) );
	}

	/**
	 * Render a block for a Project, as a template does (the Project ID
	 * comes from the block context).
	 *
	 * @param string $name       Block name.
	 * @param array  $attrs      Block attributes.
	 * @param int    $project_id Project being shown.
	 * @return string
	 */
	private function render_with_project( string $name, array $attrs, int $project_id ): string {
		$block = new WP_Block(
			array(
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			array(
				'postId'   => $project_id,
				'postType' => Project::POST_TYPE,
			)
		);

		return $block->render();
	}

	/**
	 * Render the Project Details block for a Project, as the template does.
	 *
	 * @param int $project_id Project to show.
	 * @return string
	 */
	private function render_project_meta( int $project_id ): string {
		$block = new WP_Block(
			array(
				'blockName'    => 'dsg/project-meta',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			array(
				'postId'   => $project_id,
				'postType' => Project::POST_TYPE,
			)
		);

		return $block->render();
	}
}
