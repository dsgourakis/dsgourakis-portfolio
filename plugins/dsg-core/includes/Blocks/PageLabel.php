<?php
/**
 * The small "// about" label above a page's title.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Blocks;

/**
 * A block bindings source: a Paragraph bound to "dsg-core/page-label" shows
 * "// <page slug>", in the style of the homepage's mono labels.
 * The page header uses it (parts/page-header.html).
 *
 * Pass a "label" argument to use fixed text instead of the slug:
 *
 *     {"metadata":{"bindings":{"content":{"source":"dsg-core/page-label","args":{"label":"work"}}}}}
 */
class PageLabel {

	/**
	 * Block bindings source name.
	 */
	public const SOURCE = 'dsg-core/page-label';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_binding_source' ) );
	}

	/**
	 * Register the source.
	 *
	 * @return void
	 */
	public function register_binding_source(): void {
		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Page label', 'dsg-core' ),
				'uses_context'       => array( 'postId' ),
				'get_value_callback' => array( self::class, 'value' ),
			)
		);
	}

	/**
	 * Bindings callback: reads the post from the block's context.
	 *
	 * @param array     $args  The binding's arguments.
	 * @param \WP_Block $block The bound block.
	 * @return string
	 */
	public static function value( array $args, $block ): string {
		$post_id = (int) ( $block->context['postId'] ?? get_queried_object_id() );

		return self::text( $args, $post_id );
	}

	/**
	 * The label text.
	 *
	 * @param array $args    The binding's arguments.
	 * @param int   $post_id The post the label is for.
	 * @return string
	 */
	public static function text( array $args = array(), int $post_id = 0 ): string {
		$label = trim( (string) ( $args['label'] ?? '' ) );

		if ( '' === $label && $post_id ) {
			$label = (string) get_post_field( 'post_name', $post_id );
		}

		if ( '' === $label ) {
			return '';
		}

		return '// ' . esc_html( str_replace( '-', ' ', $label ) );
	}
}
