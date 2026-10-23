<?php
/**
 * Pages (also cart, checkout, my account).
 */
defined( 'ABSPATH' ) || exit;
get_header();
$is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="sk-container sk-content<?php echo $is_wc_page ? ' sk-wc-page' : ''; ?>">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'sk-page' ); ?>>
			<header class="sk-page-head"><h1><?php the_title(); ?></h1></header>
			<div class="sk-entry"><?php the_content(); ?></div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
