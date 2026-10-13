<?php
/**
 * Seller settings and the consent statement shown under the capture form.
 *
 * LEGAL REVIEW PENDING (task #6038): the wording in parts() is a working draft of a TCPA prior express
 * written consent statement. A lawyer must approve it before the form goes live. The form and the REST
 * route both build the statement here, and every lead stores the exact text it was shown, so changing
 * the wording only affects leads captured after the change.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Consent {

	const OPTION_SELLER  = 'xophz_lead_magnet_seller_name';
	const OPTION_PRIVACY = 'xophz_lead_magnet_privacy_url';
	const LINK_TEXT      = 'Privacy Policy';

	/** The seller named in the consent statement (Settings > Lead Magnet). Empty until an admin sets it. */
	public static function seller_name(): string {
		return trim( sanitize_text_field( (string) get_option( self::OPTION_SELLER, '' ) ) );
	}

	/** The privacy policy URL setting, else the site's WordPress privacy page, else "". */
	public static function privacy_url(): string {
		$url = esc_url_raw( (string) get_option( self::OPTION_PRIVACY, '' ) );
		if ( '' === $url ) {
			$url = esc_url_raw( (string) get_privacy_policy_url() );
		}
		return $url;
	}

	/** The form only offers consent once it can name the seller and link a privacy policy. */
	public static function is_configured(): bool {
		return '' !== self::seller_name() && '' !== self::privacy_url();
	}

	/** The statement as plain text, exactly as a visitor reads it ("Privacy Policy" is the link). "" when not configured. */
	public static function text(): string {
		$parts = self::parts();
		return null === $parts ? '' : $parts[0] . self::LINK_TEXT . $parts[1];
	}

	/** The statement as escaped HTML with the privacy policy link in place. "" when not configured. */
	public static function html(): string {
		$parts = self::parts();
		if ( null === $parts ) {
			return '';
		}
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( self::privacy_url() ),
			esc_html( self::LINK_TEXT )
		);
		return esc_html( $parts[0] ) . $link . esc_html( $parts[1] );
	}

	/**
	 * Fingerprint of the statement and its link target. The form posts the version it showed; the REST
	 * route refuses a submission whose version no longer matches, so a stored record never claims a
	 * wording the visitor did not see.
	 */
	public static function version(): string {
		$text = self::text();
		return '' === $text ? '' : hash( 'sha256', $text . "\n" . self::privacy_url() );
	}

	/**
	 * The text before and after the privacy link, or null when the seller or privacy URL is missing.
	 * Not translated on purpose: the stored record must hold the exact approved wording.
	 *
	 * @return array{0: string, 1: string}|null
	 */
	private static function parts(): ?array {
		if ( ! self::is_configured() ) {
			return null;
		}
		$before = sprintf(
			'By checking this box, I give my prior express written consent for %s to contact me about annuities and related insurance products at the phone number and email address I entered above, including by calls and text messages made with an automatic telephone dialing system or an artificial or prerecorded voice, and by email. I understand that my consent is not a condition of any purchase. Message and data rates may apply. Message frequency varies. Reply STOP to stop texts, or use the unsubscribe link in any email. See our ',
			self::seller_name()
		);
		return array( $before, '.' );
	}
}
