<?php
/**
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="rf-skip-link" href="#rf-main"><?php esc_html_e( 'Skip to content', 'romsfun' ); ?></a>

<header class="rf-header">
	<div class="rf-wrap rf-header__inner">
		<a class="rf-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php bloginfo( 'name' ); ?>
		</a>

		<nav class="rf-nav" aria-label="<?php esc_attr_e( 'Primary', 'romsfun' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			} else {
				// Until a menu is assigned, link the hubs that matter so the site is never
				// orphaned — a nav-less site is a crawl dead end.
				echo '<ul>';
				printf( '<li><a href="%s">%s</a></li>', esc_url( get_post_type_archive_link( 'rom' ) ), esc_html__( 'Roms', 'romsfun' ) );
				printf( '<li><a href="%s">%s</a></li>', esc_url( get_post_type_archive_link( 'emulator' ) ), esc_html__( 'Emulators', 'romsfun' ) );
				printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/blog/' ) ), esc_html__( 'Blog', 'romsfun' ) );
				echo '</ul>';
			}
			?>
		</nav>

		<a class="rf-header__account" href="<?php echo esc_url( wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Account', 'romsfun' ); ?>">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
			</svg>
		</a>
	</div>
</header>

<main id="rf-main" class="rf-main">
	<div class="rf-wrap">
