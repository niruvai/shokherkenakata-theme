<?php
/**
 * Small template helpers and inline SVG icons.
 */
defined( 'ABSPATH' ) || exit;

function shokher_icon( $name, $size = 20 ) {
	$p = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L22 7H6"/>',
		'bag'      => '<path d="M6 7h12l1 14H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
		'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'home'     => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
		'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'flash'    => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
		'truck'    => '<path d="M1 4h14v12H1zM15 9h4l3 3v4h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
		'shield'   => '<path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/>',
		'cash'     => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/>',
		'refresh'  => '<path d="M21 12a9 9 0 0 1-15.5 6.2L3 16M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/>',
		'close'    => '<path d="M18 6 6 18M6 6l12 12"/>',
		'left'     => '<path d="m15 18-6-6 6-6"/>',
		'right'    => '<path d="m9 18 6-6-6-6"/>',
		'up'       => '<path d="m18 15-6-6-6 6"/>',
		'order'    => '<path d="M9 3h6l1 2h3v16H5V5h3z"/><path d="M9 12h6M9 16h4"/>',
		'facebook' => '<path d="M14 9h3V5h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V9.5a.5.5 0 0 1 .5-.5z"/>',
		'whatsapp' => '<path d="M3 21l1.7-5A9 9 0 1 1 8 19.4z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.3-1.8-1-1 .8a4 4 0 0 1-2.4-2.4l.8-1-1-1.8z"/>',
	);
	if ( ! isset( $p[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="sk-ic sk-ic-%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$p[ $name ]
	);
}

function shokher_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="custom-logo-link" href="%1$s" rel="home"><img class="custom-logo" src="%2$s" alt="%3$s" width="120" height="120"></a>',
		esc_url( home_url( '/' ) ),
		esc_url( get_template_directory_uri() . '/assets/img/logo.png' ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

function shokher_cart_count() {
	return ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
}

function shokher_cart_total() {
	return ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_total() : '';
}

function shokher_tel( $num ) {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', $num );
}

/** Top-level product categories used for homepage rows (customizable). */
function shokher_home_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$slugs = array_filter( array_map( 'trim', explode( ',', (string) shokher_opt( 'home_cats' ) ) ) );
	if ( $slugs ) {
		$out = array();
		foreach ( $slugs as $s ) {
			$t = get_term_by( 'slug', $s, 'product_cat' );
			if ( $t ) {
				$out[] = $t;
			}
		}
		return $out;
	}
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => true,
		'orderby'    => 'menu_order',
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	) );
	return is_wp_error( $terms ) ? array() : $terms;
}
