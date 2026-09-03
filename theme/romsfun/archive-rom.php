<?php
/**
 * ROM archive and taxonomy hubs.
 *
 * Taxonomy archives fall through to this file, so /console/psp/ and /collection/god-of-war/ get
 * the same treatment as /roms/ — each is an indexable hub page, not a bare list.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();

romsfun_breadcrumbs();

$term        = is_tax() ? get_queried_object() : null;
$total       = (int) $GLOBALS['wp_query']->found_posts;
$description = $term ? term_description( $term ) : '';
?>

<header class="rf-archive-head rf-card-surface">
	<h1><?php echo esc_html( $term ? $term->name : __( 'All ROMs', 'romsfun' ) ); ?></h1>

	<p class="rf-count">
		<?php
		printf(
			esc_html( _n( '%s ROM available', '%s ROMs available', $total, 'romsfun' ) ),
			esc_html( number_format_i18n( $total ) )
		);
		?>
	</p>

	<?php if ( $description ) : ?>
		<div class="rf-prose" style="margin-top:12px"><?php echo wp_kses_post( $description ); ?></div>
	<?php endif; ?>
</header>

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
	the_posts_pagination(
		array(
			'mid_size'  => 2,
			'type'      => 'list',
			'prev_text' => __( 'Previous', 'romsfun' ),
			'next_text' => __( 'Next', 'romsfun' ),
		)
	);
	echo '</nav>';
	?>

<?php else : ?>

	<div class="rf-section rf-card-surface">
		<p><?php esc_html_e( 'No ROMs here yet.', 'romsfun' ); ?></p>
	</div>

<?php endif;

get_footer();
