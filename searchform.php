<form role="search" method="get" class="sk-search sk-search-inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'shokher' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'shokher' ); ?>">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
	<button type="submit"><?php esc_html_e( 'Search', 'shokher' ); ?></button>
</form>
