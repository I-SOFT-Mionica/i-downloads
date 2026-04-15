<?php
defined( 'ABSPATH' ) || exit;

class IDL_Deactivator {

	public static function deactivate(): void {
		// Unschedule cron jobs.
		do_action( 'idl_deactivate' );

		// Clear all plugin transients.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Deactivator: one-shot transient cleanup.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_idl_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_idl_' ) . '%'
			)
		);
		flush_rewrite_rules();
	}
}
