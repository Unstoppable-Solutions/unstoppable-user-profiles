<?php
/**
 * Front-end profile form.
 *
 * Renders a self-service edit form via the [uupr_profile_form] shortcode for the
 * CURRENT logged-in user only, and handles the AJAX save with full nonce,
 * capability, ownership, sanitisation, and escaping discipline.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Form.
 */
class UUPR_Form {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_shortcode( 'uupr_profile_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_uupr_save_profile', array( $this, 'ajax_save_profile' ) );
		add_action( 'wp_ajax_uupr_delete_avatar', array( $this, 'ajax_delete_avatar' ) );
	}

	/**
	 * Editable fields, filterable so other plugins can extend the set.
	 *
	 * @return array
	 */
	public static function fields() {
		$enabled = get_option(
			'uupr_enabled_fields',
			array( 'first_name', 'last_name', 'display_name', 'user_email', 'user_url', 'description' )
		);

		$all = array(
			'first_name'   => array(
				'label' => __( 'First name', 'unstoppable-user-profiles' ),
				'type'  => 'text',
			),
			'last_name'    => array(
				'label' => __( 'Last name', 'unstoppable-user-profiles' ),
				'type'  => 'text',
			),
			'display_name' => array(
				'label' => __( 'Display name', 'unstoppable-user-profiles' ),
				'type'  => 'text',
			),
			'user_email'   => array(
				'label' => __( 'Email address', 'unstoppable-user-profiles' ),
				'type'  => 'email',
			),
			'user_url'     => array(
				'label' => __( 'Website', 'unstoppable-user-profiles' ),
				'type'  => 'url',
			),
			'description'  => array(
				'label' => __( 'Bio', 'unstoppable-user-profiles' ),
				'type'  => 'textarea',
			),
		);

		$fields = array();
		foreach ( $all as $key => $config ) {
			if ( in_array( $key, (array) $enabled, true ) ) {
				$fields[ $key ] = $config;
			}
		}

		return apply_filters( 'uupr_editable_fields', $fields );
	}

	/**
	 * Enqueue front-end assets (no inline blocks, no CDN, no bundled jQuery).
	 */
	public function enqueue_assets() {
		wp_register_style( 'uupr-frontend', UUPR_PLUGIN_URL . 'assets/css/frontend.css', array(), UUPR_VERSION );
		wp_register_script( 'uupr-frontend', UUPR_PLUGIN_URL . 'assets/js/frontend.js', array(), UUPR_VERSION, true );

		wp_localize_script(
			'uupr-frontend',
			'uuprData',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'uupr_save_profile' ),
				'maxBytes' => UUPR_Uploads::max_bytes(),
				'i18n'     => array(
					'saving'      => __( 'Saving…', 'unstoppable-user-profiles' ),
					'saved'       => __( 'Profile saved.', 'unstoppable-user-profiles' ),
					'genericErr'  => __( 'Something went wrong. Please try again.', 'unstoppable-user-profiles' ),
					'tooLarge'    => __( 'That image is too large.', 'unstoppable-user-profiles' ),
					'confirmDel'  => __( 'Remove your profile photo?', 'unstoppable-user-profiles' ),
				),
			)
		);
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		unset( $atts );

		if ( ! is_user_logged_in() ) {
			return '<p class="uupr-notice">' . esc_html__( 'Please log in to edit your profile.', 'unstoppable-user-profiles' ) . '</p>';
		}

		wp_enqueue_style( 'uupr-frontend' );
		wp_enqueue_script( 'uupr-frontend' );

		$user      = wp_get_current_user();
		$fields    = self::fields();
		$avatar    = get_avatar( $user->ID, 96 );
		$has_photo  = (bool) get_user_meta( $user->ID, 'uupr_avatar', true );
		$pending    = (bool) get_user_meta( $user->ID, 'uupr_avatar_pending', true );

		ob_start();
		?>
		<form class="uupr-form" id="uupr-form" enctype="multipart/form-data">
			<?php wp_nonce_field( 'uupr_save_profile', 'uupr_nonce' ); ?>

			<div class="uupr-photo">
				<div class="uupr-photo-current" id="uupr-photo-preview">
					<?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns safe, escaped HTML. ?>
				</div>
				<div class="uupr-photo-controls">
					<label class="uupr-file-label">
						<span><?php esc_html_e( 'Choose photo', 'unstoppable-user-profiles' ); ?></span>
						<input type="file" name="uupr_avatar" id="uupr-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp">
					</label>
					<?php if ( $has_photo ) : ?>
						<button type="button" class="uupr-remove" id="uupr-remove-photo">
							<?php esc_html_e( 'Remove photo', 'unstoppable-user-profiles' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $pending ) : ?>
						<p class="uupr-pending"><?php esc_html_e( 'Your new photo is awaiting approval.', 'unstoppable-user-profiles' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<?php foreach ( $fields as $key => $config ) : ?>
				<?php
				$value = self::get_field_value( $user, $key );
				$id    = 'uupr-field-' . esc_attr( $key );
				?>
				<p class="uupr-field uupr-field-<?php echo esc_attr( $config['type'] ); ?>">
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $config['label'] ); ?></label>
					<?php if ( 'textarea' === $config['type'] ) : ?>
						<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="4"><?php echo esc_textarea( $value ); ?></textarea>
					<?php else : ?>
						<input
							type="<?php echo esc_attr( $config['type'] ); ?>"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( $value ); ?>">
					<?php endif; ?>
				</p>
			<?php endforeach; ?>

			<p class="uupr-actions">
				<button type="submit" class="uupr-submit"><?php esc_html_e( 'Save profile', 'unstoppable-user-profiles' ); ?></button>
				<span class="uupr-status" id="uupr-status" role="status" aria-live="polite"></span>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a standalone photo-only form, for embedding on native profile pages
	 * (e.g. the MemberPress Account page) that already provide their own text fields.
	 *
	 * Reuses the same element IDs and AJAX endpoints as the full shortcode form, so
	 * the existing front-end script drives it with no extra code.
	 *
	 * @param WP_User $user The user whose photo is shown.
	 * @return string
	 */
	public static function render_account_photo_form( $user ) {
		$has_photo = (bool) get_user_meta( $user->ID, 'uupr_avatar', true );
		$pending   = (bool) get_user_meta( $user->ID, 'uupr_avatar_pending', true );

		ob_start();
		?>
		<form class="uupr-form uupr-photo-only" id="uupr-form" enctype="multipart/form-data">
			<?php wp_nonce_field( 'uupr_save_profile', 'uupr_nonce' ); ?>

			<div class="uupr-photo">
				<div class="uupr-photo-current" id="uupr-photo-preview">
					<?php echo get_avatar( $user->ID, 96 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns safe HTML. ?>
				</div>
				<div class="uupr-photo-controls">
					<label class="uupr-file-label">
						<span><?php esc_html_e( 'Choose photo', 'unstoppable-user-profiles' ); ?></span>
						<input type="file" name="uupr_avatar" id="uupr-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp">
					</label>
					<?php if ( $has_photo ) : ?>
						<button type="button" class="uupr-remove" id="uupr-remove-photo">
							<?php esc_html_e( 'Remove photo', 'unstoppable-user-profiles' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $pending ) : ?>
						<p class="uupr-pending"><?php esc_html_e( 'Your new photo is awaiting approval.', 'unstoppable-user-profiles' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<p class="uupr-actions">
				<button type="submit" class="uupr-submit"><?php esc_html_e( 'Save photo', 'unstoppable-user-profiles' ); ?></button>
				<span class="uupr-status" id="uupr-status" role="status" aria-live="polite"></span>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Read a field value from the user object/meta.
	 *
	 * @param WP_User $user The user.
	 * @param string  $key  Field key.
	 * @return string
	 */
	private static function get_field_value( $user, $key ) {
		switch ( $key ) {
			case 'display_name':
				return $user->display_name;
			case 'user_email':
				return $user->user_email;
			case 'user_url':
				return $user->user_url;
			default:
				return get_user_meta( $user->ID, $key, true );
		}
	}

	/**
	 * AJAX: save the current user's profile.
	 */
	public function ajax_save_profile() {
		// 1. Verify intent.
		check_ajax_referer( 'uupr_save_profile', 'nonce' );

		// 2. Must be logged in. The only profile a member may edit is their own,
		//    so the user ID comes from the session — never from the request (prevents IDOR).
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'unstoppable-user-profiles' ) ) );
		}

		$user_id = get_current_user_id();
		$fields  = self::fields();
		$userdata = array( 'ID' => $user_id );
		$errors   = array();

		// 3. Sanitise each enabled field (unslash BEFORE sanitise).
		foreach ( $fields as $key => $config ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// Baseline sanitisation on access; refined per field type below.
			$raw = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );

			switch ( $config['type'] ) {
				case 'email':
					$email = sanitize_email( $raw );
					if ( empty( $email ) || ! is_email( $email ) ) {
						$errors[] = __( 'Please enter a valid email address.', 'unstoppable-user-profiles' );
						break;
					}
					$existing = email_exists( $email );
					if ( $existing && (int) $existing !== $user_id ) {
						$errors[] = __( 'That email address is already in use.', 'unstoppable-user-profiles' );
						break;
					}
					$userdata['user_email'] = $email;
					break;

				case 'url':
					$userdata['user_url'] = esc_url_raw( $raw );
					break;

				case 'textarea':
					update_user_meta( $user_id, $key, $raw );
					break;

				case 'text':
				default:
					$clean = sanitize_text_field( $raw );
					if ( 'display_name' === $key ) {
						if ( '' === $clean ) {
							$errors[] = __( 'Display name cannot be empty.', 'unstoppable-user-profiles' );
							break;
						}
						$userdata['display_name'] = $clean;
					} else {
						update_user_meta( $user_id, $key, $clean );
					}
					break;
			}
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', array_map( 'esc_html', $errors ) ) ) );
		}

		// 4. Update core user record if needed.
		if ( count( $userdata ) > 1 ) {
			$result = wp_update_user( $userdata );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
			}
		}

		// 5. Handle an avatar upload, if present.
		$avatar_message = '';
		if ( ! empty( $_FILES['uupr_avatar']['name'] ) ) {
			// $_FILES is passed straight to wp_handle_upload(), which validates the
			// file (real MIME type, size, extension) before it is ever stored.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$file   = $_FILES['uupr_avatar'];
			$stored = UUPR_Uploads::handle_upload( $file, $user_id );

			if ( is_wp_error( $stored ) ) {
				wp_send_json_error( array( 'message' => esc_html( $stored->get_error_message() ) ) );
			}

			$moderate = ( 'yes' === get_option( 'uupr_moderate_uploads', 'no' ) );

			if ( $moderate ) {
				// Hold as pending; clean up any prior pending file first.
				$prev_pending = get_user_meta( $user_id, 'uupr_avatar_pending', true );
				if ( $prev_pending ) {
					UUPR_Uploads::delete_file( $prev_pending );
				}
				update_user_meta( $user_id, 'uupr_avatar_pending', $stored );
				$avatar_message = __( 'Your photo was uploaded and is awaiting approval.', 'unstoppable-user-profiles' );
			} else {
				// Replace the live photo; remove the old file.
				$prev = get_user_meta( $user_id, 'uupr_avatar', true );
				if ( $prev ) {
					UUPR_Uploads::delete_file( $prev );
				}
				update_user_meta( $user_id, 'uupr_avatar', $stored );
			}
		}

		$message = __( 'Profile saved.', 'unstoppable-user-profiles' );
		if ( $avatar_message ) {
			$message .= ' ' . $avatar_message;
		}

		wp_send_json_success(
			array(
				'message'    => esc_html( $message ),
				'avatarUrl'  => esc_url( get_avatar_url( $user_id, array( 'size' => 96 ) ) ),
			)
		);
	}

	/**
	 * AJAX: delete the current user's avatar.
	 */
	public function ajax_delete_avatar() {
		check_ajax_referer( 'uupr_save_profile', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'unstoppable-user-profiles' ) ) );
		}

		$user_id = get_current_user_id();
		UUPR_Uploads::delete_avatar( $user_id );

		wp_send_json_success(
			array(
				'message'   => esc_html__( 'Photo removed.', 'unstoppable-user-profiles' ),
				'avatarUrl' => esc_url( get_avatar_url( $user_id, array( 'size' => 96 ) ) ),
			)
		);
	}
}
