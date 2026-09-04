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
		<?php if ( has_custom_logo() ) : ?>
			<div class="rf-logo">
				<?php the_custom_logo(); ?>
			</div>
		<?php else : ?>
			<a class="rf-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php bloginfo( 'name' ); ?>
			</a>
		<?php endif; ?>

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

		<?php /* <details> gives a keyboard-accessible dropdown with no JavaScript at all. */ ?>
		<details class="rf-header__search">
			<summary aria-label="<?php esc_attr_e( 'Search', 'romsfun' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
				</svg>
			</summary>

			<div class="rf-header__search-panel">
				<form role="search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'rom' ) ); ?>">
					<input type="hidden" name="post_type" value="rom">
					<label class="screen-reader-text" for="rf-header-search"><?php esc_html_e( 'Search ROMs', 'romsfun' ); ?></label>
					<input type="search" id="rf-header-search" name="s" placeholder="<?php esc_attr_e( 'Search ROMs…', 'romsfun' ); ?>">
					<button type="submit" class="rf-btn rf-btn--sm"><?php esc_html_e( 'Go', 'romsfun' ); ?></button>
				</form>
			</div>
		</details>
	</div>
</header>

<main id="rf-main" class="rf-main">
	<div class="rf-wrap">
