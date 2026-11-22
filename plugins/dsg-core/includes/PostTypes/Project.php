<?php
/**
 * "Project" post type and "Technology" taxonomy.
 *
 * @package DSG\Core
 */

namespace DSG\Core\PostTypes;

/**
 * Portfolio projects, tagged with the technologies used.
 */
class Project {

	public const POST_TYPE = 'dsg_project';
	public const TAXONOMY  = 'dsg_technology';

	/**
	 * The order Projects are listed in everywhere: the "Order" field first
	 * (lowest first), then newest first. Used by the Project Grid and
	 * Next Project blocks.
	 */
	public const ORDERBY = array(
		'menu_order' => 'ASC',
		'date'       => 'DESC',
	);

	/**
	 * Project details, shown in the "Project details" sidebar panel and by
	 * the Project Meta block. Key => sanitize callback.
	 */
	public const META = array(
		'dsg_agency'   => 'sanitize_text_field',
		'dsg_year'     => 'sanitize_text_field',
		'dsg_role'     => 'sanitize_text_field',
		'dsg_live_url' => 'esc_url_raw',
	);

	/**
	 * A screenshot of the live site's homepage (attachment ID), picked in the
	 * "Project details" panel. Kept apart from META because it is an image,
	 * not a text detail shown by the Project Meta block.
	 */
	public const SCREENSHOT_META = 'dsg_screenshot';

	/**
	 * "Featured project" checkbox in the "Project details" panel. A featured
	 * Project gets the large, full-row card when it is the first card shown
	 * (see project-card/style.scss), so a filtered list never promotes a
	 * Project just because it happens to come first.
	 */
	public const FEATURED_META = 'dsg_featured';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register the Project post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Projects', 'dsg-core' ),
					'singular_name' => __( 'Project', 'dsg-core' ),
					'add_new_item'  => __( 'Add New Project', 'dsg-core' ),
					'edit_item'     => __( 'Edit Project', 'dsg-core' ),
					'all_items'     => __( 'All Projects', 'dsg-core' ),
				),
				'public'       => true,
				'has_archive'  => 'portfolio',
				'rewrite'      => array( 'slug' => 'portfolio' ),
				'menu_icon'    => 'dashicons-portfolio',
				'show_in_rest' => true, // Required for the block editor.
				// page-attributes adds the "Order" field, used to sort the Project Grid.
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
			)
		);
	}

	/**
	 * Register the Technology taxonomy (PHP, React, WooCommerce...).
	 *
	 * Technologies label Projects and filter the Portfolio page;
	 * they have no pages of their own.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Technologies', 'dsg-core' ),
					'singular_name' => __( 'Technology', 'dsg-core' ),
				),
				// No public pages: technologies are only used as filters on the
				// Portfolio page (the Project Grid's ?tech= links), not as archives.
				'public'             => false,
				'publicly_queryable' => false,
				'rewrite'            => false,
				'query_var'          => false,
				'show_ui'            => true,
				'show_in_rest'       => true, // The editor's Technologies panel.
				'show_admin_column'  => true,
				'show_in_nav_menus'  => false,
				'show_tagcloud'      => false,
				'hierarchical'       => false,
			)
		);
	}

	/**
	 * Register the project details (agency, year, role, live site) and the screenshot.
	 *
	 * The show_in_rest flag lets the block editor read and save them; the editor
	 * panel lives in src/project-meta/.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$can_edit = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', $post_id );
		};

		foreach ( self::META as $key => $sanitize ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => $can_edit,
				)
			);
		}

		register_post_meta(
			self::POST_TYPE,
			self::SCREENSHOT_META,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $can_edit,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::FEATURED_META,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => $can_edit,
			)
		);
	}

	/**
	 * Whether the Project is marked as featured.
	 *
	 * @param int $project_id The Project.
	 * @return bool
	 */
	public static function is_featured( int $project_id ): bool {
		return (bool) get_post_meta( $project_id, self::FEATURED_META, true );
	}

	/**
	 * The Project listed after this one, wrapping round to the first.
	 *
	 * @param int $project_id The current Project.
	 * @return int The next Project's ID, or 0 if there is no other Project.
	 */
	public static function next_after( int $project_id ): int {
		$ids = get_posts(
			array(
				'post_type'           => self::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => 100, // Plenty for a portfolio; keeps the query bounded.
				'orderby'             => self::ORDERBY,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);

		$position = array_search( $project_id, $ids, true );

		// Not in the list (e.g. a draft being previewed): suggest the first one.
		if ( false === $position ) {
			return (int) ( $ids[0] ?? 0 );
		}

		if ( count( $ids ) < 2 ) {
			return 0;
		}

		return (int) $ids[ ( $position + 1 ) % count( $ids ) ];
	}

	/**
	 * A Project shown as a Project Card block.
	 *
	 * Used by the Project Grid and Next Project blocks, so every card on
	 * the site comes from the same markup (project-card/render.php, which
	 * escapes its output).
	 *
	 * @param \WP_Post  $project  The Project.
	 * @param bool|null $featured Show it as featured. Null (the default)
	 *                            uses the Project's own "Featured" checkbox.
	 * @param bool      $priority Load the image right away, at high priority:
	 *                            for the first card, likely above the fold.
	 * @return string Card HTML.
	 */
	public static function render_card( \WP_Post $project, ?bool $featured = null, bool $priority = false ): string {
		$image_id     = (int) get_post_thumbnail_id( $project );
		$technologies = wp_get_post_terms( $project->ID, self::TAXONOMY, array( 'fields' => 'names' ) );

		return render_block(
			array(
				'blockName'    => 'dsg/project-card',
				'attrs'        => array(
					'title'         => get_the_title( $project ),
					'summary'       => get_the_excerpt( $project ),
					'url'           => get_permalink( $project ),
					'imageId'       => $image_id,
					'imageAlt'      => $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
					'technologies'  => is_wp_error( $technologies ) ? array() : $technologies,
					'featured'      => $featured ?? self::is_featured( $project->ID ),
					'imagePriority' => $priority,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}
}
