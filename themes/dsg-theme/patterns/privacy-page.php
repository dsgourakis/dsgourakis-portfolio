<?php
/**
 * Title: Privacy policy
 * Slug: dsg-theme/privacy-page
 * Categories: text
 * Post Types: page
 * Block Types: core/post-content
 * Description: General privacy policy text for the Privacy Policy page (slug "privacy-policy", linked from the footer).
 *
 * Written for how the site works today: self-hosted fonts, no analytics,
 * no tracking cookies, and a contact form whose messages are saved in
 * wp-admin and emailed. Update it if any of that changes (e.g. analytics).
 * Once inserted, everything is normal blocks, editable in the editor.
 *
 * @package DSG\Theme
 */

$dsg_theme_email = '<a href="mailto:hello@dsgourakis.com">hello@dsgourakis.com</a>';

$dsg_theme_sections = array(
	array(
		__( 'Who is responsible', 'dsg-theme' ),
		array(
			sprintf(
				/* translators: %s: email link. */
				__( 'This website is run by Dimitris Sgourakis, who is responsible for the personal data described here. For any question about this policy or your data, email %s.', 'dsg-theme' ),
				$dsg_theme_email
			),
		),
	),
	array(
		__( 'What I collect', 'dsg-theme' ),
		array(
			__( '<strong>The contact form.</strong> When you send a message, I receive your name, your email address and the message itself. I use them only to read and answer your message, and to talk with you about any work that follows from it.', 'dsg-theme' ),
			__( '<strong>Spam protection.</strong> To limit how many messages can be sent in a short time, the site keeps a scrambled (hashed) version of your IP address for one hour. Your actual IP address is not stored, and the hashed value is deleted automatically.', 'dsg-theme' ),
			__( '<strong>Server logs.</strong> Like almost every website, the server and the network in front of it record technical details of each request, such as IP address, browser type and the page requested. These logs are used only to keep the site running and secure, and are deleted on a rolling basis.', 'dsg-theme' ),
		),
	),
	array(
		__( 'Cookies and tracking', 'dsg-theme' ),
		array(
			__( 'This site does not use advertising or tracking cookies, and it does not use analytics. Fonts are served from this site, not from a third party.', 'dsg-theme' ),
			__( 'If you switch between the light and dark theme, your choice is saved in your own browser (local storage) so the site remembers it next time. It never leaves your device, and you can clear it at any time through your browser settings.', 'dsg-theme' ),
		),
	),
	array(
		__( 'Legal basis', 'dsg-theme' ),
		array(
			__( 'Under the EU General Data Protection Regulation (GDPR), I process contact form messages to take steps you have asked for before a possible contract, or for my legitimate interest in answering enquiries. Spam protection and server logs are based on my legitimate interest in keeping the site secure.', 'dsg-theme' ),
		),
	),
	array(
		__( 'Who else handles your data', 'dsg-theme' ),
		array(
			__( 'I do not sell or share your data. A few service providers handle it on my behalf, only as needed to run the site: the web hosting company that stores the site and its messages, Cloudflare, which delivers the site and protects it from attacks, and the email service that delivers contact form messages to my inbox. Some of these providers may process data outside the European Economic Area, under the safeguards the GDPR requires, such as Standard Contractual Clauses.', 'dsg-theme' ),
		),
	),
	array(
		__( 'How long I keep it', 'dsg-theme' ),
		array(
			__( 'Contact form messages are kept for as long as needed to deal with your enquiry and any work that follows, and deleted after at most two years without contact. If we work together, related records may be kept longer where the law requires it, for example for invoicing.', 'dsg-theme' ),
		),
	),
	array(
		__( 'Your rights', 'dsg-theme' ),
		array(
			sprintf(
				/* translators: %s: email link. */
				__( 'You can ask to see the personal data I hold about you, to correct it, to delete it, to restrict or object to how it is used, or to receive a copy of it. Email %s and I will reply within one month. You also have the right to complain to your local data protection authority.', 'dsg-theme' ),
				$dsg_theme_email
			),
		),
	),
	array(
		__( 'Changes to this policy', 'dsg-theme' ),
		array(
			__( 'If the way this site handles data changes, I will update this page. The date at the top shows when it was last changed.', 'dsg-theme' ),
		),
	),
);

$dsg_theme_allowed = array(
	'a'      => array( 'href' => array() ),
	'strong' => array(),
);
?>
<!-- wp:group {"className":"privacy-policy","layout":{"type":"constrained"}} -->
<div class="wp-block-group privacy-policy">
	<!-- wp:paragraph {"textColor":"muted"} -->
	<p class="has-muted-color has-text-color"><?php esc_html_e( 'Last updated: November 2026', 'dsg-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'This page explains what personal data this website collects, why, and what you can do about it. The short version: the only personal data I collect is what you choose to send through the contact form, and there is no tracking.', 'dsg-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<?php foreach ( $dsg_theme_sections as $dsg_theme_section ) : ?>

	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php echo esc_html( $dsg_theme_section[0] ); ?></h2>
	<!-- /wp:heading -->
		<?php foreach ( $dsg_theme_section[1] as $dsg_theme_paragraph ) : ?>

	<!-- wp:paragraph -->
	<p><?php echo wp_kses( $dsg_theme_paragraph, $dsg_theme_allowed ); ?></p>
	<!-- /wp:paragraph -->
		<?php endforeach; ?>
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
