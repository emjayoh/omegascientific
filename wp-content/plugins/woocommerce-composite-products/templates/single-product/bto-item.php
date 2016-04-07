<?php
/**
 * Composite Item Single Page Template
 */

global $product, $woocommerce_bto;

?>
<div class="bto_item product <?php echo $step == 1 ? 'active first' : ''; echo $step == $steps ? 'last' : ''; ?>" data-item-id="<?php echo $group_id; ?>" data-container-id="<?php echo $product->id; ?>">
	<script type="text/javascript">

	if ( ! compatibility_data )
			var compatibility_data = new Array();

	bto_item_descriptions[<?php echo $group_id; ?>] = <?php echo json_encode( '<p>' . $group_data['description'] . '</p>' ); ?>;

	</script>
<?php

	woocommerce_get_template('single-product/bto-item-title.php', array(
		'title' => $group_data['title']
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

	$prod_id = '';

	if ( isset( $_POST['add-product-to-cart'][ $group_id ] ) && $_POST['add-product-to-cart'][ $group_id ] !== '' )
		$prod_id = $_POST['add-product-to-cart'][ $group_id ];

	if ( $group_data['optional'] != 'yes' && count( $group_data['assigned_ids'] ) == 1 ) {
		$prod_id = $group_data['assigned_ids'][0];

		if ( get_post( $prod_id ) ) {
			?>
			<div class="bto_item_options" style="display:none">
				<select id="bto_item_options_<?php echo $group_id; ?>" name="bto_selection_<?php echo $group_id; ?>">
					<option data-title="<?php echo get_the_title( $prod_id ); ?>" value="<?php echo $prod_id; ?>"></option>
				</select>
			</div>
			<?php
		} else {
			$prod_id = 'deleted';
		}
	} else
		woocommerce_get_template('single-product/bto-item-options.php', array(
			'group_id'				=> $group_id,
			'title'					=> $group_data['title'],
			'group_options' 		=> $group_data['assigned_ids'],
			'optional' 				=> $group_data['optional'],
			'discount'				=> $group_data['discount'],
			'per_product_pricing' 	=> $product->per_product_pricing
		), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>
	<form class="variations_form" data-product_id="<?php echo $group_id; ?>">
<?php

	woocommerce_get_template('single-product/bto-item-summary.php', array(
		'description' 	=> $group_data['description'],
		'prod_id'		=> $prod_id,
		'group_id'		=> $group_id,
		'container_id'	=> $product->id
	), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

?>
	</form>
</div>
<?php

?>
