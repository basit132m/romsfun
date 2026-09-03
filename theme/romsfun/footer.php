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

		<p class="rf-footer__legal">
			<?php
			printf(
				/* translators: 1: start year, 2: current year, 3: site name */
				esc_html__( 'Copyright © %1$s–%2$s %3$s — All rights reserved', 'romsfun' ),
				'2026',
				esc_html( gmdate( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
