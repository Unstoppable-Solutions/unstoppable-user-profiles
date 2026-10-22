<?php
/**
 * Uninstall routine.
 *
 * Removes plugin options, per-user profile-photo meta, and the dedicated avatar
 * directory. Runs only when the plugin is deleted from the Plugins screen.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove options.
$uupr_options = array(
	'uupr_max_upload_mb',
	'uupr_moderate_uploads',
	'uupr_enabled_fields',
	'uupr_install_date',
);
foreach ( $uupr_options as $uupr_option ) {
	delete_option( $uupr_option );
}

// Remove per-user avatar meta.
delete_metadata( 'user', 0, 'uupr_avatar', '', true );
delete_metadata( 'user', 0, 'uupr_avatar_pending', '', true );

// Remove the dedicated avatar directory and its contents via WP_Filesystem.
$uupr_uploads = wp_get_upload_dir();
$uupr_dir     = trailingslashit( $uupr_uploads['basedir'] ) . 'uupr-avatars';

if ( is_dir( $uupr_dir ) ) {
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( WP_Filesystem() && $wp_filesystem->is_dir( $uupr_dir ) ) {
		$wp_filesystem->delete( $uupr_dir, true );
	}
}
