<?php
/**
 * WP_List_Table subclass for the Broken Links screen.
 *
 * Lists every idl_files row with is_missing = 1 together with enough
 * context for the recovery dialog to run move-back / reassign / split /
 * reupload / detach actions.
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class IDL_Broken_Links_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'broken_link',
				'plural'   => 'broken_links',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'download_title' => __( 'Download', 'i-downloads' ),
			'file_name'      => __( 'File', 'i-downloads' ),
			'category'       => __( 'Expected folder', 'i-downloads' ),
			'missing_since'  => __( 'Missing since', 'i-downloads' ),
			'actions'        => __( 'Recover', 'i-downloads' ),
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'missing_since' => array( 'missing_since', true ),
		);
	}

	protected function column_download_title( object $item ): string {
		$title = $item->download_title
			? esc_html( $item->download_title )
			: '<em>' . esc_html__( '(deleted)', 'i-downloads' ) . '</em>';

		if ( $item->download_id && get_post( $item->download_id ) ) {
			$title = '<a href="' . esc_url( get_edit_post_link( $item->download_id ) ) . '">' . $title . '</a>';
		}

		return $title;
	}

	protected function column_file_name( object $item ): string {
		$name = $item->file_name ? esc_html( $item->file_name ) : '<em>' . esc_html__( '(no name)', 'i-downloads' ) . '</em>';
		$path = $item->file_path ? '<br><code style="font-size:11px;color:#888;">' . esc_html( $item->file_path ) . '</code>' : '';
		return $name . $path;
	}

	protected function column_category( object $item ): string {
		if ( empty( $item->category_name ) ) {
			return '<em>' . esc_html__( '(uncategorized)', 'i-downloads' ) . '</em>';
		}
		return esc_html( $item->category_name );
	}

	protected function column_missing_since( object $item ): string {
		if ( empty( $item->missing_since ) ) {
			return '—';
		}
		$ts = strtotime( $item->missing_since );
		return '<span title="' . esc_attr( $item->missing_since ) . '">'
			. esc_html( human_time_diff( $ts, time() ) . ' ' . __( 'ago', 'i-downloads' ) )
			. '</span>';
	}

	protected function column_actions( object $item ): string {
		$file_id = (int) $item->id;
		return sprintf(
			'<button type="button" class="button idl-recover-btn" data-file-id="%d">%s</button>',
			$file_id,
			esc_html__( 'Recover…', 'i-downloads' )
		);
	}

	protected function column_default( $item, $column_name ): string {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	public function prepare_items(): void {
		global $wpdb;

		$per_page     = 25;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- WP_List_Table uses $_REQUEST for sorting.
		$ascending = isset( $_REQUEST['order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table lookup for admin Broken Links screen.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}idl_files WHERE is_missing = 1" );

		// Two separate prepared statements keep ORDER BY direction out of any interpolation path.
		if ( $ascending ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table lookup.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT f.*, p.post_title AS download_title
					   FROM {$wpdb->prefix}idl_files f
					   LEFT JOIN {$wpdb->posts} p ON p.ID = f.download_id
					  WHERE f.is_missing = 1
					  ORDER BY f.missing_since ASC
					  LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table lookup.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT f.*, p.post_title AS download_title
					   FROM {$wpdb->prefix}idl_files f
					   LEFT JOIN {$wpdb->posts} p ON p.ID = f.download_id
					  WHERE f.is_missing = 1
					  ORDER BY f.missing_since DESC
					  LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
		}

		// Enrich each row with category name (one term per download — uses primary category).
		foreach ( $rows as $row ) {
			$row->category_name = '';
			$terms              = get_the_terms( (int) $row->download_id, 'idl_category' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$row->category_name = $terms[0]->name;
			}
		}

		$this->items = $rows ?: array();

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}
}
