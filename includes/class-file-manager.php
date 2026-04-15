<?php
defined( 'ABSPATH' ) || exit;

class IDL_File_Manager {

	private string $table;

	public const CACHE_GROUP = 'idl_files';

	public function __construct() {
		global $wpdb;
		$this->table = "{$wpdb->prefix}idl_files";
	}

	/**
	 * Invalidate cached reads for a download and (optionally) a single file row.
	 * Public so external mutators (broken-links AJAX, file integrity scan,
	 * category-folder move) can bust without re-instantiating the manager.
	 */
	public static function bust_cache_for( int $download_id, ?int $file_id = null ): void {
		if ( $download_id > 0 ) {
			wp_cache_delete( "files_for_download_{$download_id}", self::CACHE_GROUP );
		}
		if ( null !== $file_id && $file_id > 0 ) {
			wp_cache_delete( "file_{$file_id}", self::CACHE_GROUP );
		}
	}

	/**
	 * Get all file/link records for a download, ordered by sort_order.
	 *
	 * @return object[]
	 */
	public function get_files( int $download_id ): array {
		$key    = "files_for_download_{$download_id}";
		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
				"SELECT * FROM {$this->table} WHERE download_id = %d ORDER BY sort_order ASC, id ASC",
				$download_id
			)
		) ?: [];

		wp_cache_set( $key, $rows, self::CACHE_GROUP, HOUR_IN_SECONDS );
		return $rows;
	}

	/**
	 * Get a single file record.
	 */
	public function get_file( int $file_id ): ?object {
		$key    = "file_{$file_id}";
		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ?: null;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
				"SELECT * FROM {$this->table} WHERE id = %d",
				$file_id
			)
		) ?: null;

		wp_cache_set( $key, $row, self::CACHE_GROUP, HOUR_IN_SECONDS );
		return $row;
	}

	/**
	 * Insert a DB row for a physical file that already lives under idl_files_dir().
	 * $args['file_path'] must be relative to idl_files_dir().
	 */
	public function add_local_file( int $download_id, array $args ): int|false {
		global $wpdb;

		// Capture inode for rename-recovery fast path. Gated on the
		// 'idl_integrity_use_inode' setting (default on; admins on Windows hosting
		// should turn this off — Windows does not provide stable POSIX inodes).
		$inode    = null;
		$rel_path = (string) ( $args['file_path'] ?? '' );
		if ( '' !== $rel_path && (bool) get_option( 'idl_integrity_use_inode', 1 ) ) {
			$abs = idl_files_dir() . '/' . $rel_path;
			if ( is_readable( $abs ) ) {
				$ino = @fileinode( $abs );
				if ( $ino && $ino > 0 ) {
					$inode = (int) $ino;
				}
			}
		}

		$data    = [
			'download_id' => $download_id,
			'file_type'   => 'local',
			'title'       => sanitize_text_field( $args['title'] ?? '' ),
			'description' => wp_kses_post( $args['description'] ?? '' ),
			'file_name'   => sanitize_file_name( $args['file_name'] ?? '' ),
			'file_path'   => sanitize_text_field( $rel_path ),
			'file_size'   => (int) ( $args['file_size'] ?? 0 ),
			'file_mime'   => sanitize_mime_type( $args['file_mime'] ?? '' ),
			'file_hash'   => sanitize_text_field( $args['file_hash'] ?? '' ),
			'sort_order'  => absint( $args['sort_order'] ?? 0 ),
		];
		$formats = [ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' ];
		if ( null !== $inode ) {
			$data['inode'] = $inode;
			$formats[]     = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache invalidated below.
		$result = $wpdb->insert( $this->table, $data, $formats );

		if ( false === $result ) {
			return false;
		}

		$file_id = (int) $wpdb->insert_id;
		self::bust_cache_for( $download_id, $file_id );
		do_action( 'idl_file_uploaded', $file_id, $download_id );
		return $file_id;
	}

	/**
	 * Add an external link (or mirror) to a download.
	 */
	public function add_external_link( int $download_id, string $url, array $args = [] ): int|false {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache invalidated below.
		$result = $wpdb->insert(
			$this->table,
			[
				'download_id'  => $download_id,
				'file_type'    => 'external',
				'title'        => sanitize_text_field( $args['title'] ?? $url ),
				'description'  => wp_kses_post( $args['description'] ?? '' ),
				'external_url' => esc_url_raw( $url ),
				'is_mirror'    => (int) ( $args['is_mirror'] ?? 0 ),
				'sort_order'   => absint( $args['sort_order'] ?? 0 ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%d', '%d' ]
		);

		if ( false === $result ) {
			return false;
		}

		$file_id = (int) $wpdb->insert_id;
		self::bust_cache_for( $download_id, $file_id );
		do_action( 'idl_file_uploaded', $file_id, $download_id );
		return $file_id;
	}

	/**
	 * Update editable metadata on a file record (title + description).
	 * Does not touch file_path, file_name, file_size, file_hash, or file_mime —
	 * those are derived from the physical file and must not drift.
	 */
	public function update_meta( int $file_id, string $title, string $description ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache invalidated below.
		$result = $wpdb->update(
			$this->table,
			[
				'title'       => sanitize_text_field( $title ),
				'description' => wp_kses_post( $description ),
			],
			[ 'id' => $file_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
		if ( false !== $result ) {
			$row = $this->get_file_uncached( $file_id );
			if ( $row ) {
				self::bust_cache_for( (int) $row->download_id, $file_id );
			} else {
				wp_cache_delete( "file_{$file_id}", self::CACHE_GROUP );
			}
		}
		return false !== $result;
	}

	/**
	 * Bypass the cache layer when the manager itself needs the canonical row
	 * (e.g. to look up download_id during an invalidation).
	 */
	private function get_file_uncached( int $file_id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cache-bypass lookup used during cache invalidation; caching here would defeat the purpose.
				"SELECT * FROM {$this->table} WHERE id = %d",
				$file_id
			)
		) ?: null;
	}

	/**
	 * Remove a file record. The physical file on disk is left in place —
	 * the category folder is the source of truth; deletion is a separate
	 * concern handled by the caller if needed.
	 */
	public function delete_file( int $file_id ): bool {
		global $wpdb;

		$file = $this->get_file( $file_id );
		if ( ! $file ) {
			return false;
		}

		do_action( 'idl_file_deleted', $file_id, (int) $file->download_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache invalidated below.
		$result = $wpdb->delete( $this->table, [ 'id' => $file_id ], [ '%d' ] );
		if ( false !== $result ) {
			self::bust_cache_for( (int) $file->download_id, $file_id );
		}
		return false !== $result;
	}

	/**
	 * Increment per-file download count and update the post-level cached total.
	 */
	public function increment_count( int $file_id, int $download_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Counter increment on custom table; cache invalidated below.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET download_count = download_count + 1 WHERE id = %d",
				$file_id
			)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate over custom table immediately after write; freshness required.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(download_count) FROM {$this->table} WHERE download_id = %d",
				$download_id
			)
		);
		update_post_meta( $download_id, '_idl_download_count', $total );
		self::bust_cache_for( $download_id, $file_id );
	}

	/**
	 * Check whether a file with the given SHA-256 hash is already stored.
	 */
	public function file_exists_by_hash( string $hash ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
				"SELECT id FROM {$this->table} WHERE file_hash = %s LIMIT 1",
				$hash
			)
		);
	}

	/**
	 * Persist a new sort order for a set of file IDs.
	 *
	 * @param array<int,int> $order Map of file_id => sort_order
	 */
	public function update_sort_order( array $order ): void {
		global $wpdb;
		$download_ids = [];
		foreach ( $order as $file_id => $sort_order ) {
			$file_id = absint( $file_id );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache invalidated below.
			$wpdb->update(
				$this->table,
				[ 'sort_order' => absint( $sort_order ) ],
				[ 'id' => $file_id ],
				[ '%d' ],
				[ '%d' ]
			);
			$row = $this->get_file_uncached( $file_id );
			if ( $row ) {
				$download_ids[ (int) $row->download_id ] = true;
			}
			wp_cache_delete( "file_{$file_id}", self::CACHE_GROUP );
		}
		foreach ( array_keys( $download_ids ) as $download_id ) {
			self::bust_cache_for( (int) $download_id );
		}
	}
}
