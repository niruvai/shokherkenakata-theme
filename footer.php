<?php
/**
 * Footer, floating cart and mobile bottom navigation.
 */
defined( 'ABSPATH' ) || exit;
$has_wc   = class_exists( 'WooCommerce' );
$hotline  = shokher_opt( 'hotline' );
$fb       = shokher_opt( 'facebook' );
$wa       = shokher_opt( 'whatsapp' );
$email    = shokher_opt( 'footer_email' );
$cart_url = $has_wc ? wc_get_cart_url() : '#';
$acct_url = $has_wc ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$track    = get_page_by_path( 'track-order' );
?>
</main>

<footer class="sk-footer">
	<div class="sk-container sk-footer-grid">
		<div class="sk-footer-col sk-footer-about">
			<div class="sk-logo sk-footer-logo"><?php shokher_logo(); ?></div>
			<p><?php echo wp_kses_post( shokher_opt( 'footer_about' ) ); ?></p>
			<ul class="sk-contact">
				<?php if ( shokher_opt( 'footer_address' ) ) : ?><li><?php echo shokher_icon( 'home', 16 ); // phpcs:ignore ?><span><?php echo esc_html( shokher_opt( 'footer_address' ) ); ?></span></li><?php endif; ?>
				<?php if ( $hotline ) : ?><li><?php echo shokher_icon( 'phone', 16 ); // phpcs:ignore ?><a href="<?php echo esc_attr( shokher_tel( $hotline ) ); ?>"><?php echo esc_html( $hotline ); ?></a></li><?php endif; ?>
				<?php if ( $email ) : ?><li><?php echo shokher_icon( 'order', 16 ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
			</ul>
			<div class="sk-social">
				<?php if ( $fb ) : ?><a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo shokher_icon( 'facebook', 18 ); // phpcs:ignore ?></a><?php endif; ?>
				<?php if ( $wa ) : ?><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $wa ) ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php echo shokher_icon( 'whatsapp', 18 ); // phpcs:ignore ?></a><?php endif; ?>
			</div>
		</div>

		<div class="sk-footer-col">
			<h4><?php esc_html_e( 'Company', 'shokher' ); ?></h4>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'sk-footer-links',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</div>

		<div class="sk-footer-col">
			<h4><?php esc_html_e( 'Shop By', 'shokher' ); ?></h4>
			<ul class="sk-footer-links">
				<?php
				if ( taxonomy_exists( 'product_cat' ) ) {
					$cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'menu_order', 'number' => 8, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
					foreach ( (array) $cats as $t ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $t ) ), esc_html( $t->name ) );
					}
				}
				?>
			</ul>
		</div>

		<div class="sk-footer-col">
			<h4><?php esc_html_e( 'Account', 'shokher' ); ?></h4>
			<ul class="sk-footer-links">
				<li><a href="<?php echo esc_url( $acct_url ); ?>"><?php esc_html_e( 'My account', 'shokher' ); ?></a></li>
				<?php if ( $has_wc ) : ?>
					<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'My orders', 'shokher' ); ?></a></li>
				<?php endif; ?>
				<?php if ( $track ) : ?><li><a href="<?php echo esc_url( get_permalink( $track ) ); ?>"><?php esc_html_e( 'Track my order', 'shokher' ); ?></a></li><?php endif; ?>
				<li><a href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'Cart', 'shokher' ); ?></a></li>
			</ul>
			<div class="sk-pay">
				<span>COD</span><span>bKash</span><span>Nagad</span>
			</div>
		</div>
	</div>
	<div class="sk-footer-bottom">
		<div class="sk-container"><?php echo wp_kses_post( str_replace( '{year}', gmdate( 'Y' ), shokher_opt( 'copyright' ) ) ); ?></div>
	</div>
</footer>

<?php if ( $has_wc && ! is_cart() && ! is_checkout() ) : ?>
<a class="sk-float-cart" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'View cart', 'shokher' ); ?>">
	<span class="sk-float-top"><?php echo shokher_icon( 'bag', 22 ); // phpcs:ignore ?><span class="sk-float-count"><?php $c = shokher_cart_count(); echo (int) $c . ' ' . esc_html( _n( 'Item', 'Items', $c, 'shokher' ) ); ?></span></span>
	<span class="sk-float-total"><?php echo wp_kses_post( shokher_cart_total() ); ?></span>
</a>
<?php endif; ?>

<?php if ( $wa ) : ?>
<a class="sk-wa" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $wa ) ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php echo shokher_icon( 'whatsapp', 26 ); // phpcs:ignore ?></a>
<?php endif; ?>

<button class="sk-totop" type="button" aria-label="<?php esc_attr_e( 'Back to top', 'shokher' ); ?>"><?php echo shokher_icon( 'up', 20 ); // phpcs:ignore ?></button>

<nav class="sk-bottomnav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'shokher' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'is-active' : ''; ?>"><?php echo shokher_icon( 'home', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Home', 'shokher' ); ?></span></a>
	<button type="button" data-sk-open="sk-drawer"><?php echo shokher_icon( 'grid', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Menu', 'shokher' ); ?></span></button>
	<a href="<?php echo esc_url( $cart_url ); ?>" class="sk-bn-cart"><span class="sk-bn-bubble"><?php echo shokher_icon( 'cart', 24 ); // phpcs:ignore ?><span class="sk-cart-count"><?php echo (int) shokher_cart_count(); ?></span></span><span><?php esc_html_e( 'Cart', 'shokher' ); ?></span></a>
	<button type="button" data-sk-search><?php echo shokher_icon( 'search', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Search', 'shokher' ); ?></span></button>
	<a href="<?php echo esc_url( $acct_url ); ?>"><?php echo shokher_icon( 'user', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Account', 'shokher' ); ?></span></a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
