<?php
/**
 * Section Header: the frontend markup.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * The layout comes from the block style class on the wrapper
 * (is-style-meta or is-style-intro), styled in style.scss.
 *
 * @package DSG\Core
 */

$section_heading = $attributes['heading'] ?? '';
$aside           = $attributes['aside'] ?? '';

// Nothing to show: print nothing rather than an empty row.
if ( '' === $section_heading && '' === $aside ) {
	return;
}

// Adds the block's class, alignment, style class and any margin from the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $section_heading ) : ?>
		<h2 class="wp-block-dsg-section-header__heading"><?php echo wp_kses_post( $section_heading ); ?></h2>
	<?php endif; ?>
	<?php if ( $aside ) : ?>
		<p class="wp-block-dsg-section-header__aside"><?php echo wp_kses_post( $aside ); ?></p>
	<?php endif; ?>
</div>
