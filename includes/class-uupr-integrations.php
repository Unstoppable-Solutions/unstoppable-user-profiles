<?php
/**
 * Native profile-page integrations.
 *
 * Adds the photo uploader to profile editors that already provide their own text
 * fields, so it lives "on the profile page itself" instead of a separate page:
 *
 *   - WordPress admin profile editor (Users > Profile / Edit User) — for you/staff.
 *   - MemberPress Account page (front-end) — a "Profile Photo" tab for members.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Integrations.
 */
class UUPR_Integrations {

	/**
	 * The MemberPress account-page action slug for our tab.
	 *
	 * @var string
	 */
	private $mepr_action = 'uupr-photo';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		// --- WordPress admin profile editor ---
		add_action( 'show_user_profile', array( $this, 'render_admin_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_admin_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_admin_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_admin_field' ) );
		add_action( 'user_edit_form_tag', array( $this, 'add_multipart_enctype' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_profile_assets' ) );
		add_filter( 'user_profile_picture_description', '__return_empty_string' );

		// --- MemberPress Account page ---
		add_action( 'mepr_account_nav', array( $this, 'mepr_add_tab' ) );
		add_action( 'mepr_account_nav_content', array( $this, 'mepr_tab_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_mepr_assets' ), 20 );
	}

	/* ---------------------------------------------------------------------
	 * WordPress admin profile editor
	 * ------------------------------------------------------------------- */

	/**
	 * Ensure the core profile form can carry a file upload.
	 */
	public function add_multipart_enctype() {
		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Hide the redundant native "Profile Picture" row on the profile screens.
	 *
	 * Our own "Profile Photo" section replaces it, and the native row's Gravatar
	 * link is misleading once members upload directly. Done via wp_add_inline_style
	 * (scoped to the profile editors only) rather than an echoed <style> block.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_profile_assets( $hook ) {
		if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
			return;
		}

		wp_register_style( 'uupr-admin-profile', false, array(), UUPR_VERSION );
		wp_enqueue_style( 'uupr-admin-profile' );
		wp_add_inline_style( 'uupr-admin-profile', 'tr.user-profile-picture{display:none;}' );
	}

	/**
	 * Render the photo controls on the admin profile screen.
	 *
	 * @param WP_User $user The user being edited.
	 */
	public function render_admin_field( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'Profile Photo', 'unstoppable-user-profiles' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Current photo', 'unstoppable-user-profiles' ); ?></th>
				<td><?php echo get_avatar( $user->ID, 96 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe HTML. ?></td>
			</tr>
			<tr>
				<th><label for="uupr_admin_avatar"><?php esc_html_e( 'Upload new photo', 'unstoppable-user-profiles' ); ?></label></th>
				<td>
					<?php wp_nonce_field( 'uupr_admin_save_' . $user->ID, 'uupr_admin_nonce' ); ?>
					<input type="file" name="uupr_admin_avatar" id="uupr_admin_avatar" accept="image/jpeg,image/png,image/gif,image/webp">
					<p class="description">
						<?php esc_html_e( 'JPG, PNG, GIF, or WebP. This photo replaces the avatar everywhere it appears.', 'unstoppable-user-profiles' ); ?>
					</p>
					<?php if ( get_user_meta( $user->ID, 'uupr_avatar', true ) ) : ?>
						<label>
							<input type="checkbox" name="uupr_admin_remove" value="1">
							<?php esc_html_e( 'Remove current photo', 'unstoppable-user-profiles' ); ?>
						</label>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the admin profile photo.
	 *
	 * @param int $user_id The user being saved.
	 */
	public function save_admin_field( $user_id ) {
		// Capability: own profile or, for others, edit_users.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Verify our own nonce in addition to core's profile nonce.
		if (
			! isset( $_POST['uupr_admin_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['uupr_admin_nonce'] ) ), 'uupr_admin_save_' . $user_id )
		) {
			return;
		}

		// Removal request.
		if ( ! empty( $_POST['uupr_admin_remove'] ) ) {
			UUPR_Uploads::delete_avatar( $user_id );
			return;
		}

		// New upload (admins are trusted, so it goes live immediately).
		if ( ! empty( $_FILES['uupr_admin_avatar']['name'] ) ) {
			// $_FILES is passed straight to wp_handle_upload(), which validates the
			// file (real MIME type, size, extension) before it is ever stored.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$file   = $_FILES['uupr_admin_avatar'];
			$stored = UUPR_Uploads::handle_upload( $file, $user_id );

			if ( ! is_wp_error( $stored ) ) {
				$prev = get_user_meta( $user_id, 'uupr_avatar', true );
				if ( $prev ) {
					UUPR_Uploads::delete_file( $prev );
				}
				update_user_meta( $user_id, 'uupr_avatar', $stored );
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * MemberPress Account page
	 * ------------------------------------------------------------------- */

	/**
	 * Add the "Profile Photo" tab to the MemberPress account navigation.
	 *
	 * @param WP_User $user Current account user (passed by MemberPress).
	 */
	public function mepr_add_tab( $user ) {
		unset( $user );

		$url = add_query_arg( 'action', $this->mepr_action, $this->mepr_account_url() );
		?>
		<span class="mepr-nav-item uupr-photo-tab <?php if ( class_exists( 'MeprAccountHelper' ) ) { MeprAccountHelper::active_nav( $this->mepr_action ); } ?>">
			<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Profile Photo', 'unstoppable-user-profiles' ); ?></a>
		</span>
		<?php
	}

	/**
	 * Render the content for our MemberPress account tab.
	 *
	 * @param string $action The current account action.
	 */
	public function mepr_tab_content( $action ) {
		if ( $this->mepr_action !== $action ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		echo '<h3>' . esc_html__( 'Your Profile Photo', 'unstoppable-user-profiles' ) . '</h3>';
		// render_account_photo_form() returns markup that is already escaped at the
		// point of construction (esc_* on every dynamic value).
		echo UUPR_Form::render_account_photo_form( wp_get_current_user() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueue the front-end assets when viewing the MemberPress account page.
	 */
	public function maybe_enqueue_mepr_assets() {
		if ( ! class_exists( 'MeprOptions' ) ) {
			return;
		}

		$options    = MeprOptions::fetch();
		$account_id = isset( $options->account_page_id ) ? (int) $options->account_page_id : 0;

		if ( $account_id && is_page( $account_id ) ) {
			wp_enqueue_style( 'uupr-frontend' );
			wp_enqueue_script( 'uupr-frontend' );
		}
	}

	/**
	 * Resolve the MemberPress account page URL.
	 *
	 * @return string
	 */
	private function mepr_account_url() {
		if ( class_exists( 'MeprOptions' ) ) {
			$options    = MeprOptions::fetch();
			$account_id = isset( $options->account_page_id ) ? (int) $options->account_page_id : 0;
			if ( $account_id ) {
				$url = get_permalink( $account_id );
				if ( $url ) {
					return $url;
				}
			}
		}
		return home_url( '/account/' );
	}
}
