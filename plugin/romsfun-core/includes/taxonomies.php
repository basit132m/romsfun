<?php
/**
 * Taxonomies for the ROM catalogue.
 *
 * Each of these becomes both a filter facet and an indexable hub page. That dual role is why they
 * are taxonomies rather than custom fields: a field can filter, but only a taxonomy gives you an
 * archive URL that can rank.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

function romsfun_register_taxonomies(): void {

	$taxonomies = array(
		// Hierarchical: platform families group naturally (Nintendo > GBA), and hierarchy gives us
		// breadcrumbs plus parent hub pages that can rank for broader terms.
		'console'    => array(
			'plural'       => __( 'Consoles', 'romsfun' ),
			'single'       => __( 'Console', 'romsfun' ),
			'slug'         => 'console',
			'hierarchical' => true,
		),
		'genre'      => array(
			'plural'       => __( 'Genres', 'romsfun' ),
			'single'       => __( 'Genre', 'romsfun' ),
			'slug'         => 'genre',
			'hierarchical' => true,
		),
		// Franchise groupings — "God of War", "Call of Duty". These are high-intent hub pages and
		// among the best internal-linking assets on the site.
		'collection' => array(
			'plural'       => __( 'Collections', 'romsfun' ),
			'single'       => __( 'Collection', 'romsfun' ),
			'slug'         => 'collection',
			'hierarchical' => false,
		),
		// Fan Translation, Hack, Repack, Homebrew, Fan Game.
		'rom_type'   => array(
			'plural'       => __( 'Types', 'romsfun' ),
			'single'       => __( 'Type', 'romsfun' ),
			'slug'         => 'type',
			'hierarchical' => false,
		),
		'region'     => array(
			'plural'       => __( 'Regions', 'romsfun' ),
			'single'       => __( 'Region', 'romsfun' ),
			'slug'         => 'region',
			'hierarchical' => false,
		),
	);

	foreach ( $taxonomies as $taxonomy => $config ) {
		register_taxonomy(
			$taxonomy,
			array( 'rom' ),
			array(
				'labels'            => array(
					'name'          => $config['plural'],
					'singular_name' => $config['single'],
					'search_items'  => sprintf( __( 'Search %s', 'romsfun' ), $config['plural'] ),
					'all_items'     => sprintf( __( 'All %s', 'romsfun' ), $config['plural'] ),
				),
				'public'            => true,
				'hierarchical'      => $config['hierarchical'],
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => $config['slug'],
					'with_front' => false,
				),
			)
		);
	}

	// Emulators are grouped by the console they emulate, reusing the same vocabulary so an emulator
	// page and a console hub cross-link without a second list to maintain.
	register_taxonomy_for_object_type( 'console', 'emulator' );

	// The operating systems an emulator runs on. Separate from `console`, which is what it plays:
	// PPSSPP emulates PSP (console) and runs on Windows, macOS, Linux and Android (platform).
	// Conflating the two would make both filters useless.
	register_taxonomy(
		'platform',
		array( 'emulator' ),
		array(
			'labels'            => array(
				'name'          => __( 'Platforms', 'romsfun' ),
				'singular_name' => __( 'Platform', 'romsfun' ),
				'all_items'     => __( 'All Platforms', 'romsfun' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'platform',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'romsfun_register_taxonomies' );
