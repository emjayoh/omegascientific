<?php
// Custom functions

// Custom admin login logo
function custom_login_logo() {
	echo '<style type="text/css">
	h1 a { background-image: url('.get_bloginfo('template_directory').'/assets/img/omega-logo-sharp.png) !important; width:310px !important; }
	</style>';
}
add_action('login_head', 'custom_login_logo');

// Remove default WooCommerce breadcrumbs and add Yoast ones instead
//remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);
//add_action( 'woocommerce_before_main_content','my_yoast_breadcrumb', 20, 0);
//if (!function_exists('my_yoast_breadcrumb') && function_exists('yoast_breadcrumb')) {
//	function my_yoast_breadcrumb() {
//		yoast_breadcrumb('<p id="breadcrumbs">','</p>');
//	}
//}


/**
 * This code should be added to functions.php of your theme
 **/
add_filter('woocommerce_empty_price_html', 'custom_call_for_price');

function custom_call_for_price() {
	return 'Call for price';
}

add_filter('woocommerce_get_price', 'get_price_per_user', 10, 2);


/**
 * custom_price_WPA111772
 *
 * filter the price based on category and user role
 * @param  $price
 * @param  $product
 * @return
 */
function get_price_per_user($price, $product) {
	if (!is_user_logged_in()) { return $price; }

	$user_id = get_current_user_id();
	$product_id = $product->id;

	// Loop through price alteration rows
	if (get_field('price_alteration', 'option')) {
		while (has_sub_field('price_alteration', 'option')) {
			$users = get_sub_field('user', 'option');
			foreach($users AS $user) {
				if ($user['ID'] == $user_id) {
					$products = get_sub_field('product', 'option');
					foreach($products AS $curr_product) {
						if ($product_id == $curr_product->ID) {
							$price = get_sub_field('adjusted_price', 'option');
						}
					}
				}
			}
		}
	}

	return $price;
}