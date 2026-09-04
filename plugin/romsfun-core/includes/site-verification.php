<?php
/**
 * Search engine verification and header/footer code.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verification services, keyed by the option name, with the meta tag name each one expects.
 */
function romsfun_verification_services(): array {
	return array(
		'romsfun_verify_google'    => array(
			'label' => __( 'Google Search Console', 'romsfun' ),
			'meta'  => 'google-site-verification',
			'hint'  => __( 'Search Console → Settings → Ownership verification → HTML tag.', 'romsfun' ),
		),
		'romsfun_verify_bing'      => array(
			'label' => __( 'Bing Webmaster Tools', 'romsfun' ),
			'meta'  => 'msvalidate.01',
			'hint'  => __( 'Bing Webmaster Tools → Add site → HTML Meta Tag.', 'romsfun' ),
		),
		'romsfun_verify_yandex'    => array(
			'label' => __( 'Yandex Webmaster', 'romsfun' ),
			'meta'  => 'yandex-verification',
			'hint'  => '',
		),
		'romsfun_verify_pinterest' => array(
			'label' => __( 'Pinterest', 'romsfun' ),
			'meta'  => 'p:domain_verify',
			'hint'  => '',
		),
	);
}

/**
 * Pull the token out of whatever the user pasted.
 *
 * People paste the whole `<meta name="..." content="TOKEN">` tag far more often than the bare
 * token, and a stored tag-inside-a-tag silently fails verification with no visible error. Accepting
 * both removes the most common way this goes wrong.
 */
function romsfun_extract_verification_token( string $input ): string {
	$input = trim( $input );

	if ( preg_match( '/content=["\']([^"\']+)["\']/i', $input, $matches ) ) {
		return trim( $matches[1] );
	}

	return sanitize_text_field( $input );
}

