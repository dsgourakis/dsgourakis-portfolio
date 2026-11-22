<?php
/**
 * Title: Hero
 * Slug: dsg-theme/hero
 * Categories: banner
 * Description: Homepage intro: big heading, tagline, buttons and the monogram.
 *
 * Uses the Hero block from the dsg-core plugin.
 *
 * @package DSG\Theme
 */

// Block attributes are stored as JSON in the block comment;
// serialize_block_attributes() escapes the HTML inside them the way the editor does.
$dsg_theme_hero_attributes = serialize_block_attributes(
	array(
		'heading' => sprintf(
			/* translators: %s: the name, with its first letter highlighted. */
			__( 'Hi,<br>I&#8217;m %s,<br>web developer.', 'dsg-theme' ),
			'<mark class="has-inline-color has-accent-color">D</mark>imitris'
		),
		'tagline' => __( 'WordPress developer / Full-stack / Responsive builds', 'dsg-theme' ),
	)
);
?>
<!-- wp:dsg/hero <?php echo $dsg_theme_hero_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON escaped by serialize_block_attributes(). ?> -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
	<!-- wp:button {"className":"is-style-outline"} -->
	<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/contact/"><?php esc_html_e( 'Contact me', 'dsg-theme' ); ?></a></div>
	<!-- /wp:button -->
	<!-- wp:button {"className":"is-style-text"} -->
	<div class="wp-block-button is-style-text"><a class="wp-block-button__link wp-element-button" href="/portfolio/"><?php esc_html_e( 'See work ↓', 'dsg-theme' ); ?></a></div>
	<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- /wp:dsg/hero -->
