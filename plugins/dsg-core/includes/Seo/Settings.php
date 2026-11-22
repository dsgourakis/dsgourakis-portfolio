<?php
/**
 * Site-wide SEO settings: Settings > General > "Search & sharing".
 *
 * @package DSG\Core
 */

namespace DSG\Core\Seo;

/**
 * The few things search engines need that no post holds:
 * who the site is about (job title, social profiles), the Work page's
 * description and the share image used when a page has none.
 */
class Settings {

	public const JOB_TITLE        = 'dsg_seo_job_title';
	public const PROFILES         = 'dsg_seo_profiles';
	public const WORK_DESCRIPTION = 'dsg_seo_work_description';
	public const DEFAULT_IMAGE    = 'dsg_seo_default_image';

	/**
	 * Settings section ID.
	 */
	private const SECTION = 'dsg_seo';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'add_settings_fields' ) );
	}

	/**
	 * Register the options. On "init" so the REST API knows them too.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'general',
			self::JOB_TITLE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			)
		);

		register_setting(
			'general',
			self::PROFILES,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( self::class, 'sanitize_profiles' ),
				'show_in_rest'      => true,
				'default'           => '',
			)
		);

		register_setting(
			'general',
			self::WORK_DESCRIPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'default'           => '',
			)
		);

		register_setting(
			'general',
			self::DEFAULT_IMAGE,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( self::class, 'sanitize_image' ),
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);
	}

	/**
	 * Add a "Search & sharing" section to Settings > General.
	 *
	 * @return void
	 */
	public function add_settings_fields(): void {
		add_settings_section(
			self::SECTION,
			__( 'Search & sharing', 'dsg-core' ),
			static function () {
				printf(
					'<p>%s</p>',
					esc_html__( 'Used by search engines and by link previews on social media and in chat apps. The Site Title is your name and the Tagline is the fallback description.', 'dsg-core' )
				);
			},
			'general'
		);

		add_settings_field(
			self::JOB_TITLE,
			__( 'Job title', 'dsg-core' ),
			array( $this, 'render_text_field' ),
			'general',
			self::SECTION,
			array(
				'label_for' => self::JOB_TITLE,
				'help'      => __( 'For example: WordPress Developer.', 'dsg-core' ),
			)
		);

		add_settings_field(
			self::PROFILES,
			__( 'Social profiles', 'dsg-core' ),
			array( $this, 'render_textarea_field' ),
			'general',
			self::SECTION,
			array(
				'label_for' => self::PROFILES,
				'help'      => __( 'One address per line: GitHub, LinkedIn… Tells search engines these profiles are yours.', 'dsg-core' ),
			)
		);

		add_settings_field(
			self::WORK_DESCRIPTION,
			__( 'Work page description', 'dsg-core' ),
			array( $this, 'render_textarea_field' ),
			'general',
			self::SECTION,
			array(
				'label_for' => self::WORK_DESCRIPTION,
				'help'      => __( 'Shown in search results for /portfolio/. About 155 characters.', 'dsg-core' ),
			)
		);

		add_settings_field(
			self::DEFAULT_IMAGE,
			__( 'Default share image', 'dsg-core' ),
			array( $this, 'render_image_field' ),
			'general',
			self::SECTION,
			array( 'label_for' => self::DEFAULT_IMAGE )
		);
	}

	/**
	 * A one-line text field.
	 *
	 * @param array $args label_for (the option) and help.
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%1$s" value="%2$s">',
			esc_attr( $args['label_for'] ),
			esc_attr( (string) get_option( $args['label_for'], '' ) )
		);
		printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
	}

	/**
	 * A multi-line text field.
	 *
	 * @param array $args label_for (the option) and help.
	 * @return void
	 */
	public function render_textarea_field( array $args ): void {
		printf(
			'<textarea class="large-text" rows="3" id="%1$s" name="%1$s">%2$s</textarea>',
			esc_attr( $args['label_for'] ),
			esc_textarea( (string) get_option( $args['label_for'], '' ) )
		);
		printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
	}

	/**
	 * A dropdown of the images in the Media Library.
	 *
	 * @return void
	 */
	public function render_image_field(): void {
		$images  = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 100, // The newest 100 images; plenty for a portfolio.
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$current = (int) get_option( self::DEFAULT_IMAGE, 0 );

		printf( '<select id="%1$s" name="%1$s">', esc_attr( self::DEFAULT_IMAGE ) );
		printf( '<option value="0">%s</option>', esc_html__( '— None (use the Site Icon) —', 'dsg-core' ) );
		foreach ( $images as $image ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $image->ID,
				selected( $current, $image->ID, false ),
				esc_html( get_the_title( $image ) )
			);
		}
		echo '</select>';

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Used for link previews when a page has no share image or featured image of its own. Best at 1200 × 630 pixels.', 'dsg-core' )
		);
	}

	/**
	 * Keep only valid web addresses, one per line.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_profiles( $value ): string {
		$urls = array_map( 'esc_url_raw', preg_split( '/\s+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY ) );

		return implode( "\n", array_unique( array_filter( $urls ) ) );
	}

	/**
	 * Keep the option only when it points at an image.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	public static function sanitize_image( $value ): int {
		$id = absint( $value );

		return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	/**
	 * The social profile URLs.
	 *
	 * @return string[]
	 */
	public static function profiles(): array {
		return array_values( array_filter( explode( "\n", (string) get_option( self::PROFILES, '' ) ) ) );
	}
}
