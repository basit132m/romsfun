<?php
/**
 * Homepage.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<?php
$hero_image = romsfun_get_option( 'hero_image' );

if ( $hero_image ) :
	?>
	<div class="rf-hero__media">
		<?php
		/*
		 * The hero is the largest paint on this page, so it is a real <img> loaded eagerly with
		 * high priority rather than a CSS background. Background images are discovered late — only
		 * once the stylesheet has parsed — and cannot be prioritised, which directly costs LCP.
		 */
		?>
		<img
			src="<?php echo esc_url( $hero_image ); ?>"
			alt=""
			width="1920"
			height="520"
			fetchpriority="high"
			decoding="async"
		>
	</div>
<?php endif; ?>

<section class="rf-hero__panel rf-card-surface<?php echo $hero_image ? ' rf-hero__panel--overlap' : ''; ?>">

	<?php if ( romsfun_get_option( 'hero_eyebrow' ) ) : ?>
		<p class="rf-eyebrow"><?php echo esc_html( romsfun_get_option( 'hero_eyebrow' ) ); ?></p>
	<?php endif; ?>

	<h1 class="rf-hero__title">
		<?php
		echo esc_html( romsfun_get_option( 'hero_title_before' ) );

		if ( romsfun_get_option( 'hero_title_highlight' ) ) {
			printf( ' <span class="rf-hl">%s</span> ', esc_html( romsfun_get_option( 'hero_title_highlight' ) ) );
		}

		echo esc_html( romsfun_get_option( 'hero_title_after' ) );
		?>
	</h1>

	<?php if ( romsfun_get_option( 'hero_subtitle' ) ) : ?>
		<p class="rf-hero__sub"><?php echo esc_html( romsfun_get_option( 'hero_subtitle' ) ); ?></p>
	<?php endif; ?>

	<?php romsfun_search_filters(); ?>
</section>

<?php
$announcement = romsfun_get_option( 'announcement_text' );

if ( romsfun_get_option( 'announcement_enabled' ) && $announcement ) :
	?>
	<section class="rf-section rf-card-surface rf-announcement" aria-labelledby="rf-announcement-title">
		<?php if ( romsfun_get_option( 'announcement_label' ) ) : ?>
			<p class="rf-eyebrow"><?php echo esc_html( romsfun_get_option( 'announcement_label' ) ); ?></p>
		<?php endif; ?>

		<h2 id="rf-announcement-title"><?php echo esc_html( romsfun_get_option( 'announcement_title' ) ); ?></h2>

		<div class="rf-prose"><?php echo wp_kses_post( wpautop( $announcement ) ); ?></div>
	</section>
<?php endif; ?>

<?php
$trending = romsfun_home_query( 'popular', (int) romsfun_get_option( 'trending_count' ) );

if ( $trending->have_posts() ) :
	?>
	<section class="rf-block" aria-labelledby="rf-trending-title">
		<div class="rf-block__head">
			<div>
				<p class="rf-eyebrow"><?php esc_html_e( 'Trending', 'romsfun' ); ?></p>
				<h2 id="rf-trending-title"><?php echo esc_html( romsfun_get_option( 'trending_title' ) ); ?></h2>
			</div>
			<a class="rf-btn rf-btn--sm" href="<?php echo esc_url( add_query_arg( 'sort', 'popular', get_post_type_archive_link( 'rom' ) ) ); ?>">
				<?php esc_html_e( 'More', 'romsfun' ); ?> »
			</a>
		</div>

		<div class="rf-rows">
			<?php
			while ( $trending->have_posts() ) :
				$trending->the_post();
				romsfun_rom_row( get_the_ID() );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<?php
$latest = romsfun_home_query( 'latest', (int) romsfun_get_option( 'latest_count' ) );

if ( $latest->have_posts() ) :
	?>
	<section class="rf-block" aria-labelledby="rf-latest-title">
		<div class="rf-block__head">
			<div>
				<p class="rf-eyebrow"><?php esc_html_e( 'Fresh Upload', 'romsfun' ); ?></p>
				<h2 id="rf-latest-title"><?php echo esc_html( romsfun_get_option( 'latest_title' ) ); ?></h2>
			</div>
			<a class="rf-btn rf-btn--sm" href="<?php echo esc_url( get_post_type_archive_link( 'rom' ) ); ?>">
				<?php esc_html_e( 'More', 'romsfun' ); ?> »
			</a>
		</div>

		<div class="rf-grid">
			<?php
			while ( $latest->have_posts() ) :
				$latest->the_post();
				romsfun_rom_card( get_the_ID() );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
