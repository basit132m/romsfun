<?php
/**
 * Shared template output.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomy pills. Every term links to its hub page — this is the bulk of the site's internal
 * linking, and it is what pushes authority from popular ROM pages out to the long tail.
 */
function romsfun_term_pills( int $post_id, array $taxonomies = array( 'console', 'genre' ), int $limit = 4 ): void {
	$rendered = 0;

	echo '<div class="rf-pills">';

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			if ( $rendered >= $limit ) {
				break 2;
			}

			printf(
				'<a class="rf-pill rf-pill--%1$s" href="%2$s">%3$s</a>',
				esc_attr( $taxonomy ),
				esc_url( get_term_link( $term ) ),
				esc_html( $term->name )
			);

			$rendered++;
		}
	}

	echo '</div>';
}

/**
 * The card used across archives, related ROMs and homepage grids.
 */
function romsfun_rom_card( int $post_id ): void {
	$downloads = (int) romsfun_get_field( 'download_count', $post_id );
	$bytes     = (int) romsfun_get_field( 'file_size_bytes', $post_id );
	?>
	<article class="rf-card">
		<a class="rf-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<?php echo get_the_post_thumbnail( $post_id, 'rom-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
			<?php else : ?>
				<span class="rf-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</a>

		<div class="rf-card__body">
			<h3 class="rf-card__title">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			</h3>

			<?php romsfun_term_pills( $post_id, array( 'console', 'rom_type' ), 2 ); ?>

			<?php if ( $downloads || $bytes ) : ?>
				<div class="rf-card__meta">
					<?php if ( $downloads ) : ?>
						<span class="rf-stat" title="<?php esc_attr_e( 'Downloads', 'romsfun' ); ?>">
							<?php echo esc_html( number_format_i18n( $downloads ) ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $bytes ) : ?>
						<span class="rf-stat"><?php echo esc_html( romsfun_format_bytes( $bytes ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Star rating display. Renders nothing when unrated rather than showing an empty five stars,
 * which reads as a bad score.
 */
function romsfun_stars( float $value ): void {
	if ( $value <= 0 ) {
		return;
	}

	$rounded = (int) round( $value );

	printf(
		'<span class="rf-stars" role="img" aria-label="%s">%s</span>',
		esc_attr( sprintf( __( 'Rated %s out of 5', 'romsfun' ), number_format_i18n( $value, 1 ) ) ),
		esc_html( str_repeat( '★', $rounded ) . str_repeat( '☆', 5 - $rounded ) )
	);
}

/**
 * Related ROMs, preferring the same collection (a franchise sibling is a far better suggestion
 * than a random game on the same console), then falling back to the console.
 */
function romsfun_related_roms( int $post_id, int $limit = 5 ): WP_Query {
	$collections = wp_get_post_terms( $post_id, 'collection', array( 'fields' => 'ids' ) );
	$consoles    = wp_get_post_terms( $post_id, 'console', array( 'fields' => 'ids' ) );

	$tax_query = array( 'relation' => 'OR' );

	if ( $collections && ! is_wp_error( $collections ) ) {
		$tax_query[] = array( 'taxonomy' => 'collection', 'field' => 'term_id', 'terms' => $collections );
	}

	if ( $consoles && ! is_wp_error( $consoles ) ) {
		$tax_query[] = array( 'taxonomy' => 'console', 'field' => 'term_id', 'terms' => $consoles );
	}

	return new WP_Query(
		array(
			'post_type'           => 'rom',
			'posts_per_page'      => $limit,
			'post__not_in'        => array( $post_id ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => count( $tax_query ) > 1 ? $tax_query : array(),
			'orderby'             => 'meta_value_num',
			'meta_key'            => '_rf_download_count',
		)
	);
}

/**
 * Wide list row used by the trending section — thumbnail, title, pills, downloads and size.
 * A distinct shape from the grid card so the two sections read differently on the homepage.
 */
function romsfun_rom_row( int $post_id ): void {
	$downloads = (int) romsfun_get_field( 'download_count', $post_id );
	$bytes     = (int) romsfun_get_field( 'file_size_bytes', $post_id );
	?>
	<article class="rf-row">
		<a class="rf-row__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, 'rom-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) );
			} else {
				echo '<span class="rf-card__placeholder"></span>';
			}
			?>
		</a>

		<div class="rf-row__body">
			<h3 class="rf-row__title">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			</h3>
			<?php romsfun_term_pills( $post_id, array( 'console', 'collection', 'rom_type' ), 2 ); ?>
		</div>

		<div class="rf-row__stats">
			<?php if ( $downloads ) : ?>
				<span class="rf-chip"><?php echo esc_html( number_format_i18n( $downloads ) ); ?></span>
			<?php endif; ?>
			<?php if ( $bytes ) : ?>
				<span class="rf-chip"><?php echo esc_html( romsfun_format_bytes( $bytes ) ); ?></span>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Query helper for the homepage sections.
 *
 * `no_found_rows` skips the SQL_CALC_FOUND_ROWS count, which is pure waste on a fixed-size
 * homepage list and gets expensive once the catalogue is large.
 */
function romsfun_home_query( string $mode, int $count ): WP_Query {
	$args = array(
		'post_type'           => 'rom',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( 'popular' === $mode ) {
		$args['meta_key'] = '_rf_download_count';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	return new WP_Query( $args );
}
