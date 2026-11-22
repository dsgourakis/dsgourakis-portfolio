<?php
/**
 * Next Project: the frontend markup.
 *
 * Prints the following Project as a Project Card, in a <nav> so screen
 * reader users can find it as "Next project" navigation. The card is the
 * first (and only) one in its container and always rendered as featured,
 * so it gets the large layout whether or not that Project is featured;
 * style.scss swaps its "01 — featured" counter for "// next project".
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance (its context holds the post ID)
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;

$current_id = (int) ( $block->context['postId'] ?? get_the_ID() );
$next_id    = $current_id ? Project::next_after( $current_id ) : 0;
$next       = $next_id ? get_post( $next_id ) : null;

// No other Project to point to.
if ( ! $next ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array( 'aria-label' => __( 'Next project', 'dsg-core' ) )
);
?>
<nav <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
	// The card's render.php already escaped its own output.
	echo Project::render_card( $next, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</nav>
