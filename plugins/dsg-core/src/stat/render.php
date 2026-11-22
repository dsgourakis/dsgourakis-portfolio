<?php
/**
 * Stat: the frontend markup.
 *
 * Dynamic block: WordPress runs this file every time the block is shown,
 * so changing the markup here updates every Stat on the site at once.
 * The post only stores the data: <!-- wp:dsg/stat {"title":"4+"} /-->
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * The counter ("01 / ") and the big-number look of the first Stat come from CSS.
 * The pre-title <p> is always printed (even empty) so the CSS counter has
 * somewhere to appear.
 *
 * @package DSG\Core
 */

$pretitle    = $attributes['pretitle'] ?? '';
$stat_title  = $attributes['title'] ?? '';
$description = $attributes['description'] ?? '';

// Adds the block's class plus any color/spacing chosen in the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<p class="wp-block-dsg-stat__pretitle"><?php echo wp_kses_post( $pretitle ); ?></p>
	<h3 class="wp-block-dsg-stat__title"><?php echo wp_kses_post( $stat_title ); ?></h3>
	<?php if ( $description ) : ?>
		<p class="wp-block-dsg-stat__description"><?php echo wp_kses_post( $description ); ?></p>
	<?php endif; ?>
</div>
