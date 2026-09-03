<?php
/**
 * RomsFun theme setup.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

define( 'ROMSFUN_THEME_VERSION', '1.0.0' );

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

function romsfun_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'caption', 'style', 'script' ) );

	// Box art is portrait and the LCP element on every ROM page, so it gets its own size rather
	// than being scaled down from a landscape crop.
	add_image_size( 'rom-boxart', 400, 560, true );
	add_image_size( 'rom-card', 300, 420, true );

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
	wp_enqueue_style(
		'romsfun-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array(),
		ROMSFUN_THEME_VERSION
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
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
