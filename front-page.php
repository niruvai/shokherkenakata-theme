<?php
/**
 * Homepage: slider, trust strip, category chips and category product rows.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$has_wc = class_exists( 'WooCommerce' );
$count  = max( 4, (int) shokher_opt( 'home_count' ) );

/* ---------- Slider ---------- */
$slides = array();
for ( $i = 1; $i <= 3; $i++ ) {
	$img = get_theme_mod( "sk_slide{$i}_img" );
	if ( $img ) {
		$slides[] = array( 'img' => $img, 'link' => get_theme_mod( "sk_slide{$i}_link" ) );
	}
}
if ( ! $slides ) {
	$base   = get_template_directory_uri() . '/assets/img/';
	$shop   = $has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$slides = array(
		array( 'img' => $base . 'banner-1.jpg', 'link' => $shop ),
		array( 'img' => $base . 'banner-2.jpg', 'link' => home_url( '/product-category/electronics-gadgets/' ) ),
		array( 'img' => $base . 'banner-3.jpg', 'link' => home_url( '/product-category/beauty-cosmetics/' ) ),
	);
}
?>
<section class="sk-container sk-hero-wrap">
	<div class="sk-slider" data-sk-slider>
		<div class="sk-slides">
			<?php foreach ( $slides as $n => $s ) : ?>
				<a class="sk-slide<?php echo 0 === $n ? ' is-active' : ''; ?>" href="<?php echo esc_url( $s['link'] ? $s['link'] : '#' ); ?>">
					<img src="<?php echo esc_url( $s['img'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) . ' offer ' . ( $n + 1 ) ); ?>" <?php echo 0 === $n ? 'fetchpriority="high"' : 'loading="lazy"'; ?> width="1600" height="600">
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $slides ) > 1 ) : ?>
			<button class="sk-sl-prev" type="button" aria-label="<?php esc_attr_e( 'Previous slide', 'shokher' ); ?>"><?php echo shokher_icon( 'left', 22 ); // phpcs:ignore ?></button>
			<button class="sk-sl-next" type="button" aria-label="<?php esc_attr_e( 'Next slide', 'shokher' ); ?>"><?php echo shokher_icon( 'right', 22 ); // phpcs:ignore ?></button>
			<div class="sk-dots">
				<?php foreach ( $slides as $n => $s ) : ?>
					<button type="button" class="<?php echo 0 === $n ? 'is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( 'Slide %d', $n + 1 ) ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="sk-container sk-trust-strip">
	<div><?php echo shokher_icon( 'shield', 26 ); // phpcs:ignore ?><p><b>100% Genuine</b><small>Original products only</small></p></div>
	<div><?php echo shokher_icon( 'truck', 26 ); // phpcs:ignore ?><p><b>Fast Delivery</b><small>All over Bangladesh</small></p></div>
	<div><?php echo shokher_icon( 'cash', 26 ); // phpcs:ignore ?><p><b>Cash on Delivery</b><small>Pay when you receive</small></p></div>
	<div><?php echo shokher_icon( 'refresh', 26 ); // phpcs:ignore ?><p><b>Easy Exchange</b><small>Within 3 days</small></p></div>
</section>

<?php
if ( ! $has_wc ) {
	get_footer();
	return;
}

$cats = shokher_home_categories();

/* ---------- Category chips ---------- */
if ( $cats ) :
	?>
	<section class="sk-container sk-section">
		<div class="sk-sec-head"><h2><?php esc_html_e( 'Shop by Category', 'shokher' ); ?></h2></div>
		<div class="sk-chips">
			<?php
			foreach ( $cats as $t ) :
				$thumb = get_term_meta( $t->term_id, 'thumbnail_id', true );
				?>
				<a class="sk-chip" href="<?php echo esc_url( get_term_link( $t ) ); ?>">
					<span class="sk-chip-img">
						<?php
						if ( $thumb ) {
							echo wp_get_attachment_image( $thumb, 'thumbnail' );
						} else {
							echo '<span class="sk-chip-letter">' . esc_html( mb_substr( $t->name, 0, 1 ) ) . '</span>';
						}
						?>
					</span>
					<span class="sk-chip-name"><?php echo esc_html( $t->name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endif;

/** Render one horizontal product row. */
$render_row = function ( $title, $products, $more_url ) {
	if ( ! $products ) {
		return;
	}
	?>
	<section class="sk-container sk-section">
		<div class="sk-sec-head">
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php if ( $more_url ) : ?><a class="sk-more" href="<?php echo esc_url( $more_url ); ?>"><?php esc_html_e( 'View all', 'shokher' ); ?> <?php echo shokher_icon( 'right', 14 ); // phpcs:ignore ?></a><?php endif; ?>
		</div>
		<div class="sk-rail" data-sk-rail>
			<button class="sk-rail-prev" type="button" aria-label="<?php esc_attr_e( 'Scroll left', 'shokher' ); ?>"><?php echo shokher_icon( 'left', 20 ); // phpcs:ignore ?></button>
			<ul class="products sk-row">
				<?php
				foreach ( $products as $p ) {
					$GLOBALS['post'] = get_post( $p->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $GLOBALS['post'] );
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
				?>
			</ul>
			<button class="sk-rail-next" type="button" aria-label="<?php esc_attr_e( 'Scroll right', 'shokher' ); ?>"><?php echo shokher_icon( 'right', 20 ); // phpcs:ignore ?></button>
		</div>
	</section>
	<?php
};

/* ---------- Flash sale row ---------- */
if ( get_theme_mod( 'sk_home_flash', true ) ) {
	$sale_ids = wc_get_product_ids_on_sale();
	if ( $sale_ids ) {
		$render_row(
			shokher_opt( 'flash_label' ),
			wc_get_products( array( 'include' => $sale_ids, 'limit' => $count, 'status' => 'publish', 'visibility' => 'catalog', 'orderby' => 'date', 'order' => 'DESC' ) ),
			home_url( shokher_opt( 'flash_link' ) )
		);
	}
}

/* ---------- One row per category ---------- */
foreach ( $cats as $t ) {
	$render_row(
		$t->name,
		wc_get_products( array( 'category' => array( $t->slug ), 'limit' => $count, 'status' => 'publish', 'visibility' => 'catalog', 'orderby' => 'date', 'order' => 'DESC' ) ),
		get_term_link( $t )
	);
}

/* ---------- Page content (optional extra blocks) ---------- */
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$content = trim( get_the_content() );
		if ( $content && false === strpos( $content, 'sk-hero' ) ) {
			echo '<section class="sk-container sk-section sk-page-content">';
			the_content();
			echo '</section>';
		}
	}
}

$hotline = shokher_opt( 'hotline' );
?>
<section class="sk-container sk-cta">
	<div><b><?php esc_html_e( 'Need help ordering?', 'shokher' ); ?></b> <?php esc_html_e( 'Call or WhatsApp us', 'shokher' ); ?> <?php if ( $hotline ) : ?><a href="<?php echo esc_attr( shokher_tel( $hotline ) ); ?>"><?php echo esc_html( $hotline ); ?></a><?php endif; ?></div>
	<?php if ( shokher_opt( 'facebook' ) ) : ?><a class="sk-btn sk-btn-dark" href="<?php echo esc_url( shokher_opt( 'facebook' ) ); ?>" target="_blank" rel="noopener"><?php echo shokher_icon( 'facebook', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Message on Facebook', 'shokher' ); ?></a><?php endif; ?>
</section>
<?php
get_footer();
