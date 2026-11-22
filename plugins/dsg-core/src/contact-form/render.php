<?php
/**
 * Contact Form: the frontend markup.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * A real form that posts to admin-post.php, so it works without JS
 * (FallbackHandler). With JS, view.js sends it to the REST endpoint
 * instead and updates the page in place.
 *
 * The data-wp-* attributes are Interactivity API directives. WordPress
 * also runs them here on the server, so the first HTML already shows the
 * right errors and status, and the script picks up from the same state.
 *
 * @package DSG\Core
 */

use DSG\Core\Contact\ContactEndpoint;
use DSG\Core\Contact\ContactForm;
use DSG\Core\Contact\FallbackHandler;

$button_label    = '' !== ( $attributes['buttonLabel'] ?? '' ) ? $attributes['buttonLabel'] : __( 'Send message', 'dsg-core' );
$success_message = '' !== ( $attributes['successMessage'] ?? '' ) ? $attributes['successMessage'] : ContactEndpoint::success_message();

// Settings shared by every contact form on the page.
wp_interactivity_state(
	'dsg/contact-form',
	array(
		'endpoint' => rest_url( ContactEndpoint::NAMESPACE . ContactEndpoint::ROUTE ),
		'messages' => ContactForm::messages(),
	)
);

// The result of a no-JS post, when the page was loaded right after one.
$fallback     = FallbackHandler::current_result();
$field_errors = array_merge(
	array(
		'name'    => '',
		'email'   => '',
		'message' => '',
	),
	$fallback['fields'] ?? array()
);
$values       = $fallback['values'] ?? array();

$status_text = '';
if ( 'sent' === ( $fallback['status'] ?? '' ) ) {
	$status_text = $success_message;
} elseif ( 'error' === ( $fallback['status'] ?? '' ) ) {
	$status_text = $fallback['message'];
}

// This form's own state.
$form_context = array(
	'isSent'         => 'sent' === ( $fallback['status'] ?? '' ),
	'isError'        => 'error' === ( $fallback['status'] ?? '' ),
	'statusText'     => $status_text,
	'successMessage' => $success_message,
	'errors'         => $field_errors,
	'invalid'        => array_map( 'boolval', $field_errors ),
	'isSending'      => false,
);

// IDs connect labels and error messages to their fields.
$form_id = wp_unique_id( 'dsg-contact-' );

$fields = array(
	'name'    => array(
		'label'        => __( 'Name', 'dsg-core' ),
		'type'         => 'text',
		'autocomplete' => 'name',
	),
	'email'   => array(
		'label'        => __( 'Email address', 'dsg-core' ),
		'type'         => 'email',
		'autocomplete' => 'email',
	),
	'message' => array(
		'label'        => __( 'Message', 'dsg-core' ),
		'type'         => 'textarea',
		'autocomplete' => 'off',
	),
);

$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<form
		class="wp-block-dsg-contact-form__form"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		method="post"
		data-wp-interactive="dsg/contact-form"
		<?php echo wp_interactivity_data_wp_context( $form_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>
		data-wp-init="callbacks.init"
		data-wp-on--submit="actions.submit"
		data-wp-on--input="actions.clearError"
	>
		<input type="hidden" name="action" value="<?php echo esc_attr( FallbackHandler::ACTION ); ?>">
		<input type="hidden" name="<?php echo esc_attr( ContactForm::TOKEN ); ?>" value="<?php echo esc_attr( ContactForm::token() ); ?>">

		<?php foreach ( $fields as $key => $field ) : ?>
			<?php
			$field_id = $form_id . '-' . $key;
			$error_id = $field_id . '-error';
			$value    = $values[ $key ] ?? '';
			$common   = sprintf(
				'id="%1$s" name="%2$s" required maxlength="%3$d" autocomplete="%4$s" aria-describedby="%5$s" data-wp-bind--aria-invalid="context.invalid.%2$s"',
				esc_attr( $field_id ),
				esc_attr( $key ),
				(int) ContactForm::MAX_LENGTH[ $key ],
				esc_attr( $field['autocomplete'] ),
				esc_attr( $error_id )
			);
			?>
			<div class="wp-block-dsg-contact-form__field">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<?php if ( 'textarea' === $field['type'] ) : ?>
					<textarea rows="4" <?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?>
					<input type="<?php echo esc_attr( $field['type'] ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>>
				<?php endif; ?>
				<p
					id="<?php echo esc_attr( $error_id ); ?>"
					class="wp-block-dsg-contact-form__error"
					data-wp-text="context.errors.<?php echo esc_attr( $key ); ?>"
					data-wp-bind--hidden="!context.errors.<?php echo esc_attr( $key ); ?>"
				></p>
			</div>
		<?php endforeach; ?>

		<?php // Honeypot: hidden from people and screen readers, filled in by bots. ?>
		<div class="wp-block-dsg-contact-form__trap" aria-hidden="true">
			<label for="<?php echo esc_attr( $form_id . '-trap' ); ?>"><?php esc_html_e( 'Leave this empty', 'dsg-core' ); ?></label>
			<input id="<?php echo esc_attr( $form_id . '-trap' ); ?>" type="text" name="<?php echo esc_attr( ContactForm::HONEYPOT ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<div class="wp-block-dsg-contact-form__footer">
			<button
				type="submit"
				class="wp-block-dsg-contact-form__submit wp-element-button"
				data-wp-bind--aria-disabled="context.isSending"
			><?php echo esc_html( $button_label ); ?></button>

			<?php // Read out by screen readers whenever its text changes. ?>
			<p
				class="wp-block-dsg-contact-form__status"
				role="status"
				data-wp-text="context.statusText"
				data-wp-class--is-error="context.isError"
				data-wp-class--is-sent="context.isSent"
			></p>
		</div>
	</form>
</div>
