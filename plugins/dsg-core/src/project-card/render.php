<?php
/**
 * Project Card: the frontend markup.
 *
 * Dynamic block: WordPress runs this file every time the block is shown,
 * so changing the markup here updates every card on the site at once.
 * The post only stores the data: <!-- wp:dsg/project-card {"title":"…"} /-->
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * @package DSG\Core
 */

$card_title   = $attributes['title'] ?? '';
$summary      = $attributes['summary'] ?? '';
$url          = $attributes['url'] ?? '';
$image_id     = $attributes['imageId'] ?? 0;
$image_url    = $attributes['imageUrl'] ?? '';
$image_alt    = $attributes['imageAlt'] ?? '';
$technologies = $attributes['technologies'] ?? array();
$featured     = ! empty( $attributes['featured'] );
$priority     = ! empty( $attributes['imagePriority'] );

// Adds the block's class plus any color/spacing chosen in the sidebar.
$wrapper_attributes = get_block_wrapper_attributes(
	$featured ? array( 'class' => 'is-featured' ) : array()
);
?>
<article <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $image_id || $image_url ) : ?>
		<div class="wp-block-dsg-project-card__media">
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php endif; ?>
			<?php
			if ( $image_id ) {
				/*
				 * Outputs srcset too, so browsers download the right size.
				 *
				 * Loading: with imagePriority (the Project Grid sets it on its
				 * first card), the image loads right away at high priority.
				 * Otherwise WordPress decides. Inside post content it leaves the
				 * first images eager; elsewhere (the Portfolio archive template)
				 * it lazy-loads them all, which is why the grid flags the first card.
				 *
				 * `sizes` describes how wide the image is shown (see style.scss
				 * and the grid's 781px breakpoint), so the browser doesn't pick
				 * the 1024px file for a half-width card
				 */
				echo wp_get_attachment_image(
					$image_id,
					'large',
					false,
					array(
						'alt'   => $image_alt,
						'sizes' => $featured
							? '(max-width: 1000px) 100vw, 760px'
							: '(max-width: 781px) 100vw, (max-width: 1280px) 50vw, 600px',
					) + ( $priority ? array(
						'loading'       => 'eager',
						'fetchpriority' => 'high',
					) : array() )
				);
			} else {
				printf( '<img src="%s" alt="%s" loading="lazy" />', esc_url( $image_url ), esc_attr( $image_alt ) );
			}
			?>
			<?php if ( $url ) : ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="wp-block-dsg-project-card__body">
		<h3 class="wp-block-dsg-project-card__title">
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $card_title ); ?></a>
			<?php else : ?>
				<?php echo wp_kses_post( $card_title ); ?>
			<?php endif; ?>
		</h3>

		<?php if ( $summary ) : ?>
			<p class="wp-block-dsg-project-card__summary"><?php echo wp_kses_post( $summary ); ?></p>
		<?php endif; ?>

		<?php if ( $technologies ) : ?>
			<ul class="wp-block-dsg-project-card__tags" aria-label="<?php esc_attr_e( 'Technologies used', 'dsg-core' ); ?>">
				<?php foreach ( $technologies as $technology ) : ?>
					<li><?php echo esc_html( $technology ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $url ) : ?>
			<a class="wp-block-dsg-project-card__cta" href="<?php echo esc_url( $url ); ?>">
				<?php esc_html_e( 'View the project', 'dsg-core' ); ?>
				<span class="screen-reader-text">: <?php echo esc_html( wp_strip_all_tags( $card_title ) ); ?></span>
				<span aria-hidden="true">→</span>
			</a>
		<?php endif; ?>
	</div>
</article>
