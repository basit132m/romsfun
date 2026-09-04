<?php
/**
 * ROM metadata.
 *
 * Registered here rather than through ACF so the catalogue carries no paid dependency and the data
 * stays in plain post meta — readable by any importer, exporter or future rebuild.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions. Single source of truth: the meta box, the save handler, the REST
 * registration and the templates all read from this.
 */
function romsfun_rom_fields(): array {
	return array(
		'release_date'    => array( 'label' => 'Release Date', 'type' => 'date',   'meta' => 'string' ),
		'publisher'       => array( 'label' => 'Publisher',    'type' => 'text',   'meta' => 'string' ),
		'developer'       => array( 'label' => 'Developer',    'type' => 'text',   'meta' => 'string' ),
		'version'         => array( 'label' => 'Version',      'type' => 'text',   'meta' => 'string' ),
		'languages'       => array( 'label' => 'Languages',    'type' => 'text',   'meta' => 'string' ),
		// Entered as a value plus a unit, but stored in bytes. Bytes are what make the field
		// sortable and filterable; "1.2 GB" as a string is neither. The unit selector exists so
		// nobody has to type 1288490188.
		'file_size_bytes' => array( 'label' => 'File Size', 'type' => 'filesize', 'meta' => 'integer' ),
		'download_url'    => array( 'label' => 'Download URL',  'type' => 'url',    'meta' => 'string' ),
		'download_count'  => array( 'label' => 'Download Count','type' => 'number', 'meta' => 'integer' ),
		'md5'             => array( 'label' => 'MD5',           'type' => 'text',   'meta' => 'string' ),
		'sha1'            => array( 'label' => 'SHA1',          'type' => 'text',   'meta' => 'string' ),
	);
}

/**
 * Emulator metadata. A separate set from ROMs — an emulator has a licence and a project homepage,
 * and no region or checksum.
 */
function romsfun_emulator_fields(): array {
	return array(
		'version'         => array( 'label' => 'Version',       'type' => 'text',     'meta' => 'string' ),
		'developer'       => array( 'label' => 'Developer',     'type' => 'text',     'meta' => 'string' ),
		'license'         => array( 'label' => 'License',       'type' => 'text',     'meta' => 'string' ),
		'release_date'    => array( 'label' => 'Last Updated',  'type' => 'date',     'meta' => 'string' ),
		'file_size_bytes' => array( 'label' => 'File Size',     'type' => 'filesize', 'meta' => 'integer' ),
		'download_url'    => array( 'label' => 'Download URL',  'type' => 'url',      'meta' => 'string' ),
		'website'         => array( 'label' => 'Official Site', 'type' => 'url',      'meta' => 'string' ),
		'download_count'  => array( 'label' => 'Download Count','type' => 'number',   'meta' => 'integer' ),
	);
}

/**
 * Which post types carry RomsFun metadata, and which field set each uses.
 */
function romsfun_fields_for( string $post_type ): array {
	return match ( $post_type ) {
		'rom'      => romsfun_rom_fields(),
		'emulator' => romsfun_emulator_fields(),
		default    => array(),
	};
}

function romsfun_meta_post_types(): array {
	return array( 'rom', 'emulator' );
}

/**
 * Expose fields to the REST API so the block editor and any future headless client can read them.
 */
function romsfun_register_rom_meta(): void {
	foreach ( romsfun_meta_post_types() as $post_type ) {
	foreach ( romsfun_fields_for( $post_type ) as $key => $field ) {
		register_post_meta(
			$post_type,
			'_rf_' . $key,
			array(
				'type'         => $field['meta'],
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
			)
		);
	}
	}

	register_post_meta(
		'rom',
		'_primary_console',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
		)
	);
}
add_action( 'init', 'romsfun_register_rom_meta' );

function romsfun_add_rom_meta_box(): void {
	add_meta_box(
		'romsfun_rom_details',
		__( 'ROM Details', 'romsfun' ),
		'romsfun_render_rom_meta_box',
		'rom',
		'normal',
		'high'
	);

	add_meta_box(
		'romsfun_emulator_details',
		__( 'Emulator Details', 'romsfun' ),
		'romsfun_render_rom_meta_box',
		'emulator',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'romsfun_add_rom_meta_box' );

function romsfun_render_rom_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'romsfun_save_rom', 'romsfun_rom_nonce' );

	echo '<style>.rf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}.rf-grid label{display:block;font-weight:600;margin-bottom:4px}.rf-grid input{width:100%}</style>';
	echo '<div class="rf-grid">';

	foreach ( romsfun_fields_for( $post->post_type ) as $key => $field ) {
		$value = get_post_meta( $post->ID, '_rf_' . $key, true );

		if ( 'filesize' === $field['type'] ) {
			romsfun_render_filesize_field( $key, $field['label'], (int) $value );
			continue;
		}

		printf(
			'<div><label for="rf_%1$s">%2$s</label><input type="%3$s" id="rf_%1$s" name="rf_%1$s" value="%4$s" %5$s></div>',
			esc_attr( $key ),
			esc_html( $field['label'] ),
			esc_attr( $field['type'] ),
			esc_attr( $value ),
			'number' === $field['type'] ? 'step="any"' : ''
		);
	}

	echo '</div>';

	// The emulator relation only makes sense on a ROM.
	if ( 'rom' === $post->post_type ) {
		romsfun_render_emulator_field( $post->ID );
	}

	romsfun_render_screenshots_field( $post->ID );
}

