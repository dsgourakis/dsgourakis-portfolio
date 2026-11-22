<?php
/**
 * Tests for the page label binding source.
 *
 * @package DSG\Core
 */

use DSG\Core\Blocks\PageLabel;

/**
 * The "// slug" label above page titles.
 */
class Test_Page_Label extends WP_UnitTestCase {

	public function test_source_is_registered() {
		$this->assertNotNull( get_block_bindings_source( PageLabel::SOURCE ) );
	}

	public function test_uses_the_page_slug() {
		$page_id = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'about-me',
			)
		);

		$this->assertSame( '// about me', PageLabel::text( array(), $page_id ) );
	}

	public function test_label_argument_wins() {
		$this->assertSame( '// work', PageLabel::text( array( 'label' => 'work' ), 123 ) );
	}

	public function test_empty_without_post_or_label() {
		$this->assertSame( '', PageLabel::text() );
	}

	public function test_escapes_the_label() {
		$this->assertSame( '// &lt;b&gt;', PageLabel::text( array( 'label' => '<b>' ) ) );
	}
}
