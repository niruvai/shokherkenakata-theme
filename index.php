<?php
/**
 * Fallback template (blog, archives, search).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sk-container sk-content">
	<header class="sk-page-head">
		<h1><?php
		if ( is_search() ) {
			printf( esc_html__( 'Search results for “%s”', 'shokher' ), esc_html( get_search_query() ) );
		} elseif ( is_archive() ) {
			the_archive_title();
		} else {
			single_post_title();
		}
		?></h1>
	</header>
	<?php if ( have_posts() ) : ?>
		<div class="sk-posts">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'sk-post-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a><?php endif; ?>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'shokher' ); ?></p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
