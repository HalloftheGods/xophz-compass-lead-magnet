<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/admin
 * @author     Your Name <email@example.com>
 */
class Xophz_Compass_Lead_Magnet_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/xophz-compass-lead-magnet-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/xophz-compass-lead-magnet-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add menu item 
	 *
	 * @since    1.0.0
	 */
	public function addToMenu(){
        Xophz_Compass::add_submenu($this->plugin_name);
	}

	/**
	 * Retrieve Hustle modules (Popups, Slide-ins, Embeds, Social Shares) for the Vue frontend.
	 */
	public function get_hustle_modules() {
		if ( ! class_exists( 'Hustle_Module_Collection' ) ) {
			wp_send_json_error( array( 'message' => 'Hustle plugin is not active' ) );
			return;
		}

		$collection = Hustle_Module_Collection::instance();
		$popups = $collection->get_all( null, array( 'module_type' => 'popup' ) );
		$slideins = $collection->get_all( null, array( 'module_type' => 'slidein' ) );
		$embeds = $collection->get_all( null, array( 'module_type' => 'embedded' ) );
		$socials = $collection->get_all( null, array( 'module_type' => 'social_sharing' ) ); // Note: type depends on sub_type sometimes for socials: 'sshare' or 'social_sharing', will check 'social_sharing'.

		$format_modules = function($modules) {
			$res = array();
			if (is_array($modules)) {
				foreach($modules as $m) {
					$data = method_exists($m, 'get_data') ? $m->get_data() : array();
					$res[] = array(
						'id' => isset($m->id) ? $m->id : '',
						'name' => isset($m->module_name) ? $m->module_name : '',
						'status' => isset($m->active) ? $m->active : 0,
						'type' => isset($m->module_type) ? $m->module_type : '',
						'data' => $data
					);
				}
			}
			return $res;
		};

		$response = array(
			'popups' => $format_modules($popups),
			'slide_ins' => $format_modules($slideins),
			'embeds' => $format_modules($embeds),
			'social_shares' => $format_modules($socials)
		);

		wp_send_json_success( $response );
	}

}
