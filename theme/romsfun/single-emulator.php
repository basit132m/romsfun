<?php
/**
 * Single emulator.
 *
 * Mirrors the ROM page so the two read as one system, with the fields an emulator actually has:
 * licence, supported platforms and a project homepage rather than region and checksums.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id      = get_the_ID();
	$downloads    = (int) romsfun_get_field( 'download_count' );
	$bytes        = (int) romsfun_get_field( 'file_size_bytes' );
	$download_url = romsfun_get_field( 'download_url' );
	$website      = romsfun_get_field( 'website' );
	$rating       = function_exists( 'romsfun_get_rating_average' ) ? romsfun_get_rating_average( $post_id ) : 0.0;
	$rating_count = function_exists( 'romsfun_get_rating_count' ) ? romsfun_get_rating_count( $post_id ) : 0;
	$has_rated    = function_exists( 'romsfun_has_rated' ) && romsfun_has_rated( $post_id );
	?>

	<article <?php post_class(); ?>>

		<div class="rf-rom-hero">

			<div class="rf-rom-hero__art rf-card-surface">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'alt' => esc_attr( get_the_title() ) ) );
				} else {
					echo '<span class="rf-card__placeholder" style="aspect-ratio:1;border-radius:9px" aria-hidden="true"></span>';
				}
				?>
			</div>

			<div class="rf-rom-hero__body rf-card-surface">

				<?php romsfun_breadcrumbs(); ?>

				<h1 class="rf-rom-hero__title"><?php the_title(); ?></h1>

				<div class="rf-rom-facts">
					<span class="rf-rate"
						data-rf-rate="<?php echo esc_attr( $post_id ); ?>"
						data-rf-endpoint="<?php echo esc_url( rest_url( 'romsfun/v1/rate' ) ); ?>"
						data-rf-current="<?php echo esc_attr( (string) round( $rating ) ); ?>"
						data-rf-rated="<?php echo $has_rated ? '1' : '0'; ?>">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<button type="button" class="rf-rate__star<?php echo $i <= round( $rating ) ? ' is-on' : ''; ?>"
								data-rf-star="<?php echo esc_attr( (string) $i ); ?>"
								aria-label="<?php printf( esc_attr__( 'Rate %d out of 5', 'romsfun' ), (int) $i ); ?>">★</button>
						<?php endfor; ?>
					</span>

					<span>
						<strong data-rf-avg><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong>
						<span class="rf-count">(<span data-rf-count><?php echo esc_html( number_format_i18n( $rating_count ) ); ?></span>
						<?php esc_html_e( 'ratings', 'romsfun' ); ?>)</span>
					</span>

					<?php if ( $downloads ) : ?>
						<span><?php printf( esc_html__( '%s downloads', 'romsfun' ), esc_html( number_format_i18n( $downloads ) ) ); ?></span>
					<?php endif; ?>

					<?php if ( $bytes ) : ?>
						<span><?php echo esc_html( romsfun_format_bytes( $bytes ) ); ?></span>
					<?php endif; ?>
				</div>

				<?php romsfun_term_pills( $post_id, array( 'console', 'platform' ), 8 ); ?>

				<ul class="rf-specs">
					<?php
					$specs = array(
						'version'      => __( 'Version', 'romsfun' ),
						'developer'    => __( 'Developer', 'romsfun' ),
						'license'      => __( 'License', 'romsfun' ),
						'release_date' => __( 'Last Updated', 'romsfun' ),
					);

					foreach ( $specs as $field => $label ) {
						$value = romsfun_get_field( $field );

						if ( ! $value ) {
							continue;
						}

						if ( 'release_date' === $field ) {
							$value = sprintf(
								'<time datetime="%s">%s</time>',
								esc_attr( $value ),
								esc_html( date_i18n( get_option( 'date_format' ), strtotime( $value ) ) )
							);
						} else {
							$value = esc_html( $value );
						}

						printf(
							'<li><span class="rf-specs__label">%s</span><span class="rf-specs__value">%s</span></li>',
							esc_html( $label ),
							$value // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
						);
					}

					if ( $website ) {
						printf(
							'<li><span class="rf-specs__label">%s</span><span class="rf-specs__value"><a href="%s" rel="nofollow noopener" target="_blank">%s</a></span></li>',
							esc_html__( 'Official Site', 'romsfun' ),
							esc_url( $website ),
							esc_html( wp_parse_url( $website, PHP_URL_HOST ) )
						);
					}
					?>
				</ul>

				<p class="rf-rate__status" data-rf-rate-status aria-live="polite"><?php
					echo $has_rated ? esc_html__( 'You have already rated this emulator.', 'romsfun' ) : '';
				?></p>

				<?php if ( $download_url ) : ?>
					<div class="rf-rom-actions">
						<a class="rf-btn" href="<?php echo esc_url( $download_url ); ?>" rel="nofollow noopener">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/>
							</svg>
							<span class="rf-btn__label"><?php esc_html_e( 'Download Emulator', 'romsfun' ); ?></span>
						</a>
					</div>
				<?php endif; ?>

			</div>
		</div>

		<?php
		$shots = function_exists( 'romsfun_get_screenshots' ) ? romsfun_get_screenshots( $post_id ) : array();

		if ( $shots ) :
			?>
			<section class="rf-section rf-card-surface">
				<h2><?php esc_html_e( 'Screenshots', 'romsfun' ); ?></h2>
				<div class="rf-shots" data-rf-gallery>
					<?php
					foreach ( $shots as $shot_id ) :
						$full = wp_get_attachment_image_url( $shot_id, 'full' );
						$alt  = get_post_meta( $shot_id, '_wp_attachment_image_alt', true );

						if ( ! $full ) {
							continue;
						}
						?>
						<a class="rf-shot" href="<?php echo esc_url( $full ); ?>" data-rf-shot data-alt="<?php echo esc_attr( $alt ); ?>">
							<?php
							echo wp_get_attachment_image(
								$shot_id,
								'rom-shot',
								false,
								array(
									'loading'  => 'lazy',
									'decoding' => 'async',
									'alt'      => $alt ? $alt : sprintf( esc_attr__( 'Screenshot from %s', 'romsfun' ), get_the_title( $post_id ) ),
								)
							);
							?>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( get_the_content() ) : ?>
			<section class="rf-section rf-card-surface">
				<h2><?php esc_html_e( 'About this emulator', 'romsfun' ); ?></h2>
				<div class="rf-prose"><?php the_content(); ?></div>
			</section>
		<?php endif; ?>

		<?php if ( function_exists( 'romsfun_get_rating_distribution' ) ) :
			$dist = romsfun_get_rating_distribution( $post_id );
			?>
			<section class="rf-section rf-card-surface" aria-labelledby="rf-ratings-title">
				<h2 id="rf-ratings-title"><?php esc_html_e( 'Ratings & reviews', 'romsfun' ); ?></h2>

				<div class="rf-ratings">
					<div class="rf-ratings__score">
						<p class="rf-ratings__avg" data-rf-avg><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></p>
						<?php romsfun_stars( max( $rating, 0 ) ); ?>
						<p class="rf-ratings__count">
							<span data-rf-count><?php echo esc_html( number_format_i18n( $rating_count ) ); ?></span>
							<?php esc_html_e( 'ratings', 'romsfun' ); ?>
						</p>
					</div>

					<ul class="rf-ratings__bars">
						<?php
						for ( $star = 5; $star >= 1; $star-- ) :
							$votes   = (int) $dist[ $star ];
							$percent = $rating_count ? ( $votes / $rating_count ) * 100 : 0;
							?>
							<li>
								<span class="rf-ratings__star"><?php echo esc_html( (string) $star ); ?></span>
								<span class="rf-ratings__track" data-rf-bar="<?php echo esc_attr( (string) $star ); ?>"
									style="--rf-bar: <?php echo esc_attr( number_format( $percent, 1, '.', '' ) ); ?>%"></span>
								<span class="rf-ratings__num" data-rf-bar-count="<?php echo esc_attr( (string) $star ); ?>"><?php echo esc_html( number_format_i18n( $votes ) ); ?></span>
							</li>
						<?php endfor; ?>
					</ul>
				</div>

				<?php if ( ! $rating_count ) : ?>
					<p class="rf-ratings__empty"><?php esc_html_e( 'No ratings yet — be the first to rate this emulator using the stars above.', 'romsfun' ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php
		// ROMs this emulator can actually run. The most useful thing on the page, and it feeds
		// authority from a guide page back into the catalogue.
		$consoles = wp_get_post_terms( $post_id, 'console', array( 'fields' => 'ids' ) );

		if ( $consoles && ! is_wp_error( $consoles ) ) :
			$playable = new WP_Query(
				array(
					'post_type'      => 'rom',
					'posts_per_page' => 10,
					'no_found_rows'  => true,
					'meta_key'       => '_rf_download_count',
					'orderby'        => 'meta_value_num',
					'order'          => 'DESC',
					'tax_query'      => array(
						array( 'taxonomy' => 'console', 'field' => 'term_id', 'terms' => $consoles ),
					),
				)
			);

			if ( $playable->have_posts() ) :
				?>
				<section class="rf-section rf-card-surface">
					<h2><?php printf( esc_html__( 'Popular ROMs for %s', 'romsfun' ), esc_html( get_the_title() ) ); ?></h2>
					<div class="rf-grid">
						<?php
						while ( $playable->have_posts() ) :
							$playable->the_post();
							romsfun_rom_card( get_the_ID() );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
				<?php
			endif;
		endif;
		?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<section class="rf-section rf-card-surface">
				<?php comments_template( '', true ); ?>
			</section>
		<?php endif; ?>

	</article>

	<?php
endwhile;

get_footer();
