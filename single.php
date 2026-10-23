<?php
/**
 * Single post.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sk-container sk-content sk-narrow">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'sk-page' ); ?>>
			<header class="sk-page-head"><h1><?php the_title(); ?></h1><p class="sk-meta"><?php echo esc_html( get_the_date() ); ?></p></header>
			<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
			<div class="sk-entry"><?php the_content(); ?></div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
