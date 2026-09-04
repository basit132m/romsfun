<?php
/**
 * Emulator archive.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();

romsfun_breadcrumbs();

$total = (int) $GLOBALS['wp_query']->found_posts;
?>

<header class="rf-archive-head rf-card-surface">
	<h1><?php esc_html_e( 'Emulators', 'romsfun' ); ?></h1>
	<p class="rf-count">
		<?php
		printf(
			esc_html( _n( '%s emulator available', '%s emulators available', $total, 'romsfun' ) ),
			esc_html( number_format_i18n( $total ) )
		);
		?>
	</p>
</header>

<?php if ( have_posts() ) : ?>

	<div class="rf-grid rf-grid--wide">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="rf-card">
				<a class="rf-card__media rf-card__media--square" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'rom-shot', array( 'loading' => 'lazy', 'decoding' => 'async' ) );
					} else {
						echo '<span class="rf-card__placeholder"></span>';
					}
					?>
				</a>
				<div class="rf-card__body">
					<h3 class="rf-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php romsfun_term_pills( get_the_ID(), array( 'console', 'platform' ), 3 ); ?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php
	echo '<nav class="rf-pagination" aria-label="' . esc_attr__( 'Pagination', 'romsfun' ) . '">';
	the_posts_pagination( array( 'mid_size' => 2, 'type' => 'list' ) );
	echo '</nav>';
	?>

<?php else : ?>
	<div class="rf-section rf-card-surface">
		<p><?php esc_html_e( 'No emulators published yet.', 'romsfun' ); ?></p>
	</div>
<?php endif;

get_footer();
