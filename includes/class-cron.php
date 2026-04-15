<?php
/**
 * Scheduled tasks for i-Downloads.
 *
 * Jobs:
 *   idl_daily_cron — runs at 01:00 site time:
 *     1. Recalculates HOT flag (top 10 downloads last 7 days from daily table).
 *     2. Purges daily rows older than 8 days (keep one extra day as buffer).
 *     3. Purges log entries beyond the configured retention period.
 */
defined( 'ABSPATH' ) || exit;

class IDL_Cron {

	private const HOOK = 'idl_daily_cron';

	public function register_hooks(): void {
		add_action( 'init', [ $this, 'schedule' ] );
		add_action( self::HOOK, [ $this, 'run' ] );
		add_action( 'idl_deactivate', [ $this, 'unschedule' ] );
	}

	// -------------------------------------------------------------------------
	// Scheduling
	// -------------------------------------------------------------------------

	public function schedule(): void {
		if ( wp_next_scheduled( self::HOOK ) ) {
			return;
		}

		// First run: next 01:00 in site timezone.
		$timezone  = wp_timezone();
		$next_1am  = new DateTimeImmutable( 'tomorrow 01:00:00', $timezone );
		$timestamp = $next_1am->getTimestamp();

		wp_schedule_event( $timestamp, 'daily', self::HOOK );
	}

	public function unschedule(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	// -------------------------------------------------------------------------
	// Main job
	// -------------------------------------------------------------------------

	public function run(): void {
		$this->recalculate_hot();
		$this->purge_daily_old();
		( new IDL_Download_Logger() )->purge_old_logs();

		do_action( 'idl_daily_cron_complete' );
	}

	// -------------------------------------------------------------------------
	// HOT recalculation
	// -------------------------------------------------------------------------

	/**
	 * Mark the top 10 downloads by 7-day count as HOT; clear the flag on all others.
	 */
	private function recalculate_hot(): void {
		global $wpdb;

		// Sum counts from the daily table for the last 7 days.
		$hot_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT download_id
				   FROM {$wpdb->prefix}idl_download_daily
				  WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				  GROUP BY download_id
				  ORDER BY SUM(count) DESC
				  LIMIT 10",
				7
			)
		);

		$hot_ids = array_map( 'intval', $hot_ids ?: [] );

		// Clear HOT flag on all idl posts.
		$wpdb->query(
			"DELETE FROM {$wpdb->postmeta}
			  WHERE meta_key = '_idl_is_hot'"
		);

		// Set HOT flag on the winners.
		foreach ( $hot_ids as $post_id ) {
			update_post_meta( $post_id, '_idl_is_hot', 1 );
		}

		// Store the ranked list with counts for the stats dashboard.
		$hot_with_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT download_id, SUM(count) AS weekly_count
				   FROM {$wpdb->prefix}idl_download_daily
				  WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				  GROUP BY download_id
				  ORDER BY weekly_count DESC
				  LIMIT 10",
				7
			)
		);

		update_option( 'idl_hot_downloads', $hot_with_counts, false );
		update_option( 'idl_hot_calculated_at', current_time( 'mysql' ), false );

		do_action( 'idl_hot_recalculated', $hot_ids );
	}

	// -------------------------------------------------------------------------
	// Daily table cleanup
	// -------------------------------------------------------------------------

	private function purge_daily_old(): void {
		global $wpdb;

		// Keep 8 days so the 7-day window always has full data.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}idl_download_daily
				  WHERE log_date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				8
			)
		);
	}
}
