<?php
/**
 * Composite Item Options Drop-Down Template
 */

?>
<div class="bto_item_options">
	<select id="bto_item_options_<?php echo $group_id; ?>" name="bto_selection_<?php echo $group_id; ?>">
		<option value=""><?php echo __( 'Select an option', 'woocommerce-bto' ); ?>&hellip;</option>
		<?php

		$selected_value = '';

		if ( isset( $_POST['add-product-to-cart'][ $group_id ] ) && $_POST['add-product-to-cart'][ $group_id ] !== '' )
			$selected_value = $_POST['add-product-to-cart'][ $group_id ];

		if ( $optional == 'yes' ) {
			?>
			<option data-title="<?php echo __( 'None', 'woocommerce-bto' ); ?>" value="0" <?php echo selected( $selected_value, '0', false ); ?>><?php echo __( 'None', 'woocommerce-bto' ); ?></option>
			<?php
		}

		foreach ( $group_options as $product_id ) {

			if ( get_post_status( $product_id ) != 'publish' )
				continue;

			?>
			<option data-title="<?php echo get_the_title( $product_id ); ?>" value="<?php echo $product_id; ?>" <?php echo selected( $selected_value, $product_id, false ); ?>><?php

				echo get_the_title( $product_id );

				if ( $per_product_pricing == 'yes' ) {

					// Get product type
					$terms 			= get_the_terms( $product_id, 'product_type' );
					$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

					if ( $product_type == 'simple' ) {

						$regular_price 	= get_post_meta( $product_id , '_regular_price', true );
						$price 			= get_post_meta( $product_id , '_price', true );

						$product_regular_price 	= empty( $regular_price ) ? ( double ) $price : ( double ) $regular_price;
						$product_price 			= empty( $discount ) || empty( $regular_price ) ? ( double ) $price : $regular_price * ( 100 - $discount ) / 100;

						$on_sale 	= $product_price < $product_regular_price ? ' ' . __( '- sale!', 'woocommerce-bto' ) : '';
						$price 		= $product_price > 0 ? woocommerce_price( $product_price ) : __( 'Free!', 'woocommerce' );
					}
					elseif ( $product_type == 'variable' ) {

						$min_variation_regular_price 	= get_post_meta( $product_id, '_min_variation_regular_price', true );
						$min_variation_sale_price 		= get_post_meta( $product_id, '_min_variation_sale_price', true );
						$max_variation_regular_price 	= get_post_meta( $product_id, '_max_variation_regular_price', true );
						$max_variation_sale_price 		= get_post_meta( $product_id, '_max_variation_sale_price', true );

						$min_variation_sale_price 	= empty( $discount ) ? $min_variation_sale_price : ( double ) $min_variation_regular_price * ( 100 - $discount ) / 100;
						$max_variation_sale_price 	= empty( $discount ) ? $max_variation_sale_price : ( double ) $max_variation_regular_price * ( 100 - $discount ) / 100;

						$product_min_price 		= ( $min_variation_sale_price === '' || $min_variation_regular_price < $min_variation_sale_price ) ? $min_variation_regular_price : $min_variation_sale_price;
						$product_max_price 		= ( $max_variation_sale_price === '' || $max_variation_regular_price < $max_variation_sale_price ) ? $max_variation_regular_price : $max_variation_sale_price;

						$on_sale = $min_variation_sale_price !== '' ? ' ' . __( '- sale!', 'woocommerce-bto' ) : '';

						if ( $product_max_price > $product_min_price ) {
							$price = $product_min_price > 0 ? __( 'from', 'woocommerce-bto' ) . ' ' . woocommerce_price( $product_min_price ) : __( 'from', 'woocommerce-bto' ) . ' ' . __( 'Free!', 'woocommerce' );
						} else {
							$price = $product_min_price > 0 ? woocommerce_price( $product_min_price ) : __( 'Free!', 'woocommerce' );
						}
					}

					echo ' - ' . $price . $on_sale;

				}
			?>
			</option>
			<?php
		}
	?>
	</select>
	<a class="reset_composite_options" href="#reset_composite"><?php echo __( 'Clear options', 'woocommerce-bto' ); ?></a>
</div>
