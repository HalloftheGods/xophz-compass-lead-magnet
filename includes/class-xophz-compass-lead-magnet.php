<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://youmeos.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 * @author     Your Name
 */
if ( ! class_exists( 'Xophz_Compass_Plugin_Base' ) ) {
	$core_plugin_base = dirname( dirname( __DIR__ ) ) . '/xophz-compass/includes/core/class-compass-plugin-base.php';
	if ( file_exists( $core_plugin_base ) ) {
		require_once $core_plugin_base;
	}
}

class Xophz_Compass_Lead_Magnet extends Xophz_Compass_Plugin_Base {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Xophz_Compass_Lead_Magnet_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected string $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( ?string $param1 = null, ?string $version = null, string $param3 = '' ) {
		if ( null === $param1 ) {
			$file = dirname( __DIR__ ) . '/xophz-compass-lead-magnet.php';
			$ver  = defined( 'XOPHZ_COMPASS_LEAD_MAGNET_VERSION' ) ? XOPHZ_COMPASS_LEAD_MAGNET_VERSION : '1.0.0';
			parent::__construct( $file, $ver, 'xophz-compass-lead-magnet' );
		} else {
			parent::__construct( $param1, $version ?? '1.0.0', $param3 );
		}
		$this->plugin_name = $this->text_domain;
		$this->loader = $this;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_capture_hooks();
		$this->define_pattern_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Xophz_Compass_Lead_Magnet_Loader. Orchestrates the hooks of the plugin.
	 * - Xophz_Compass_Lead_Magnet_i18n. Defines internationalization functionality.
	 * - Xophz_Compass_Lead_Magnet_Admin. Defines all hooks for the admin area.
	 * - Xophz_Compass_Lead_Magnet_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-xophz-compass-lead-magnet-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-xophz-compass-lead-magnet-public.php';

		/**
		 * The annuity capture flow: Lead post type, [lead_magnet_capture] shortcode, REST route,
		 * settings and lead admin.
		 */
		$dir = plugin_dir_path( dirname( __FILE__ ) );
		require_once $dir . 'includes/class-lead-magnet-consent.php';
		require_once $dir . 'includes/class-lead-magnet-rate-limit.php';
		require_once $dir . 'includes/class-lead-magnet-leads.php';
		require_once $dir . 'includes/class-lead-magnet-form.php';
		require_once $dir . 'includes/class-xophz-compass-lead-magnet-capture.php';
		require_once $dir . 'admin/class-lead-magnet-settings.php';
		require_once $dir . 'admin/class-lead-magnet-leads-admin.php';

		/**
		 * The annuity educator landing page block pattern.
		 */
		require_once $dir . 'includes/class-lead-magnet-educator-pattern.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Xophz_Compass_Lead_Magnet_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		// Localization handled by Xophz_Compass_Plugin_Base on init priority 5
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Xophz_Compass_Lead_Magnet_Admin( $this->get_xophz_compass_lead_magnet(), $this->get_version() );

		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'addToMenu' );
		$this->loader->add_action( 'wp_ajax_get_hustle_modules', $plugin_admin, 'get_hustle_modules' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Xophz_Compass_Lead_Magnet_Public( $this->get_xophz_compass_lead_magnet(), $this->get_version() );

		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'init', $plugin_public, 'capture_corp_id' );
		$this->loader->add_action( 'init', $plugin_public, 'register_corp_id_shortcode' );
	}

	/**
	 * Register the capture flow. It fires xophz_lead_magnet_lead_captured for every stored lead.
	 *
	 * @access   private
	 */
	private function define_capture_hooks() {
		( new Xophz_Compass_Lead_Magnet_Capture() )->init();
	}

	/**
	 * Register the "Lead Magnet" block pattern category and the annuity educator landing page pattern.
	 *
	 * @access   private
	 */
	private function define_pattern_hooks() {
		( new Lead_Magnet_Educator_Pattern() )->init();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run(): void {
		$this->run_hooks();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_xophz_compass_lead_magnet() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Xophz_Compass_Lead_Magnet_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): self {
		return $this;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}

}
