<?php
/**
 * Shokher Kenakata theme functions.
 */
defined( 'ABSPATH' ) || exit;

define( 'SHOKHER_VER', '1.0.0' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/woocommerce.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'shokher', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 120, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 400,
		'single_image_width'    => 700,
		'product_grid'          => array( 'default_columns' => 5, 'min_columns' => 2, 'max_columns' => 6 ),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Category Bar (Desktop)', 'shokher' ),
		'mobile'  => __( 'Mobile Menu', 'shokher' ),
		'footer'  => __( 'Footer – Company', 'shokher' ),
	) );
} );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name'          => __( 'Shop Sidebar', 'shokher' ),
		'id'            => 'shop-sidebar',
		'before_widget' => '<section id="%1$s" class="sk-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="sk-widget-title">',
		'after_title'   => '</h3>',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'shokher-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'shokher-main', get_template_directory_uri() . '/assets/css/main.css', array(), (string) filemtime( get_template_directory() . '/assets/css/main.css' ) );
	$c = shokher_colors();
	wp_add_inline_style( 'shokher-main', sprintf(
		':root{--sk-primary:%1$s;--sk-dark:%2$s;--sk-accent:%3$s;}',
		esc_attr( $c['primary'] ), esc_attr( $c['dark'] ), esc_attr( $c['accent'] )
	) );
	wp_enqueue_script( 'shokher-main', get_template_directory_uri() . '/assets/js/main.js', array(), (string) filemtime( get_template_directory() . '/assets/js/main.js' ), true );
	wp_localize_script( 'shokher-main', 'shokherData', array(
		'cartUrl' => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
	) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
} );

add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	$c = shokher_colors();
	echo '<meta name="theme-color" content="' . esc_attr( $c['primary'] ) . '">' . "\n";
}, 1 );

/** Excerpt length for blog. */
add_filter( 'excerpt_length', function () {
	return 24;
} );
