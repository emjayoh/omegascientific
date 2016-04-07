<?php

class WC_Dynamic_Pricing_Advanced_Totals extends WC_Dynamic_Pricing_Advanced_Base {

    private static $instance;

    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new WC_Dynamic_Pricing_Advanced_Totals('advanced_totals');
        }
        return self::$instance;
    }

    public $adjustment_sets;

    public function __construct($module_id) {
        parent::__construct($module_id);

        $sets = get_option('_a_totals_pricing_rules');
        if ($sets && is_array($sets) && sizeof($sets) > 0) {
            foreach ($sets as $id => $set_data) {
                $obj_adjustment_set = new WC_Dynamic_Pricing_Adjustment_Set_Totals($id, $set_data);
                $this->adjustment_sets[$id] = $obj_adjustment_set;
            }
        }
    }

    public function adjust_cart($temp_cart) {
        if ($this->adjustment_sets && count($this->adjustment_sets)) {

            foreach ($temp_cart as $cart_item_key => $cart_item) {

                foreach ($this->adjustment_sets as $set_id => $set) {

                    //check if this set is valid for the current user;
                    $is_valid_for_user = $set->is_valid_for_user();

                    if (!($is_valid_for_user)) {
                        continue;
                    }

                    if (!$this->is_cumulative($cart_item, $cart_item_key)) {

                        if ($this->is_item_discounted($cart_item, $cart_item_key)) {
                            continue;
                        }
                    }

                    $original_price = $this->get_price_to_discount($cart_item, $cart_item_key);
                    if ($original_price) {
                        $price_adjusted = false;

                        $price_adjusted = $this->get_adjusted_price($set, $original_price, $cart_item);

                        if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
                            WC_Dynamic_Pricing::apply_cart_item_adjustment($cart_item_key, $original_price, $price_adjusted, $this->module_id, $set_id);
                            break;
                        }
                    }
                }
            }
        }
    }

    private function get_adjusted_price($set, $price, $cart_item) {
        $result = false;

        $result = false;

        $pricing_rules = $set->pricing_rules;
        $collector = $set->get_collector();
        $rule_set_id = $set->set_id;


        if (is_array($pricing_rules) && sizeof($pricing_rules) > 0) {
            foreach ($pricing_rules as $rule) {

                $q = $this->get_quantity_to_compare($cart_item, $collector);

                if ($rule['from'] == '*') {
                    $rule['from'] = 0;
                }

                if ($rule['to'] == '*') {
                    $rule['to'] = $q;
                }

                if ($q >= $rule['from'] && $q <= $rule['to']) {

                    switch ($rule['type']) {
                        case 'percentage_discount':
                            if ($rule['amount'] > 1) {
                                $rule['amount'] = $rule['amount'] / 100;
                            }
                            $result = round(floatval($price) - ( floatval($rule['amount']) * $price), (int) get_option( 'woocommerce_price_num_decimals' ));
                            break;
                        default:
                            $result = false;
                            break;
                    }

                    break; //break out here only the first matched pricing rule will be evaluated.
                }
            }
        }

        return $result;
    }

    private function get_quantity_to_compare($cart_item, $collector) {
        global $woocommerce;
        $quantity = 0;

        switch ($collector['type']) {
            case 'cart_total':
                $quantity = 0;
                $working_price = 0.00;
                foreach ($woocommerce->cart->cart_contents as $cart_item) {
                    $q = $cart_item['quantity'] ? $cart_item['quantity'] : 1;

                    if (isset($cart_item['discounts']) && isset($cart_item['discounts']['by']) && $cart_item['discounts']['by'] == $this->module_id) {
                        $quantity += floatval($cart_item['discounts']['price_base']) * $q;
                    } else {
                        $quantity += $cart_item['data']->get_price() * $q;
                    }
                }
                break;
            default:
                break;
        }

        return $quantity;
    }

}

?>