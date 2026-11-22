<?php
/**
 * Title: Contact page
 * Slug: dsg-theme/contact-page
 * Categories: text
 * Post Types: page
 * Block Types: core/post-content
 * Description: Contact card (availability, details and the form) plus a short FAQ. For the Contact page, whose template adds the big heading above.
 *
 * The card uses the same classes as the footer's contact card, so it
 * looks the same; the footer's card is hidden on this page (style.css).
 *
 * @package DSG\Theme
 */

?>
<!-- wp:group {"align":"wide","className":"contact-card","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} -->
<div class="wp-block-group alignwide contact-card">
	<!-- wp:group {"className":"contact-card__intro","layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group contact-card__intro">
		<!-- wp:paragraph {"className":"contact-status"} -->
		<p class="contact-status"><?php esc_html_e( 'Available for new projects', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"fontSize":"xx-large"} -->
		<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e( 'Tell me about your project', 'dsg-theme' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"muted"} -->
		<p class="has-muted-color has-text-color"><?php esc_html_e( 'A few lines are enough: what you need, roughly when, and a link to the current site if there is one.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'email', 'dsg-theme' ); ?></span> <a href="mailto:hello@dsgourakis.com">hello@dsgourakis.com</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'elsewhere', 'dsg-theme' ); ?></span> <a href="https://www.linkedin.com/in/dimitris-sgourakis/" target="_blank" rel="noreferrer noopener">LinkedIn</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-detail"} -->
		<p class="contact-detail"><span class="contact-detail__label">// <?php esc_html_e( 'replies', 'dsg-theme' ); ?></span> <?php esc_html_e( 'Usually within two working days', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:dsg/contact-form {"className":"contact-card__form"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"contact-faq","style":{"spacing":{"margin":{"top":"var:preset|spacing|80"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide contact-faq" style="margin-top:var(--wp--preset--spacing--80)">
	<!-- wp:dsg/section-header {"heading":"<?php echo esc_attr__( 'Good to know', 'dsg-theme' ); ?>","aside":"// faq"} /-->

	<!-- wp:details -->
	<details class="wp-block-details"><summary><?php esc_html_e( 'What kind of work do you take on?', 'dsg-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Custom WordPress themes and blocks, WooCommerce shops, and turning a finished design into a fast, accessible site. Also fixes and improvements on existing sites.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details -->
	<details class="wp-block-details"><summary><?php esc_html_e( 'Do you work with agencies and designers?', 'dsg-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Yes. Most of my work is building what a design team has designed, and handing over a site that is easy for them to maintain.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details -->
	<details class="wp-block-details"><summary><?php esc_html_e( 'Can you take over an existing WordPress site?', 'dsg-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Usually, yes. I start with a short review of the code, plugins and performance, then suggest what to fix first.', 'dsg-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->
</div>
<!-- /wp:group -->
