<?php
/**
 * Permalink handling for the %console% rewrite tag.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the console term that represents a ROM in its URL.
 *
 * A ROM can carry several console terms, but a URL can only hold one, and it must be stable — a
 * permalink that changes when someone reorders terms silently breaks every inbound link to the
 * page. So we prefer an explicit `_primary_console` meta value, and otherwise fall back to the
 * lowest term ID, which is stable because it reflects creation order rather than anything an
 * editor can shuffle.
 */
function romsfun_get_primary_console_slug( int $post_id ): string {
	$primary = get_post_meta( $post_id, '_primary_console', true );

	if ( $primary ) {
		$term = get_term_by( 'slug', $primary, 'console' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term->slug;
		}
	}

	$terms = get_the_terms( $post_id, 'console' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return 'other';
	}

	usort(
		$terms,
		static fn( $a, $b ) => $a->term_id <=> $b->term_id
	);

	return $terms[0]->slug;
}

/**
 * Swap %console% for the real slug when WordPress builds a ROM permalink.
 */
function romsfun_rom_permalink( string $permalink, WP_Post $post ): string {
	if ( 'rom' !== $post->post_type || ! str_contains( $permalink, '%console%' ) ) {
		return $permalink;
	}

	return str_replace( '%console%', romsfun_get_primary_console_slug( $post->ID ), $permalink );
}
add_filter( 'post_type_link', 'romsfun_rom_permalink', 10, 2 );

/**
 * Match incoming /roms/<console>/<slug>/ requests.
 *
 * The console segment is deliberately ignored when resolving the post — the slug alone identifies
 * it. That means a ROM whose console changes still resolves at its old URL instead of 404ing, and
 * the canonical tag in the template points crawlers at the current one.
 */
function romsfun_rom_rewrite_rules(): void {
	add_rewrite_rule(
		'^roms/[^/]+/([^/]+)/?$',
		'index.php?post_type=rom&name=$matches[1]',
		'top'
	);

	// Paginated ROM archive: /roms/page/2/
	add_rewrite_rule(
		'^roms/page/([0-9]{1,})/?$',
		'index.php?post_type=rom&paged=$matches[1]',
		'top'
	);
}
add_action( 'init', 'romsfun_rom_rewrite_rules' );
