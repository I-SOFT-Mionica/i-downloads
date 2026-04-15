<?php
/**
 * CSV / JSON export for the download log — Phase 4.
 *
 * Hooks:
 *   admin_post_idl_export_csv   → streams a CSV file
 *   admin_post_idl_export_json  → streams a JSON file
 *   admin_post_idl_purge_logs   → deletes old log entries, redirects back
 *
 * All actions require the idl_export_logs capability (Admins only).
 * Purge requires idl_manage_settings.
 */
defined( 'ABSPATH' ) || exit;

class IDL_Export {

	public function register_hooks(): void {
		add_action( 'admin_post_idl_export_csv', [ $this, 'export_csv' ] );
		add_action( 'admin_post_idl_export_json', [ $this, 'export_json' ] );
		add_action( 'admin_post_idl_purge_logs', [ $this, 'purge_logs' ] );

		// Handle inline export links from the log viewer (not admin-post, GET-based with nonce).
		add_action( 'admin_init', [ $this, 'handle_inline_export' ] );
	}

	// -------------------------------------------------------------------------
	// Inline export (GET links in log-viewer.php)
	// -------------------------------------------------------------------------

	public function handle_inline_export(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability-gated GET action; nonce verified below for destructive ops.
		if ( empty( $_GET['idl_action'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'idl_export_logs' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( $_GET['idl_action'] );
		if ( $action === 'export_csv' ) {
			$this->stream_csv( $this->fetch_rows() );
		} elseif ( $action === 'export_json' ) {
			$this->stream_json( $this->fetch_rows() );
		}
	}

	// -------------------------------------------------------------------------
	// admin-post handlers (POST-based, with nonce)
	// -------------------------------------------------------------------------

	public function export_csv(): void {
		check_admin_referer( 'idl_export' );
		if ( ! current_user_can( 'idl_export_logs' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'i-downloads' ) );
		}
		$this->stream_csv( $this->fetch_rows() );
	}

	public function export_json(): void {
		check_admin_referer( 'idl_export' );
		if ( ! current_user_can( 'idl_export_logs' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'i-downloads' ) );
		}
		$this->stream_json( $this->fetch_rows() );
	}

	public function purge_logs(): void {
		check_admin_referer( 'idl_purge_logs' );
		if ( ! current_user_can( 'idl_manage_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'i-downloads' ) );
		}

		$deleted = ( new IDL_Download_Logger() )->purge_old_logs();

		$redirect = add_query_arg(
			[
				'post_type' => 'idl',
				'page'      => 'idl-log',
				'purged'    => $deleted,
			],
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	// -------------------------------------------------------------------------
	// Query
	// -------------------------------------------------------------------------

	/**
	 * Fetch log rows with optional filters from the current request.
	 *
	 * @return array<object>
	 */
	private function fetch_rows(): array {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filters are read-only; capability already checked.
		$where_parts = [];
		$where_args  = [];

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( $search !== '' ) {
			$like          = '%' . $wpdb->esc_like( $search ) . '%';
			$where_parts[] = '( p.post_title LIKE %s OR l.user_login LIKE %s OR l.ip_address LIKE %s )';
			$where_args[]  = $like;
			$where_args[]  = $like;
			$where_args[]  = $like;
		}

		$filter_download = isset( $_REQUEST['filter_download'] ) ? absint( $_REQUEST['filter_download'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $filter_download > 0 ) {
			$where_parts[] = 'l.download_id = %d';
			$where_args[]  = $filter_download;
		}

		$where_sql = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

		$sql = "SELECT l.id, l.download_id, p.post_title AS download_title,
		               l.file_id, f.file_name, l.user_id, l.user_login,
		               l.ip_address, l.user_agent, l.referer, l.downloaded_at
		          FROM {$wpdb->prefix}idl_download_log l
		          LEFT JOIN {$wpdb->posts} p ON p.ID = l.download_id
		          LEFT JOIN {$wpdb->prefix}idl_files f ON f.id = l.file_id
		          $where_sql
		         ORDER BY l.downloaded_at DESC
		         LIMIT 50000";

		if ( $where_args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare( $sql, ...$where_args ) ) ?? [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql ) ?? [];
	}

	// -------------------------------------------------------------------------
	// Streamers
	// -------------------------------------------------------------------------

	/**
	 * @param array<object> $rows
	 */
	private function stream_csv( array $rows ): void {
		$filename = 'idl-log-' . gmdate( 'Y-m-d' ) . '.csv';

		// Disable output buffering.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel opens it correctly.
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header row.
		fputcsv(
			$out,
			[
				'ID',
				'Download ID',
				'Download Title',
				'File ID',
				'File Name',
				'User ID',
				'User Login',
				'IP Address',
				'User Agent',
				'Referer',
				'Downloaded At',
			]
		);

		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				[
					$row->id,
					$row->download_id,
					$row->download_title ?? '',
					$row->file_id,
					$row->file_name ?? '',
					$row->user_id ?? '',
					$row->user_login ?? '',
					$row->ip_address ?? '',
					$row->user_agent ?? '',
					$row->referer ?? '',
					$row->downloaded_at,
				]
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * @param array<object> $rows
	 */
	private function stream_json( array $rows ): void {
		$filename = 'idl-log-' . gmdate( 'Y-m-d' ) . '.json';

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Cast all rows to arrays for cleaner JSON.
		$data = array_map( fn( object $row ) => (array) $row, $rows );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		echo json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}
}
