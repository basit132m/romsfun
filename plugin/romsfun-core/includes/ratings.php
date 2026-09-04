<?php
/**
 * Visitor ratings.
 *
 * Ratings are collected from real visitors rather than typed in by an editor. That is not only
 * more honest — Google's structured data policy prohibits self-serving AggregateRating markup, and
 * fabricated ratings put the rich result (and potentially the whole site) at risk of a manual
 * action. Real votes are the only version of this feature worth shipping.
 *
 * The full 1–5 distribution is stored so the histogram costs nothing to render, and the derived
 * average and count are mirrored into flat meta keys so `orderby => meta_value_num` sorting keeps
 * working.
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

const ROMSFUN_DIST_META  = '_rf_rating_dist';
const ROMSFUN_VALUE_META = '_rf_rating_value';
const ROMSFUN_COUNT_META = '_rf_rating_count';

/**
 * ROMs and emulators are both rateable — an emulator page is a recommendation like any other, and
 * the rating is what makes it useful.
 */
function romsfun_is_rateable( int $post_id ): bool {
	return in_array( (string) get_post_type( $post_id ), array( 'rom', 'emulator' ), true );
}

function romsfun_get_rating_distribution( ?int $post_id = null ): array {
	$stored = get_post_meta( $post_id ?: get_the_ID(), ROMSFUN_DIST_META, true );
	$dist   = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );

	if ( is_array( $stored ) ) {
		foreach ( $dist as $star => $unused ) {
			$dist[ $star ] = isset( $stored[ $star ] ) ? (int) $stored[ $star ] : 0;
		}
	}

	return $dist;
}

function romsfun_get_rating_count( ?int $post_id = null ): int {
	return array_sum( romsfun_get_rating_distribution( $post_id ) );
}

function romsfun_get_rating_average( ?int $post_id = null ): float {
	$dist  = romsfun_get_rating_distribution( $post_id );
	$count = array_sum( $dist );

	if ( ! $count ) {
		return 0.0;
	}

	$total = 0;
	foreach ( $dist as $star => $votes ) {
		$total += $star * $votes;
	}

	return round( $total / $count, 1 );
}

/**
 * Has this visitor already rated this ROM?
 *
 * Logged-in users are tracked authoritatively in user meta. Anonymous visitors are deduplicated by
 * a hashed IP transient, which expires rather than accumulating — storing every voter's IP in post
 * meta would grow unbounded across 70,000 ROMs. The IP is hashed with the site's auth salt so the
 * stored value is not personally identifying.
 *
 * This stops casual double-voting, not a determined attacker. Vote manipulation on a public rating
 * widget is a fundamentally unwinnable arms race; the goal is a signal that is broadly honest.
 */
function romsfun_has_rated( int $post_id ): bool {
	if ( is_user_logged_in() ) {
		return (bool) get_user_meta( get_current_user_id(), '_rf_rated_' . $post_id, true );
	}

	return (bool) get_transient( romsfun_rating_key( $post_id ) );
}

function romsfun_rating_key( int $post_id ): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return 'rf_rate_' . substr( wp_hash( $ip . '|' . $post_id ), 0, 24 );
}

function romsfun_record_rating( int $post_id, int $stars ): bool {
	if ( $stars < 1 || $stars > 5 || ! romsfun_is_rateable( $post_id ) ) {
		return false;
	}

	if ( romsfun_has_rated( $post_id ) ) {
		return false;
	}

	$dist            = romsfun_get_rating_distribution( $post_id );
	$dist[ $stars ]++;

	update_post_meta( $post_id, ROMSFUN_DIST_META, $dist );

	// Mirrored so sorting by rating stays a simple numeric meta query.
	$count = array_sum( $dist );
	$total = 0;
	foreach ( $dist as $star => $votes ) {
		$total += $star * $votes;
	}

	update_post_meta( $post_id, ROMSFUN_VALUE_META, round( $total / $count, 1 ) );
	update_post_meta( $post_id, ROMSFUN_COUNT_META, $count );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), '_rf_rated_' . $post_id, $stars );
	} else {
		set_transient( romsfun_rating_key( $post_id ), $stars, MONTH_IN_SECONDS );
	}

	return true;
}

/**
 * REST endpoint for submitting a rating.
 *
 * Deliberately public. The page HTML is served from Varnish and Cloudflare, so a nonce embedded in
 * it would be stale for most visitors and the widget would fail for exactly the people it is meant
 * for. Rating a public page is not a privileged action — the worst outcome of a forged request is
 * a single skewed vote, which the deduplication above already limits, and a nonce would not stop
 * scripted manipulation regardless.
 */
function romsfun_register_rating_route(): void {
	register_rest_route(
		'romsfun/v1',
		'/rate',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'romsfun_handle_rating',
			'args'                => array(
				'post_id' => array( 'required' => true, 'type' => 'integer' ),
				'stars'   => array( 'required' => true, 'type' => 'integer' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'romsfun_register_rating_route' );

function romsfun_handle_rating( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$stars   = (int) $request->get_param( 'stars' );

	if ( 'publish' !== get_post_status( $post_id ) || ! romsfun_is_rateable( $post_id ) ) {
		return new WP_Error( 'romsfun_invalid_post', __( 'That page could not be found.', 'romsfun' ), array( 'status' => 404 ) );
	}

	if ( $stars < 1 || $stars > 5 ) {
		return new WP_Error( 'romsfun_invalid_rating', __( 'Ratings run from 1 to 5.', 'romsfun' ), array( 'status' => 400 ) );
	}

	$already = romsfun_has_rated( $post_id );

	if ( ! $already ) {
		romsfun_record_rating( $post_id, $stars );
	}

	return rest_ensure_response(
		array(
			'recorded'     => ! $already,
			'message'      => $already
				? __( 'You have already rated this ROM.', 'romsfun' )
				: __( 'Thanks for rating!', 'romsfun' ),
			'average'      => romsfun_get_rating_average( $post_id ),
			'count'        => romsfun_get_rating_count( $post_id ),
			'distribution' => romsfun_get_rating_distribution( $post_id ),
		)
	);
}
