<?php
/**
 * Saved contact form messages.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Contact;

use WP_Post;

/**
 * A private "Message" post type: a copy of every message sent through the
 * contact form, readable in wp-admin under Messages.
 *
 * - Messages can't be created in wp-admin, only by the form.
 * - Not public and not in the REST API, so they can't be read from outside.
 * - The message is the post content; the sender's name and email are meta.
 */
class Messages {

	public const POST_TYPE      = 'dsg_message';
	public const META_NAME      = '_dsg_name';
	public const META_EMAIL     = '_dsg_email';
	public const META_MAIL_SENT = '_dsg_mail_sent';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Register the Message post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Messages', 'dsg-core' ),
					'singular_name' => __( 'Message', 'dsg-core' ),
					'edit_item'     => __( 'Message', 'dsg-core' ),
					'all_items'     => __( 'All Messages', 'dsg-core' ),
					'not_found'     => __( 'No messages yet.', 'dsg-core' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => false,
				'menu_icon'    => 'dashicons-email-alt',
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap' => true,
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}

	/**
	 * Save a message.
	 *
	 * @param array $fields Clean fields: name, email, message.
	 * @return int The new post's ID, or 0 if it couldn't be saved.
	 */
	public static function save( array $fields ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'private',
				/* translators: %s: the sender's name. */
				'post_title'   => sprintf( __( 'From %s', 'dsg-core' ), $fields['name'] ),
				'post_content' => $fields['message'],
				'meta_input'   => array(
					self::META_NAME  => $fields['name'],
					self::META_EMAIL => $fields['email'],
				),
			),
			true
		);

		return is_wp_error( $post_id ) ? 0 : $post_id;
	}

	/**
	 * Show who sent the message above the editor, with a reply link.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'dsg-message-sender',
			__( 'Sender', 'dsg-core' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * The Sender box.
	 *
	 * @param WP_Post $post The message.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		$email = (string) get_post_meta( $post->ID, self::META_EMAIL, true );
		$sent  = get_post_meta( $post->ID, self::META_MAIL_SENT, true );

		printf( '<p><strong>%s</strong><br>', esc_html( (string) get_post_meta( $post->ID, self::META_NAME, true ) ) );
		printf( '<a href="mailto:%1$s">%2$s</a></p>', esc_attr( $email ), esc_html( $email ) );

		if ( '0' === $sent ) {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'The email notification for this message failed to send.', 'dsg-core' )
			);
		}
	}

	/**
	 * Add an Email column to the list.
	 *
	 * @param array $columns Column names, keyed by ID.
	 * @return array
	 */
	public function columns( array $columns ): array {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		$columns['dsg_email'] = __( 'Email', 'dsg-core' );
		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Fill the Email column.
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id The message.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( 'dsg_email' !== $column ) {
			return;
		}

		$email = (string) get_post_meta( $post_id, self::META_EMAIL, true );
		printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
	}
}
