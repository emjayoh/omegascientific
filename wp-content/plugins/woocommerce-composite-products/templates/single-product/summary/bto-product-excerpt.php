<?php
/**
 * Composited Product Excerpt
 */

$post = get_post( $product_id );
echo '<p>' . __( $post->post_excerpt ) . '</p>';

?>
