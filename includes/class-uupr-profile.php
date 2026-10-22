<?php
/**
 * Main plugin controller.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Profile.
 */
class UUPR_Profile {

	/**
	 * Singleton instance.
	 *
	 * @var UUPR_Profile|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return UUPR_Profile
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: instantiate the components.
	 */
	private function __construct() {
		new UUPR_Avatar();
		new UUPR_Form();
		new UUPR_Integrations();

		if ( is_admin() ) {
			new UUPR_Admin();
		}
	}
}
