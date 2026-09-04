<?php
/**
 * Single ROM.
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
	$rating       = function_exists( 'romsfun_get_rating_average' ) ? romsfun_get_rating_average( $post_id ) : 0.0;
	$rating_count = function_exists( 'romsfun_get_rating_count' ) ? romsfun_get_rating_count( $post_id ) : 0;
	$has_rated    = function_exists( 'romsfun_has_rated' ) && romsfun_has_rated( $post_id );
	$download_url = romsfun_get_field( 'download_url' );
	$release      = romsfun_get_field( 'release_date' );

	romsfun_breadcrumbs();
	?>

	<article <?php post_class(); ?>>

		<div class="rf-rom-hero">

			<div class="rf-rom-hero__art rf-card-surface">
				<?php
				if ( has_post_thumbnail() ) {
					// Explicit dimensions ship with the image, so the browser reserves space and
					// the page does not shift as it loads. Layout shift is a Core Web Vital.
					the_post_thumbnail( 'rom-boxart', array( 'alt' => esc_attr( get_the_title() ) ) );
				} else {
					echo '<span class="rf-card__placeholder" style="aspect-ratio:3/4;border-radius:9px" aria-hidden="true"></span>';
				}
				?>
			</div>

			<div class="rf-rom-hero__body rf-card-surface">

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
								aria-label="<?php
									/* translators: %d: number of stars */
									printf( esc_attr__( 'Rate %d out of 5', 'romsfun' ), (int) $i );
								?>">★</button>
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

				<?php romsfun_term_pills( $post_id, array( 'console', 'genre', 'rom_type' ), 4 ); ?>

				<ul class="rf-specs">
					<?php
					$specs = array(
						'release_date' => __( 'Release Date', 'romsfun' ),
						'publisher'    => __( 'Publisher', 'romsfun' ),
						'developer'    => __( 'Developer', 'romsfun' ),
						'version'      => __( 'Version', 'romsfun' ),
						'languages'    => __( 'Languages', 'romsfun' ),
					);

					foreach ( $specs as $field => $label ) {
						$value = romsfun_get_field( $field );

						if ( ! $value ) {
							continue;
						}

						// Machine-readable date for the crawler, formatted date for the reader.
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

					$regions = get_the_terms( $post_id, 'region' );
					if ( $regions && ! is_wp_error( $regions ) ) {
						$links = array_map(
							static fn( $t ) => sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $t ) ), esc_html( $t->name ) ),
							$regions
						);
						printf(
							'<li><span class="rf-specs__label">%s</span><span class="rf-specs__value">%s</span></li>',
							esc_html__( 'Region', 'romsfun' ),
							implode( ', ', $links ) // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
						);
					}
					?>
				</ul>

				<p class="rf-rate__status" data-rf-rate-status aria-live="polite"><?php
					echo $has_rated ? esc_html__( 'You have already rated this ROM.', 'romsfun' ) : '';
				?></p>

				<div class="rf-rom-actions">
					<?php if ( $download_url ) : ?>
						<a class="rf-btn" href="<?php echo esc_url( $download_url ); ?>" rel="nofollow noopener">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/>
							</svg>
							<span class="rf-btn__label"><?php echo esc_html( romsfun_get_option( 'download_label' ) ); ?></span>
						</a>
					<?php endif; ?>
				</div>

			</div><!-- .rf-rom-hero__body -->
		</div><!-- .rf-rom-hero -->

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
						<a class="rf-shot" href="<?php echo esc_url( $full ); ?>"
							data-rf-shot
							data-alt="<?php echo esc_attr( $alt ); ?>">
							<?php
							echo wp_get_attachment_image(
								$shot_id,
								'rom-shot',
								false,
								array(
									'loading'  => 'lazy',
									'decoding' => 'async',
									'alt'      => $alt ? $alt : sprintf(
										/* translators: %s: ROM title */
										esc_attr__( 'Screenshot from %s', 'romsfun' ),
										get_the_title( $post_id )
									),
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
				<h2><?php esc_html_e( 'Description', 'romsfun' ); ?></h2>
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
					<p class="rf-ratings__empty"><?php esc_html_e( 'No ratings yet — be the first to rate this ROM using the stars above.', 'romsfun' ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php
		$checksums = array_filter(
			array(
				'MD5'  => romsfun_get_field( 'md5' ),
				'SHA1' => romsfun_get_field( 'sha1' ),
			)
		);

		// Almost nobody publishes checksums. They are genuinely useful to the reader and they are
		// unique text on a page that would otherwise be a spec table — which is exactly the kind
		// of thin-content problem that limits indexing at this scale.
		if ( $checksums && romsfun_get_option( 'show_checksums' ) ) :
			?>
			<section class="rf-section rf-card-surface">
				<h2><?php esc_html_e( 'File Verification', 'romsfun' ); ?></h2>
				<ul class="rf-specs">
					<?php foreach ( $checksums as $label => $value ) : ?>
						<li>
							<span class="rf-specs__label"><?php echo esc_html( $label ); ?></span>
							<span class="rf-specs__value"><code><?php echo esc_html( $value ); ?></code></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php
		$related = romsfun_get_option( 'show_related' )
			? romsfun_related_roms( $post_id, (int) romsfun_get_option( 'related_count' ) )
			: null;

		if ( $related && $related->have_posts() ) :
			?>
			<section class="rf-section rf-card-surface">
				<h2><?php esc_html_e( 'Related ROMs', 'romsfun' ); ?></h2>
				<div class="rf-grid">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						romsfun_rom_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<section class="rf-section rf-card-surface">
				<?php comments_template( '', true ); ?>
			</section>
		<?php endif; ?>

	</article>

	<?php
endwhile;

get_footer();
