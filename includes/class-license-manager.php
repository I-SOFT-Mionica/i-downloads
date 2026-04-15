<?php
defined( 'ABSPATH' ) || exit;

class IDL_License_Manager {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'idl_licenses';
	}

	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_form_actions' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=idl',
			__( 'Licenses', 'i-downloads' ),
			__( 'Licenses', 'i-downloads' ),
			'idl_manage_settings',
			'idl-licenses',
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'idl_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage licenses.', 'i-downloads' ) );
		}
		require IDL_PLUGIN_DIR . 'admin/views/licenses-page.php';
	}

	public function handle_form_actions(): void {
		if ( empty( $_POST['idl_license_action'] ) ) {
			return;
		}
		check_admin_referer( 'idl_license_action' );
		if ( ! current_user_can( 'idl_manage_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'i-downloads' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['idl_license_action'] ) );
		if ( 'save' === $action ) {
			$this->save();
		} elseif ( 'delete' === $action ) {
			$this->delete( absint( $_POST['license_id'] ?? 0 ) );
		}
	}

	/** @return object[] */
	public function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
			"SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC"
		) ?: [];
	}

	public function get( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			)
		) ?: null;
	}

	private function save(): void {
		global $wpdb;

		// Nonce verified by caller (handle_form_actions).
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$id   = absint( $_POST['license_id'] ?? 0 );
		$data = [
			'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'slug'        => sanitize_title( wp_unslash( $_POST['slug'] ?? $_POST['title'] ?? '' ) ),
			'description' => sanitize_text_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'full_text'   => wp_kses_post( wp_unslash( $_POST['full_text'] ?? '' ) ),
			'url'         => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
			'is_default'  => (int) ! empty( $_POST['is_default'] ),
			'sort_order'  => absint( $_POST['sort_order'] ?? 0 ),
		];
		$fmt  = [ '%s', '%s', '%s', '%s', '%s', '%d', '%d' ];

		if ( $id ) {
			$wpdb->update( $this->table, $data, [ 'id' => $id ], $fmt, [ '%d' ] );
		} else {
			$wpdb->insert( $this->table, $data, $fmt );
			$id = (int) $wpdb->insert_id;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Only one default allowed
		if ( $data['is_default'] && $id ) {
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Class-property table name.
					"UPDATE {$this->table} SET is_default = 0 WHERE id != %d",
					$id
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'      => 'idl-licenses',
					'post_type' => 'idl',
					'saved'     => '1',
				],
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	private function delete( int $id ): void {
		global $wpdb;
		if ( $id ) {
			$wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
		}
		wp_safe_redirect(
			add_query_arg(
				[
					'page'      => 'idl-licenses',
					'post_type' => 'idl',
					'deleted'   => '1',
				],
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
