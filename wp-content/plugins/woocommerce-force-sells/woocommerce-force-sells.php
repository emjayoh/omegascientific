<?php
/*
Plugin Name: WooCommerce Force Sells
Plugin URI: http://woothemes.com/woocommerce
Description: Allows you to select products which will be used as force-sells - items which get added to the cart along with other items.
Version: 1.1.2
Author: WooThemes
Author URI: http://woothemes.com/woocommerce
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '3ebddfc491ca168a4ea4800b893302b0', '18678' );

load_plugin_textdomain( 'wc_force_sell', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

add_action( 'plugins_loaded', 'woocommerce_force_sell_init', 0 );

function woocommerce_force_sell_init() {

	class WC_Force_Sells {
		private $synced_types = array();

		public function __construct() {
			add_action( 'woocommerce_product_options_related', array( &$this, 'write_panel_tab' ) );
			add_action( 'woocommerce_process_product_meta', array( &$this, 'process_extra_product_meta' ), 1, 2 );
			add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'show_force_sell_products' ) );
			add_action( 'woocommerce_add_to_cart', array( &$this, 'add_force_sell_items_to_cart' ), 1, 6 );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( &$this, 'update_force_sell_quantity_in_cart'), 1, 2 );
			add_action( 'woocommerce_before_cart_item_quantity_zero', array( &$this, 'update_force_sell_quantity_in_cart'), 1, 2 );

			// Keep force sell data in the cart
			add_filter( 'woocommerce_get_cart_item_from_session', array(&$this, 'get_cart_item_from_session'), 10, 2 );
			add_filter( 'woocommerce_get_item_data', array( &$this, 'get_linked_to_product_data' ), 10, 2 );

			// Don't allow synced force sells to be removed or change quantity
			add_filter( 'woocommerce_cart_item_remove_link', array( &$this, 'cart_item_remove_link' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_quantity', array( &$this, 'cart_item_quantity' ), 10, 2 );

			$this->synced_types = array(
	    		'normal' => array(
	    			'field_name' => 'force_sell_ids',
	    			'meta_name'  => '_force_sell_ids',
	    		),
	    		'synced' => array(
	    			'field_name' => 'force_sell_synced_ids',
	    			'meta_name'  => '_force_sell_synced_ids',
	    		),
    		);
	    }

    	public function get_cart_item_from_session( $cart_item, $values ) {
			if ( isset( $values['forced_by'] ) )
				$cart_item['forced_by'] = $values['forced_by'];

			return $cart_item;
		}

		public function get_linked_to_product_data( $data, $cart_item ) {
			global $woocommerce;

			if ( isset ( $cart_item['forced_by'] ) ) {
				$product_key = $woocommerce->cart->find_product_in_cart( $cart_item['forced_by'] );

				if ( ! empty( $product_key ) ) {
					$product_name = $woocommerce->cart->cart_contents[ $product_key ]['data']->post->post_title;
					$data[] = array(
						'name'    => __( 'Linked to', 'wc_force_sell' ),
						'display' => $product_name,
					);
				}
			}

			return $data;
		}

		public function cart_item_remove_link( $link, $cart_item_key ) {
			global $woocommerce;

			if ( isset ( $woocommerce->cart->cart_contents[ $cart_item_key ]['forced_by'] ) )
				return '';

			return $link;
		}

		public function cart_item_quantity( $quantity, $cart_item_key ) {
			global $woocommerce;

			if ( isset ( $woocommerce->cart->cart_contents[ $cart_item_key ]['forced_by'] ) )
				return $woocommerce->cart->cart_contents[ $cart_item_key ]['quantity'];

			return $quantity;
		}

	    public function write_panel_tab() {
	    	global $post, $woocommerce;
	    	?>
	    	<p class="form-field"><label for="force_sell_ids"><?php _e('Force Sells', 'wc_force_sell'); ?></label>
				<select id="force_sell_ids" name="force_sell_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e( 'Search for a product...', 'wc_force_sell' ); ?>">
					<?php
					if ( $product_ids = $this->get_force_sell_ids( $post->ID, array( 'normal' ) ) ) {
						foreach ( $product_ids as $product_id ) {
							$title 	= get_the_title( $product_id );
							$sku 	= get_post_meta( $product_id, '_sku', true );

							if (!$title) continue;

							if ( isset( $sku ) && $sku ) $sku = ' (SKU: ' . $sku . ')';

							echo '<option value="' . $product_id . '" selected="selected">#'. $product_id . ' &ndash; ' . $title . $sku .'</option>';
						}
					}
					?>
				</select>
				<img class="help_tip" width="16" data-tip='<?php _e( 'These products will be added to the cart when the main product is added. Quantity will not be synced in case the main product quantity changes.', 'wc_force_sell') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" />
			</p>
			<p class="form-field"><label for="force_sell_synced_ids"><?php _e( 'Synced Force Sells', 'wc_force_sell'); ?></label>
				<select id="force_sell_synced_ids" name="force_sell_synced_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e('Search for a product...', 'wc_force_sell'); ?>">
					<?php
					if ( $product_ids = $this->get_force_sell_ids( $post->ID, array( 'synced' ) ) ) {
						foreach ( $product_ids as $product_id ) {
							$title 	= get_the_title( $product_id );
							$sku 	= get_post_meta( $product_id, '_sku', true );

							if (!$title) continue;

							if ( isset( $sku ) && $sku ) $sku = ' (SKU: ' . $sku . ')';

							echo '<option value="' . $product_id . '" selected="selected">#'. $product_id . ' &ndash; ' . $title . $sku .'</option>';
						}
					}
					?>
				</select>
				<img class="help_tip" width="16" data-tip='<?php _e( 'These products will be added to the cart when the main product is added and quantity will be synced in case the main product quantity changes.', 'wc_force_sell') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" />
			</p>
	    	<?php
	    }

	    public function process_extra_product_meta( $post_id, $post ) {
	    	foreach ( $this->synced_types as $key => $value ) {
		    	if ( isset( $_POST[ $value['field_name'] ] ) ) {
					$force_sells = array();
					$ids = $_POST[ $value['field_name'] ];

					foreach ( $ids as $id ) {
						if ( $id && $id > 0 ) $force_sells[] = $id;
					}

					update_post_meta( $post_id, $value['meta_name'], $force_sells );
				} else {
					delete_post_meta( $post_id, $value['meta_name'] );
				}
			}
	    }

	    public function show_force_sell_products() {
	    	global $post;

	    	if ( $product_ids = $this->get_force_sell_ids( $post->ID, array( 'normal', 'synced' ) ) ) {
	    		echo '<div class="clear"></div>';
	    		echo '<div class="wc-force-sells">';
	    		echo '<p>' . __( 'This will also add the following products to your cart:', 'wc_force_sell' ) . '</p>';
	    		echo '<ul>';

	    		foreach ( $product_ids as $product_id ) {
	    			$title 	= get_the_title( $product_id );
	    			echo '<li>' . $title . '</li>';
	    		}

	    		echo '</ul></div>';
	    	}
	    }

	    public function add_force_sell_items_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	    	global $woocommerce;

	    	// Check if this product is forced in itself, so it can't force in others (to prevent adding in loops)
    		if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ]['forced_by'] ) ) {
    			$forced_by_key = $woocommerce->cart->cart_contents[ $cart_item_key ]['forced_by'];

				if ( isset( $woocommerce->cart->cart_contents[ $forced_by_key ] ) ) {
    				return;
    			}
    		}

	    	if ( $product_ids = $this->get_force_sell_ids( $product_id, array( 'normal', 'synced' ) ) ) {
	    		foreach ( $product_ids as $id ) {
	    			$cart_id = $woocommerce->cart->generate_cart_id( $id, '', '', array( 'forced_by' => $cart_item_key ) );
	    			$key = $woocommerce->cart->find_product_in_cart( $cart_id );

	    			if ( ! empty( $key ) ) {
	    				$woocommerce->cart->set_quantity( $key, $woocommerce->cart->cart_contents[ $key ]['quantity'] );
	    			} else {
	    				$args = array();

	    				if ( $synced_ids = $this->get_force_sell_ids( $product_id, array( 'synced' ) ) ) {
	    					if ( in_array( $id, $synced_ids ) ) {
	    						$args['forced_by'] = $cart_item_key;
	    					}
	    				}

						$woocommerce->cart->add_to_cart( $id, $quantity, '', '', $args );
					}
	    		}
	    	}
	    }

	    public function update_force_sell_quantity_in_cart( $cart_item_key, $quantity = 0 ) {
	    	global $woocommerce;

	    	if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ] ) ) {
	    		if ( $quantity == 0 || $quantity < 0 ) {
	    			$quantity = 0;
	    		} else {
	    			$quantity = $woocommerce->cart->cart_contents[ $cart_item_key ]['quantity'];
	    		}

	    		foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
	    			if ( isset( $value['forced_by'] ) && $cart_item_key == $value['forced_by'] ) {
	    				$woocommerce->cart->set_quantity( $key, $quantity );
	    			}
	    		}
	    	}
	    }

	    private function get_force_sell_ids( $product_id, $types ) {
	    	if ( ! is_array( $types ) || empty( $types ) ) {
	    		return false;
	    	}

	    	$ids = array();

	    	foreach ( $types as $type ) {
	    		$new_ids = array();

	    		if ( isset( $this->synced_types[ $type ] ) ) {
	    			$new_ids = get_post_meta( $product_id, $this->synced_types[ $type ]['meta_name'], true );

	    			if ( is_array( $new_ids ) && ! empty( $new_ids ) ) {
		    			$ids = array_merge( $ids, $new_ids );
		    		}
	    		}
	    	}

	    	if ( is_array( $ids ) && ! empty( $ids ) ) {
	    		return $ids;
	    	}

	    	return false;
	    }
	}

	$GLOBALS['WC_Force_Sells'] = new WC_Force_Sells();
}