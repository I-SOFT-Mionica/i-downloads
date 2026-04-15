<?php
defined( 'ABSPATH' ) || exit;

class IDL_Download_Logger {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'idl_download_log';
	}

	/**
	 * Write a download log entry.
	 *
	 * @return int|null  Inserted log ID, or null if logging is disabled or insert failed.
	 */
	public function log( int $download_id, int $file_id ): ?int {
		global $wpdb;

		$settings = idl_get_settings();
		if ( ! $settings['enable_logging'] ) {
			return null;
		}

		$user = wp_get_current_user();

		$now   = current_time( 'mysql' );
		$today = current_time( 'Y-m-d' );

		$data   = [
			'download_id'   => $download_id,
			'file_id'       => $file_id,
			'user_id'       => $user->ID ?: null,
			'user_login'    => $user->ID ? $user->user_login : null,
			'downloaded_at' => $now,
			'log_date'      => $today,
		];
		$format = [ '%d', '%d', '%d', '%s', '%s', '%s' ];

		// PII fields — only when detailed logging is explicitly enabled
		if ( $settings['enable_detailed_logging'] ) {
			$data['ip_address'] = $this->client_ip();
			$data['user_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] )
				? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 )
				: null;
			$data['referer']    = isset( $_SERVER['HTTP_REFERER'] )
				? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
				: null;
			array_push( $format, '%s', '%s', '%s' );
		}

		$data = apply_filters( 'idl_log_entry_data', $data );

		if ( false === $wpdb->insert( $this->table, $data, $format ) ) {
			return null;
		}

		$log_id = (int) $wpdb->insert_id;

		// Increment daily bucket — used by HOT cron, avoids full log scans.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}idl_download_daily (download_id, log_date, count)
				 VALUES (%d, %s, 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				$download_id,
				$today
			)
		);

		return $log_id;
	}

	/**
	 * Delete log entries older than the configured retention period.
	 *
	 * @return int  Number of rows deleted.
	 */
	public function purge_old_logs(): int {
		global $wpdb;

		$days = (int) idl_get_settings()['log_retention_days'];
		if ( 0 === $days ) {
			return 0;
		}

		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
				"DELETE FROM {$this->table} WHERE downloaded_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		if ( $deleted > 0 ) {
			do_action( 'idl_log_purged', $deleted );
		}

		return $deleted;
	}

	private function client_ip(): ?string {
		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ] as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			// X-Forwarded-For may be a comma-separated list; take the first.
			if ( str_contains( $ip, ',' ) ) {
				$ip = trim( explode( ',', $ip )[0] );
			}
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return null;
	}
}
