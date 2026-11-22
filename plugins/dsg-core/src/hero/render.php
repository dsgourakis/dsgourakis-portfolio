<?php
/**
 * Hero: the frontend markup.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    the HTML of the Buttons block inside, already rendered
 * - $block:      the WP_Block instance
 *
 * The <body>, <h1> and </h1> code tags are CSS pseudo-elements (see
 * style.scss), so they are never part of the heading's text for screen
 * readers or search engines. The monogram is decoration only.
 *
 * @package DSG\Core
 */

$hero_heading   = $attributes['heading'] ?? '';
$tagline        = $attributes['tagline'] ?? '';
$monogram       = $attributes['monogram'] ?? '';
$show_code_tags = $attributes['showCodeTags'] ?? true;

// Adds the block's class, alignment and any spacing from the sidebar.
$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => $show_code_tags ? 'has-code-tags' : '' )
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<div class="wp-block-dsg-hero__content">
		<?php if ( $hero_heading ) : ?>
			<div class="wp-block-dsg-hero__heading">
				<h1 class="wp-block-dsg-hero__title"><?php echo wp_kses_post( $hero_heading ); ?></h1>
			</div>
		<?php endif; ?>
		<?php if ( $tagline ) : ?>
			<p class="wp-block-dsg-hero__tagline"><?php echo wp_kses_post( $tagline ); ?></p>
		<?php endif; ?>
		<?php if ( trim( $content ) ) : ?>
			<div class="wp-block-dsg-hero__actions">
				<?php
				// The Buttons block's own render already escaped its output.
				echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( '' !== $monogram ) : ?>
		<div class="wp-block-dsg-hero__monogram" aria-hidden="true">
			<span class="wp-block-dsg-hero__letter is-back"><?php echo esc_html( $monogram ); ?></span>
			<span class="wp-block-dsg-hero__letter is-front"><?php echo esc_html( $monogram ); ?></span>
		</div>
	<?php endif; ?>
</div>
