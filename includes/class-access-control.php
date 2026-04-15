<?php
defined( 'ABSPATH' ) || exit;

class IDL_Access_Control {

	/** Role hierarchy from lowest to highest. */
	private const HIERARCHY = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];

	public function register_hooks(): void {
		// Intentionally empty — methods are called directly by other classes.
	}

	/**
	 * Check if the current (or given) user may access a download.
	 */
	public function can_access_download( int $download_id, int $user_id = 0 ): bool {
		$required = get_post_meta( $download_id, '_idl_access_role', true ) ?: 'public';
		$allowed  = $this->user_meets_role( $required, $user_id );

		return (bool) apply_filters( 'idl_access_check', $allowed, $download_id, $user_id );
	}

	/**
	 * Check whether a user meets a minimum role requirement.
	 */
	public function user_meets_role( string $required_role, int $user_id = 0 ): bool {
		if ( 'public' === $required_role ) {
			return true;
		}

		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$required_index = array_search( $required_role, self::HIERARCHY, true );
		if ( false === $required_index ) {
			return false;
		}

		foreach ( $user->roles as $role ) {
			$role_index = array_search( $role, self::HIERARCHY, true );
			if ( false !== $role_index && $role_index >= $required_index ) {
				return true;
			}
		}

		return false;
	}
}
