<?php
/**
 * Admin interface: settings, upload moderation, and branded menu.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Admin.
 */
class UUPR_Admin {

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	private $menu_slug = 'uupr-settings';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_post_uupr_approve_avatar', array( $this, 'handle_approve' ) );
		add_action( 'admin_post_uupr_reject_avatar', array( $this, 'handle_reject' ) );
	}

	/**
	 * Add the top-level menu and sub-pages.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'User Profiles', 'unstoppable-user-profiles' ),
			__( 'User Profiles', 'unstoppable-user-profiles' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_settings_page' ),
			'dashicons-id',
			72
		);

		add_submenu_page(
			$this->menu_slug,
			__( 'Settings', 'unstoppable-user-profiles' ),
			__( 'Settings', 'unstoppable-user-profiles' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			$this->menu_slug,
			__( 'Approve Photos', 'unstoppable-user-profiles' ),
			__( 'Approve Photos', 'unstoppable-user-profiles' ),
			'manage_options',
			'uupr-approvals',
			array( $this, 'render_approvals_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'uupr_settings',
			'uupr_max_upload_mb',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_max_mb' ),
				'default'           => 2,
			)
		);

		register_setting(
			'uupr_settings',
			'uupr_moderate_uploads',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
				'default'           => 'no',
			)
		);

		register_setting(
			'uupr_settings',
			'uupr_enabled_fields',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_fields' ),
				'default'           => array( 'first_name', 'last_name', 'display_name', 'user_email', 'user_url', 'description' ),
			)
		);
	}

	/**
	 * Sanitize the max MB value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_max_mb( $value ) {
		$value = absint( $value );
		if ( $value < 1 ) {
			$value = 1;
		}
		if ( $value > 20 ) {
			$value = 20;
		}
		return $value;
	}

	/**
	 * Sanitize a yes/no toggle.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_yes_no( $value ) {
		return ( 'yes' === $value ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize the enabled fields array against a whitelist.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public function sanitize_fields( $value ) {
		$allowed = array( 'first_name', 'last_name', 'display_name', 'user_email', 'user_url', 'description' );
		if ( ! is_array( $value ) ) {
			return array();
		}
		$clean = array();
		foreach ( $value as $field ) {
			$field = sanitize_key( $field );
			if ( in_array( $field, $allowed, true ) ) {
				$clean[] = $field;
			}
		}
		return $clean;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-user-profiles' ) );
		}

		$enabled    = (array) get_option( 'uupr_enabled_fields', array() );
		$all_fields = array(
			'first_name'   => __( 'First name', 'unstoppable-user-profiles' ),
			'last_name'    => __( 'Last name', 'unstoppable-user-profiles' ),
			'display_name' => __( 'Display name', 'unstoppable-user-profiles' ),
			'user_email'   => __( 'Email address', 'unstoppable-user-profiles' ),
			'user_url'     => __( 'Website', 'unstoppable-user-profiles' ),
			'description'  => __( 'Bio', 'unstoppable-user-profiles' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'User Profiles Settings', 'unstoppable-user-profiles' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Add the profile editor to any page with this shortcode:', 'unstoppable-user-profiles' ); ?>
				<code>[uupr_profile_form]</code>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'uupr_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Editable fields', 'unstoppable-user-profiles' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $all_fields as $key => $label ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input
											type="checkbox"
											name="uupr_enabled_fields[]"
											value="<?php echo esc_attr( $key ); ?>"
											<?php checked( in_array( $key, $enabled, true ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="uupr_max_upload_mb"><?php esc_html_e( 'Max photo size (MB)', 'unstoppable-user-profiles' ); ?></label>
						</th>
						<td>
							<input
								type="number"
								min="1"
								max="20"
								id="uupr_max_upload_mb"
								name="uupr_max_upload_mb"
								value="<?php echo esc_attr( get_option( 'uupr_max_upload_mb', 2 ) ); ?>"
								class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Moderate photo uploads', 'unstoppable-user-profiles' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="uupr_moderate_uploads"
									value="yes"
									<?php checked( 'yes', get_option( 'uupr_moderate_uploads', 'no' ) ); ?>>
								<?php esc_html_e( 'Require admin approval before a new photo goes live', 'unstoppable-user-profiles' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the photo approvals page.
	 */
	public function render_approvals_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-user-profiles' ) );
		}

		$pending = get_users(
			array(
				'meta_key'     => 'uupr_avatar_pending', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => 100,
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Approve Profile Photos', 'unstoppable-user-profiles' ); ?></h1>

			<?php if ( empty( $pending ) ) : ?>
				<p><?php esc_html_e( 'No photos are awaiting approval.', 'unstoppable-user-profiles' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Member', 'unstoppable-user-profiles' ); ?></th>
							<th><?php esc_html_e( 'New photo', 'unstoppable-user-profiles' ); ?></th>
							<th><?php esc_html_e( 'Action', 'unstoppable-user-profiles' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending as $member ) : ?>
							<?php
							$filename = get_user_meta( $member->ID, 'uupr_avatar_pending', true );
							$img_url  = trailingslashit( UUPR_Uploads::avatar_url_base() ) . rawurlencode( basename( $filename ) );
							?>
							<tr>
								<td><?php echo esc_html( $member->display_name ); ?> (<?php echo esc_html( $member->user_email ); ?>)</td>
								<td>
									<img src="<?php echo esc_url( $img_url ); ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:4px;">
								</td>
								<td>
									<a href="<?php echo esc_url( $this->action_url( 'uupr_approve_avatar', $member->ID ) ); ?>" class="button button-primary">
										<?php esc_html_e( 'Approve', 'unstoppable-user-profiles' ); ?>
									</a>
									<a href="<?php echo esc_url( $this->action_url( 'uupr_reject_avatar', $member->ID ) ); ?>" class="button">
										<?php esc_html_e( 'Reject', 'unstoppable-user-profiles' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build a nonced admin-post URL for a moderation action.
	 *
	 * @param string $action  Action name.
	 * @param int    $user_id Target user.
	 * @return string
	 */
	private function action_url( $action, $user_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . $action . '&user_id=' . absint( $user_id ) ),
			$action . '_' . $user_id
		);
	}

	/**
	 * Approve a pending photo.
	 */
	public function handle_approve() {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-user-profiles' ) );
		}
		check_admin_referer( 'uupr_approve_avatar_' . $user_id );

		$pending = get_user_meta( $user_id, 'uupr_avatar_pending', true );
		if ( $pending ) {
			$prev = get_user_meta( $user_id, 'uupr_avatar', true );
			if ( $prev ) {
				UUPR_Uploads::delete_file( $prev );
			}
			update_user_meta( $user_id, 'uupr_avatar', $pending );
			delete_user_meta( $user_id, 'uupr_avatar_pending' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=uupr-approvals' ) );
		exit;
	}

	/**
	 * Reject a pending photo.
	 */
	public function handle_reject() {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-user-profiles' ) );
		}
		check_admin_referer( 'uupr_reject_avatar_' . $user_id );

		$pending = get_user_meta( $user_id, 'uupr_avatar_pending', true );
		if ( $pending ) {
			UUPR_Uploads::delete_file( $pending );
			delete_user_meta( $user_id, 'uupr_avatar_pending' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=uupr-approvals' ) );
		exit;
	}

	/**
	 * Brand the admin menu gold using the compliant inline-style method.
	 *
	 * Per the WordPress.org Submission Guide, inline <style> echoed via admin_head
	 * is a rejection reason; we register an empty handle and attach the CSS with
	 * wp_add_inline_style() instead.
	 */
	public function enqueue_admin_styles() {
		wp_register_style( 'uupr-admin', false, array(), UUPR_VERSION );
		wp_enqueue_style( 'uupr-admin' );

		$css = '
			#adminmenu .toplevel_page_' . $this->menu_slug . ' .wp-menu-name { color: #f0b849; }
			#adminmenu .toplevel_page_' . $this->menu_slug . ':hover .wp-menu-name,
			#adminmenu .toplevel_page_' . $this->menu_slug . '.current .wp-menu-name { color: #fff; }
			#adminmenu .toplevel_page_' . $this->menu_slug . ' .wp-menu-image:before { color: #f0b849; }
			#adminmenu .toplevel_page_' . $this->menu_slug . ':hover .wp-menu-image:before,
			#adminmenu .toplevel_page_' . $this->menu_slug . '.current .wp-menu-image:before { color: #fff; }
		';

		wp_add_inline_style( 'uupr-admin', $css );
	}
}
