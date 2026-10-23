<?php
/**
 * Product card (used on shop, category pages and homepage rows).
 *
 * @package Shokher
 * @version 9.4.0
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
$pct  = shokher_discount_pct( $product );
$link = get_permalink( $product->get_id() );
?>
<li <?php wc_product_class( 'sk-card', $product ); ?>>
	<a class="sk-card-img" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
		<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore ?>
		<?php if ( $pct ) : ?>
			<span class="sk-badge">-<?php echo (int) $pct; ?>%</span>
		<?php endif; ?>
		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="sk-oos"><?php esc_html_e( 'Stock Out', 'shokher' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="sk-card-body">
		<h3 class="sk-card-title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<?php if ( wc_review_ratings_enabled() && $product->get_average_rating() > 0 ) : ?>
			<?php echo wc_get_rating_html( $product->get_average_rating() ); // phpcs:ignore ?>
		<?php endif; ?>
		<div class="sk-card-price"><?php echo $product->get_price_html(); // phpcs:ignore ?></div>
		<div class="sk-card-btn">
			<?php
			woocommerce_template_loop_add_to_cart(
				array(
					'class' => implode( ' ', array_filter( array(
						'button',
						'sk-btn-outline',
						'product_type_' . $product->get_type(),
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
					) ) ),
				)
			);
			?>
		</div>
	</div>
</li>
