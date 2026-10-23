<?php
/**
 * Customizer options: Appearance → Customize → "Shokher Theme".
 */
defined( 'ABSPATH' ) || exit;

function shokher_defaults() {
	return array(
		'color_primary'  => '#FFC107',
		'color_dark'     => '#111111',
		'color_accent'   => '#E53935',
		'hotline'        => '01742851043',
		'whatsapp'       => '8801742851043',
		'facebook'       => 'https://www.facebook.com/ShokherKenakataa',
		'top_notice'     => 'Cash on Delivery all over Bangladesh · Delivery ৳80 inside Dhaka, ৳150 outside',
		'flash_label'    => 'Flash Sale',
		'flash_link'     => '/shop/?on_sale=1',
		'home_cats'      => '',
		'home_count'     => 10,
		'footer_about'   => 'Your easy destination for everything you love. Genuine products, fast delivery and Cash on Delivery all over Bangladesh.',
		'footer_address' => 'Bangladesh',
		'footer_email'   => '',
		'copyright'      => '© {year} Shokher Kenakata. All rights reserved.',
	);
}

function shokher_opt( $key ) {
	$d = shokher_defaults();
	return get_theme_mod( 'sk_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

function shokher_colors() {
	return array(
		'primary' => shokher_opt( 'color_primary' ),
		'dark'    => shokher_opt( 'color_dark' ),
		'accent'  => shokher_opt( 'color_accent' ),
	);
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = shokher_defaults();

	$wp->add_panel( 'shokher', array( 'title' => __( 'Shokher Theme', 'shokher' ), 'priority' => 20 ) );

	$sections = array(
		'sk_general' => __( 'Colors & Contact', 'shokher' ),
		'sk_slider'  => __( 'Homepage Slider', 'shokher' ),
		'sk_home'    => __( 'Homepage Sections', 'shokher' ),
		'sk_footer'  => __( 'Footer', 'shokher' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp->add_section( $id, array( 'title' => $title, 'panel' => 'shokher' ) );
	}

	foreach ( array( 'color_primary' => 'Primary (brand) color', 'color_dark' => 'Dark color', 'color_accent' => 'Accent / price color' ) as $k => $label ) {
		$wp->add_setting( 'sk_' . $k, array( 'default' => $d[ $k ], 'sanitize_callback' => 'sanitize_hex_color' ) );
		$wp->add_control( new WP_Customize_Color_Control( $wp, 'sk_' . $k, array( 'label' => $label, 'section' => 'sk_general' ) ) );
	}

	$text = array(
		'hotline'        => array( 'sk_general', 'Hotline number' ),
		'whatsapp'       => array( 'sk_general', 'WhatsApp number (with 880, digits only)' ),
		'facebook'       => array( 'sk_general', 'Facebook page URL' ),
		'top_notice'     => array( 'sk_general', 'Notice text (under search on mobile, hero area)' ),
		'flash_label'    => array( 'sk_general', 'Category bar button label' ),
		'flash_link'     => array( 'sk_general', 'Category bar button link' ),
		'home_cats'      => array( 'sk_home', 'Category slugs to show on homepage (comma separated, empty = all main categories)' ),
		'footer_about'   => array( 'sk_footer', 'About text' ),
		'footer_address' => array( 'sk_footer', 'Address' ),
		'footer_email'   => array( 'sk_footer', 'Email' ),
		'copyright'      => array( 'sk_footer', 'Copyright ({year} is replaced)' ),
	);
	foreach ( $text as $k => $cfg ) {
		$wp->add_setting( 'sk_' . $k, array( 'default' => $d[ $k ], 'sanitize_callback' => 'wp_kses_post' ) );
		$wp->add_control( 'sk_' . $k, array(
			'label'   => $cfg[1],
			'section' => $cfg[0],
			'type'    => in_array( $k, array( 'footer_about', 'top_notice' ), true ) ? 'textarea' : 'text',
		) );
	}

	$wp->add_setting( 'sk_home_count', array( 'default' => 10, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( 'sk_home_count', array( 'label' => 'Products per row section', 'section' => 'sk_home', 'type' => 'number' ) );

	$wp->add_setting( 'sk_home_flash', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
	$wp->add_control( 'sk_home_flash', array( 'label' => 'Show "Flash Sale" (discounted products) section first', 'section' => 'sk_home', 'type' => 'checkbox' ) );

	for ( $i = 1; $i <= 3; $i++ ) {
		$wp->add_setting( "sk_slide{$i}_img", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp->add_control( new WP_Customize_Image_Control( $wp, "sk_slide{$i}_img", array(
			'label'       => sprintf( 'Slide %d image (recommended 1600×600)', $i ),
			'section'     => 'sk_slider',
			'description' => $i === 1 ? 'Leave empty to use the built-in banners.' : '',
		) ) );
		$wp->add_setting( "sk_slide{$i}_link", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp->add_control( "sk_slide{$i}_link", array( 'label' => sprintf( 'Slide %d link', $i ), 'section' => 'sk_slider', 'type' => 'url' ) );
	}
} );
