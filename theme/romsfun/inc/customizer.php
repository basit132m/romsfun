<?php
/**
 * Theme settings.
 *
 * Built on the native Customizer rather than a settings framework: it ships with WordPress, gives
 * live preview for free, and adds no dependency to maintain.
 *
 * Every colour and metric here is emitted as a CSS custom property overriding the defaults in
 * main.css. The stylesheet stays the single source of truth for *how* things are styled; these
 * settings only change *what values* it uses.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults. Single source of truth — registration, sanitisation and CSS output all read this, so
 * adding a setting means editing one array.
 */
function romsfun_settings(): array {
	return array(
		// Brand colours apply in both light and dark.
		'brand_color'     => array( 'default' => '#e0175b', 'type' => 'color',  'var' => '--rf-brand',      'scope' => 'brand' ),
		'header_bg'       => array( 'default' => '#e0175b', 'type' => 'color',  'var' => '--rf-header-bg',  'scope' => 'brand' ),
		'header_text'     => array( 'default' => '#ffffff', 'type' => 'color',  'var' => '--rf-header-text','scope' => 'brand' ),
		'footer_bg'       => array( 'default' => '#e0175b', 'type' => 'color',  'var' => '--rf-footer-bg',  'scope' => 'brand' ),
		'footer_text'     => array( 'default' => '#ffffff', 'type' => 'color',  'var' => '--rf-footer-text','scope' => 'brand' ),

		// Light-mode palette. Dark mode derives its own values, so these are scoped to light only.
		'page_bg'         => array( 'default' => '#f6f7f9', 'type' => 'color',  'var' => '--rf-bg',         'scope' => 'light' ),
		'surface_bg'      => array( 'default' => '#ffffff', 'type' => 'color',  'var' => '--rf-surface',    'scope' => 'light' ),
		'border_color'    => array( 'default' => '#e6e8ec', 'type' => 'color',  'var' => '--rf-border',     'scope' => 'light' ),
		'text_color'      => array( 'default' => '#14161a', 'type' => 'color',  'var' => '--rf-text',       'scope' => 'light' ),
		'muted_color'     => array( 'default' => '#667085', 'type' => 'color',  'var' => '--rf-text-muted', 'scope' => 'light' ),

		// Layout metrics.
		'container_width' => array( 'default' => 1180, 'type' => 'number', 'var' => '--rf-wrap',          'unit' => 'px', 'min' => 900, 'max' => 1600, 'scope' => 'brand' ),
		'hero_height'     => array( 'default' => 520,  'type' => 'number', 'var' => '--rf-hero-height',   'unit' => 'px', 'min' => 240, 'max' => 800,  'scope' => 'brand' ),
		'hero_overlap'    => array( 'default' => 48,   'type' => 'number', 'var' => '--rf-hero-overlap',  'unit' => 'px', 'min' => 0,   'max' => 160,  'scope' => 'brand' ),
		'corner_radius'   => array( 'default' => 14,   'type' => 'number', 'var' => '--rf-radius',     'unit' => 'px', 'min' => 0,    'max' => 28,   'scope' => 'brand' ),
		'base_font_size'  => array( 'default' => 16,   'type' => 'number', 'var' => '--rf-font-size',  'unit' => 'px', 'min' => 14,   'max' => 20,   'scope' => 'brand' ),
	);
}

/**
 * Non-colour options that do not map to a CSS variable.
 */
