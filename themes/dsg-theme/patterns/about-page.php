<?php
/**
 * Title: About page
 * Slug: dsg-theme/about-page
 * Categories: text
 * Post Types: page
 * Block Types: core/post-content
 * Description: Bio with quick facts, results in numbers, the tech stack and an experience timeline. For the About page, whose template adds the big heading above.
 *
 * Facts come from the CV. Once inserted, everything is normal blocks,
 * edited in the page editor.
 *
 * @package DSG\Theme
 */

// Tech Stack cards: label => chips.
$dsg_theme_stacks = array(
	__( 'CMS & e-commerce', 'dsg-theme' )    => array( 'WordPress', 'Gutenberg blocks', 'WooCommerce', 'ACF' ),
	__( 'Front-end', 'dsg-theme' )           => array( 'JavaScript (ES6+)', 'HTML5', 'Modern CSS', 'SCSS', 'Responsive design' ),
	__( 'Back-end', 'dsg-theme' )            => array( 'PHP', 'MySQL', 'REST API', 'WordPress Coding Standards', 'PHPUnit' ),
	__( 'Accessibility & SEO', 'dsg-theme' ) => array( 'WCAG / ADA', 'On-page SEO', 'WAVE' ),
	__( 'Tools & workflow', 'dsg-theme' )    => array( 'Git', 'WP-CLI', 'GitHub Actions', 'Claude', 'GitHub Copilot' ),
);

