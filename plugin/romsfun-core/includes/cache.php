<?php
/**
 * Cache purging.
 *
 * This stack has four independent caches — the browser, Cloudflare's edge, Varnish on the server,
 * and Redis via the object cache. A change can look like it "didn't work" when it is really just
 * being served from one of them, and chasing that through four separate interfaces wastes a lot of
 * time. This clears all of them from one button.
 *
 * The browser layer is handled at the source: assets are versioned by file modification time, so
 * an edited stylesheet gets a new URL automatically.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

const ROMSFUN_CF_ZONE_OPTION  = 'romsfun_cf_zone_id';
const ROMSFUN_CF_TOKEN_OPTION = 'romsfun_cf_api_token';
const ROMSFUN_AUTO_PURGE      = 'romsfun_auto_purge';

/**
 * Flush the WordPress object cache (Redis, when the drop-in is active).
 */
function romsfun_purge_object_cache(): array {
	$flushed = wp_cache_flush();

	return array(
		'label'  => __( 'Object cache (Redis)', 'romsfun' ),
		'ok'     => (bool) $flushed,
		'detail' => $flushed
			? __( 'Flushed.', 'romsfun' )
			: __( 'Nothing flushed — no persistent object cache appears to be active.', 'romsfun' ),
	);
}

/**
 * Ban everything for this host in Varnish.
 *
 * Varnish is only reachable from the server itself, so this is a loopback request. A connection
 * error here usually means Varnish simply is not fronting this site, which is not a failure worth
 * alarming anyone about — hence the softer reporting.
 */
