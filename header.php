<?php
/**
 * Site header: logo, big search, hotline, account, cart + category bar.
 */
defined( 'ABSPATH' ) || exit;
$hotline   = shokher_opt( 'hotline' );
$has_wc    = class_exists( 'WooCommerce' );
$cart_url  = $has_wc ? wc_get_cart_url() : '#';
$acct_url  = $has_wc ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$shop_url  = $has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$logged_in = is_user_logged_in();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text sk-skip" href="#sk-main"><?php esc_html_e( 'Skip to content', 'shokher' ); ?></a>

<header class="sk-header" id="sk-header">
	<div class="sk-header-main">
		<div class="sk-container sk-header-row">
			<button class="sk-icon-btn sk-only-mobile" type="button" data-sk-open="sk-drawer" aria-label="<?php esc_attr_e( 'Open menu', 'shokher' ); ?>"><?php echo shokher_icon( 'menu', 24 ); // phpcs:ignore ?></button>

			<div class="sk-logo"><?php shokher_logo(); ?></div>

			<form class="sk-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="sk-s"><?php esc_html_e( 'Search products', 'shokher' ); ?></label>
				<input id="sk-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search in Shokher Kenakata…', 'shokher' ); ?>">
				<?php if ( $has_wc ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
				<button type="submit"><?php echo shokher_icon( 'search', 18 ); // phpcs:ignore ?><span><?php esc_html_e( 'Search', 'shokher' ); ?></span></button>
			</form>

			<div class="sk-header-actions">
				<?php if ( $hotline ) : ?>
					<a class="sk-hotline" href="<?php echo esc_attr( shokher_tel( $hotline ) ); ?>">
						<span class="sk-hotline-ic"><?php echo shokher_icon( 'phone', 18 ); // phpcs:ignore ?></span>
						<span class="sk-hotline-txt"><small><?php esc_html_e( 'Hotline', 'shokher' ); ?></small><b><?php echo esc_html( $hotline ); ?></b></span>
					</a>
				<?php endif; ?>
				<a class="sk-icon-btn sk-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'shokher' ); ?>">
					<?php echo shokher_icon( 'bag', 24 ); // phpcs:ignore ?>
					<span class="sk-cart-count"><?php echo (int) shokher_cart_count(); ?></span>
				</a>
				<a class="sk-login-btn sk-only-desktop" href="<?php echo esc_url( $acct_url ); ?>">
					<?php echo shokher_icon( 'user', 18 ); // phpcs:ignore ?>
					<span><?php echo $logged_in ? esc_html__( 'My Account', 'shokher' ) : esc_html__( 'Login / Register', 'shokher' ); ?></span>
				</a>
				<a class="sk-icon-btn sk-only-mobile" href="<?php echo esc_url( $acct_url ); ?>" aria-label="<?php esc_attr_e( 'Account', 'shokher' ); ?>"><?php echo shokher_icon( 'user', 24 ); // phpcs:ignore ?></a>
			</div>
		</div>
	</div>

	<nav class="sk-catbar" aria-label="<?php esc_attr_e( 'Categories', 'shokher' ); ?>">
		<div class="sk-container sk-catbar-row">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'sk-catmenu',
					'depth'          => 2,
					'fallback_cb'    => false,
				) );
			} elseif ( taxonomy_exists( 'product_cat' ) ) {
				echo '<ul class="sk-catmenu">';
				foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'menu_order', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ) as $t ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $t ) ), esc_html( $t->name ) );
				}
				echo '</ul>';
			}
			?>
			<a class="sk-flash-btn" href="<?php echo esc_url( home_url( shokher_opt( 'flash_link' ) ) ); ?>"><?php echo shokher_icon( 'flash', 16 ); // phpcs:ignore ?><?php echo esc_html( shokher_opt( 'flash_label' ) ); ?></a>
		</div>
	</nav>

	<form class="sk-search sk-search-mobile" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'shokher' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'shokher' ); ?>">
		<?php if ( $has_wc ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
		<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'shokher' ); ?>"><?php echo shokher_icon( 'search', 18 ); // phpcs:ignore ?></button>
	</form>
</header>

<!-- Mobile drawer -->
<div class="sk-drawer" id="sk-drawer" aria-hidden="true">
	<div class="sk-drawer-panel" role="dialog" aria-label="<?php esc_attr_e( 'Menu', 'shokher' ); ?>">
		<div class="sk-drawer-head">
			<div class="sk-logo"><?php shokher_logo(); ?></div>
			<button class="sk-icon-btn" type="button" data-sk-close aria-label="<?php esc_attr_e( 'Close menu', 'shokher' ); ?>"><?php echo shokher_icon( 'close', 24 ); // phpcs:ignore ?></button>
		</div>
		<?php
		wp_nav_menu( array(
			'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
			'container'      => false,
			'menu_class'     => 'sk-drawer-menu',
			'depth'          => 2,
			'fallback_cb'    => false,
		) );
		?>
		<div class="sk-drawer-foot">
			<?php if ( $hotline ) : ?><a href="<?php echo esc_attr( shokher_tel( $hotline ) ); ?>"><?php echo shokher_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( $hotline ); ?></a><?php endif; ?>
			<?php if ( shokher_opt( 'facebook' ) ) : ?><a href="<?php echo esc_url( shokher_opt( 'facebook' ) ); ?>" target="_blank" rel="noopener"><?php echo shokher_icon( 'facebook', 18 ); // phpcs:ignore ?> Facebook</a><?php endif; ?>
		</div>
	</div>
	<div class="sk-drawer-bg" data-sk-close></div>
</div>

<main id="sk-main" class="sk-main">
