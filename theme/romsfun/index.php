<?php
/**
 * Fallback template.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();

romsfun_breadcrumbs();

if ( have_posts() ) : ?>
	<header class="rf-archive-head rf-card-surface">
		<h1><?php echo esc_html( wp_get_document_title() ); ?></h1>
	</header>

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

else : ?>
	<div class="rf-section rf-card-surface">
		<h1><?php esc_html_e( 'Nothing found', 'romsfun' ); ?></h1>
		<p><?php esc_html_e( 'Try a different search or browse the ROM library.', 'romsfun' ); ?></p>
		<p><a class="rf-btn" href="<?php echo esc_url( get_post_type_archive_link( 'rom' ) ); ?>"><?php esc_html_e( 'Browse all ROMs', 'romsfun' ); ?></a></p>
	</div>
<?php endif;

get_footer();
