<?php
/**
 * Search results.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();

romsfun_breadcrumbs();

$total = (int) $GLOBALS['wp_query']->found_posts;
?>

<header class="rf-archive-head rf-card-surface">
	<h1>
		<?php
		printf(
			/* translators: %s: search term */
			esc_html__( 'Search results for “%s”', 'romsfun' ),
			esc_html( get_search_query() )
		);
		?>
	</h1>

	<p class="rf-count">
		<?php
		printf(
			esc_html( _n( '%s result', '%s results', $total, 'romsfun' ) ),
			esc_html( number_format_i18n( $total ) )
		);
		?>
	</p>
</header>

<section class="rf-section rf-card-surface">
	<?php romsfun_search_filters( false ); ?>
</section>

<?php if ( have_posts() ) : ?>

	<div class="rf-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			romsfun_rom_card( get_the_ID() );
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
		<p><?php esc_html_e( 'No ROMs matched that search. Try a different term or clear the filters.', 'romsfun' ); ?></p>
		<p><a class="rf-btn" href="<?php echo esc_url( get_post_type_archive_link( 'rom' ) ); ?>"><?php esc_html_e( 'Browse all ROMs', 'romsfun' ); ?></a></p>
	</div>

<?php endif;

get_footer();
