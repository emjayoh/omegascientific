<?php
/**
 * Plugin Name: WooCommerce Elavon VM Gateway
 * Plugin URI: http://www.woothemes.com/products/elavon-vm-payment-gateway/
 * Description: Adds the Elavon Virtual Merchant Gateway to your WooCommerce website. Requires an SSL certificate.
 * Version: 1.1
 * Author: Justin Stern
 * Author URI: http://www.foxrunsoftware.net
 *
 * Text Domain: wc_elavon
 * Domain Path: /languages/
 * 
 * Copyright: © 2012-2013 Justin Stern (justin@foxrunsoftware.net)
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WCGatewayElavon
 * @category  Payment Gateways
 * @author    Justin Stern
 * @copyright Copyright (c) 2013, Justin Stern
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '2732aedb77a13149b4db82d484d3bb22', '18722' );

// Check if WooCommerce is active
if ( ! is_woocommerce_active() ) return;

class WC_Elavon_VM {

	const VERSION = "1.1";
	const VERSION_OPTION_NAME = "wc_gateway_elavon_vm";

	/** string class name to load as gateway, can be base or subscriptions class */
	const GATEWAY_CLASS_NAME = 'WC_Gateway_Elavon_VM';

	/** string gateway id */
	const GATEWAY_ID = 'elavon_vm';

	/** @var array required PHP extension names */
	private $extension_dependencies = array( 'simplexml', 'dom' );

	/** @var string the plugin path */
	private $plugin_path;

	/** @var string the plugin url */
	private $plugin_url;

	/** @var object WC_Logger instance */
	private $logger;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		// Installation/Upgrade
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();

		// Load the gateway
		add_action( 'plugins_loaded', array( $this, 'load_classes' ) );

		add_action( 'init', array( $this, 'load_translation' ) );

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// verify dependencies are met
			add_action( 'admin_notices',             array( $this, 'check_dependencies' ) );

			// add a 'Configure' link to the plugin action links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_manage_link' ), 10, 4 );    // remember, __FILE__ derefs symlinks :(
		}
	}


	/**
	 * Loads Gateway class once parent class is available
	 */
	public function load_classes() {

		// Elavon gateway
		require_once( 'classes/class-wc-gateway-elavon-vm.php' );

		// Add class to WC Payment Methods
		add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateway' ) );
	}


	/**
	 * Adds gateway to the list of available payment gateways
	 *
	 * @param array $gateways array of gateway names or objects
	 * @return array $gateways array of gateway names or objects
	 */
	public function load_gateway( $gateways ) {

		$gateways[] = WC_Elavon_VM::GATEWAY_CLASS_NAME;

		return $gateways;
	}


	/**
	 * Load the translation so that WPML is supported
	 */
	public function load_translation() {
		load_plugin_textdomain( 'wc_elavon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Render a warning message if any required PHP extensions are missing
	 */
	public function check_dependencies() {

		$missing_extensions = $this->get_missing_extension_dependencies();

		if ( count( $missing_extensions ) ) {
			$message = sprintf( _n( "WooCommerce Elavon Gateway requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.",
			                        "WooCommerce Elavon Gateway requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.",
			                        count( $missing_extensions ), 'wc_elavon' ),
			                    '<strong>' . implode( ', ', $missing_extensions ) . '</strong>' );

			echo '<div class="error"><p>' . $message . '</p></div>';
		}

		// check ssl dependency
		$this->check_ssl();
	}


	/**
	 * Returns an array of missing required PHP extensions
	 */
	public function get_missing_extension_dependencies() {
		$missing_extensions = array();

		foreach ( $this->extension_dependencies as $ext ) {
			if ( ! extension_loaded( $ext ) ) $missing_extensions[] = $ext;
		}

		return $missing_extensions;
	}


	/**
 	 * Check if SSL is enabled and notify the admin user.  The gateway can technically still
 	 * function without SSL, so this isn't a fatal dependency, not to mention users might
 	 * not bother to configure SSL for their test server.
 	 */
	private function check_ssl() {
		if ( get_option( 'woocommerce_force_ssl_checkout' ) != 'yes' ) {
			echo '<div class="error"><p>WooCommerce is not being forced over SSL; your customer\'s credit card data is at risk.</p></div>';
		}
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @param array $actions associative array of action names to anchor tags
	 * @param string $plugin_file plugin file name, ie my-plugin/my-plugin.php
	 * @param array $plugin_data associative array of plugin data from the plugin file headers
	 * @param string $context plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
	 *
	 * @return array associative array of plugin action links
	 */
	public function plugin_manage_link( $actions, $plugin_file, $plugin_data, $context ) {

		$manage_url = admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways' );

		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) 
			$manage_url = add_query_arg( array( 'section' => WC_Elavon_VM::GATEWAY_CLASS_NAME ), $manage_url ); // WC 2.0+
		else
			$manage_url = add_query_arg( array( 'subtab' => 'gateway-' . WC_Elavon_VM::GATEWAY_ID ), $manage_url ); // WC 1.6.6-

		// add a 'Configure' link to the front of the actions list for this plugin
		return array_merge( array( 'configure' => '<a href="' . $manage_url . '">' . __( 'Configure', 'wc_elavon' ) . '</a>' ),
			                $actions );

	}


	/** Helper methods ******************************************************/


	/**
	 * Logs $message using the woocommerce logging facility
	 *
	 * @param string $message the string to log
	 */
	public function log( $message ) {
		global $woocommerce;

		if ( ! is_object( $this->logger ) )
			$this->logger = $woocommerce->logger();

		// logs to wp-content/plugins/woocommerce/logs/elavon_vm.txt
		$this->logger->add( WC_Elavon_VM::GATEWAY_ID, $message );
	}


	/**
	 * Get the plugin path
	 */
	public function plugin_path() { 	
		if ( $this->plugin_path === null ) $this->plugin_path = plugin_dir_path( __FILE__ );

		return $this->plugin_path;
	}


	/**
	 * Get the plugin url, ie http://example.com/wp-content/plugins/plugin-name
	 *
	 * @return string the plugin url
	 */
	public function plugin_url() {
		if ( $this->plugin_url === null ) $this->plugin_url = plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );

		return $this->plugin_url;
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Run every time.  Used since the activation hook is not executed when updating a plugin
	 */
	private function install() {

		$installed_version = get_option( WC_Elavon_VM::VERSION_OPTION_NAME );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, WC_Elavon_VM::VERSION ) === -1 ) {
			$this->upgrade( $installed_version );

			// new version number
			update_option( WC_Elavon_VM::VERSION_OPTION_NAME, WC_Elavon_VM::VERSION );
		}
	}


	/**
	 * Run when plugin version number changes
	 */
	private function upgrade( $installed_version ) {
		global $wpdb;

		if ( version_compare( $installed_version, "1.0.4" ) === -1 ) {

			// Can't think of a great way of grabbing this from the abstract WC_Settings_API class
			$plugin_id = 'woocommerce_';

			// if installed version is less than 1.0.4, set the correct account type, if needed
			$form_field_settings = (array) get_option( $plugin_id . WC_Elavon_VM::GATEWAY_ID . '_settings' );

			// for existing installs, configured prior to the introduction of the 'account' setting
			if ( $form_field_settings && ! isset( $form_field_settings['account'] ) ) {

				if ( isset( $form_field_settings['testmode'] ) && $form_field_settings['testmode'] == 'yes' ) {
					$form_field_settings['account'] = 'demo';
				} else {
					$form_field_settings['account'] = 'production';
				}

				// set the account type
				update_option( $plugin_id . WC_Elavon_VM::GATEWAY_ID . '_settings', $form_field_settings );
			}
		}
	}
}

// Init plugin
$GLOBALS['wc_elavon_vm'] = new WC_Elavon_VM();