// Experience, newest first: years, role, where, one line (optional).
$dsg_theme_jobs = array(
	array( '2025 — now', __( 'Senior WordPress Developer', 'dsg-theme' ), 'WildWildWeb.gr · ' . __( 'Remote', 'dsg-theme' ), __( 'Lead custom WordPress and WooCommerce builds, mentor junior developers, and look after performance, security and accessibility across client sites.', 'dsg-theme' ) ),
	array( '2020 — 2025', __( 'Freelance WordPress Developer', 'dsg-theme' ), __( 'Remote', 'dsg-theme' ), __( 'Over 15 projects end to end, from requirements to launch and support: block-based themes, ACF, analytics and UX improvements.', 'dsg-theme' ) ),
	array( '2020', __( 'WordPress Developer', 'dsg-theme' ), 'Greatway.gr · Kalamata', __( 'Custom themes and site maintenance; brought 22 sites in line with WCAG / ADA accessibility standards.', 'dsg-theme' ) ),
	array( '2018 — 2020', __( 'Freelance WordPress Developer', 'dsg-theme' ), 'Kalamata', __( 'Small WordPress projects and technical support for local businesses.', 'dsg-theme' ) ),
	array( '2016 — 2017', __( 'Web Developer', 'dsg-theme' ), 'Advertek.gr · Kalamata', __( 'Designed and built 30+ responsive WordPress sites for agency clients, and looked after hosting for over 50 sites.', 'dsg-theme' ) ),
	array( '2014 — 2016', __( 'Software Applications Technician', 'dsg-theme' ), __( 'Education', 'dsg-theme' ) . ' · IEK of Kalamata', '' ),
);
?>
<!-- wp:group {"align":"wide","className":"about-intro","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} -->
<div class="wp-block-group alignwide about-intro">
	<!-- wp:group {"className":"about-intro__text","layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group about-intro__text">
		<!-- wp:paragraph {"fontSize":"large"} -->
		<p class="has-large-font-size"><?php esc_html_e( 'Most of my work starts with someone else’s design. I turn it into a site that matches it, loads fast, works for everyone, and is easy for the client to edit.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"textColor":"muted"} -->
		<p class="has-muted-color has-text-color"><?php esc_html_e( 'I work across PHP and JavaScript, from the WordPress REST API to custom Gutenberg blocks, with a focus on clean, accessible, secure and high-performing code.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"textColor":"muted"} -->
		<p class="has-muted-color has-text-color"><?php esc_html_e( 'Today I’m a Senior WordPress Developer at WildWildWeb, where I lead builds, review code and mentor junior developers.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"about-facts","layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group about-facts">
		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'based in', 'dsg-theme' ); ?></span> <?php esc_html_e( 'Kalamata, Greece · works remotely', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'focus', 'dsg-theme' ); ?></span> <?php esc_html_e( 'Accessibility · WooCommerce · Performance', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'languages', 'dsg-theme' ); ?></span> <?php esc_html_e( 'Greek (native) · English (C2)', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline","metadata":{"bindings":{"url":{"source":"dsg-core/resume"}}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Resume', 'dsg-theme' ); ?> <span aria-hidden="true">↗</span><span class="screen-reader-text"> <?php esc_html_e( '(PDF, opens in a new tab)', 'dsg-theme' ); ?></span></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"about-section","style":{"spacing":{"margin":{"top":"var:preset|spacing|80"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide about-section" style="margin-top:var(--wp--preset--spacing--80)">
	<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'By the numbers', 'dsg-theme' ); ?>","aside":"// impact"} /-->

	<!-- wp:dsg/stats -->
		<!-- wp:dsg/stat {"title":"7+","description":"<?php echo esc_attr__( 'years building WordPress and WooCommerce sites.', 'dsg-theme' ); ?>"} /-->
		<!-- wp:dsg/stat {"pretitle":"speed","title":"30% <?php echo esc_attr__( 'faster', 'dsg-theme' ); ?>","description":"<?php echo esc_attr__( 'Average page load improvement across 40 client sites.', 'dsg-theme' ); ?>"} /-->
		<!-- wp:dsg/stat {"pretitle":"ship","title":"25+ <?php echo esc_attr__( 'sites', 'dsg-theme' ); ?>","description":"<?php echo esc_attr__( 'Responsive, production-ready sites shipped with designers.', 'dsg-theme' ); ?>"} /-->
		<!-- wp:dsg/stat {"pretitle":"a11y","title":"WCAG","description":"<?php echo esc_attr__( 'Accessibility standards on every client deliverable.', 'dsg-theme' ); ?>"} /-->
	<!-- /wp:dsg/stats -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"about-section","style":{"spacing":{"margin":{"top":"var:preset|spacing|80"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide about-section" style="margin-top:var(--wp--preset--spacing--80)">
	<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'Stack', 'dsg-theme' ); ?>","aside":"// <?php echo esc_attr__( 'tools I use every day', 'dsg-theme' ); ?>"} /-->

	<!-- wp:group {"className":"about-stack","layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
	<div class="wp-block-group about-stack">
		<?php
		foreach ( $dsg_theme_stacks as $dsg_theme_label => $dsg_theme_items ) :
			// Block attributes as JSON, escaped the way the editor does.
			$dsg_theme_attributes = serialize_block_attributes(
				array(
					'label' => $dsg_theme_label,
					'items' => $dsg_theme_items,
				)
			);
			?>
		<!-- wp:dsg/tech-stack <?php echo $dsg_theme_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON escaped by serialize_block_attributes(). ?> /-->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"about-section","style":{"spacing":{"margin":{"top":"var:preset|spacing|80"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide about-section" style="margin-top:var(--wp--preset--spacing--80)">
	<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'Experience', 'dsg-theme' ); ?>","aside":"// <?php echo esc_attr__( 'since 2016', 'dsg-theme' ); ?>"} /-->

	<!-- wp:group {"className":"about-timeline","layout":{"type":"default"}} -->
	<div class="wp-block-group about-timeline">
		<?php foreach ( $dsg_theme_jobs as list( $dsg_theme_years, $dsg_theme_role, $dsg_theme_where, $dsg_theme_summary ) ) : ?>
		<!-- wp:group {"className":"about-timeline__item","layout":{"type":"default"}} -->
		<div class="wp-block-group about-timeline__item">
			<!-- wp:paragraph {"className":"about-timeline__years"} -->
			<p class="about-timeline__years"><?php echo esc_html( $dsg_theme_years ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"about-timeline__role"} -->
			<h3 class="wp-block-heading about-timeline__role"><?php echo esc_html( $dsg_theme_role ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"about-timeline__where"} -->
			<p class="about-timeline__where"><?php echo esc_html( $dsg_theme_where ); ?></p>
			<!-- /wp:paragraph -->
			<?php if ( $dsg_theme_summary ) : ?>
			<!-- wp:paragraph {"className":"about-timeline__summary"} -->
			<p class="about-timeline__summary"><?php echo esc_html( $dsg_theme_summary ); ?></p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