/**
 * Emulator picker.
 *
 * A relation to a published `emulator` post rather than a free-text field, so the Download
 * Emulator button always points at a page that exists and the two post types stay linked for
 * internal linking.
 */
function romsfun_render_emulator_field( int $post_id ): void {
	$selected = (int) get_post_meta( $post_id, '_rf_emulator', true );

	$emulators = get_posts(
		array(
			'post_type'      => 'emulator',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<hr style="margin:20px 0">
	<p style="font-weight:600;margin-bottom:6px"><?php esc_html_e( 'Recommended Emulator', 'romsfun' ); ?></p>

	<?php if ( $emulators ) : ?>
		<select name="rf_emulator" style="max-width:420px;width:100%">
			<option value="0"><?php esc_html_e( '— Auto (match by console) —', 'romsfun' ); ?></option>
			<?php foreach ( $emulators as $emulator ) : ?>
				<option value="<?php echo esc_attr( $emulator->ID ); ?>" <?php selected( $selected, $emulator->ID ); ?>>
					<?php echo esc_html( $emulator->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Shown as the "Download Emulator" button. On Auto, the newest published emulator sharing this ROM\'s console is used.', 'romsfun' ); ?>
		</p>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'No emulators published yet. Add some under Emulators, give each one a Console, and they will appear here.', 'romsfun' ); ?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Split a byte count back into the largest sensible unit for editing, so a value saved as
 * 1288490188 comes back as "1.2 GB" rather than a wall of digits.
 */
function romsfun_split_bytes( int $bytes ): array {
	if ( $bytes <= 0 ) {
		return array( '', 'MB' );
	}

	foreach ( array( 'GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024 ) as $unit => $factor ) {
		if ( $bytes >= $factor ) {
			return array( rtrim( rtrim( number_format( $bytes / $factor, 2, '.', '' ), '0' ), '.' ), $unit );
		}
	}

	return array( (string) $bytes, 'B' );
}

function romsfun_render_filesize_field( string $key, string $label, int $bytes ): void {
	list( $value, $unit ) = romsfun_split_bytes( $bytes );
	?>
	<div>
		<label for="rf_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		<span style="display:flex;gap:6px">
			<input type="number" step="any" min="0" style="flex:1"
				id="rf_<?php echo esc_attr( $key ); ?>"
				name="rf_<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $value ); ?>">
			<select name="rf_<?php echo esc_attr( $key ); ?>_unit" style="width:auto">
				<?php foreach ( array( 'B', 'KB', 'MB', 'GB' ) as $u ) : ?>
					<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $unit, $u ); ?>><?php echo esc_html( $u ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</div>
	<?php
}

/**
 * Screenshot gallery, backed by the native media modal.
 */
function romsfun_render_screenshots_field( int $post_id ): void {
	$ids = array_filter( array_map( 'absint', (array) get_post_meta( $post_id, '_rf_screenshots', true ) ) );
	?>
	<hr style="margin:20px 0">
	<p style="font-weight:600;margin-bottom:6px"><?php esc_html_e( 'Screenshots', 'romsfun' ); ?></p>
	<p class="description" style="margin-bottom:10px">
		<?php esc_html_e( 'Upload at 1280×720. Shown as a row under the details table and opened in a lightbox.', 'romsfun' ); ?>
	</p>

	<div id="rf-screenshots" class="rf-shots-admin">
		<?php foreach ( $ids as $id ) : ?>
			<span class="rf-shot-admin" data-id="<?php echo esc_attr( $id ); ?>">
				<?php echo wp_get_attachment_image( $id, array( 120, 68 ) ); ?>
				<button type="button" class="rf-shot-remove" aria-label="<?php esc_attr_e( 'Remove', 'romsfun' ); ?>">&times;</button>
			</span>
		<?php endforeach; ?>
	</div>

	<input type="hidden" id="rf-screenshots-input" name="rf_screenshots" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<button type="button" class="button" id="rf-screenshots-add"><?php esc_html_e( 'Add / Edit Screenshots', 'romsfun' ); ?></button>

	<style>
		.rf-shots-admin { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
		.rf-shot-admin { position: relative; display: inline-block; line-height: 0; }
		.rf-shot-admin img { border-radius: 4px; display: block; }
		.rf-shot-remove { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%;
			border: 0; background: #d63638; color: #fff; cursor: pointer; line-height: 1; font-size: 14px; }
	</style>
	<?php
}

/**
 * The media modal is not loaded on every admin screen, so it has to be requested for this one.
 */
function romsfun_admin_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true )
		|| ! in_array( (string) get_post_type(), romsfun_meta_post_types(), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'romsfun-admin',
		ROMSFUN_CORE_URL . 'assets/admin.js',
		array( 'jquery' ),
		ROMSFUN_CORE_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'romsfun_admin_assets' );

function romsfun_save_rom_meta( int $post_id ): void {
	if ( ! isset( $_POST['romsfun_rom_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['romsfun_rom_nonce'] ) ), 'romsfun_save_rom' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( romsfun_fields_for( (string) get_post_type( $post_id ) ) as $key => $field ) {
		$input = 'rf_' . $key;

		if ( ! isset( $_POST[ $input ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $input ] );

		if ( 'filesize' === $field['type'] ) {
			$unit  = sanitize_key( wp_unslash( $_POST[ $input . '_unit' ] ?? 'MB' ) );
			$scale = array( 'b' => 1, 'kb' => 1024, 'mb' => 1048576, 'gb' => 1073741824 );
			$value = (int) round( (float) $raw * ( $scale[ $unit ] ?? 1 ) );
		} else {
			$value = match ( $field['type'] ) {
				'url'    => esc_url_raw( $raw ),
				'number' => 'integer' === $field['meta'] ? (int) $raw : (float) $raw,
				default  => sanitize_text_field( $raw ),
			};
		}

		if ( '' === $value || null === $value ) {
			delete_post_meta( $post_id, '_rf_' . $key );
			continue;
		}

		update_post_meta( $post_id, '_rf_' . $key, $value );
	}

	if ( isset( $_POST['rf_emulator'] ) ) {
		$emulator_id = absint( wp_unslash( $_POST['rf_emulator'] ) );

		if ( $emulator_id && 'emulator' === get_post_type( $emulator_id ) ) {
			update_post_meta( $post_id, '_rf_emulator', $emulator_id );
		} else {
			delete_post_meta( $post_id, '_rf_emulator' );
		}
	}

	if ( isset( $_POST['rf_screenshots'] ) ) {
		$ids = array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['rf_screenshots'] ) ) ) ) ) );

		if ( $ids ) {
			update_post_meta( $post_id, '_rf_screenshots', $ids );
		} else {
			delete_post_meta( $post_id, '_rf_screenshots' );
		}
	}
}
add_action( 'save_post_rom', 'romsfun_save_rom_meta' );
add_action( 'save_post_emulator', 'romsfun_save_rom_meta' );

function romsfun_get_screenshots( ?int $post_id = null ): array {
	return array_filter( array_map( 'absint', (array) get_post_meta( $post_id ?: get_the_ID(), '_rf_screenshots', true ) ) );
}

/**
 * Format a byte count for display. Kept in the plugin so templates, the REST API and any future
 * importer all render sizes identically.
 */
function romsfun_format_bytes( $bytes ): string {
	$bytes = (int) $bytes;

	if ( $bytes <= 0 ) {
		return '';
	}

	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$power = min( (int) floor( log( $bytes, 1024 ) ), count( $units ) - 1 );
	$size  = $bytes / ( 1024 ** $power );

	return sprintf( $size < 10 && $power > 0 ? '%.2f %s' : '%.0f %s', $size, $units[ $power ] );
}

function romsfun_get_field( string $key, ?int $post_id = null ) {
	return get_post_meta( $post_id ?: get_the_ID(), '_rf_' . $key, true );
}

/**
 * Resolve the emulator to offer alongside a ROM.
 *
 * An explicit selection wins. Otherwise fall back to an emulator sharing the ROM's console, so a
 * catalogue imported in bulk still offers the right emulator without anyone setting it by hand on
 * 70,000 entries.
 */
function romsfun_get_rom_emulator( ?int $post_id = null ): ?WP_Post {
	$post_id  = $post_id ?: get_the_ID();
	$explicit = (int) get_post_meta( $post_id, '_rf_emulator', true );

	if ( $explicit && 'publish' === get_post_status( $explicit ) ) {
		return get_post( $explicit );
	}

	$consoles = wp_get_post_terms( $post_id, 'console', array( 'fields' => 'ids' ) );

	if ( empty( $consoles ) || is_wp_error( $consoles ) ) {
		return null;
	}

	$matches = get_posts(
		array(
			'post_type'      => 'emulator',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'console',
					'field'    => 'term_id',
					'terms'    => $consoles,
				),
			),
		)
	);

	return $matches ? $matches[0] : null;
}
