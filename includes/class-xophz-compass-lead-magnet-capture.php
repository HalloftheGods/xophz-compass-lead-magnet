<?php
/**
 * Annuity capture flow: wires the Lead post type, the [lead_magnet_capture] shortcode, the admin
 * screens, and the public POST lead-magnet/v1/capture route that validates and stores each lead.
 *
 * After a lead is stored it fires do_action( 'xophz_lead_magnet_lead_captured', $lead ) once, with
 * the record from Lead_Magnet_Leads::get(). Other plugins (the Fresh Mints HighLevel relay, #6024)
 * subscribe to that action; this plugin never calls them directly.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Xophz_Compass_Lead_Magnet_Capture {

	const REST_NAMESPACE = 'lead-magnet/v1';
	const REST_ROUTE     = '/capture';
	const ACTION         = 'xophz_lead_magnet_lead_captured';

	/** Attribution read from the landing URL's query (contract shared with #6029). */
	const ATTRIBUTION_KEYS = array( 'utm_source', 'utm_medium', 'utm_campaign', 'fm_thread', 'fm_reply' );

	public function init(): void {
		( new Lead_Magnet_Leads() )->init();
		( new Lead_Magnet_Form() )->init();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		if ( is_admin() ) {
			( new Lead_Magnet_Settings() )->init();
			( new Lead_Magnet_Leads_Admin() )->init();
		}
	}

	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);
	}

	/** A public form route: anyone may post, but only with the nonce the form rendered. */
	public function check_nonce( WP_REST_Request $request ) {
		$nonce = (string) $request->get_param( Lead_Magnet_Form::NONCE_FIELD );
		if ( '' !== $nonce && false !== wp_verify_nonce( $nonce, Lead_Magnet_Form::NONCE_ACTION ) ) {
			return true;
		}
		return new WP_Error(
			'lead_magnet_invalid_nonce',
			__( 'This form has expired. Reload the page and try again.', 'xophz-compass-lead-magnet' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Order: configured, per-IP limit (every post counts), honeypot (looks like success, stores
	 * nothing), validation, consent wording unchanged, per-email limit, store, then the action.
	 */
	public function submit( WP_REST_Request $request ): WP_REST_Response {
		if ( ! Lead_Magnet_Consent::is_configured() ) {
			return self::fail( 'not_configured', __( 'This form is not available right now.', 'xophz-compass-lead-magnet' ), 503 );
		}

		$ip = self::client_ip();
		if ( Lead_Magnet_Rate_Limit::hit( 'ip', '' === $ip ? 'unknown' : $ip ) ) {
			return self::fail( 'rate_limited', __( 'Too many tries. Please wait a while and try again.', 'xophz-compass-lead-magnet' ), 429 );
		}

		if ( '' !== trim( (string) $request->get_param( Lead_Magnet_Form::HONEYPOT ) ) ) {
			return self::thanks();
		}

		list( $lead, $errors ) = self::validate( $request );
		if ( ! empty( $errors ) ) {
			return self::fail( 'invalid_fields', __( 'Please fix the fields marked below.', 'xophz-compass-lead-magnet' ), 400, $errors );
		}

		if ( ! hash_equals( Lead_Magnet_Consent::version(), (string) $request->get_param( 'consent_version' ) ) ) {
			return self::fail( 'consent_changed', __( 'The consent wording on this page has changed. Reload the page, read it, and submit again.', 'xophz-compass-lead-magnet' ), 409 );
		}

		if ( Lead_Magnet_Rate_Limit::hit( 'email', $lead['email'] ) ) {
			return self::fail( 'rate_limited', __( 'Too many tries. Please wait a while and try again.', 'xophz-compass-lead-magnet' ), 429 );
		}

		$page_url = self::page_url( $request );
		$record   = array_merge(
			$lead,
			array(
				'consent_text'    => Lead_Magnet_Consent::text(),
				'consent_version' => Lead_Magnet_Consent::version(),
				'seller'          => Lead_Magnet_Consent::seller_name(),
				'privacy_url'     => Lead_Magnet_Consent::privacy_url(),
				'captured_at'     => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'ip'              => $ip,
				'user_agent'      => self::user_agent( $request ),
				'page_url'        => $page_url,
			),
			self::attribution( $page_url )
		);

		$stored = Lead_Magnet_Leads::store( $record );
		if ( null === $stored ) {
			return self::fail( 'store_failed', __( 'Something went wrong. Please try again.', 'xophz-compass-lead-magnet' ), 500 );
		}

		do_action( self::ACTION, $stored );
		return self::thanks();
	}

	/**
	 * Sanitize and check the visitor's fields. Phone is optional; when given it must be a US number
	 * and the consent box must be checked (calls and texts need prior express written consent).
	 * Email-only leads may leave consent unchecked and are marked for email contact only.
	 *
	 * @return array{0: array<string, mixed>, 1: array<string, string>} The lead fields and any errors by field.
	 */
	public static function validate( WP_REST_Request $request ): array {
		$errors = array();

		$name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( '' === $name || mb_strlen( $name ) > 100 ) {
			$errors['name'] = __( 'Enter your name (up to 100 characters).', 'xophz-compass-lead-magnet' );
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( '' === $email || strlen( $email ) > 254 || ! is_email( $email ) ) {
			$errors['email'] = __( 'Enter a valid email address.', 'xophz-compass-lead-magnet' );
		}

		$phone = self::normalize_phone( (string) $request->get_param( 'phone' ) );
		if ( null === $phone ) {
			$errors['phone'] = __( 'Enter a 10-digit US phone number, or leave it blank.', 'xophz-compass-lead-magnet' );
		}

		$state = strtoupper( sanitize_text_field( (string) $request->get_param( 'state' ) ) );
		if ( ! isset( Lead_Magnet_Form::STATES[ $state ] ) ) {
			$errors['state'] = __( 'Select your state.', 'xophz-compass-lead-magnet' );
		}

		$question = trim( sanitize_textarea_field( (string) $request->get_param( 'question' ) ) );
		if ( mb_strlen( $question ) > 1000 ) {
			$errors['question'] = __( 'Keep your question under 1,000 characters.', 'xophz-compass-lead-magnet' );
		}

		$consent_raw = $request->get_param( 'consent' );
		$consent     = true === $consent_raw || ( is_scalar( $consent_raw ) && in_array( strtolower( (string) $consent_raw ), array( '1', 'true', 'on' ), true ) );
		if ( is_string( $phone ) && '' !== $phone && ! $consent ) {
			$errors['consent'] = __( 'To be called or texted, check the consent box. To be contacted by email only, clear the phone number.', 'xophz-compass-lead-magnet' );
		}

		$channels = array( 'email' );
		if ( $consent && is_string( $phone ) && '' !== $phone ) {
			$channels = array( 'email', 'phone', 'sms' );
		}

		$lead = array(
			'name'             => $name,
			'email'            => $email,
			'phone'            => (string) $phone,
			'state'            => $state,
			'question'         => $question,
			'offer'            => Lead_Magnet_Form::sanitize_offer( $request->get_param( 'offer' ) ),
			'consent_given'    => $consent,
			'contact_channels' => $channels,
		);
		return array( $lead, $errors );
	}

	/** "" for a blank phone, +1 and ten digits for a US number, null for anything else. */
	public static function normalize_phone( string $raw ): ?string {
		$digits = preg_replace( '/\D+/', '', $raw );
		if ( '' === trim( $raw ) ) {
			return '';
		}
		if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
			$digits = substr( $digits, 1 );
		}
		return ( 10 === strlen( $digits ) && preg_match( '/^[2-9]\d{2}[2-9]\d{6}$/', $digits ) ) ? '+1' . $digits : null;
	}

	/** The page the form was posted from, kept only when it is on this site; "" otherwise. */
	private static function page_url( WP_REST_Request $request ): string {
		$url = esc_url_raw( substr( (string) $request->get_param( 'page_url' ), 0, 2000 ), array( 'http', 'https' ) );
		$own = wp_parse_url( home_url(), PHP_URL_HOST );
		return ( '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) === $own ) ? $url : '';
	}

	/** utm_source, utm_medium, utm_campaign, fm_thread and fm_reply from the landing URL's query. */
	public static function attribution( string $page_url ): array {
		$query = array();
		wp_parse_str( (string) wp_parse_url( $page_url, PHP_URL_QUERY ), $query );

		$out = array();
		foreach ( self::ATTRIBUTION_KEYS as $key ) {
			$value       = isset( $query[ $key ] ) && is_string( $query[ $key ] ) ? $query[ $key ] : '';
			$out[ $key ] = 'fm_thread' === $key
				? esc_url_raw( substr( $value, 0, 2000 ), array( 'http', 'https' ) )
				: substr( sanitize_text_field( $value ), 0, 200 );
		}
		return $out;
	}

	/** The connecting address as the web server saw it (REMOTE_ADDR); "" when it is not an IP. */
	private static function client_ip(): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return false !== filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '';
	}

	private static function user_agent( WP_REST_Request $request ): string {
		$agent = (string) $request->get_header( 'user_agent' );
		if ( '' === $agent && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$agent = wp_unslash( $_SERVER['HTTP_USER_AGENT'] );
		}
		return substr( sanitize_text_field( $agent ), 0, 500 );
	}

	/** The same 201 body for a stored lead and a honeypot hit, so a bot cannot tell them apart. */
	private static function thanks(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => __( 'Thank you. We received your request.', 'xophz-compass-lead-magnet' ),
			),
			201
		);
	}

	private static function fail( string $code, string $message, int $status, array $errors = array() ): WP_REST_Response {
		$body = array(
			'ok'      => false,
			'code'    => $code,
			'message' => $message,
		);
		if ( ! empty( $errors ) ) {
			$body['errors'] = $errors;
		}
		return new WP_REST_Response( $body, $status );
	}
}
