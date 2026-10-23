<?php
/**
 * Comments.
 */
defined( 'ABSPATH' ) || exit;
if ( post_password_required() ) {
	return;
}
?>
<section class="sk-comments">
	<?php if ( have_comments() ) : ?>
		<h2><?php comments_number(); ?></h2>
		<ol class="sk-comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?></ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
