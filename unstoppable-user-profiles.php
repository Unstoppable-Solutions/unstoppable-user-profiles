<?php
/**
 * Plugin Name:       Unstoppable User Profiles
 * Plugin URI:        https://unstoppablesolutions.app/plugins/unstoppable-user-profiles
 * Description:       Let logged-in members edit their own profile and upload a profile photo from the front end — no media library access, works with any membership plugin.
 * Version:           1.1.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Unstoppable Solutions
 * Author URI:        https://unstoppablesolutions.app
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unstoppable-user-profiles
 * Domain Path:       /languages
 * Tested up to:      7.1
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core constants.
 */
define( 'UUPR_VERSION', '1.1.3' );
define( 'UUPR_PLUGIN_FILE', __FILE__ );
define( 'UUPR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UUPR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UUPR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Name of the dedicated uploads subfolder used for member photos.
// Kept OUT of the WordPress media library so it never clutters it or exposes other files.
define( 'UUPR_AVATAR_DIR', 'uupr-avatars' );

/**
 * Load plugin classes.
 */
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-uploads.php';
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-avatar.php';
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-form.php';
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-integrations.php';
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-admin.php';
require_once UUPR_PLUGIN_DIR . 'includes/class-uupr-profile.php';

/**
 * Boot the plugin once everything is loaded.
 *
 * @return UUPR_Profile
 */
function uupr_init() {
	return UUPR_Profile::get_instance();
}
add_action( 'plugins_loaded', 'uupr_init' );

/**
 * Activation: record install date and create the avatar upload directory.
 */
function uupr_activate() {
	if ( ! get_option( 'uupr_install_date' ) ) {
		update_option( 'uupr_install_date', time() );
	}

	UUPR_Uploads::ensure_avatar_dir();
}
register_activation_hook( __FILE__, 'uupr_activate' );
