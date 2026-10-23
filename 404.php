<?php
/**
 * Not found.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sk-container sk-content sk-404">
	<h1>404</h1>
	<p><?php esc_html_e( 'Sorry, this page could not be found.', 'shokher' ); ?></p>
	<a class="sk-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'shokher' ); ?></a>
</div>
<?php
get_footer();