function romsfun_option_defaults(): array {
	return array(
		'color_scheme'       => 'auto',
		'font_family'        => 'system',
		'sticky_header'      => true,
		'logo_max_width'     => 200,
		'show_checksums'     => true,
		'show_related'       => true,
		'related_count'      => 5,
		'download_label'     => __( 'Download ROM', 'romsfun' ),
		'footer_tagline'     => '',
		'copyright_text'     => '',

		// Homepage.
		'hero_image'           => 'https://roms-fun.net/wp-content/uploads/2026/09/hero.webp',
		'hero_eyebrow'         => __( 'Play Retro ROMs', 'romsfun' ),
		'hero_title_before'    => __( 'Play', 'romsfun' ),
		'hero_title_highlight' => __( 'Video Game Roms', 'romsfun' ),
		'hero_title_after'     => __( 'on your Computer or Phone', 'romsfun' ),
		'hero_subtitle'        => __( 'Search, filter, and download your favourite retro games from thousands of ROMs curated for nostalgia lovers.', 'romsfun' ),
		'announcement_enabled' => true,
		'announcement_label'   => __( 'Announcement', 'romsfun' ),
		'announcement_title'   => __( 'Welcome to RomsFun', 'romsfun' ),
		'announcement_text'    => '',
		'trending_title'       => __( 'Popular ROM', 'romsfun' ),
		'trending_count'       => 10,
		'latest_title'         => __( 'Latest ROM', 'romsfun' ),
		'latest_count'         => 10,
	);
}

