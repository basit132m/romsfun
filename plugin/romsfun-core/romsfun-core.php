<?php
/**
 * Plugin Name:       RomsFun Core
 * Description:       Content architecture for the RomsFun catalogue — post types, taxonomies and permalinks.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            RomsFun
 * License:           GPL-2.0-or-later
 * Text Domain:       romsfun
 *
 * Registration lives in a plugin rather than the theme on purpose: the catalogue must survive a
 * theme change. A theme holding 70,000 posts hostage is the most common way these sites end up
 * unable to redesign.
 */

defined( 'ABSPATH' ) || exit;

define( 'ROMSFUN_CORE_VERSION', '1.0.0' );
define( 'ROMSFUN_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ROMSFUN_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once ROMSFUN_CORE_PATH . 'includes/post-types.php';
require_once ROMSFUN_CORE_PATH . 'includes/taxonomies.php';
require_once ROMSFUN_CORE_PATH . 'includes/permalinks.php';
require_once ROMSFUN_CORE_PATH . 'includes/fields.php';
require_once ROMSFUN_CORE_PATH . 'includes/cache.php';
require_once ROMSFUN_CORE_PATH . 'includes/ratings.php';
require_once ROMSFUN_CORE_PATH . 'includes/comments.php';
require_once ROMSFUN_CORE_PATH . 'includes/site-verification.php';
require_once ROMSFUN_CORE_PATH . 'includes/meta-tags.php';

/**
 * Rewrite rules are expensive to regenerate, so we only flush on activation and deactivation —
 * never on init, which is a common and costly mistake.
 */
function romsfun_activate(): void {
	romsfun_register_post_types();
	romsfun_register_taxonomies();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'romsfun_activate' );

function romsfun_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'romsfun_deactivate' );
