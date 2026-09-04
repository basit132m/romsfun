<?php
/**
 * Corrections to third-party SEO plugin output.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the missing `item` to the last breadcrumb in Yoast's BreadcrumbList.
 *
 * Yoast omits `item` on the final crumb. The schema.org spec permits that, but Google Search
 * Console reports it as "Missing field 'item'" and marks the whole BreadcrumbList invalid, which
 * forfeits the breadcrumb rich result. This is a long-standing and widely reported disagreement
 * between Yoast's reading of the spec and Google's validator.
 *
 * Rather than argue with either, we fill the field in with the crumb's own URL, which is valid
 * under both readings.
 */
function romsfun_fix_yoast_breadcrumbs( $data ) {
	if ( empty( $data['itemListElement'] ) || ! is_array( $data['itemListElement'] ) ) {
		return $data;
	}

	$current = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );

	foreach ( $data['itemListElement'] as $index => $element ) {
		if ( ! empty( $element['item'] ) ) {
			continue;
		}

		// Yoast sometimes carries the URL as `@id` on the element itself; prefer that over the
		// current request URL so the value is right even on non-singular views.
		$data['itemListElement'][ $index ]['item'] = ! empty( $element['@id'] ) ? $element['@id'] : $current;
	}

	return $data;
}
add_filter( 'wpseo_schema_breadcrumb', 'romsfun_fix_yoast_breadcrumbs', 20 );

/**
 * Rank Math builds its breadcrumb list differently but hits the same validator complaint.
 */
function romsfun_fix_rankmath_breadcrumbs( $entity ) {
	if ( empty( $entity['itemListElement'] ) || ! is_array( $entity['itemListElement'] ) ) {
		return $entity;
	}

	$current = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );

	foreach ( $entity['itemListElement'] as $index => $element ) {
		if ( empty( $element['item'] ) ) {
			$entity['itemListElement'][ $index ]['item'] = $current;
		}
	}

	return $entity;
}
add_filter( 'rank_math/snippet/rich_snippet_breadcrumblist_entity', 'romsfun_fix_rankmath_breadcrumbs', 20 );
