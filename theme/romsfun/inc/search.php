<?php
/**
 * Catalogue search and filtering.
 *
 * The form is a plain GET form with no JavaScript. That is deliberate: it works before any script
 * runs, it is crawlable, it costs nothing in Core Web Vitals, and every filtered view is a real
 * URL that can be linked and shared.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filters exposed in the UI, mapped to the taxonomy each one queries.
 */
function romsfun_filter_taxonomies(): array {
	return array(
		'console'    => __( 'Console', 'romsfun' ),
		'genre'      => __( 'Genre', 'romsfun' ),
		'collection' => __( 'Collection', 'romsfun' ),
		'rom_type'   => __( 'Type', 'romsfun' ),
	);
}

function romsfun_sort_options(): array {
	return array(
		''          => __( 'Select Sort', 'romsfun' ),
		'newest'    => __( 'Newest first', 'romsfun' ),
		'popular'   => __( 'Most downloaded', 'romsfun' ),
		'rating'    => __( 'Highest rated', 'romsfun' ),
		'name'      => __( 'Title A–Z', 'romsfun' ),
		'size_desc' => __( 'Largest file', 'romsfun' ),
	);
}

/**
 * The filter values actually present on this request, sanitised.
 */
function romsfun_active_filters(): array {
	$active = array();

	foreach ( array_keys( romsfun_filter_taxonomies() ) as $taxonomy ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only filter.
		if ( ! empty( $_GET[ $taxonomy ] ) ) {
			$active[ $taxonomy ] = sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) );
		}
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['sort'] ) ) {
		$sort = sanitize_key( wp_unslash( $_GET['sort'] ) );
		if ( array_key_exists( $sort, romsfun_sort_options() ) ) {
			$active['sort'] = $sort;
		}
	}

	return $active;
}

/**
 * Apply the filters to the main ROM query.
 */
