<?php
/**
 * Breadcrumb trail.
 *
 * Built once and consumed twice — rendered visually in the templates and serialised into
 * BreadcrumbList schema. Deriving both from one function keeps the markup and the structured data
 * from drifting apart, which is the usual reason breadcrumb rich results silently stop appearing.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

function romsfun_get_breadcrumbs(): array {
	$crumbs = array(
		array(
			'label' => __( 'Home', 'romsfun' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'rom' ) ) {
		$crumbs[] = array(
			'label' => __( 'ROMs', 'romsfun' ),
			'url'   => get_post_type_archive_link( 'rom' ),
		);

		$slug = romsfun_get_primary_console_slug( get_the_ID() );
		$term = get_term_by( 'slug', $slug, 'console' );

		if ( $term && ! is_wp_error( $term ) ) {
			$crumbs[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}

		$crumbs[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);

		return $crumbs;
	}

	if ( is_tax() ) {
		$term = get_queried_object();

		$crumbs[] = array(
			'label' => __( 'ROMs', 'romsfun' ),
			'url'   => get_post_type_archive_link( 'rom' ),
		);

		// Walk up hierarchical taxonomies so a child console sits under its family.
		if ( $term instanceof WP_Term && is_taxonomy_hierarchical( $term->taxonomy ) ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$crumbs[] = array(
						'label' => $ancestor->name,
						'url'   => get_term_link( $ancestor ),
					);
				}
			}
		}

		$crumbs[] = array(
			'label' => $term->name ?? '',
			'url'   => '',
		);

		return $crumbs;
	}

	if ( is_post_type_archive( 'rom' ) ) {
		$crumbs[] = array( 'label' => __( 'ROMs', 'romsfun' ), 'url' => '' );
		return $crumbs;
	}

	if ( is_singular() ) {
		$crumbs[] = array( 'label' => get_the_title(), 'url' => '' );
	}

	return $crumbs;
}

function romsfun_breadcrumbs(): void {
	$crumbs = romsfun_get_breadcrumbs();

	if ( count( $crumbs ) < 2 ) {
		return;
	}

	echo '<nav class="rf-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'romsfun' ) . '"><ol>';

	foreach ( $crumbs as $crumb ) {
		if ( $crumb['url'] ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $crumb['url'] ),
				esc_html( $crumb['label'] )
			);
		} else {
			printf( '<li><span aria-current="page">%s</span></li>', esc_html( $crumb['label'] ) );
		}
	}

	echo '</ol></nav>';
}
