<?php
/**
 * Composited Simple Product Summary
 */
global $woocommerce_bto;


woocommerce_get_template('single-product/summary/bto-product-title.php', array(
	'title' => get_the_title( $product->id )
), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );


woocommerce_get_template('single-product/summary/bto-product-image.php', array(
	'product_id' => $product->id
), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>

<div class="details">
	<?php
	woocommerce_get_template('single-product/summary/bto-product-excerpt.php', array(
		'product_id' => $product->id
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );
	?>

	<div class="bundled_item_wrap">
		<?php
			if ( $per_product_pricing == 'yes' )
				woocommerce_get_template('single-product/summary/bto-product-price.php', array(
					'product_price_html' => $product_price_html
				), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );
		?>

		<?php
			// Availability
			$availability = $product->get_availability();

			if ($availability['availability']) {
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );
		    }
		?>

		<?php
			if ( $product->is_in_stock() ) {
			// TODO: What if quantity_min = quantity_max?
		?>
			<div class="quantity_button">
		 	<?php
		 		if ( ! $product->is_sold_individually() )
		 			woocommerce_quantity_input( array(
		 				'min_value' => $quantity_min,
		 				'max_value' => $product->get_stock_quantity() === '' ? $quantity_max : min( $quantity_max, $product->get_stock_quantity() )
		 			) );
		 		else {
		 			?>
		 			<div class="quantity" style="display:none;"><input type="hidden" name="quantity" class="qty" value="1" /></div>
		 			<?php
		 		}
		 	?>
		 	</div>
		<?php
			}
		?>

	</div>
</div>

