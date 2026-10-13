<?php
/**
 * Markup for [lead_magnet_capture], included by Lead_Magnet_Form::render() with $id (a unique element
 * id prefix) and $atts (offer, button) in scope. Unstyled on purpose: themes style the
 * lead-magnet-capture__* classes. The only inline style hides the honeypot from people.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/public/partials
 *
 * @var string               $id
 * @var array<string, mixed> $atts
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$lm_endpoint   = rest_url( Xophz_Compass_Lead_Magnet_Capture::REST_NAMESPACE . Xophz_Compass_Lead_Magnet_Capture::REST_ROUTE );
$lm_rest_nonce = is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '';
?>
<form id="<?php echo esc_attr( $id ); ?>" class="lead-magnet-capture" method="post" data-lead-magnet-capture data-endpoint="<?php echo esc_url( $lm_endpoint ); ?>"<?php echo '' !== $lm_rest_nonce ? ' data-rest-nonce="' . esc_attr( $lm_rest_nonce ) . '"' : ''; ?>>
	<input type="hidden" name="<?php echo esc_attr( Lead_Magnet_Form::NONCE_FIELD ); ?>" value="<?php echo esc_attr( wp_create_nonce( Lead_Magnet_Form::NONCE_ACTION ) ); ?>">
	<input type="hidden" name="offer" value="<?php echo esc_attr( Lead_Magnet_Form::sanitize_offer( $atts['offer'] ) ); ?>">
	<input type="hidden" name="consent_version" value="<?php echo esc_attr( Lead_Magnet_Consent::version() ); ?>">

	<div class="lead-magnet-capture__fields" data-lm-fields>
		<p class="lead-magnet-capture__note"><?php esc_html_e( 'Fields marked * are required.', 'xophz-compass-lead-magnet' ); ?></p>

		<p class="lead-magnet-capture__field">
			<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Full name', 'xophz-compass-lead-magnet' ); ?> <span aria-hidden="true">*</span></label>
			<input id="<?php echo esc_attr( $id ); ?>-name" name="name" type="text" autocomplete="name" maxlength="100" required aria-describedby="<?php echo esc_attr( $id ); ?>-name-error">
			<span id="<?php echo esc_attr( $id ); ?>-name-error" class="lead-magnet-capture__error" data-lm-error="name"></span>
		</p>

		<p class="lead-magnet-capture__field">
			<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'xophz-compass-lead-magnet' ); ?> <span aria-hidden="true">*</span></label>
			<input id="<?php echo esc_attr( $id ); ?>-email" name="email" type="email" autocomplete="email" maxlength="254" required aria-describedby="<?php echo esc_attr( $id ); ?>-email-error">
			<span id="<?php echo esc_attr( $id ); ?>-email-error" class="lead-magnet-capture__error" data-lm-error="email"></span>
		</p>

		<p class="lead-magnet-capture__field">
			<label for="<?php echo esc_attr( $id ); ?>-phone"><?php esc_html_e( 'Phone', 'xophz-compass-lead-magnet' ); ?></label>
			<input id="<?php echo esc_attr( $id ); ?>-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="20" aria-describedby="<?php echo esc_attr( $id ); ?>-phone-hint <?php echo esc_attr( $id ); ?>-phone-error">
			<span id="<?php echo esc_attr( $id ); ?>-phone-hint" class="lead-magnet-capture__hint"><?php esc_html_e( 'Optional. Leave it blank to be contacted by email only.', 'xophz-compass-lead-magnet' ); ?></span>
			<span id="<?php echo esc_attr( $id ); ?>-phone-error" class="lead-magnet-capture__error" data-lm-error="phone"></span>
		</p>

		<p class="lead-magnet-capture__field">
			<label for="<?php echo esc_attr( $id ); ?>-state"><?php esc_html_e( 'State', 'xophz-compass-lead-magnet' ); ?> <span aria-hidden="true">*</span></label>
			<select id="<?php echo esc_attr( $id ); ?>-state" name="state" autocomplete="address-level1" required aria-describedby="<?php echo esc_attr( $id ); ?>-state-error">
				<option value=""><?php esc_html_e( 'Select your state', 'xophz-compass-lead-magnet' ); ?></option>
				<?php foreach ( Lead_Magnet_Form::STATES as $lm_code => $lm_state ) : ?>
					<option value="<?php echo esc_attr( $lm_code ); ?>"><?php echo esc_html( $lm_state ); ?></option>
				<?php endforeach; ?>
			</select>
			<span id="<?php echo esc_attr( $id ); ?>-state-error" class="lead-magnet-capture__error" data-lm-error="state"></span>
		</p>

		<p class="lead-magnet-capture__field">
			<label for="<?php echo esc_attr( $id ); ?>-question"><?php esc_html_e( "What's your question?", 'xophz-compass-lead-magnet' ); ?></label>
			<textarea id="<?php echo esc_attr( $id ); ?>-question" name="question" rows="4" maxlength="1000" aria-describedby="<?php echo esc_attr( $id ); ?>-question-hint <?php echo esc_attr( $id ); ?>-question-error"></textarea>
			<span id="<?php echo esc_attr( $id ); ?>-question-hint" class="lead-magnet-capture__hint"><?php esc_html_e( 'Optional.', 'xophz-compass-lead-magnet' ); ?></span>
			<span id="<?php echo esc_attr( $id ); ?>-question-error" class="lead-magnet-capture__error" data-lm-error="question"></span>
		</p>

		<p class="lead-magnet-capture__hp" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;">
			<label for="<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( Lead_Magnet_Form::HONEYPOT ); ?>"><?php esc_html_e( 'Leave this field empty', 'xophz-compass-lead-magnet' ); ?></label>
			<input id="<?php echo esc_attr( $id ); ?>-<?php echo esc_attr( Lead_Magnet_Form::HONEYPOT ); ?>" name="<?php echo esc_attr( Lead_Magnet_Form::HONEYPOT ); ?>" type="text" tabindex="-1" autocomplete="off">
		</p>

		<fieldset class="lead-magnet-capture__consent">
			<legend><?php esc_html_e( 'Consent to be contacted', 'xophz-compass-lead-magnet' ); ?></legend>
			<p class="lead-magnet-capture__field">
				<input id="<?php echo esc_attr( $id ); ?>-consent" name="consent" type="checkbox" value="1" aria-describedby="<?php echo esc_attr( $id ); ?>-consent-hint <?php echo esc_attr( $id ); ?>-consent-error">
				<label for="<?php echo esc_attr( $id ); ?>-consent"><?php echo Lead_Magnet_Consent::html(); // phpcs:ignore WordPress.Security.EscapeOutput -- html() escapes each part. ?></label>
			</p>
			<p id="<?php echo esc_attr( $id ); ?>-consent-hint" class="lead-magnet-capture__hint"><?php esc_html_e( 'Required only if you enter a phone number.', 'xophz-compass-lead-magnet' ); ?></p>
			<p id="<?php echo esc_attr( $id ); ?>-consent-error" class="lead-magnet-capture__error" data-lm-error="consent"></p>
		</fieldset>

		<p class="lead-magnet-capture__actions">
			<button type="submit"><?php echo esc_html( $atts['button'] ); ?></button>
		</p>
	</div>

	<p class="lead-magnet-capture__status" role="status" aria-live="polite" data-lm-status></p>
	<noscript><p><?php esc_html_e( 'This form needs JavaScript to send. Please turn on JavaScript and reload the page.', 'xophz-compass-lead-magnet' ); ?></p></noscript>
</form>
