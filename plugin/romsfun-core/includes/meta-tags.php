<?php
/**
 * Meta descriptions and social tags.
 *
 * WordPress core emits no meta description, so without this the site has none at all.
 *
 * Everything here stands down automatically if a dedicated SEO plugin is activated — two
 * competing descriptions or two sets of Open Graph tags on a page is worse than having neither,
 * and it is the kind of conflict that is invisible until someone views source.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a dedicated SEO plugin handling this already?
 */
function romsfun_seo_plugin_active(): bool {
	return defined( 'RANK_MATH_VERSION' )
		|| defined( 'WPSEO_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| class_exists( 'The_SEO_Framework\\Load' );
}

/**
 * Trim to a length search engines will actually display, breaking on a word boundary rather than
 * mid-word.
 */
function romsfun_trim_description( string $text, int $length = 158 ): string {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );

	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}

	$cut   = mb_substr( $text, 0, $length );
	$space = mb_strrpos( $cut, ' ' );

	return rtrim( $space ? mb_substr( $cut, 0, $space ) : $cut, ' ,.;:-' ) . '…';
}

/**
 * The best available description for whatever is being viewed.
 */
function romsfun_get_meta_description(): string {
	if ( is_front_page() ) {
		$custom = get_option( 'romsfun_home_description', '' );

		return $custom ? $custom : romsfun_trim_description( (string) get_bloginfo( 'description' ) );
	}

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( $post instanceof WP_Post ) {
			$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
			return romsfun_trim_description( $excerpt );
		}
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			if ( $term->description ) {
				return romsfun_trim_description( $term->description );
			}

			// A generated fallback still beats no description on a hub page.
			return romsfun_trim_description(
				sprintf(
					/* translators: 1: term name, 2: number of ROMs */
					__( 'Browse %1$s ROMs — %2$s titles available to download free, with file details and emulator recommendations.', 'romsfun' ),
					$term->name,
					number_format_i18n( $term->count )
				)
			);
		}
	}

	if ( is_post_type_archive( 'rom' ) ) {
		return romsfun_trim_description( (string) get_bloginfo( 'description' ) );
	}

	return '';
}

/**
 * Emit the description plus Open Graph and Twitter tags.
 *
 * Social tags are grouped here because they share the same description and image, and because
 * having them is what stops a shared link rendering as a bare URL with no preview.
 */
function romsfun_output_meta_tags(): void {
	if ( romsfun_seo_plugin_active() ) {
		return;
	}

	// Never advertise a page that should not be indexed.
	if ( is_404() || is_search() ) {
		return;
	}

	$description = romsfun_get_meta_description();
	$title       = wp_get_document_title();
	$url         = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );

	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
	}

	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );

	$image = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'full' );
	} elseif ( function_exists( 'romsfun_get_option' ) ) {
		$image = (string) romsfun_get_option( 'hero_image' );
	}

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		// summary_large_image only renders properly when an image is actually present.
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	} else {
		echo '<meta name="twitter:card" content="summary">' . "\n";
	}
}
add_action( 'wp_head', 'romsfun_output_meta_tags', 2 );
