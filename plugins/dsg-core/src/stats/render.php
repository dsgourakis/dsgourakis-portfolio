<?php
/**
 * Stats: the frontend markup.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data (here only "align")
 * - $content:    the HTML of the Stat blocks inside, already rendered
 * - $block:      the WP_Block instance
 *
 * @package DSG\Core
 */

// Adds the block's class, alignment (alignwide) and any margin from the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
	// Each Stat's render.php already escaped its own output.
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
