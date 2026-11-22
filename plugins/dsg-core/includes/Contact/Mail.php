<?php
/**
 * Email for the contact form: who gets it, and how it's sent.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Contact;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends the "new message" email, and routes all site mail through SMTP
 * when it's set up.
 *
 * Everything is set with constants in each server's wp-config.php, never in
 * the repo or the database. With none set, WordPress sends mail its usual
 * way (PHP mail()).
 *
 *     define( 'DSG_CONTACT_TO', 'you@example.com' ); // Default: the admin email.
 *     define( 'DSG_MAIL_FROM', 'noreply@dsgourakis.com' );
 *     define( 'DSG_MAIL_FROM_NAME', 'dsgourakis.com' );
 *     define( 'DSG_SMTP_HOST', 'smtp.example.com' );
 *     define( 'DSG_SMTP_PORT', 587 );
 *     define( 'DSG_SMTP_SECURE', 'tls' );            // 'tls' (587) or 'ssl' (465).
 *     define( 'DSG_SMTP_USER', '...' );
 *     define( 'DSG_SMTP_PASS', '...' );
 *
 * Changing mail provider means changing these, not the code.
 */
class Mail {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'phpmailer_init', array( $this, 'use_smtp' ) );
		add_filter( 'wp_mail_from', array( $this, 'from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
	}

	/**
	 * Email a new message to the site owner.
	 *
	 * Sent from the site's own address, with Reply-To set to the visitor, so
	 * pressing Reply answers them. Sending "from" the visitor's address
	 * would fail the domain checks (SPF/DKIM) and land in spam.
	 *
	 * @param array $fields Clean fields: name, email, message.
	 * @return bool Whether the email was handed over for sending.
	 */
	public static function send_notification( array $fields ): bool {
		$subject = sprintf(
			/* translators: 1: the sender's name, 2: the site's name. */
			__( 'New message from %1$s via %2$s', 'dsg-core' ),
			$fields['name'],
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$body = sprintf(
			"%s\n\n---\n%s <%s>\n%s",
			$fields['message'],
			$fields['name'],
			$fields['email'],
			admin_url( 'edit.php?post_type=' . Messages::POST_TYPE )
		);

		// Strip line breaks from the name, so it can't add extra headers.
		$reply_to = sprintf( 'Reply-To: %s <%s>', str_replace( array( "\r", "\n" ), '', $fields['name'] ), $fields['email'] );

		return wp_mail( self::recipient(), $subject, $body, array( $reply_to ) );
	}

	/**
	 * Who receives the messages.
	 *
	 * @return string
	 */
	public static function recipient(): string {
		$to = defined( 'DSG_CONTACT_TO' ) ? (string) DSG_CONTACT_TO : '';

		return is_email( $to ) ? $to : (string) get_option( 'admin_email' );
	}

	/**
	 * Send through SMTP when DSG_SMTP_HOST is set.
	 *
	 * @param PHPMailer $phpmailer The mailer WordPress is about to use.
	 * @return void
	 */
	public function use_smtp( $phpmailer ): void {
		if ( ! defined( 'DSG_SMTP_HOST' ) || ! DSG_SMTP_HOST ) {
			return;
		}

		// PHPMailer's own property names, which aren't in snake_case.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->isSMTP();
		$phpmailer->Host       = (string) DSG_SMTP_HOST;
		$phpmailer->Port       = defined( 'DSG_SMTP_PORT' ) ? (int) DSG_SMTP_PORT : 587;
		$phpmailer->SMTPSecure = defined( 'DSG_SMTP_SECURE' ) ? (string) DSG_SMTP_SECURE : 'tls';

		if ( defined( 'DSG_SMTP_USER' ) && DSG_SMTP_USER ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = (string) DSG_SMTP_USER;
			$phpmailer->Password = defined( 'DSG_SMTP_PASS' ) ? (string) DSG_SMTP_PASS : '';
		}
		// phpcs:enable
	}

	/**
	 * The From address, when DSG_MAIL_FROM is set.
	 *
	 * @param string $from WordPress's default (wordpress@your-domain).
	 * @return string
	 */
	public function from_address( string $from ): string {
		return defined( 'DSG_MAIL_FROM' ) && is_email( DSG_MAIL_FROM ) ? (string) DSG_MAIL_FROM : $from;
	}

	/**
	 * The From name, when DSG_MAIL_FROM_NAME is set.
	 *
	 * @param string $name WordPress's default ("WordPress").
	 * @return string
	 */
	public function from_name( string $name ): string {
		return defined( 'DSG_MAIL_FROM_NAME' ) && DSG_MAIL_FROM_NAME ? (string) DSG_MAIL_FROM_NAME : $name;
	}
}
