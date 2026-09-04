<?php
/**
 * Comment policy: no links.
 *
 * A ROM site attracts a lot of automated link-drop spam, and links in user comments are both an
 * SEO liability (you are vouching for whatever gets posted) and a safety one (visitors trust links
 * that appear on your pages). Rather than moderate that stream forever, links are simply not
 * permitted.
 *
 * Enforced in three places, because any one of them alone leaves a gap:
 *   1. Rejected on submission
 *   2. WordPress's auto-linking of bare URLs is switched off
 *   3. Any anchor that reaches the database anyway is stripped on output
 *
 * @package romsfun
 */

defined( 'ABSPATH' ) || exit;

/**
 * Matches URLs, bare domains and raw anchor tags.
 *
 * `\w+\.(com|net|...)` catches "example.com" written without a scheme, which is how most spam
 * arrives once a naive http:// check is in place.
 */
function romsfun_comment_link_pattern(): string {
	return '#(https?://|ftp://|www\.|<a\s|\[url|\b[a-z0-9-]+\.(com|net|org|io|ru|cn|xyz|top|info|biz|shop|online|site)\b)#i';
}

/**
 * Reject comments containing links.
 *
 * Editors and administrators are exempt — they may legitimately need to link to a related ROM or
 * an emulator guide when answering someone.
 */
function romsfun_reject_comment_links( array $commentdata ): array {
	if ( current_user_can( 'moderate_comments' ) ) {
		return $commentdata;
	}

	$haystack = $commentdata['comment_content'] . ' ' . ( $commentdata['comment_author_url'] ?? '' );

	if ( preg_match( romsfun_comment_link_pattern(), $haystack ) ) {
		wp_die(
			esc_html__( 'Links are not allowed in comments. Please remove any web addresses and try again.', 'romsfun' ),
			esc_html__( 'Comment not posted', 'romsfun' ),
			array(
				'response'  => 403,
				'back_link' => true,
			)
		);
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'romsfun_reject_comment_links' );

/**
 * Drop any author URL that gets through, so the comment author name never becomes a link.
 */
function romsfun_strip_comment_author_url( $url ) {
	return '';
}
add_filter( 'get_comment_author_url', 'romsfun_strip_comment_author_url' );
add_filter( 'pre_comment_author_url', 'romsfun_strip_comment_author_url' );

/**
 * Remove the website field from the comment form entirely — the most effective way to stop people
 * filling it in is not to ask.
 */
function romsfun_remove_comment_url_field( array $fields ): array {
	unset( $fields['url'] );
	return $fields;
}
add_filter( 'comment_form_default_fields', 'romsfun_remove_comment_url_field' );

/**
 * Stop WordPress turning bare URLs in comment text into links, and strip any anchor that is
 * already stored — older comments, or anything inserted directly into the database.
 */
function romsfun_unlink_comment_text(): void {
	remove_filter( 'comment_text', 'make_clickable', 9 );
}
add_action( 'init', 'romsfun_unlink_comment_text' );

function romsfun_strip_comment_anchors( string $text ): string {
	return preg_replace( '#<a\b[^>]*>(.*?)</a>#is', '$1', $text );
}
add_filter( 'comment_text', 'romsfun_strip_comment_anchors', 20 );
