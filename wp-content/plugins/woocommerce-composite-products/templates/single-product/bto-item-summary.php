<?php
/**
 * Composite Item Short Description Template
 */

global $product, $woocommerce_bto;

?>
<div class="bto_item_summary">
	<div class="product content">
		<?php

		if  ( $prod_id == 'deleted' )
			echo '<p>' . __( 'No options are currently available for this item.', 'woocommerce_bto' ) . '</p>';

		if ( $prod_id > 0 || $prod_id === '0' ) {
			$woocommerce_bto->woo_bto_show_product( $prod_id, $group_id, $container_id );
		}

		if ( $prod_id === '0' || $prod_id === '' ) {
			?>
			<p><?php echo $description; ?></p>
			<?php
		}

		?>
	</div>
</div>
