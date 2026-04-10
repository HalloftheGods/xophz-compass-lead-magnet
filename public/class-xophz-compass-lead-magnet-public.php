<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/public
 * @author     Your Name <email@example.com>
 */
class Xophz_Compass_Lead_Magnet_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Lead_Magnet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Lead_Magnet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/xophz-compass-lead-magnet-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Lead_Magnet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Lead_Magnet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/xophz-compass-lead-magnet-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Capture the corp_id from the URL and save it to a cookie.
	 *
	 * @since    1.0.0
	 */
	public function capture_corp_id() {
		if ( isset( $_GET['corp_id'] ) ) {
			$corp_id = sanitize_text_field( wp_unslash( $_GET['corp_id'] ) );
			// Set cookie for 30 days
			setcookie( 'compass_corp_id', $corp_id, time() + ( DAY_IN_SECONDS * 30 ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Register the shortcode to retrieve the corp_id.
	 *
	 * @since    1.0.0
	 */
	public function register_corp_id_shortcode() {
		add_shortcode( 'compass_corp_id', array( $this, 'render_corp_id_shortcode' ) );
	}

	/**
	 * Render the corp_id shortcode.
	 *
	 * @since    1.0.0
	 */
	public function render_corp_id_shortcode( $atts ) {
		// Priority 1: Use GET param if it's available in the URL immediately
		if ( isset( $_GET['corp_id'] ) ) {
			return sanitize_text_field( wp_unslash( $_GET['corp_id'] ) );
		}

		// Priority 2: Use the saved cookie
		if ( isset( $_COOKIE['compass_corp_id'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['compass_corp_id'] ) );
		}

		return '';
	}

}
