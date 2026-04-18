<?php
defined( 'ABSPATH' ) || exit;

class IDL_Settings {

	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'handle_flush_rewrite' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue( string $hook ): void {
		$idl_pages = [
			'idl_page_idl-stats',
			'idl_page_idl-log',
			'idl_page_idl-settings',
			'idl_page_idl-broken-links',
		];
		if ( ! in_array( $hook, $idl_pages, true ) ) {
			return;
		}
		wp_enqueue_style( 'idl-admin', IDL_PLUGIN_URL . 'admin/css/admin-style.css', [], IDL_VERSION );

		if ( 'idl_page_idl-broken-links' === $hook ) {
			wp_enqueue_script( 'idl-broken-links', IDL_PLUGIN_URL . 'admin/js/broken-links.js', [ 'jquery' ], IDL_VERSION, true );
			wp_localize_script(
				'idl-broken-links',
				'idlBrokenLinks',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'idl_broken_links' ),
					'i18n'    => [
						'confirmMoveBack' => __( 'Move the file back to the original folder?', 'i-downloads' ),
						'confirmReassign' => __( 'Move this download (and all its files) to the new category?', 'i-downloads' ),
						'confirmSplit'    => __( 'Create a new download for this file in its new category?', 'i-downloads' ),
						'confirmDetach'   => __( 'Remove this file from the download? The file on disk will not be deleted.', 'i-downloads' ),
						'generic_error'   => __( 'Action failed. Please reload the page and try again.', 'i-downloads' ),
					],
				]
			);
		}
	}

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=idl',
			__( 'Statistics', 'i-downloads' ),
			__( 'Statistics', 'i-downloads' ),
			'idl_view_logs',
			'idl-stats',
			[ $this, 'render_stats' ]
		);
		add_submenu_page(
			'edit.php?post_type=idl',
			__( 'Download Log', 'i-downloads' ),
			__( 'Download Log', 'i-downloads' ),
			'idl_view_logs',
			'idl-log',
			[ $this, 'render_log' ]
		);

		// Broken Links — label carries a count badge when rows are flagged.
		$missing_count = IDL_File_Integrity::missing_count();
		$broken_label  = __( 'Broken Links', 'i-downloads' );
		if ( $missing_count > 0 ) {
			$broken_label .= ' <span class="awaiting-mod idl-broken-badge">' . number_format_i18n( $missing_count ) . '</span>';
		}
		add_submenu_page(
			'edit.php?post_type=idl',
			__( 'Broken Links', 'i-downloads' ),
			$broken_label,
			'idl_manage_settings',
			'idl-broken-links',
			[ $this, 'render_broken_links' ]
		);

		add_submenu_page(
			'edit.php?post_type=idl',
			__( 'Settings', 'i-downloads' ),
			__( 'Settings', 'i-downloads' ),
			'idl_manage_settings',
			'idl-settings',
			[ $this, 'render_page' ]
		);
	}

	public function render_broken_links(): void {
		if ( ! current_user_can( 'idl_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to view broken links.', 'i-downloads' ) );
		}
		$table = new IDL_Broken_Links_Table();
		$table->prepare_items();
		require IDL_PLUGIN_DIR . 'admin/views/broken-links-page.php';
	}

	public function render_stats(): void {
		if ( ! current_user_can( 'idl_view_logs' ) ) {
			wp_die( esc_html__( 'You do not have permission to view statistics.', 'i-downloads' ) );
		}
		require IDL_PLUGIN_DIR . 'admin/views/stats-dashboard.php';
	}

	public function render_log(): void {
		if ( ! current_user_can( 'idl_view_logs' ) ) {
			wp_die( esc_html__( 'You do not have permission to view the download log.', 'i-downloads' ) );
		}
		$table = new IDL_Log_Table();
		$table->prepare_items();
		require IDL_PLUGIN_DIR . 'admin/views/log-viewer.php';
	}

	public function render_page(): void {
		if ( ! current_user_can( 'idl_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'i-downloads' ) );
		}
		require IDL_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public function register_settings(): void {
		$options = [
			// General
			'idl_default_access_role'      => 'sanitize_text_field',
			'idl_enable_counting'          => 'absint',
			'idl_enable_logging'           => 'absint',
			'idl_enable_detailed_logging'  => 'absint',
			'idl_log_retention_days'       => 'absint',
			'idl_enable_pdf_thumbnails'    => 'absint',
			'idl_pdf_thumb_width'          => 'absint',
			'idl_pdf_thumb_height'         => 'absint',
			'idl_pdf_thumb_quality'        => 'absint',
			'idl_overwrite_pdf_thumbnail'  => 'absint',
			// Display
			'idl_default_button_text'      => 'sanitize_text_field',
			'idl_listing_layout'           => 'sanitize_text_field',
			'idl_items_per_page'           => 'absint',
			'idl_show_file_size'           => 'absint',
			'idl_show_download_count'      => 'absint',
			'idl_show_date'                => 'absint',
			'idl_date_format'              => 'sanitize_text_field',
			// Security
			'idl_serve_method'             => 'sanitize_text_field',
			'idl_nginx_config_confirmed'   => 'absint',
			'idl_rate_limit_per_hour'      => 'absint',
			'idl_block_user_agents'        => 'sanitize_textarea_field', // Planned: user-agent blocklist enforcement in download handler.
			'idl_enable_zip_bundle'        => 'absint', // Planned: combine multi-file downloads into a single ZIP on the fly.
			'idl_hotlink_protection'       => 'absint',
			// Files
			'idl_allowed_extensions'       => 'sanitize_textarea_field',
			'idl_cyrillic_titles'          => 'absint',
			// Advanced
			'idl_custom_css'               => 'wp_strip_all_tags',
			'idl_archive_slug'             => 'sanitize_title',
			'idl_category_slug'            => 'sanitize_title',
			'idl_tag_slug'                 => 'sanitize_title',
			'idl_delete_data_on_uninstall' => 'absint',
			// Maintenance / File integrity
			'idl_integrity_check_enabled'  => 'absint',
			'idl_integrity_check_time'     => [ $this, 'sanitize_time' ],
			'idl_integrity_autorelink'     => 'absint',
			'idl_integrity_use_inode'      => 'absint',
		];

		foreach ( $options as $option => $sanitize ) {
			register_setting( 'idl_settings', $option, [ 'sanitize_callback' => $sanitize ] );
		}
	}

	public function sanitize_time( $value ): string {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $value, $m ) ) {
			$h = max( 0, min( 23, (int) $m[1] ) );
			$i = max( 0, min( 59, (int) $m[2] ) );
			return sprintf( '%02d:%02d', $h, $i );
		}
		return '02:30';
	}

	public function handle_flush_rewrite(): void {
		if ( isset( $_POST['idl_flush_rewrite'] ) && current_user_can( 'idl_manage_settings' ) ) {
			check_admin_referer( 'idl_settings-options' );
			flush_rewrite_rules();
			add_settings_error( 'idl_settings', 'flushed', __( 'Rewrite rules flushed.', 'i-downloads' ), 'updated' );
		}
	}
}