function romsfun_get_option( string $key ) {
	$defaults = romsfun_option_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

function romsfun_customize_register( WP_Customize_Manager $wp_customize ): void {

	$wp_customize->add_panel(
		'romsfun_theme',
		array(
			'title'       => __( 'RomsFun Theme', 'romsfun' ),
			'description' => __( 'Colours, layout and catalogue options.', 'romsfun' ),
			'priority'    => 10,
		)
	);

	$sections = array(
		'romsfun_brand'   => __( 'Brand Colours', 'romsfun' ),
		'romsfun_palette' => __( 'Page Colours', 'romsfun' ),
		'romsfun_layout'  => __( 'Layout & Typography', 'romsfun' ),
		'romsfun_rom'     => __( 'ROM Pages', 'romsfun' ),
		'romsfun_footer'  => __( 'Footer', 'romsfun' ),
		'romsfun_home'    => __( 'Homepage', 'romsfun' ),
	);

	$order = 10;
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title'    => $title,
				'panel'    => 'romsfun_theme',
				'priority' => $order,
			)
		);
		$order += 10;
	}

	$labels = array(
		'brand_color'     => array( __( 'Brand / Accent', 'romsfun' ), 'romsfun_brand' ),
		'header_bg'       => array( __( 'Header Background', 'romsfun' ), 'romsfun_brand' ),
		'header_text'     => array( __( 'Header Text', 'romsfun' ), 'romsfun_brand' ),
		'footer_bg'       => array( __( 'Footer Background', 'romsfun' ), 'romsfun_brand' ),
		'footer_text'     => array( __( 'Footer Text', 'romsfun' ), 'romsfun_brand' ),
		'page_bg'         => array( __( 'Page Background', 'romsfun' ), 'romsfun_palette' ),
		'surface_bg'      => array( __( 'Card Background', 'romsfun' ), 'romsfun_palette' ),
		'border_color'    => array( __( 'Borders', 'romsfun' ), 'romsfun_palette' ),
		'text_color'      => array( __( 'Body Text', 'romsfun' ), 'romsfun_palette' ),
		'muted_color'     => array( __( 'Muted Text', 'romsfun' ), 'romsfun_palette' ),
		'container_width' => array( __( 'Content Width (px)', 'romsfun' ), 'romsfun_layout' ),
		'hero_height'     => array( __( 'Hero Image Height (px)', 'romsfun' ), 'romsfun_home' ),
		'hero_overlap'    => array( __( 'Search Panel Overlap (px)', 'romsfun' ), 'romsfun_home' ),
		'corner_radius'   => array( __( 'Corner Radius (px)', 'romsfun' ), 'romsfun_layout' ),
		'base_font_size'  => array( __( 'Base Font Size (px)', 'romsfun' ), 'romsfun_layout' ),
	);

	foreach ( romsfun_settings() as $key => $config ) {
		if ( ! isset( $labels[ $key ] ) ) {
			continue;
		}

		list( $label, $section ) = $labels[ $key ];

		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $config['default'],
				'sanitize_callback' => 'color' === $config['type'] ? 'sanitize_hex_color' : 'absint',
				// postMessage keeps colour changes instant in the preview instead of reloading the
				// whole page on every drag of the picker.
				'transport'         => 'postMessage',
			)
		);

		if ( 'color' === $config['type'] ) {
			$wp_customize->add_control(
				new WP_Customize_Color_Control( $wp_customize, $key, array( 'label' => $label, 'section' => $section ) )
			);
		} else {
			$wp_customize->add_control(
				$key,
				array(
					'label'       => $label,
					'section'     => $section,
					'type'        => 'number',
					'input_attrs' => array( 'min' => $config['min'], 'max' => $config['max'], 'step' => 1 ),
				)
			);
		}
	}

	// --- Non-CSS options -------------------------------------------------

	$wp_customize->add_setting( 'color_scheme', array( 'default' => 'auto', 'sanitize_callback' => 'sanitize_key' ) );
	$wp_customize->add_control(
		'color_scheme',
		array(
			'label'       => __( 'Colour Scheme', 'romsfun' ),
			'description' => __( '"Follow visitor device" respects each visitor\'s own light or dark preference.', 'romsfun' ),
			'section'     => 'romsfun_palette',
			'type'        => 'select',
			'choices'     => array(
				'auto'  => __( 'Follow visitor device', 'romsfun' ),
				'light' => __( 'Always light', 'romsfun' ),
				'dark'  => __( 'Always dark', 'romsfun' ),
			),
		)
	);

	$wp_customize->add_setting( 'font_family', array( 'default' => 'system', 'sanitize_callback' => 'sanitize_key' ) );
	$wp_customize->add_control(
		'font_family',
		array(
			'label'       => __( 'Font', 'romsfun' ),
			'description' => __( 'System fonts render instantly with no extra download. Web fonts add a network request before text can paint, which costs page-speed score.', 'romsfun' ),
			'section'     => 'romsfun_layout',
			'type'        => 'select',
			'choices'     => array(
				'system'    => __( 'System (fastest)', 'romsfun' ),
				'inter'     => 'Inter',
				'poppins'   => 'Poppins',
				'rubik'     => 'Rubik',
			),
		)
	);

	$wp_customize->add_setting( 'sticky_header', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control( 'sticky_header', array( 'label' => __( 'Sticky Header', 'romsfun' ), 'section' => 'romsfun_layout', 'type' => 'checkbox' ) );

	$wp_customize->add_setting( 'logo_max_width', array( 'default' => 200, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp_customize->add_control(
		'logo_max_width',
		array(
			'label'       => __( 'Logo Max Width (px)', 'romsfun' ),
			'section'     => 'title_tagline',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 80, 'max' => 420, 'step' => 5 ),
		)
	);

	// ROM page options.
	$wp_customize->add_setting( 'show_checksums', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control(
		'show_checksums',
		array(
			'label'       => __( 'Show File Verification (MD5 / SHA1)', 'romsfun' ),
			'description' => __( 'Recommended. Useful to visitors and unique text on pages that are otherwise mostly specifications.', 'romsfun' ),
			'section'     => 'romsfun_rom',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting( 'show_related', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control( 'show_related', array( 'label' => __( 'Show Related ROMs', 'romsfun' ), 'section' => 'romsfun_rom', 'type' => 'checkbox' ) );

	$wp_customize->add_setting( 'related_count', array( 'default' => 5, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		'related_count',
		array(
			'label'       => __( 'Number of Related ROMs', 'romsfun' ),
			'section'     => 'romsfun_rom',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 2, 'max' => 12, 'step' => 1 ),
		)
	);

	$wp_customize->add_setting( 'download_label', array( 'default' => __( 'Download ROM', 'romsfun' ), 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'postMessage' ) );
	$wp_customize->add_control( 'download_label', array( 'label' => __( 'Download Button Text', 'romsfun' ), 'section' => 'romsfun_rom', 'type' => 'text' ) );

	// Footer.
	$wp_customize->add_setting( 'footer_tagline', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'postMessage' ) );
	$wp_customize->add_control( 'footer_tagline', array( 'label' => __( 'Footer Tagline', 'romsfun' ), 'section' => 'romsfun_footer', 'type' => 'text' ) );

	$wp_customize->add_setting( 'copyright_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'postMessage' ) );
	$wp_customize->add_control(
		'copyright_text',
		array(
			'label'       => __( 'Copyright Text', 'romsfun' ),
			'description' => __( 'Leave blank for the automatic notice.', 'romsfun' ),
			'section'     => 'romsfun_footer',
			'type'        => 'text',
		)
	);

	// --- Homepage --------------------------------------------------------

	$wp_customize->add_setting(
		'hero_image',
		array(
			'default'           => romsfun_option_defaults()['hero_image'],
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'hero_image',
			array(
				'label'       => __( 'Hero Image', 'romsfun' ),
				'description' => __( 'Wide banner across the top of the homepage. Use a WebP around 1920px wide — it is the largest element on the page, so its file size directly affects your page-speed score.', 'romsfun' ),
				'section'     => 'romsfun_home',
			)
		)
	);

	$home_text = array(
		'hero_eyebrow'         => array( __( 'Hero Eyebrow', 'romsfun' ), 'text' ),
		'hero_title_before'    => array( __( 'Headline — before highlight', 'romsfun' ), 'text' ),
		'hero_title_highlight' => array( __( 'Headline — highlighted words', 'romsfun' ), 'text' ),
		'hero_title_after'     => array( __( 'Headline — after highlight', 'romsfun' ), 'text' ),
		'hero_subtitle'        => array( __( 'Hero Subtitle', 'romsfun' ), 'textarea' ),
		'announcement_label'   => array( __( 'Announcement Label', 'romsfun' ), 'text' ),
		'announcement_title'   => array( __( 'Announcement Title', 'romsfun' ), 'text' ),
		'trending_title'       => array( __( 'Trending Section Title', 'romsfun' ), 'text' ),
		'latest_title'         => array( __( 'Latest Section Title', 'romsfun' ), 'text' ),
	);

	foreach ( $home_text as $key => $conf ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => romsfun_option_defaults()[ $key ],
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control( $key, array( 'label' => $conf[0], 'section' => 'romsfun_home', 'type' => $conf[1] ) );
	}

	$wp_customize->add_setting( 'announcement_enabled', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control( 'announcement_enabled', array( 'label' => __( 'Show Announcement Section', 'romsfun' ), 'section' => 'romsfun_home', 'type' => 'checkbox' ) );

	$wp_customize->add_setting( 'announcement_text', array( 'default' => '', 'sanitize_callback' => 'wp_kses_post' ) );
	$wp_customize->add_control(
		'announcement_text',
		array(
			'label'       => __( 'Announcement Content', 'romsfun' ),
			'description' => __( 'Basic HTML allowed. Leave blank to hide the section.', 'romsfun' ),
			'section'     => 'romsfun_home',
			'type'        => 'textarea',
		)
	);

	foreach ( array( 'trending_count' => __( 'Trending — how many', 'romsfun' ), 'latest_count' => __( 'Latest — how many', 'romsfun' ) ) as $key => $label ) {
		$wp_customize->add_setting( $key, array( 'default' => 10, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control(
			$key,
			array(
				'label'       => $label,
				'section'     => 'romsfun_home',
				'type'        => 'number',
				'input_attrs' => array( 'min' => 3, 'max' => 24, 'step' => 1 ),
			)
		);
	}

	// Live-preview bindings for the pieces postMessage handles.
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'copyright_text',
			array(
				'selector'        => '.rf-footer__legal',
				'render_callback' => 'romsfun_render_copyright',
			)
		);
	}
}
add_action( 'customize_register', 'romsfun_customize_register' );

/**
 * Emit the settings as CSS custom properties.
 *
 * Light-mode overrides are wrapped in a light media query rather than applied to bare `:root`.
 * Inline styles load after the stylesheet, so an unguarded `:root` block would also win inside the
 * dark-mode media query and silently break dark mode.
 */
function romsfun_customizer_css(): string {
	$settings = romsfun_settings();
	$brand    = array();
	$light    = array();

	foreach ( $settings as $key => $config ) {
		$value = get_theme_mod( $key, $config['default'] );

		if ( '' === $value || null === $value ) {
			continue;
		}

		$declaration = sprintf( '%s:%s%s;', $config['var'], $value, $config['unit'] ?? '' );

		if ( 'light' === $config['scope'] ) {
			$light[] = $declaration;
		} else {
			$brand[] = $declaration;
		}
	}

	$fonts = array(
		'system'  => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		'inter'   => 'Inter, system-ui, sans-serif',
		'poppins' => 'Poppins, system-ui, sans-serif',
		'rubik'   => 'Rubik, system-ui, sans-serif',
	);

	$font = romsfun_get_option( 'font_family' );
	if ( isset( $fonts[ $font ] ) ) {
		$brand[] = sprintf( '--rf-font:%s;', $fonts[ $font ] );
	}

	$css = ':root{' . implode( '', $brand ) . '}';

	if ( $light ) {
		$light_css = implode( '', $light );
		$css .= sprintf(
			'@media(prefers-color-scheme:light){:root:not([data-theme="dark"]){%s}}:root[data-theme="light"]{%s}',
			$light_css,
			$light_css
		);
	}

	if ( ! romsfun_get_option( 'sticky_header' ) ) {
		$css .= '.rf-header{position:static;}';
	}

	$logo_width = (int) romsfun_get_option( 'logo_max_width' );
	if ( $logo_width ) {
		$css .= sprintf( '.rf-logo img{max-width:%dpx;height:auto;}', $logo_width );
	}

	return $css;
}

function romsfun_enqueue_customizer_css(): void {
	wp_add_inline_style( 'romsfun-main', romsfun_customizer_css() );

	$font = romsfun_get_option( 'font_family' );

	// Only request a web font when one was actually chosen — the default costs nothing.
	if ( 'system' !== $font ) {
		$families = array(
			'inter'   => 'Inter:wght@400;500;600;700',
			'poppins' => 'Poppins:wght@400;500;600;700',
			'rubik'   => 'Rubik:wght@400;500;600;700',
		);

		if ( isset( $families[ $font ] ) ) {
			wp_enqueue_style(
				'romsfun-font',
				sprintf( 'https://fonts.googleapis.com/css2?family=%s&display=swap', $families[ $font ] ),
				array(),
				null
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'romsfun_enqueue_customizer_css', 20 );

/**
 * Apply an explicit colour scheme to the <html> element when the site forces light or dark.
 */
function romsfun_html_scheme_attribute( string $output ): string {
	$scheme = romsfun_get_option( 'color_scheme' );

	if ( in_array( $scheme, array( 'light', 'dark' ), true ) ) {
		$output .= sprintf( ' data-theme="%s"', esc_attr( $scheme ) );
	}

	return $output;
}
add_filter( 'language_attributes', 'romsfun_html_scheme_attribute' );

function romsfun_render_copyright(): string {
	$custom = romsfun_get_option( 'copyright_text' );

	if ( $custom ) {
		return esc_html( $custom );
	}

	return sprintf(
		/* translators: 1: current year, 2: site name */
		esc_html__( 'Copyright © %1$s %2$s — All rights reserved', 'romsfun' ),
		esc_html( gmdate( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

function romsfun_customize_preview_js(): void {
	wp_enqueue_script(
		'romsfun-customizer-preview',
		get_template_directory_uri() . '/assets/js/customizer-preview.js',
		array( 'customize-preview' ),
		ROMSFUN_THEME_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'romsfun_customize_preview_js' );
