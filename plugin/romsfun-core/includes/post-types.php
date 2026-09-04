<?php
/**
 * Custom post types.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * The `rom` slug carries `%console%`, which permalinks.php rewrites into the entry's primary
 * console term. That produces /roms/psp/god-of-war-chains-of-olympus/ — the console sits in the
 * URL because searchers type the platform alongside the title ("god of war psp rom"), and a
 * keyword in the path is worth more than a tidier flat URL.
 */
function romsfun_register_post_types(): void {

	register_post_type(
		'rom',
		array(
			'labels'             => array(
				'name'               => __( 'ROMs', 'romsfun' ),
				'singular_name'      => __( 'ROM', 'romsfun' ),
				'add_new_item'       => __( 'Add New ROM', 'romsfun' ),
				'edit_item'          => __( 'Edit ROM', 'romsfun' ),
				'search_items'       => __( 'Search ROMs', 'romsfun' ),
				'not_found'          => __( 'No ROMs found', 'romsfun' ),
				'all_items'          => __( 'All ROMs', 'romsfun' ),
			),
			'public'             => true,
			'has_archive'        => 'roms',
			'menu_icon'          => 'dashicons-games',
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'comments' ),
			'show_in_rest'       => true,
			'rewrite'            => array(
				'slug'       => 'roms/%console%',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'emulator',
		array(
			'labels'        => array(
				'name'          => __( 'Emulators', 'romsfun' ),
				'singular_name' => __( 'Emulator', 'romsfun' ),
				'all_items'     => __( 'All Emulators', 'romsfun' ),
			),
			'public'        => true,
			'has_archive'   => 'emulators',
			'menu_icon'     => 'dashicons-desktop',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'show_in_rest'  => true,
			'rewrite'       => array(
				'slug'       => 'emulators',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'romsfun_register_post_types' );
