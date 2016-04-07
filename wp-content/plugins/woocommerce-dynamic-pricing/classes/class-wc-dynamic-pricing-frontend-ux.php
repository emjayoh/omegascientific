<?php

class WC_Dynamic_Pricing_FrontEnd_UX {

    private static $instance;

    public static function init() {
        if (self::$instance == null) {
            self::$instance = new WC_Dynamic_Pricing_FrontEnd_UX();
        }
    }

    public function __construct() {
        //Filter for the cart adjustment for advanced rules. 
        add_filter('woocommerce_cart_item_price_html', array(&$this, 'on_display_cart_item_price_html'), 10, 3);
    }

    public function on_display_cart_item_price_html($html, $cart_item, $cart_item_key) {
        if ($this->is_cart_item_discounted($cart_item)) {
            $_product = $cart_item['data'];
            
            if (function_exists('get_product')) {
                $price_adjusted = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();
                $price_base = $cart_item['discounts']['display_price'];
            } else {
                if (get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes') :
                    $price_adjusted = $cart_item['data']->get_price_excluding_tax();
                    $price_base = $cart_item['discounts']['display_price'];
                else :
                    $price_adjusted = $cart_item['data']->get_price();
                    $price_base = $cart_item['discounts']['display_price'];
                endif;
            }
            
            if (!empty($price_adjusted)) {
                $html = '<del>' . woocommerce_price($price_base) . '</del><ins> ' . woocommerce_price($price_adjusted) . '</ins>';
            }
        }

        return $html;
    }

    public function is_cart_item_discounted($cart_item) {
        return isset($cart_item['discounts']);
    }

}

?>