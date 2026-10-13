<?php
/**
 * Settings > Lead Magnet: the seller named in the consent statement and the privacy policy URL.
 * Plain options, not Connectors: neither is a secret.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Settings {

	const PAGE  = 'xophz-lead-magnet-settings';
	const GROUP = 'xophz_lead_magnet_settings';

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	public function add_page(): void {
		add_options_page(
			__( 'Lead Magnet Settings', 'xophz-compass-lead-magnet' ),
			__( 'Lead Magnet', 'xophz-compass-lead-magnet' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	public function register(): void {
		register_setting(
			self::GROUP,
			Lead_Magnet_Consent::OPTION_SELLER,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_seller' ),
				'default'           => '',
			)
		);
		register_setting(
			self::GROUP,
			Lead_Magnet_Consent::OPTION_PRIVACY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_privacy_url' ),
				'default'           => '',
			)
		);

		add_settings_section( 'consent', __( 'Consent', 'xophz-compass-lead-magnet' ), array( $this, 'render_section' ), self::PAGE );
		add_settings_field(
			Lead_Magnet_Consent::OPTION_SELLER,
			__( 'Seller name', 'xophz-compass-lead-magnet' ),
			array( $this, 'render_seller_field' ),
			self::PAGE,
			'consent',
			array( 'label_for' => Lead_Magnet_Consent::OPTION_SELLER )
		);
		add_settings_field(
			Lead_Magnet_Consent::OPTION_PRIVACY,
			__( 'Privacy policy URL', 'xophz-compass-lead-magnet' ),
			array( $this, 'render_privacy_field' ),
			self::PAGE,
			'consent',
			array( 'label_for' => Lead_Magnet_Consent::OPTION_PRIVACY )
		);
	}

	public function sanitize_seller( $value ): string {
		return substr( trim( sanitize_text_field( (string) $value ) ), 0, 200 );
	}

	public function sanitize_privacy_url( $value ): string {
		return esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
	}

	public function render_section(): void {
		echo '<p>' . esc_html__( 'The capture form names this seller in its consent statement and links this privacy policy. The form stays hidden until both are set. Leave the URL blank to use the site privacy page (Settings > Privacy).', 'xophz-compass-lead-magnet' ) . '</p>';
	}

	public function render_seller_field(): void {
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%1$s" value="%2$s" maxlength="200"><p class="description">%3$s</p>',
			esc_attr( Lead_Magnet_Consent::OPTION_SELLER ),
			esc_attr( (string) get_option( Lead_Magnet_Consent::OPTION_SELLER, '' ) ),
			esc_html__( 'The legal name of the business that will call, text and email leads.', 'xophz-compass-lead-magnet' )
		);
	}

	public function render_privacy_field(): void {
		printf(
			'<input type="url" class="regular-text" id="%1$s" name="%1$s" value="%2$s">',
			esc_attr( Lead_Magnet_Consent::OPTION_PRIVACY ),
			esc_attr( (string) get_option( Lead_Magnet_Consent::OPTION_PRIVACY, '' ) )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$text = Lead_Magnet_Consent::text();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Lead Magnet Settings', 'xophz-compass-lead-magnet' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Consent statement shown on the form', 'xophz-compass-lead-magnet' ); ?></h2>
			<p><strong><?php esc_html_e( 'Pending legal review: have a lawyer approve this wording before the form goes live.', 'xophz-compass-lead-magnet' ); ?></strong></p>
			<?php if ( '' === $text ) : ?>
				<p><?php esc_html_e( 'Set the seller name (and a privacy policy) to see the statement.', 'xophz-compass-lead-magnet' ); ?></p>
			<?php else : ?>
				<blockquote><?php echo Lead_Magnet_Consent::html(); // phpcs:ignore WordPress.Security.EscapeOutput -- html() escapes each part. ?></blockquote>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Using the form', 'xophz-compass-lead-magnet' ); ?></h2>
			<p><code>[lead_magnet_capture offer="annuity-checklist"]</code></p>
			<p><a class="button" href="<?php echo esc_url( Lead_Magnet_Leads_Admin::export_url() ); ?>"><?php esc_html_e( 'Export consent records (CSV)', 'xophz-compass-lead-magnet' ); ?></a></p>
		</div>
		<?php
	}
}
