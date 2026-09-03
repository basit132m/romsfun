<?php
/**
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- .rf-wrap -->
</main>

<footer class="rf-footer">
	<div class="rf-wrap">
		<a class="rf-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>

		<?php
		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
		}
		?>

		<?php $tagline = romsfun_get_option( 'footer_tagline' ); ?>
		<p class="rf-footer__tagline"<?php echo $tagline ? '' : ' hidden'; ?>><?php echo esc_html( $tagline ); ?></p>

		<p class="rf-footer__legal"><?php echo esc_html( romsfun_render_copyright() ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
