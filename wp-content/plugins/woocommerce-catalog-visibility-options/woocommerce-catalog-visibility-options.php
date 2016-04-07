<?php
/*
  Plugin Name:  WooCommerce Catalog Visibility Options
  Plugin URI: http://woothemes.com/woocommerce
  Description: Provides the ability to hide prices, or show prices only to authenticated users. Provides the ability to disable e-commerce functionality by disabling the cart. 
  Version: 1.1.1
  Author: Lucas Stark
  Author URI: http://lucasstark.com
  Requires at least: 3.1
  Tested up to: 3.3

	Copyright: © 2012 Lucas Stark.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 **/
if (is_admin()) {
	$woo_plugin_updater_catalog_visibility = new WooThemes_Plugin_Updater( __FILE__ );
	$woo_plugin_updater_catalog_visibility->api_key = 'dc52465143d278457cce607c0200c5ca';
	$woo_plugin_updater_catalog_visibility->init();
}
		
if (is_woocommerce_active()) {

    require 'classes/class-wc-catalog-visibility-options.php';
    require 'shortcodes/shortcodes-init.php';

    class WC_Catalog_Visibility_Options {
        
        public function __construct() {
            
            
            $this->current_tab = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'general';
            $this->settings_tabs = array(
                'visibility_options' => __('Visibility Options', 'woothemes')
            );
            
            add_action('woocommerce_settings_tabs', array(&$this, 'on_add_tab'), 10);

            // Run these actions when generating the settings tabs.
            foreach ($this->settings_tabs as $name => $label) {
                add_action('woocommerce_settings_tabs_' . $name, array(&$this, 'settings_tab_action'), 10);
                add_action('woocommerce_update_options_' . $name, array(&$this, 'save_settings'), 10);
            }

            // Add the settings fields to each tab.
            add_action('woocommerce_visibility_options_settings', array(&$this, 'add_settings_fields'), 10);
            add_action('woocommerce_admin_field_editor', array(&$this, 'on_editor_field'));
            
            if (!is_admin()) {
                $this->wc_cvo = new WC_CVO_Visibility_Options();
            }
            
        }

        /*
         * Admin Functions
         */

        /* ----------------------------------------------------------------------------------- */
        /* Admin Tabs */
        /* ----------------------------------------------------------------------------------- */

        function on_add_tab() {
            foreach ($this->settings_tabs as $name => $label) :
                $class = 'nav-tab';
                if ($this->current_tab == $name)
                    $class .= ' nav-tab-active';
                echo '<a href="' . admin_url('admin.php?page=woocommerce&tab=' . $name) . '" class="' . $class . '">' . $label . '</a>';
            endforeach;
        }

        /**
         * settings_tab_action()
         *
         * Do this when viewing our custom settings tab(s). One function for all tabs.
         */
        function settings_tab_action() {
            global $woocommerce_settings;

            // Determine the current tab in effect.
            $current_tab = $this->get_tab_in_view(current_filter(), 'woocommerce_settings_tabs_');

            // Hook onto this from another function to keep things clean.
            // do_action( 'woocommerce_newsletter_settings' );

            do_action('woocommerce_visibility_options_settings');

            // Display settings for this tab (make sure to add the settings to the tab).
            woocommerce_admin_fields($woocommerce_settings[$current_tab]);
        }

        /**
         * add_settings_fields()
         *
         * Add settings fields for each tab.
         */
        function add_settings_fields() {
            global $woocommerce_settings;

            // Load the prepared form fields.
            $this->init_form_fields();

            if (is_array($this->fields)) :
                foreach ($this->fields as $k => $v) :
                    $woocommerce_settings[$k] = $v;
                endforeach;
            endif;
        }

        /**
         * get_tab_in_view()
         *
         * Get the tab current in view/processing.
         */
        function get_tab_in_view($current_filter, $filter_base) {
            return str_replace($filter_base, '', $current_filter);
        }

        /**
         * init_form_fields()
         *
         * Prepare form fields to be used in the various tabs.
         */
        function init_form_fields() {

            // Define settings			
            $this->fields['visibility_options'] = apply_filters('woocommerce_visibility_options_settings_fields', array(
                array(
                    'name' => __('Shopping', 'woothemes'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'visibility_options_add-to-cart'
                ),
                array(
                    'name' => __('Purchases', 'woothemes'),
                    'desc' => '',
                    'id' => 'wc_cvo_atc',
                    'type' => 'select',
                    'std' => 'enabled',
                    'class' => 'chosen_select',
                    'options' => array('enabled' => 'Enabled', 'disabled' => 'Disabled', 'secured' => 'Enabled for Logged In Users')
                ),
                array(
                    'name' => __('Prices', 'woothemes'),
                    'desc' => '',
                    'id' => 'wc_cvo_prices',
                    'type' => 'select',
                    'std' => 'enabled',
                    'class' => 'chosen_select',
                    'options' => array('enabled' => 'Enabled', 'disabled' => 'Disabled', 'secured' => 'Enabled for Logged In Users')
                ),
                array(
                    'name' => __('Catalog Add to Cart Button Text', 'woothemes'),
                    'type' => 'textarea',
                    'desc' => '',
                    'css' => 'min-width:500px;',
                    'desc' => '',
                    'id' => 'wc_cvo_atc_text'
                ),
                array(
                    'name' => __('Catalog Price Text', 'woothemes'),
                    'type' => 'text',
                    'desc' => '',
                    'css' => 'min-width:500px;',
                    'std' => '',
                    'id' => 'wc_cvo_c_price_text'
                ),
                array(
                    'name' => __('Alternate Content', 'woothemes'),
                    'type' => 'editor',
                    'desc' => '',
                    'id' => 'wc_cvo_s_price_text'
                ),
                array('type' => 'sectionend', 'id' => 'visibility_options_prices')
                    ));
        }

        /**
         * save_settings()
         *
         * Save settings in a single field in the database for each tab's fields (one field per tab).
         */
        function save_settings() {
            global $woocommerce_settings;

            // Make sure our settings fields are recognised.
            $this->add_settings_fields();

            $current_tab = $this->get_tab_in_view(current_filter(), 'woocommerce_update_options_');


            woocommerce_update_options($woocommerce_settings[$current_tab]);
        }

        /** Helper functions ***************************************************** */
         
        /**
         * Gets a setting
         */
        public function setting($key) {
            return get_option($key);
        }

        
        /**
         * Get the custom admin field: editor
         */
        public function on_editor_field($value) {
            $content = get_option($value['id']);
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                <td class="forminp">
                    <?php wp_editor($content, $value['id']); ?>
                </td>
            </tr>
            <?php
        }

    }

	global $wc_cvo;
	$wc_cvo = new WC_Catalog_Visibility_Options();

}


//Configure default options
register_activation_hook( __FILE__, 'activate_wc_cvo' );
function activate_wc_cvo() {
    if (!get_option('wc_cvo_atc')) {
        update_option('wc_cvo_atc', 'enabled');
    }
    
    if (!get_option('wc_cvo_prices')) {
        update_option('wc_cvo_prices', 'enabled');
    }
}

function catalog_visibility_user_has_access() {
    return apply_filters('catalog_visibility_user_has_access', is_user_logged_in() );
}