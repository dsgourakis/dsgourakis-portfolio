<?php
/**
 * Title: Case study
 * Slug: dsg-theme/case-study
 * Categories: text
 * Post Types: dsg_project
 * Block Types: core/post-content
 * Description: Starting point for a Project write-up: the problem, what was built, and the result with numbers.
 *
 * Offered when a new Project is created. The agency, year, role, live
 * link, cover image and technologies are filled in elsewhere (sidebar),
 * so this is only the story.
 *
 * @package DSG\Theme
 */

?>
<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'The problem', 'dsg-theme' ); ?>","aside":"// 01 / problem"} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Who the client is, what they needed and why the old setup was not working. One short paragraph.', 'dsg-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'What I built', 'dsg-theme' ); ?>","aside":"// 02 / build"} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The approach, and the parts you built yourself: theme, blocks, plugins, integrations.', 'dsg-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list">
	<!-- wp:list-item -->
	<li><?php esc_html_e( 'A key feature, and the problem it solves', 'dsg-theme' ); ?></li>
	<!-- /wp:list-item -->
	<!-- wp:list-item -->
	<li><?php esc_html_e( 'Another feature or technical decision', 'dsg-theme' ); ?></li>
	<!-- /wp:list-item -->
	<!-- wp:list-item -->
	<li><?php esc_html_e( 'Accessibility or performance work', 'dsg-theme' ); ?></li>
	<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'The result', 'dsg-theme' ); ?>","aside":"// 03 / result"} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'What changed for the client once it launched.', 'dsg-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:dsg/stats -->
	<!-- wp:dsg/stat {"title":"30%","description":"<?php echo esc_attr__( 'faster page loads', 'dsg-theme' ); ?>"} /-->
	<!-- wp:dsg/stat {"pretitle":"<?php echo esc_attr__( 'launch', 'dsg-theme' ); ?>","title":"<?php echo esc_attr__( 'On time', 'dsg-theme' ); ?>","description":"<?php echo esc_attr__( 'Shipped in 8 weeks', 'dsg-theme' ); ?>"} /-->
	<!-- wp:dsg/stat {"pretitle":"<?php echo esc_attr__( 'a11y', 'dsg-theme' ); ?>","title":"WCAG 2.1 AA","description":"<?php echo esc_attr__( 'Checked with axe and the keyboard', 'dsg-theme' ); ?>"} /-->
<!-- /wp:dsg/stats -->
