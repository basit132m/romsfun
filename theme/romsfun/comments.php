<?php
/**
 * Comments.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

// Never expose comments on a password-protected post before the password is entered.
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="rf-comments">

	<h2>
		<?php
		$count = (int) get_comments_number();

		if ( $count ) {
			printf(
				esc_html( _n( '%s Comment', '%s Comments', $count, 'romsfun' ) ),
				esc_html( number_format_i18n( $count ) )
			);
		} else {
			esc_html_e( 'Comments', 'romsfun' );
		}
		?>
	</h2>

	<?php if ( have_comments() ) : ?>
		<ol class="rf-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => __( 'Previous', 'romsfun' ),
				'next_text' => __( 'Next', 'romsfun' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="rf-comments__closed"><?php esc_html_e( 'Comments are closed.', 'romsfun' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_form'         => 'rf-comment-form',
			'title_reply'        => __( 'Leave a comment', 'romsfun' ),
			'title_reply_before' => '<h3 class="rf-comment-form__title">',
			'title_reply_after'  => '</h3>',
			'comment_notes_before' => '<p class="rf-comment-form__notes">' . esc_html__( 'Your email address will not be published.', 'romsfun' ) . '</p>',
			'class_submit'       => 'rf-btn',
		)
	);
	?>
</div>
