<?php
/**
 * WooCommerce integration.
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/* Wrappers: our header/footer already open/close <main>. */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_action( 'woocommerce_before_main_content', function () {
	echo '<div class="sk-container sk-shop">';
}, 10 );
add_action( 'woocommerce_after_main_content', function () {
	echo '</div>';
}, 10 );

/* Shop grid. */
add_filter( 'loop_shop_columns', function () {
	return 5;
} );
add_filter( 'loop_shop_per_page', function () {
	return 30;
} );
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 10;
	$args['columns']        = 5;
	return $args;
} );

/** Discount percentage for a product (handles variable products). */
function shokher_discount_pct( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return 0;
	}
	if ( $product->is_type( 'variable' ) ) {
		$max = 0;
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v && $v->is_on_sale() && (float) $v->get_regular_price() > 0 ) {
				$max = max( $max, round( 100 - ( (float) $v->get_sale_price() / (float) $v->get_regular_price() * 100 ) ) );
			}
		}
		return (int) $max;
	}
	$r = (float) $product->get_regular_price();
	$s = (float) $product->get_sale_price();
	return ( $r > 0 && $s > 0 ) ? (int) round( 100 - ( $s / $r * 100 ) ) : 0;
}

add_filter( 'woocommerce_sale_flash', function ( $html, $post, $product ) {
	$pct = shokher_discount_pct( $product );
	return $pct ? '<span class="onsale sk-badge">-' . $pct . '%</span>' : '';
}, 10, 3 );

/* Show "?on_sale=1" filter on shop (used by Flash Sale button). */
add_action( 'woocommerce_product_query', function ( $q ) {
	if ( ! empty( $_GET['on_sale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ids = wc_get_product_ids_on_sale();
		$q->set( 'post__in', $ids ? array_merge( array( 0 ), $ids ) : array( 0 ) );
	}
} );

/* Live header + floating cart counts. */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $f ) {
	$count            = shokher_cart_count();
	$f['.sk-cart-count'] = '<span class="sk-cart-count">' . (int) $count . '</span>';
	$f['.sk-float-count'] = '<span class="sk-float-count">' . (int) $count . ' ' . _n( 'Item', 'Items', $count, 'shokher' ) . '</span>';
	$f['.sk-float-total'] = '<span class="sk-float-total">' . wp_kses_post( shokher_cart_total() ) . '</span>';
	return $f;
} );

/* Product card add-to-cart text. */
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	if ( $product->is_type( 'variable' ) && $product->is_purchasable() ) {
		return __( 'Select Option', 'shokher' );
	}
	if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		return __( 'Add to Cart', 'shokher' );
	}
	return $text;
}, 10, 2 );

/* Single product: add a "Buy Now" button that goes straight to checkout. */
add_action( 'woocommerce_after_add_to_cart_button', function () {
	global $product;
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	echo '<button type="submit" name="sk_buy_now" value="1" class="button alt sk-buy-now">' . esc_html__( 'Buy Now', 'shokher' ) . '</button>';
} );
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
	if ( ! empty( $_REQUEST['sk_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return wc_get_checkout_url();
	}
	return $url;
} );

/* Single product trust box + hotline under the cart button. */
add_action( 'woocommerce_single_product_summary', function () {
	$tel = shokher_opt( 'hotline' );
	echo '<div class="sk-trust">';
	echo '<div>' . shokher_icon( 'truck', 18 ) . '<span>Delivery ৳80 inside Dhaka · ৳150 outside Dhaka</span></div>';
	echo '<div>' . shokher_icon( 'cash', 18 ) . '<span>Cash on Delivery · bKash · Nagad</span></div>';
	echo '<div>' . shokher_icon( 'refresh', 18 ) . '<span>Easy exchange within 3 days</span></div>';
	if ( $tel ) {
		echo '<a class="sk-call" href="' . esc_attr( shokher_tel( $tel ) ) . '">' . shokher_icon( 'phone', 18 ) . '<span>Order by phone: <b>' . esc_html( $tel ) . '</b></span></a>';
	}
	echo '</div>';
}, 35 );

/* Remove default breadcrumb position; we print it inside container. */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $d ) {
	$d['delimiter']   = '<span class="sk-sep">/</span>';
	$d['wrap_before'] = '<nav class="woocommerce-breadcrumb sk-breadcrumb" aria-label="Breadcrumb">';
	return $d;
} );

/* Checkout: simpler fields for Bangladesh. */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	unset( $fields['billing']['billing_company'], $fields['billing']['billing_address_2'], $fields['shipping']['shipping_company'], $fields['shipping']['shipping_address_2'] );
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['priority'] = 25;
		$fields['billing']['billing_phone']['label']    = __( 'Mobile number', 'shokher' );
	}
	if ( isset( $fields['billing']['billing_address_1'] ) ) {
		$fields['billing']['billing_address_1']['label']       = __( 'Full address', 'shokher' );
		$fields['billing']['billing_address_1']['placeholder'] = __( 'House, road, area, thana', 'shokher' );
	}
	if ( isset( $fields['billing']['billing_postcode'] ) ) {
		$fields['billing']['billing_postcode']['required'] = false;
	}
	return $fields;
} );
