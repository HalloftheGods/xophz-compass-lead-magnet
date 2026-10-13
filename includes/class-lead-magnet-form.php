<?php
/**
 * The [lead_magnet_capture] shortcode: an accessible, unstyled capture form with a separate,
 * unchecked consent checkbox. Usage: [lead_magnet_capture offer="annuity-checklist"].
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Form {

	const SHORTCODE    = 'lead_magnet_capture';
	const NONCE_ACTION = 'xophz_lead_magnet_capture';
	const NONCE_FIELD  = 'lm_nonce';
	const HONEYPOT     = 'website';
	const SCRIPT       = 'xophz-lead-magnet-capture';

	/** The 50 states and DC, by USPS code: annuities are sold under a state license. */
	const STATES = array(
		'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
		'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia',
		'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois',
		'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
		'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
		'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
		'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
		'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon',
		'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota',
		'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia',
		'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
	);

	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register(): void {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
	}

	/** The offer slug from the shortcode or a submission: lowercase key, at most 64 characters. */
	public static function sanitize_offer( $value ): string {
		return substr( sanitize_key( (string) $value ), 0, 64 );
	}

	/**
	 * Render the form. Without a seller name and privacy URL there is no consent to offer, so admins
	 * see what to set and visitors see that the form is unavailable.
	 *
	 * @param array|string $atts Shortcode attributes.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'offer'  => '',
				'button' => __( 'Submit', 'xophz-compass-lead-magnet' ),
			),
			$atts,
			self::SHORTCODE
		);

		if ( ! Lead_Magnet_Consent::is_configured() ) {
			$message = current_user_can( 'manage_options' )
				? __( 'Lead Magnet: set the seller name and privacy policy URL under Settings > Lead Magnet. The form stays hidden until both are set.', 'xophz-compass-lead-magnet' )
				: __( 'This form is not available right now.', 'xophz-compass-lead-magnet' );
			return '<p class="lead-magnet-capture__unavailable">' . esc_html( $message ) . '</p>';
		}

		wp_enqueue_script(
			self::SCRIPT,
			plugins_url( 'public/js/xophz-compass-lead-magnet-capture.js', dirname( __FILE__ ) ),
			array(),
			defined( 'XOPHZ_COMPASS_LEAD_MAGNET_VERSION' ) ? XOPHZ_COMPASS_LEAD_MAGNET_VERSION : '1.0.0',
			true
		);

		$id = wp_unique_id( 'lead-magnet-capture-' );
		ob_start();
		include dirname( __DIR__ ) . '/public/partials/lead-magnet-capture-form.php';
		return (string) ob_get_clean();
	}
}