function romsfun_apply_filters( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_catalogue = $query->is_post_type_archive( 'rom' )
		|| $query->is_tax( array( 'console', 'genre', 'collection', 'rom_type', 'region' ) )
		|| ( $query->is_search() && 'rom' === $query->get( 'post_type' ) );

	if ( ! $is_catalogue ) {
		return;
	}

	$active    = romsfun_active_filters();
	$tax_query = (array) $query->get( 'tax_query' );

	foreach ( array_keys( romsfun_filter_taxonomies() ) as $taxonomy ) {
		if ( empty( $active[ $taxonomy ] ) ) {
			continue;
		}

		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $active[ $taxonomy ],
		);
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	if ( $tax_query ) {
		$query->set( 'tax_query', $tax_query );
	}

	switch ( $active['sort'] ?? '' ) {
		case 'popular':
			$query->set( 'meta_key', '_rf_download_count' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'rating':
			$query->set( 'meta_key', '_rf_rating_value' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'size_desc':
			$query->set( 'meta_key', '_rf_file_size_bytes' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'name':
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			break;
		case 'newest':
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'romsfun_apply_filters' );

/**
 * Keep filtered views out of the index.
 *
 * Five filters with dozens of values each generate millions of crawlable combinations. Left
 * indexable, Googlebot spends its crawl budget on permutations and never reaches the ROM pages
 * themselves — the standard way large catalogue sites fail.
 *
 * `follow` is kept so link equity still flows through to the ROM pages being listed.
 */
function romsfun_noindex_filtered_views( $robots ) {
	if ( romsfun_active_filters() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'romsfun_noindex_filtered_views' );

/**
 * Point filtered views at the clean archive so any signals they accumulate consolidate there.
 */
function romsfun_canonical_for_filters(): void {
	if ( ! romsfun_active_filters() ) {
		return;
	}

	$canonical = is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'rom' );

	if ( $canonical && ! is_wp_error( $canonical ) ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
	}
}
add_action( 'wp_head', 'romsfun_canonical_for_filters', 1 );

/**
 * Remove WordPress's own canonical when we are emitting a filtered one, so the page never carries
 * two competing canonical tags — which Google resolves by ignoring both.
 */
function romsfun_remove_default_canonical(): void {
	if ( romsfun_active_filters() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'wp', 'romsfun_remove_default_canonical' );

/**
 * Sort and view parameters never change which ROMs are listed, only their order, so there is no
 * reason to let a crawler walk them.
 */
function romsfun_robots_txt( string $output ): string {
	$output .= "\n# Ordering parameters produce no new content\n";
	$output .= "Disallow: /*?*sort=\n";
	$output .= "Disallow: /*&sort=\n";

	return $output;
}
add_filter( 'robots_txt', 'romsfun_robots_txt' );

/**
 * Render the search + filter form.
 */
function romsfun_search_filters( bool $show_suggested = true ): void {
	$active = romsfun_active_filters();
	$action = get_post_type_archive_link( 'rom' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$term   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	?>
	<form class="rf-search" role="search" method="get" action="<?php echo esc_url( $action ); ?>">

		<?php /* Scopes the search to the catalogue. Without it a search drops into the global
		         WordPress search and returns blog posts and pages alongside ROMs. */ ?>
		<input type="hidden" name="post_type" value="rom">

		<div class="rf-search__bar">
			<label class="screen-reader-text" for="rf-search-input"><?php esc_html_e( 'Search ROMs', 'romsfun' ); ?></label>
			<input
				type="search"
				id="rf-search-input"
				name="s"
				value="<?php echo esc_attr( $term ); ?>"
				placeholder="<?php esc_attr_e( 'Search for ROMs, consoles, emulators…', 'romsfun' ); ?>"
			>
			<button type="submit" class="rf-btn"><?php esc_html_e( 'Search', 'romsfun' ); ?></button>
		</div>

		<div class="rf-search__filters">
			<?php
			foreach ( romsfun_filter_taxonomies() as $taxonomy => $label ) :
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
						'number'     => 300,
						'orderby'    => 'count',
						'order'      => 'DESC',
					)
				);

				if ( is_wp_error( $terms ) ) {
					continue;
				}
				?>
				<div class="rf-field">
					<label for="rf-filter-<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( $label ); ?></label>
					<select id="rf-filter-<?php echo esc_attr( $taxonomy ); ?>" name="<?php echo esc_attr( $taxonomy ); ?>">
						<option value=""><?php printf( esc_html__( 'Select %s', 'romsfun' ), esc_html( $label ) ); ?></option>
						<?php foreach ( $terms as $t ) : ?>
							<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $active[ $taxonomy ] ?? '', $t->slug ); ?>>
								<?php echo esc_html( $t->name ); ?> (<?php echo esc_html( number_format_i18n( $t->count ) ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endforeach; ?>

			<div class="rf-field">
				<label for="rf-filter-sort"><?php esc_html_e( 'Sort By', 'romsfun' ); ?></label>
				<select id="rf-filter-sort" name="sort">
					<?php foreach ( romsfun_sort_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $active['sort'] ?? '', $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php if ( $active || $term ) : ?>
			<a class="rf-btn rf-btn--ghost rf-btn--sm" href="<?php echo esc_url( $action ); ?>">
				<?php esc_html_e( 'Clear Filters', 'romsfun' ); ?>
			</a>
		<?php endif; ?>
	</form>

	<?php
	if ( ! $show_suggested ) {
		return;
	}

	$suggested = get_terms(
		array(
			'taxonomy'   => 'rom_type',
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( empty( $suggested ) || is_wp_error( $suggested ) ) {
		return;
	}
	?>
	<div class="rf-suggested">
		<h2 class="rf-suggested__title"><?php esc_html_e( 'Suggested Filters', 'romsfun' ); ?></h2>
		<div class="rf-pills">
			<?php foreach ( $suggested as $t ) : ?>
				<a class="rf-pill" href="<?php echo esc_url( get_term_link( $t ) ); ?>">
					<?php echo esc_html( $t->name ); ?>
					<span class="rf-pill__count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
