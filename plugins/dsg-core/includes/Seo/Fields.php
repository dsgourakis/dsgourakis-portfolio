<?php
/**
 * The "Search & sharing" fields on Pages and Projects.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

use DSG\Core\PostTypes\Project;

/**
 * Post meta for a custom search title, description, share image and a
 * "hide from search engines" switch. Every field is optional: when one
 * is empty, Context falls back to the post's own title, excerpt and
 * featured image.
 *
 * The editor panel lives in src/seo-panel/.
 */
class Fields {

	public const TITLE       = 'dsg_seo_title';
	public const DESCRIPTION = 'dsg_seo_description';
	public const IMAGE       = 'dsg_seo_image';
	public const NOINDEX     = 'dsg_seo_noindex';

	/**
	 * Post types that get the fields.
	 */
	public const POST_TYPES = array( 'page', Project::POST_TYPE );

	/**
	 * Script handle of the editor panel.
	 */
	public const SCRIPT = 'dsg-core-seo-panel';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_panel' ) );
	}

	/**
	 * Register the fields for the block editor (show_in_rest).
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$can_edit = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', $post_id );
		};

		$fields = array(
			self::TITLE       => array( 'string', '', 'sanitize_text_field' ),
			self::DESCRIPTION => array( 'string', '', 'sanitize_textarea_field' ),
			self::IMAGE       => array( 'integer', 0, 'absint' ),
			self::NOINDEX     => array( 'boolean', false, 'rest_sanitize_boolean' ),
		);

		foreach ( self::POST_TYPES as $post_type ) {
			foreach ( $fields as $key => list( $type, $default, $sanitize ) ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $type,
						'single'            => true,
						'default'           => $default,
						'show_in_rest'      => true,
						'sanitize_callback' => $sanitize,
						'auth_callback'     => $can_edit,
					)
				);
			}
		}
	}

	/**
	 * Load the "Search & sharing" panel when editing a Page or Project.
	 *
	 * The panel isn't a block, so it has its own build entry
	 * (see webpack.config.js in the repo root).
	 *
	 * @return void
	 */
	public function enqueue_panel(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, self::POST_TYPES, true ) ) {
			return;
		}

		$asset_file = DSG_CORE_DIR . 'build/seo-panel/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::SCRIPT,
			plugins_url( 'build/seo-panel/index.js', DSG_CORE_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( self::SCRIPT, 'dsg-core' );
	}
}