function romsfun_output_verification_tags(): void {
	foreach ( romsfun_verification_services() as $option => $service ) {
		$token = get_option( $option, '' );

		if ( ! $token ) {
			continue;
		}

		printf(
			'<meta name="%s" content="%s">' . "\n",
			esc_attr( $service['meta'] ),
			esc_attr( $token )
		);
	}

	$header_code = get_option( 'romsfun_header_code', '' );

	if ( $header_code ) {
		// Intentionally unescaped: this field exists to inject analytics and ad tags, and it is
		// writable only by an administrator who can already edit plugin files.
		echo "\n" . $header_code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_head', 'romsfun_output_verification_tags', 1 );

function romsfun_output_footer_code(): void {
	$footer_code = get_option( 'romsfun_footer_code', '' );

	if ( $footer_code ) {
		echo "\n" . $footer_code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_footer', 'romsfun_output_footer_code', 99 );

function romsfun_verification_menu(): void {
	add_options_page(
		__( 'RomsFun SEO', 'romsfun' ),
		__( 'RomsFun SEO', 'romsfun' ),
		'manage_options',
		'romsfun-seo',
		'romsfun_render_verification_page'
	);
}
add_action( 'admin_menu', 'romsfun_verification_menu' );

function romsfun_render_verification_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'romsfun' ) );
	}

	if ( isset( $_POST['romsfun_seo_save'] ) && check_admin_referer( 'romsfun_seo_save' ) ) {
		foreach ( array_keys( romsfun_verification_services() ) as $option ) {
			$raw = isset( $_POST[ $option ] ) ? wp_unslash( $_POST[ $option ] ) : '';
			update_option( $option, romsfun_extract_verification_token( (string) $raw ) );
		}

		// Raw code is stored only for users who may post unfiltered HTML, which on a single site
		// means administrators. Anyone else saving this form leaves these two fields untouched
		// rather than having their input silently mangled by sanitisation.
		update_option(
			'romsfun_home_description',
			sanitize_text_field( (string) wp_unslash( $_POST['romsfun_home_description'] ?? '' ) )
		);

		if ( current_user_can( 'unfiltered_html' ) ) {
			update_option( 'romsfun_header_code', (string) wp_unslash( $_POST['romsfun_header_code'] ?? '' ) );
			update_option( 'romsfun_footer_code', (string) wp_unslash( $_POST['romsfun_footer_code'] ?? '' ) );
		}

		// The tag has to be visible to the crawler, and the page it needs to appear on is almost
		// certainly sitting in Varnish or Cloudflare right now.
		if ( function_exists( 'romsfun_purge_all_caches' ) ) {
			romsfun_purge_all_caches();
		}

		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__( 'Saved, and caches purged so the tag is live immediately.', 'romsfun' )
			. '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RomsFun SEO', 'romsfun' ); ?></h1>

		<form method="post">
			<?php wp_nonce_field( 'romsfun_seo_save' ); ?>
			<input type="hidden" name="romsfun_seo_save" value="1">

			<div class="card" style="max-width:820px;padding:20px">
				<h2><?php esc_html_e( 'Homepage meta description', 'romsfun' ); ?></h2>
				<p>
					<?php esc_html_e( 'Shown under your homepage title in search results. Aim for 150–160 characters — longer gets truncated. Every other page derives its description automatically from its excerpt or term description.', 'romsfun' ); ?>
				</p>
				<?php $home_desc = (string) get_option( 'romsfun_home_description', '' ); ?>
				<textarea name="romsfun_home_description" rows="3" class="large-text"
					maxlength="300"><?php echo esc_textarea( $home_desc ); ?></textarea>
				<p class="description">
					<?php
					printf(
						/* translators: %d: character count */
						esc_html__( 'Currently %d characters.', 'romsfun' ),
						(int) mb_strlen( $home_desc )
					);
					?>
				</p>
			</div>

			<div class="card" style="max-width:820px;padding:20px;margin-top:20px">
				<h2><?php esc_html_e( 'Site verification', 'romsfun' ); ?></h2>
				<p><?php esc_html_e( 'Paste either the full meta tag or just the token — both work.', 'romsfun' ); ?></p>

				<table class="form-table" role="presentation">
					<?php foreach ( romsfun_verification_services() as $option => $service ) : ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $service['label'] ); ?></label></th>
							<td>
								<input type="text" class="large-text code"
									id="<?php echo esc_attr( $option ); ?>"
									name="<?php echo esc_attr( $option ); ?>"
									value="<?php echo esc_attr( get_option( $option, '' ) ); ?>">
								<?php if ( $service['hint'] ) : ?>
									<p class="description"><?php echo esc_html( $service['hint'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>

			<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
				<div class="card" style="max-width:820px;padding:20px;margin-top:20px">
					<h2><?php esc_html_e( 'Header & footer code', 'romsfun' ); ?></h2>
					<p>
						<?php esc_html_e( 'For analytics, ad tags and anything else that must load site-wide. Output exactly as entered — anything pasted here runs on every page, so only paste code you trust and understand.', 'romsfun' ); ?>
					</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="romsfun_header_code"><?php esc_html_e( 'Before &lt;/head&gt;', 'romsfun' ); ?></label></th>
							<td>
								<textarea id="romsfun_header_code" name="romsfun_header_code" rows="7" class="large-text code"><?php
									echo esc_textarea( get_option( 'romsfun_header_code', '' ) );
								?></textarea>
								<p class="description"><?php esc_html_e( 'Google Analytics, AdSense, Tag Manager.', 'romsfun' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="romsfun_footer_code"><?php esc_html_e( 'Before &lt;/body&gt;', 'romsfun' ); ?></label></th>
							<td>
								<textarea id="romsfun_footer_code" name="romsfun_footer_code" rows="7" class="large-text code"><?php
									echo esc_textarea( get_option( 'romsfun_footer_code', '' ) );
								?></textarea>
								<p class="description"><?php esc_html_e( 'Chat widgets and anything else that should not block rendering.', 'romsfun' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			<?php endif; ?>

			<?php submit_button( __( 'Save & Purge Caches', 'romsfun' ) ); ?>
		</form>

		<div class="card" style="max-width:820px;padding:20px">
			<h2><?php esc_html_e( 'A better option than the meta tag', 'romsfun' ); ?></h2>
			<p>
				<?php esc_html_e( 'Your DNS is on Cloudflare, so you can verify a Domain property instead: in Search Console choose "Domain" rather than "URL prefix", then add the TXT record it gives you in Cloudflare DNS.', 'romsfun' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'A Domain property covers http and https, www and non-www, and every subdomain in one property — so you see all your data in one place instead of split across four. It also cannot be broken by a theme change or a caching layer serving a stale page.', 'romsfun' ); ?>
			</p>
		</div>
	</div>
	<?php
}