function romsfun_purge_varnish(): array {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	$response = wp_remote_request(
		'http://127.0.0.1:6081/',
		array(
			'method'      => 'BAN',
			'timeout'     => 5,
			'sslverify'   => false,
			'headers'     => array(
				'Host'        => $host,
				'X-Ban-Host'  => $host,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'label'  => __( 'Varnish', 'romsfun' ),
			'ok'     => null,
			'detail' => __( 'Not reachable on port 6081 — likely not in front of this site. Skipped.', 'romsfun' ),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	return array(
		'label'  => __( 'Varnish', 'romsfun' ),
		'ok'     => $code >= 200 && $code < 300,
		'detail' => sprintf( __( 'Responded with HTTP %d.', 'romsfun' ), $code ),
	);
}

/**
 * Purge the Cloudflare edge cache.
 */
function romsfun_purge_cloudflare(): array {
	$zone  = trim( (string) get_option( ROMSFUN_CF_ZONE_OPTION, '' ) );
	$token = trim( (string) get_option( ROMSFUN_CF_TOKEN_OPTION, '' ) );

	if ( ! $zone || ! $token ) {
		return array(
			'label'  => __( 'Cloudflare', 'romsfun' ),
			'ok'     => null,
			'detail' => __( 'No API credentials saved. Skipped.', 'romsfun' ),
		);
	}

	$response = wp_remote_post(
		sprintf( 'https://api.cloudflare.com/client/v4/zones/%s/purge_cache', rawurlencode( $zone ) ),
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array( 'purge_everything' => true ) ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'label'  => __( 'Cloudflare', 'romsfun' ),
			'ok'     => false,
			'detail' => $response->get_error_message(),
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! empty( $body['success'] ) ) {
		return array(
			'label'  => __( 'Cloudflare', 'romsfun' ),
			'ok'     => true,
			'detail' => __( 'Edge cache purged.', 'romsfun' ),
		);
	}

	$message = $body['errors'][0]['message'] ?? __( 'Unknown error.', 'romsfun' );

	return array(
		'label'  => __( 'Cloudflare', 'romsfun' ),
		'ok'     => false,
		'detail' => $message,
	);
}

function romsfun_purge_all_caches(): array {
	return array(
		romsfun_purge_object_cache(),
		romsfun_purge_varnish(),
		romsfun_purge_cloudflare(),
	);
}

/**
 * Purge automatically when content changes, if enabled.
 *
 * Off by default: on a catalogue this size a bulk import would fire thousands of purges and burn
 * through the Cloudflare API rate limit. Worth enabling once the catalogue is stable and edits are
 * occasional.
 */
function romsfun_auto_purge_on_save( int $post_id, WP_Post $post ): void {
	if ( ! get_option( ROMSFUN_AUTO_PURGE, false ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post->post_status ) {
		return;
	}

	romsfun_purge_object_cache();
	romsfun_purge_varnish();
	romsfun_purge_cloudflare();
}
add_action( 'save_post', 'romsfun_auto_purge_on_save', 10, 2 );

// --- Admin UI ------------------------------------------------------------

function romsfun_cache_menu(): void {
	add_management_page(
		__( 'RomsFun Cache', 'romsfun' ),
		__( 'RomsFun Cache', 'romsfun' ),
		'manage_options',
		'romsfun-cache',
		'romsfun_render_cache_page'
	);
}
add_action( 'admin_menu', 'romsfun_cache_menu' );

/**
 * A one-click purge in the toolbar, available from any screen.
 */
function romsfun_cache_admin_bar( WP_Admin_Bar $bar ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$bar->add_node(
		array(
			'id'    => 'romsfun-purge',
			'title' => __( 'Purge Cache', 'romsfun' ),
			'href'  => wp_nonce_url( admin_url( 'tools.php?page=romsfun-cache&romsfun_purge=1' ), 'romsfun_purge' ),
			'meta'  => array( 'title' => __( 'Clear Redis, Varnish and Cloudflare caches', 'romsfun' ) ),
		)
	);
}
add_action( 'admin_bar_menu', 'romsfun_cache_admin_bar', 100 );

function romsfun_render_cache_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'romsfun' ) );
	}

	$results = null;

	// Save credentials.
	if ( isset( $_POST['romsfun_cache_settings'] ) && check_admin_referer( 'romsfun_cache_settings' ) ) {
		update_option( ROMSFUN_CF_ZONE_OPTION, sanitize_text_field( wp_unslash( $_POST['cf_zone_id'] ?? '' ) ) );

		// An empty token field means "leave it alone", so a saved token is never wiped just
		// because the form redisplays it masked.
		$token = trim( (string) wp_unslash( $_POST['cf_api_token'] ?? '' ) );
		if ( '' !== $token && ! str_contains( $token, '•' ) ) {
			update_option( ROMSFUN_CF_TOKEN_OPTION, sanitize_text_field( $token ) );
		}

		update_option( ROMSFUN_AUTO_PURGE, ! empty( $_POST['auto_purge'] ) );

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'romsfun' ) . '</p></div>';
	}

	// Run a purge.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified immediately below.
	if ( isset( $_GET['romsfun_purge'] ) || isset( $_POST['romsfun_purge_now'] ) ) {
		$valid = isset( $_GET['romsfun_purge'] )
			? wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'romsfun_purge' )
			: check_admin_referer( 'romsfun_purge_now' );

		if ( $valid ) {
			$results = romsfun_purge_all_caches();
		}
	}

	$zone       = (string) get_option( ROMSFUN_CF_ZONE_OPTION, '' );
	$has_token  = (bool) get_option( ROMSFUN_CF_TOKEN_OPTION, '' );
	$auto_purge = (bool) get_option( ROMSFUN_AUTO_PURGE, false );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RomsFun Cache', 'romsfun' ); ?></h1>

		<?php if ( $results ) : ?>
			<div class="notice notice-info">
				<p><strong><?php esc_html_e( 'Purge results', 'romsfun' ); ?></strong></p>
				<ul style="margin-left:18px;list-style:disc">
					<?php foreach ( $results as $result ) : ?>
						<li>
							<?php
							$icon = true === $result['ok'] ? '✅' : ( null === $result['ok'] ? '⏭️' : '⚠️' );
							printf(
								'%s <strong>%s</strong> — %s',
								esc_html( $icon ),
								esc_html( $result['label'] ),
								esc_html( $result['detail'] )
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p><?php esc_html_e( 'Browser caching is handled automatically — theme assets are versioned by their file modification time, so an edited file always gets a fresh URL.', 'romsfun' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:760px;padding:20px">
			<h2><?php esc_html_e( 'Purge everything now', 'romsfun' ); ?></h2>
			<p><?php esc_html_e( 'Clears the Redis object cache, Varnish, and the Cloudflare edge cache in one go. Use this whenever a change is not showing up on the live site.', 'romsfun' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'romsfun_purge_now' ); ?>
				<button type="submit" name="romsfun_purge_now" value="1" class="button button-primary button-hero">
					<?php esc_html_e( 'Purge All Caches', 'romsfun' ); ?>
				</button>
			</form>
		</div>

		<div class="card" style="max-width:760px;padding:20px;margin-top:20px">
			<h2><?php esc_html_e( 'Cloudflare credentials', 'romsfun' ); ?></h2>
			<p>
				<?php esc_html_e( 'Needed only for the Cloudflare step. Create a token scoped to Zone → Cache Purge on this zone alone — never use a Global API Key, which would grant full account access if the database were ever exposed.', 'romsfun' ); ?>
			</p>

			<form method="post">
				<?php wp_nonce_field( 'romsfun_cache_settings' ); ?>
				<input type="hidden" name="romsfun_cache_settings" value="1">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cf_zone_id"><?php esc_html_e( 'Zone ID', 'romsfun' ); ?></label></th>
						<td>
							<input type="text" id="cf_zone_id" name="cf_zone_id" class="regular-text" value="<?php echo esc_attr( $zone ); ?>">
							<p class="description"><?php esc_html_e( 'Cloudflare dashboard → your domain → Overview, in the right-hand sidebar.', 'romsfun' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cf_api_token"><?php esc_html_e( 'API Token', 'romsfun' ); ?></label></th>
						<td>
							<input type="password" id="cf_api_token" name="cf_api_token" class="regular-text" autocomplete="off"
								placeholder="<?php echo $has_token ? '••••••••••••••••' : ''; ?>">
							<p class="description">
								<?php
								echo $has_token
									? esc_html__( 'A token is saved. Leave blank to keep it, or paste a new one to replace it.', 'romsfun' )
									: esc_html__( 'Cloudflare dashboard → My Profile → API Tokens → Create Token → Custom → Zone / Cache Purge / Purge.', 'romsfun' );
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatic purge', 'romsfun' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="auto_purge" value="1" <?php checked( $auto_purge ); ?>>
								<?php esc_html_e( 'Purge caches whenever a post is published or updated', 'romsfun' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Leave this off while importing ROMs in bulk — an import would fire thousands of purges and exhaust the Cloudflare API rate limit. Useful once the catalogue is stable.', 'romsfun' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'romsfun' ) ); ?>
			</form>
		</div>
	</div>
	<?php
}
