<?php
/**
 * Composite Item Multi-Page Template
 */

global $product, $woocommerce_bto;

?>
<div class="bto_item bto_multipage product <?php echo $step == 1 ? ( ! $added ? 'active first' : 'first' ) : ''; echo $step == $steps ? 'last' : ''; ?>" data-item-id="<?php echo $group_id; ?>" data-container-id="<?php echo $product->id; ?>" style="display:none;">
	<script type="text/javascript">

	if ( ! compatibility_data )
			var compatibility_data = new Array();

	bto_item_descriptions[<?php echo $group_id; ?>] = <?php echo json_encode( '<p>' . $group_data['description'] . '</p>' ); ?>;
	bto_nav_titles[<?php echo $group_id; ?>] 		= <?php echo json_encode( $group_data['title'] ); ?>;

	</script>

	<div class="multipage_title">
		<?php
		woocommerce_get_template('single-product/bto-item-title.php', array(
			'title' => sprintf( __('Step <span class="step">%d</span> of <span class="steps">%d</span> - Select <span class="item_title">%s</span>'), $step, $steps + 1, $group_data['title'] )
		), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

		echo '<p class="description">' . $group_data['description'] . '</p>';

		?>
	</div>

	<?php

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
			'discount' 				=> $group_data['discount'],
			'per_product_pricing' 	=> $product->per_product_pricing
		), '', $woocommerce_bto->woo_bto_plugin_path() . '/templates/' );

	?>
	<form class="variations_form" data-product_id="<?php echo $group_id; ?>">
		<div class="bto_item_summary <?php echo $prod_id > 0 ? 'single' : ''; ?>">
			<div class="product content">
				<?php

				if  ( $prod_id == 'deleted' )
					echo '<p>' . __( 'No options are currently available for this item.', 'woocommerce_bto' ) . '</p>';

				if ( $prod_id > 0 || $prod_id === '0' )
					$woocommerce_bto->woo_bto_show_product( $prod_id, $group_id, $product->id );
				else {
					echo '<p>&nbsp</p>';
				}
				?>
			</div>
		</div>
	</form>
</div>
<?php

?>
