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
		// Bytes, not "1.2 GB". Stored as a number so it can be sorted and filtered; formatted only
		// on output. Storing the display string is the classic mistake that makes a size facet
		// impossible later.
		'file_size_bytes' => array( 'label' => 'File Size (bytes)', 'type' => 'number', 'meta' => 'integer' ),
		'download_url'    => array( 'label' => 'Download URL',  'type' => 'url',    'meta' => 'string' ),
		'download_count'  => array( 'label' => 'Download Count','type' => 'number', 'meta' => 'integer' ),
		'md5'             => array( 'label' => 'MD5',           'type' => 'text',   'meta' => 'string' ),
		'sha1'            => array( 'label' => 'SHA1',          'type' => 'text',   'meta' => 'string' ),
		'rating_value'    => array( 'label' => 'Rating (0-5)',  'type' => 'number', 'meta' => 'number' ),
		'rating_count'    => array( 'label' => 'Rating Count',  'type' => 'number', 'meta' => 'integer' ),
	);
}

/**
 * Expose fields to the REST API so the block editor and any future headless client can read them.
 */
function romsfun_register_rom_meta(): void {
	foreach ( romsfun_rom_fields() as $key => $field ) {
		register_post_meta(
			'rom',
			'_rf_' . $key,
			array(
				'type'         => $field['meta'],
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
			)
		);
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
}
add_action( 'add_meta_boxes', 'romsfun_add_rom_meta_box' );

function romsfun_render_rom_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'romsfun_save_rom', 'romsfun_rom_nonce' );

	echo '<style>.rf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}.rf-grid label{display:block;font-weight:600;margin-bottom:4px}.rf-grid input{width:100%}</style>';
	echo '<div class="rf-grid">';

	foreach ( romsfun_rom_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, '_rf_' . $key, true );
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
}

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

	foreach ( romsfun_rom_fields() as $key => $field ) {
		$input = 'rf_' . $key;

		if ( ! isset( $_POST[ $input ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $input ] );

		$value = match ( $field['type'] ) {
			'url'    => esc_url_raw( $raw ),
			'number' => 'integer' === $field['meta'] ? (int) $raw : (float) $raw,
			default  => sanitize_text_field( $raw ),
		};

		if ( '' === $value || null === $value ) {
			delete_post_meta( $post_id, '_rf_' . $key );
			continue;
		}

		update_post_meta( $post_id, '_rf_' . $key, $value );
	}
}
add_action( 'save_post_rom', 'romsfun_save_rom_meta' );

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
