<?php
/**
 * Project Details: the frontend markup.
 *
 * Reads the Project's details (post meta, see PostTypes/Project.php) and
 * prints them as a description list. Empty details are left out; with
 * none at all, the block prints nothing.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance (its context holds the post ID)
 *
 * @package DSG\Core
 */

$project_id = (int) ( $block->context['postId'] ?? get_the_ID() );

if ( ! $project_id ) {
	return;
}

$detail_labels = array(
	'dsg_agency'   => __( 'Agency', 'dsg-core' ),
	'dsg_year'     => __( 'Year', 'dsg-core' ),
	'dsg_role'     => __( 'Role', 'dsg-core' ),
	'dsg_live_url' => __( 'Live site', 'dsg-core' ),
);

$details = array();

foreach ( $detail_labels as $meta_key => $label ) {
	$value = trim( (string) get_post_meta( $project_id, $meta_key, true ) );
	if ( '' !== $value ) {
		$details[ $meta_key ] = array(
			'label' => $label,
			'value' => $value,
		);
	}
}

if ( ! $details ) {
	return;
}

// Adds the block's class, alignment and any margin from the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<dl <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php foreach ( $details as $meta_key => $detail ) : ?>
		<div class="wp-block-dsg-project-meta__item">
			<dt><?php echo esc_html( $detail['label'] ); ?></dt>
			<dd>
				<?php if ( 'dsg_live_url' === $meta_key ) : ?>
					<a class="wp-block-dsg-project-meta__link" href="<?php echo esc_url( $detail['value'] ); ?>" target="_blank" rel="noopener">
						<?php
						// "https://www.redcross.gr/" is shown as "redcross.gr" (same as displayUrl() in fields.js).
						echo esc_html( preg_replace( array( '#^https?://#i', '#^www\.#i', '#/$#' ), '', $detail['value'] ) );
						?>
						<span aria-hidden="true">↗</span>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'dsg-core' ); ?></span>
					</a>
				<?php else : ?>
					<?php echo esc_html( $detail['value'] ); ?>
				<?php endif; ?>
			</dd>
		</div>
	<?php endforeach; ?>
</dl>
