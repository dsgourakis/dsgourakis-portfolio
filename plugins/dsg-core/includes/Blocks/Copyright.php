<?php
/**
 * The footer's copyright line, with a year that keeps itself current.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Blocks;

/**
 * A block bindings source: a Paragraph bound to "dsg-core/copyright" shows
 * "© <this year> <name>". The footer uses it (parts/footer.html).
 *
 * The name comes from the binding's "name" argument, or the site title:
 *
 *     {"metadata":{"bindings":{"content":{"source":"dsg-core/copyright","args":{"name":"Dimitris Sgourakis"}}}}}
 *
 * Whatever text the paragraph itself holds is only shown in the editor.
 */
class Copyright {

	/**
	 * Block bindings source name.
	 */
	public const SOURCE = 'dsg-core/copyright';

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
				'label'              => __( 'Copyright line', 'dsg-core' ),
				'get_value_callback' => array( self::class, 'text' ),
			)
		);
	}

	/**
	 * The copyright line.
	 *
	 * @param array $args The binding's arguments.
	 * @return string
	 */
	public static function text( array $args = array() ): string {
		$name = trim( (string) ( $args['name'] ?? '' ) );
		if ( '' === $name ) {
			$name = get_bloginfo( 'name' );
		}

		return sprintf( '© %s %s', wp_date( 'Y' ), esc_html( $name ) );
	}
}
