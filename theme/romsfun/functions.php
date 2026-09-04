<?php
/**
 * RomsFun theme setup.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

define( 'ROMSFUN_THEME_VERSION', '1.1.0' );

/**
 * The theme reads catalogue data through the romsfun-core plugin. If that plugin is ever
 * deactivated, these no-op fallbacks let the site degrade to a plain listing instead of fatally
 * erroring — a white screen across 70,000 indexed URLs is far more damaging than missing metadata,
 * and Google will drop pages that return 500s.
 */
if ( ! function_exists( 'romsfun_get_field' ) ) {
	function romsfun_get_field( string $key, ?int $post_id = null ) {
		return '';
	}
}

if ( ! function_exists( 'romsfun_format_bytes' ) ) {
	function romsfun_format_bytes( $bytes ): string {
		return '';
	}
}

if ( ! function_exists( 'romsfun_get_primary_console_slug' ) ) {
	function romsfun_get_primary_console_slug( int $post_id ): string {
		return 'other';
	}
}

/**
 * Tell the administrator why the site looks wrong, rather than leaving them to guess.
 */
function romsfun_core_dependency_notice(): void {
	if ( post_type_exists( 'rom' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'RomsFun theme:', 'romsfun' ),
		esc_html__( 'the RomsFun Core plugin is not active. ROM post types, taxonomies and metadata are unavailable until you activate it.', 'romsfun' )
	);
}
add_action( 'admin_notices', 'romsfun_core_dependency_notice' );

require_once get_template_directory() . '/inc/breadcrumbs.php';
require_once get_template_directory() . '/inc/schema.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/search.php';

function romsfun_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'caption', 'style', 'script' ) );

	// Logo is uploaded and swapped from Appearance > Customize > Site Identity, so changing it
	// never requires touching a file.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// Box art is portrait and the LCP element on every ROM page, so it gets its own size rather
	// than being scaled down from a landscape crop. 3:4 matches most console cover art.
	add_image_size( 'rom-boxart', 450, 600, true );
	add_image_size( 'rom-card', 300, 400, true );

	// Screenshots are 16:9. The thumbnail is generated at 2x its display width so it stays sharp
	// on high-density screens without shipping the full 1280px file to the row.
	add_image_size( 'rom-shot', 640, 360, true );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'romsfun' ),
			'footer'  => __( 'Footer Menu', 'romsfun' ),
		)
	);
}
add_action( 'after_setup_theme', 'romsfun_theme_setup' );

/**
 * One stylesheet, no jQuery on the front end, no webfont request.
 *
 * Every external request is a round trip before first paint. A system font stack renders
 * instantly and costs nothing, which matters more on a catalogue whose traffic arrives on mobile
 * from search.
 */
function romsfun_enqueue_assets(): void {
	/*
	 * Version the stylesheet by its modification time rather than the theme version. Bumping the
	 * theme version by hand is easy to forget, and when it is forgotten the browser and the CDN
	 * both keep serving the previous CSS — the change looks like it silently failed to apply.
	 * filemtime makes every edit cache-bust itself.
	 */
	$css_path = get_template_directory() . '/assets/css/main.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ROMSFUN_THEME_VERSION;

	wp_enqueue_style(
		'romsfun-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array(),
		$css_ver
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	if ( is_singular( 'rom' ) ) {
		$rate_path = get_template_directory() . '/assets/js/ratings.js';

		wp_enqueue_script(
			'romsfun-ratings',
			get_template_directory_uri() . '/assets/js/ratings.js',
			array(),
			file_exists( $rate_path ) ? (string) filemtime( $rate_path ) : ROMSFUN_THEME_VERSION,
			true
		);
	}

	// Loaded only where it is used. Shipping a lightbox to every visitor for a feature most never
	// open is exactly the kind of dead weight that costs a page-speed score.
	if ( is_singular( 'rom' ) && function_exists( 'romsfun_get_screenshots' ) && romsfun_get_screenshots() ) {
		$js_path = get_template_directory() . '/assets/js/lightbox.js';

		wp_enqueue_script(
			'romsfun-lightbox',
			get_template_directory_uri() . '/assets/js/lightbox.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : ROMSFUN_THEME_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'romsfun_enqueue_assets' );

/**
 * Strip output WordPress emits by default that costs bytes and leaks version numbers.
 */
function romsfun_clean_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'romsfun_clean_head' );

/**
 * Lazy-loading every image is the default, but the box art on a ROM page is the LCP element.
 * Lazy-loading it delays the largest paint and directly costs Core Web Vitals, so the first
 * in-content image on a singular view is loaded eagerly with high priority instead.
 */
function romsfun_prioritise_lcp_image( $attr, $attachment, $size ) {
	static $done = false;

	if ( ! $done && is_singular() && in_array( $size, array( 'rom-boxart', 'post-thumbnail', 'large', 'full' ), true ) ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
		$done                  = true;
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'romsfun_prioritise_lcp_image', 10, 3 );

/**
 * Archives are hub pages we want fully crawled, so they carry a generous page size. Anything much
 * larger starts hurting render time on mobile.
 */
function romsfun_archive_posts_per_page( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'rom' ) || $query->is_tax( array( 'console', 'genre', 'collection', 'rom_type', 'region' ) ) ) {
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'romsfun_archive_posts_per_page' );
