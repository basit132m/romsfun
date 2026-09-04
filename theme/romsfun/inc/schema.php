<?php
/**
 * Structured data.
 *
 * This is the clearest place to out-perform a competitor on a catalogue site. Rich results —
 * star ratings, breadcrumb trails, download counts in the SERP — lift click-through on listings
 * that are otherwise indistinguishable from one another.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

function romsfun_output_schema(): void {
	// Belt and braces against a second block being emitted if wp_head ever runs twice (some
	// caching and page-builder setups do). Two BreadcrumbLists on a page is a validation error.
	static $done = false;

	if ( $done ) {
		return;
	}

	$done  = true;
	$graph = array();

	$graph[] = romsfun_schema_website();

	$breadcrumbs = romsfun_schema_breadcrumbs();
	if ( $breadcrumbs ) {
		$graph[] = $breadcrumbs;
	}

	if ( is_singular( 'rom' ) ) {
		$graph[] = romsfun_schema_rom( get_the_ID() );
	}

	if ( is_singular( 'emulator' ) ) {
		$graph[] = romsfun_schema_emulator( get_the_ID() );
	}

	if ( ! $graph ) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		)
	);
}
add_action( 'wp_head', 'romsfun_output_schema', 20 );

function romsfun_schema_website(): array {
	return array(
		'@type'    => 'WebSite',
		'@id'      => home_url( '/#website' ),
		'url'      => home_url( '/' ),
		'name'     => get_bloginfo( 'name' ),
		'inLanguage' => get_bloginfo( 'language' ),
		// Declares the on-site search endpoint so Google can offer a sitelinks search box.
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * A ROM is modelled as a VideoGame. `SoftwareApplication` is added as a second type because the
 * download offer, file size and operating system properties belong to that vocabulary — together
 * they describe the page more completely than either alone.
 */
function romsfun_schema_rom( int $post_id ): array {
	$console = get_the_terms( $post_id, 'console' );
	$genre   = get_the_terms( $post_id, 'genre' );

	$schema = array(
		'@type'       => array( 'VideoGame', 'SoftwareApplication' ),
		'@id'         => get_permalink( $post_id ) . '#rom',
		'name'        => get_the_title( $post_id ),
		'url'         => get_permalink( $post_id ),
		'description' => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'applicationCategory' => 'GameApplication',
	);

	if ( has_post_thumbnail( $post_id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $post_id, 'rom-boxart' );
	}

	if ( $console && ! is_wp_error( $console ) ) {
		$schema['gamePlatform']    = wp_list_pluck( $console, 'name' );
		$schema['operatingSystem'] = implode( ', ', wp_list_pluck( $console, 'name' ) );
	}

	if ( $genre && ! is_wp_error( $genre ) ) {
		$schema['genre'] = wp_list_pluck( $genre, 'name' );
	}

	$map = array(
		'publisher'    => 'publisher',
		'developer'    => 'author',
		'version'      => 'softwareVersion',
		'release_date' => 'datePublished',
	);

	foreach ( $map as $field => $property ) {
		$value = romsfun_get_field( $field, $post_id );
		if ( ! $value ) {
			continue;
		}

		$schema[ $property ] = in_array( $property, array( 'publisher', 'author' ), true )
			? array( '@type' => 'Organization', 'name' => $value )
			: $value;
	}

	$bytes = (int) romsfun_get_field( 'file_size_bytes', $post_id );
	if ( $bytes > 0 ) {
		$schema['fileSize'] = romsfun_format_bytes( $bytes );
	}

	$rating_value = function_exists( 'romsfun_get_rating_average' ) ? romsfun_get_rating_average( $post_id ) : 0.0;
	$rating_count = function_exists( 'romsfun_get_rating_count' ) ? romsfun_get_rating_count( $post_id ) : 0;

	// Google requires a non-zero review count for an AggregateRating; emitting one without
	// ratings is a structured-data error and can cost the rich result entirely.
	if ( $rating_value > 0 && $rating_count > 0 ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $rating_value,
			'ratingCount' => $rating_count,
			'bestRating'  => 5,
			'worstRating' => 1,
		);
	}

	// Free downloads still need an Offer for the rich result to qualify.
	$schema['offers'] = array(
		'@type'         => 'Offer',
		'price'         => '0',
		'priceCurrency' => 'USD',
		'availability'  => 'https://schema.org/InStock',
		'url'           => get_permalink( $post_id ),
	);

	return $schema;
}

function romsfun_schema_breadcrumbs(): ?array {
	$crumbs = romsfun_get_breadcrumbs();

	if ( count( $crumbs ) < 2 ) {
		return null;
	}

	$current = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
	$items   = array();

	foreach ( array_values( $crumbs ) as $i => $crumb ) {
		/*
		 * `item` is emitted on every element, including the last.
		 *
		 * The spec calls it optional on the final crumb, but Search Console reports the omission
		 * as "Missing field 'item'" and marks the whole BreadcrumbList invalid — which costs the
		 * breadcrumb rich result. Supplying the page's own URL is valid, and it is what the major
		 * SEO plugins do for exactly this reason.
		 *
		 * The visible trail still renders the last crumb as plain text; only the markup differs.
		 */
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['label'],
			'item'     => ! empty( $crumb['url'] ) ? $crumb['url'] : $current,
		);
	}

	return array(
		'@type'           => 'BreadcrumbList',
		// Named so it does not show as "Unnamed item" in Search Console's report.
		'name'            => __( 'Breadcrumb', 'romsfun' ),
		'@id'             => $current . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * An emulator is a plain SoftwareApplication — no VideoGame type, because it is not a game and
 * claiming otherwise is the kind of mismatch that gets structured data ignored.
 */
function romsfun_schema_emulator( int $post_id ): array {
	$schema = array(
		'@type'               => 'SoftwareApplication',
		'@id'                 => get_permalink( $post_id ) . '#emulator',
		'name'                => get_the_title( $post_id ),
		'url'                 => get_permalink( $post_id ),
		'description'         => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'applicationCategory' => 'GameApplication',
	);

	if ( has_post_thumbnail( $post_id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
	}

	$platforms = get_the_terms( $post_id, 'platform' );
	if ( $platforms && ! is_wp_error( $platforms ) ) {
		$schema['operatingSystem'] = implode( ', ', wp_list_pluck( $platforms, 'name' ) );
	}

	foreach ( array( 'version' => 'softwareVersion', 'developer' => 'author', 'license' => 'license' ) as $field => $property ) {
		$value = romsfun_get_field( $field, $post_id );

		if ( ! $value ) {
			continue;
		}

		$schema[ $property ] = 'author' === $property
			? array( '@type' => 'Organization', 'name' => $value )
			: $value;
	}

	$bytes = (int) romsfun_get_field( 'file_size_bytes', $post_id );
	if ( $bytes > 0 ) {
		$schema['fileSize'] = romsfun_format_bytes( $bytes );
	}

	$rating_value = function_exists( 'romsfun_get_rating_average' ) ? romsfun_get_rating_average( $post_id ) : 0.0;
	$rating_count = function_exists( 'romsfun_get_rating_count' ) ? romsfun_get_rating_count( $post_id ) : 0;

	if ( $rating_value > 0 && $rating_count > 0 ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $rating_value,
			'ratingCount' => $rating_count,
			'bestRating'  => 5,
			'worstRating' => 1,
		);
	}

	$schema['offers'] = array(
		'@type'         => 'Offer',
		'price'         => '0',
		'priceCurrency' => 'USD',
		'availability'  => 'https://schema.org/InStock',
		'url'           => get_permalink( $post_id ),
	);

	return $schema;
}
