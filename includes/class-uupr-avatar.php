<?php
/**
 * Avatar override.
 *
 * Filters WordPress core avatar resolution so a member's uploaded photo is used
 * everywhere get_avatar() / get_avatar_url() is called. Because membership and
 * community plugins (MemberPress, ClubSuite, BuddyPress, bbPress, WooCommerce, the
 * comments list, etc.) all ultimately call these core functions, a single override
 * makes the custom photo appear across all of them with no per-plugin integration.
 *
 * @package Unstoppable_User_Profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UUPR_Avatar.
 */
class UUPR_Avatar {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'get_avatar_data', array( $this, 'filter_avatar_data' ), 10, 2 );
	}

	/**
	 * Replace the avatar URL with the member's uploaded photo when one exists.
	 *
	 * @param array $args        Avatar data args (includes 'url').
	 * @param mixed $id_or_email User ID, email, WP_User, WP_Post, or WP_Comment.
	 * @return array
	 */
	public function filter_avatar_data( $args, $id_or_email ) {
		$user_id = $this->resolve_user_id( $id_or_email );

		if ( ! $user_id ) {
			return $args;
		}

		$filename = get_user_meta( $user_id, 'uupr_avatar', true );

		if ( empty( $filename ) ) {
			return $args;
		}

		$url = trailingslashit( UUPR_Uploads::avatar_url_base() ) . rawurlencode( basename( $filename ) );

		$args['url']          = $url;
		$args['found_avatar'] = true;

		return $args;
	}

	/**
	 * Resolve various identifier shapes to a user ID.
	 *
	 * @param mixed $id_or_email Identifier passed by core.
	 * @return int 0 when no user can be resolved.
	 */
	private function resolve_user_id( $id_or_email ) {
		if ( is_numeric( $id_or_email ) ) {
			return absint( $id_or_email );
		}

		if ( $id_or_email instanceof WP_User ) {
			return (int) $id_or_email->ID;
		}

		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author;
		}

		if ( $id_or_email instanceof WP_Comment ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				return (int) $id_or_email->user_id;
			}
			if ( ! empty( $id_or_email->comment_author_email ) ) {
				$user = get_user_by( 'email', $id_or_email->comment_author_email );
				return $user ? (int) $user->ID : 0;
			}
			return 0;
		}

		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? (int) $user->ID : 0;
		}

		return 0;
	}
}
