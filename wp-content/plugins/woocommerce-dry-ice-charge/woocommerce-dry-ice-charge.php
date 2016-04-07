<?php
/*
Plugin Name: WooCommerce Dry Ice Charge
Plugin URI: http://www.omegascientific.com
Description: Charges extra fee for products that ship with dry ice.
Version: 0.91
Author: Matthew J. Ogram
Author URI: http://www.omegascientific.com
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly


class WC_Dry_Ice_Charge {

	public function __construct(){
		$this->current_extra_charges = '';
		add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 10, 1);
//		add_action('woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 10, 1);
//		add_action('woocommerce_cart_subtotal', array( $this, 'calculate_totals' ), 10, 1);
	}

	public function before_calculate_totals($stuff) {
		global $woocommerce;
		$num_frozen = 0;
		$cart_contents = $woocommerce->cart->cart_contents;
		foreach($cart_contents AS $product) {
			$shipping_class = $product['data']->get_shipping_class();
			if ($shipping_class == 'frozen') {
				$num_frozen += $product['quantity'];
			}
		}
		$num_dry_ice_packs = ceil($num_frozen/30);
		$this->current_extra_charges = $num_dry_ice_packs * 35;
//		$totals->cart_contents_total = $totals->cart_contents_total + $this->current_extra_charges;
//		add_action('woocommerce_cart_totals_before_order_total', array($this, 'add_cart_extra_charges_row'));
//		add_action('woocommerce_review_order_before_order_total', array($this, 'add_checkout_extra_charges_row'));

		if ($this->current_extra_charges > 0) {
			$woocommerce->cart->add_fee('Dry Ice', $this->current_extra_charges, true);
		}
	}

	public function calculate_totals($totals) {
		global $woocommerce;
		$num_frozen = 0;
		$cart_contents = $woocommerce->cart->cart_contents;
		foreach($cart_contents AS $product) {
			$shipping_class = $product['data']->get_shipping_class();
			if ($shipping_class == 'frozen') {
				$num_frozen += $product['quantity'];
			}
		}
		$num_dry_ice_packs = ceil($num_frozen/35);
		$this->current_extra_charges = $num_dry_ice_packs * 35;
		$totals->cart_contents_total = $totals->cart_contents_total + $this->current_extra_charges;
		add_action('woocommerce_cart_totals_before_order_total', array($this, 'add_cart_extra_charges_row'));
		add_action('woocommerce_review_order_before_order_total', array($this, 'add_checkout_extra_charges_row'));
		return $totals;
	}

	function add_cart_extra_charges_row(){
		?>
		<tr class="dry-ice-charge">
			<th>Dry Ice</th>
			<td><?php echo woocommerce_price($this->current_extra_charges); ?></td>
		</tr>
		<?php
	}

	function add_checkout_extra_charges_row(){
		?>
		<tr class="payment-extra-charge">
			<th>Dry Ice</th>
			<td><?php echo woocommerce_price($this->current_extra_charges); ?></td>
		</tr>
		<?php
	}

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		if ( $this->plugin_url ) return $this->plugin_url;
		return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
	}


	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		if ( $this->plugin_path ) return $this->plugin_path;
		return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

new WC_Dry_Ice_Charge();
