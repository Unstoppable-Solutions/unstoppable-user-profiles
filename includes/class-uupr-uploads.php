<?php
/**
 * Secure avatar upload handling.
 *
 * Stores member photos in a dedicated uploads subfolder, NOT as media library
 * attachments, so they never appear in (or clutter) the WordPress media library
 * and members never gain access to the library picker. Uses WordPress's own
 * upload and filesystem APIs throughout.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Uploads.
 */
class UUPR_Uploads {

	/**
	 * User ID for the in-progress upload, used by the filename callback.
	 *
	 * @var int
	 */
	private static $upload_user_id = 0;

	/**
	 * Allowed image MIME types (validated against the real file, not the extension).
	 *
	 * @return array
	 */
	public static function allowed_mimes() {
		return apply_filters(
			'uupr_allowed_mimes',
			array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'gif'      => 'image/gif',
				'webp'     => 'image/webp',
			)
		);
	}

	/**
	 * Maximum upload size in bytes, derived from the admin setting (MB).
	 *
	 * @return int
	 */
	public static function max_bytes() {
		$mb = (int) get_option( 'uupr_max_upload_mb', 2 );
		if ( $mb < 1 ) {
			$mb = 1;
		}
		return $mb * 1024 * 1024;
	}

	/**
	 * Absolute path to the dedicated avatar directory.
	 *
	 * @return string
	 */
	public static function avatar_path() {
		$uploads = wp_get_upload_dir();
		return trailingslashit( $uploads['basedir'] ) . UUPR_AVATAR_DIR;
	}

	/**
	 * Public base URL for the dedicated avatar directory.
	 *
	 * @return string
	 */
	public static function avatar_url_base() {
		$uploads = wp_get_upload_dir();
		return trailingslashit( $uploads['baseurl'] ) . UUPR_AVATAR_DIR;
	}

	/**
	 * Ensure the avatar directory exists and is protected from directory listing.
	 *
	 * @return bool
	 */
	public static function ensure_avatar_dir() {
		$path = self::avatar_path();

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		$index = trailingslashit( $path ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			self::write_silence_file( $index );
		}

		return file_exists( $path );
	}

	/**
	 * Write a silent index.php into the avatar directory via WP_Filesystem.
	 *
	 * @param string $index Absolute path to the index file.
	 * @return void
	 */
	private static function write_silence_file( $index ) {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( WP_Filesystem() ) {
			$mode = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : false;
			$wp_filesystem->put_contents( $index, "<?php\n// Silence is golden.\n", $mode );
		}
	}

	/**
	 * Handle a single uploaded avatar file for a given user.
	 *
	 * Validates the real MIME type, enforces the size cap, stores the file in the
	 * dedicated folder via wp_handle_upload(), and re-encodes the image to strip
	 * EXIF/metadata and normalise dimensions. Returns the stored filename on success.
	 *
	 * @param array $file    A single entry from $_FILES.
	 * @param int   $user_id Target user ID (ownership already verified by caller).
	 * @return string|WP_Error Stored filename (relative to the avatar dir) or error.
	 */
	public static function handle_upload( $file, $user_id ) {
		if ( empty( $file ) || empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'uupr_no_file', __( 'No file was uploaded.', 'unstoppable-user-profiles' ) );
		}

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'uupr_upload_error', __( 'The file could not be uploaded. Please try again.', 'unstoppable-user-profiles' ) );
		}

		// Custom size cap (friendlier than PHP's, and enforces the admin setting).
		if ( isset( $file['size'] ) && (int) $file['size'] > self::max_bytes() ) {
			return new WP_Error(
				'uupr_too_large',
				sprintf(
					/* translators: %d: maximum size in megabytes. */
					__( 'That image is too large. Maximum size is %d MB.', 'unstoppable-user-profiles' ),
					(int) get_option( 'uupr_max_upload_mb', 2 )
				)
			);
		}

		// Validate the REAL type from the file contents, not the claimed extension.
		$allowed = self::allowed_mimes();
		$check   = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $allowed );

		if ( empty( $check['type'] ) || ! in_array( $check['type'], $allowed, true ) ) {
			return new WP_Error( 'uupr_bad_type', __( 'Only JPG, PNG, GIF, or WebP images are allowed.', 'unstoppable-user-profiles' ) );
		}

		self::ensure_avatar_dir();

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Route this upload into the dedicated folder and name it predictably.
		self::$upload_user_id = absint( $user_id );
		add_filter( 'upload_dir', array( __CLASS__, 'filter_upload_dir' ) );

		$overrides = array(
			'test_form'                => false,
			'mimes'                    => $allowed,
			'unique_filename_callback' => array( __CLASS__, 'unique_filename_cb' ),
		);

		$result = wp_handle_upload( $file, $overrides );

		remove_filter( 'upload_dir', array( __CLASS__, 'filter_upload_dir' ) );
		self::$upload_user_id = 0;

		if ( ! is_array( $result ) || isset( $result['error'] ) ) {
			$message = is_array( $result ) && isset( $result['error'] )
				? $result['error']
				: __( 'The image could not be saved. Please try again.', 'unstoppable-user-profiles' );
			return new WP_Error( 'uupr_move_failed', $message );
		}

		// Re-encode to strip EXIF/metadata and cap dimensions. This neutralises any
		// payload hidden in metadata and normalises the stored image.
		$editor = wp_get_image_editor( $result['file'] );
		if ( ! is_wp_error( $editor ) ) {
			$max_dim = (int) apply_filters( 'uupr_max_dimension', 512 );
			$editor->resize( $max_dim, $max_dim, false );
			$editor->save( $result['file'] );
		}

		return basename( $result['file'] );
	}

	/**
	 * Filter the upload directory to the dedicated avatar folder (no year/month).
	 *
	 * @param array $dirs Upload directory data.
	 * @return array
	 */
	public static function filter_upload_dir( $dirs ) {
		$subdir = '/' . UUPR_AVATAR_DIR;

		$dirs['subdir'] = $subdir;
		$dirs['path']   = $dirs['basedir'] . $subdir;
		$dirs['url']    = $dirs['baseurl'] . $subdir;

		return $dirs;
	}

	/**
	 * Generate a non-guessable, user-scoped filename for wp_handle_upload().
	 *
	 * @param string $dir  Target directory.
	 * @param string $name Proposed file name.
	 * @param string $ext  File extension (includes the leading dot).
	 * @return string
	 */
	public static function unique_filename_cb( $dir, $name, $ext ) {
		unset( $dir, $name );
		return 'user-' . absint( self::$upload_user_id ) . '-' . wp_generate_password( 12, false, false ) . $ext;
	}

	/**
	 * Delete the stored avatar for a user (both live and pending), if any.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function delete_avatar( $user_id ) {
		foreach ( array( 'uupr_avatar', 'uupr_avatar_pending' ) as $meta_key ) {
			$filename = get_user_meta( $user_id, $meta_key, true );
			if ( ! empty( $filename ) ) {
				self::delete_file( $filename );
				delete_user_meta( $user_id, $meta_key );
			}
		}
	}

	/**
	 * Delete a single stored file by its relative filename, guarding the path.
	 *
	 * @param string $filename Relative filename inside the avatar dir.
	 * @return void
	 */
	public static function delete_file( $filename ) {
		$filename = basename( $filename ); // Never allow traversal.
		$full     = trailingslashit( self::avatar_path() ) . $filename;
		$real     = realpath( $full );
		$base     = realpath( self::avatar_path() );

		if ( $real && $base && 0 === strpos( $real, $base ) && is_file( $real ) ) {
			wp_delete_file( $real );
		}
	}

	/**
	 * Map a MIME type to a safe extension.
	 *
	 * @param string $mime MIME type.
	 * @return string
	 */
	public static function ext_for_mime( $mime ) {
		$map = array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		);
		return isset( $map[ $mime ] ) ? $map[ $mime ] : 'jpg';
	}
}
