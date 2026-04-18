<?php
defined( 'ABSPATH' ) || exit;

class IDL_Admin_Meta_Boxes {

	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_idl', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_access_role_in_publish_box' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'strip_post_password' ), 10, 2 );
		add_action( 'wp_ajax_idl_delete_file', array( $this, 'ajax_delete_file' ) );
		add_action( 'wp_ajax_idl_save_file_order', array( $this, 'ajax_save_order' ) );
		add_action( 'wp_ajax_idl_add_external', array( $this, 'ajax_add_external' ) );
		add_action( 'wp_ajax_idl_update_file_meta', array( $this, 'ajax_update_file_meta' ) );
		add_action( 'wp_ajax_idl_upload_file', array( $this, 'ajax_upload_file' ) );
		add_action( 'wp_ajax_idl_browse_category', array( $this, 'ajax_browse_category' ) );
		add_action( 'wp_ajax_idl_import_file', array( $this, 'ajax_import_file' ) );
	}

	/** Resolve the single idl_category term id assigned to a download. */
	public static function get_download_category( int $download_id ): ?int {
		$terms = wp_get_object_terms( $download_id, 'idl_category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}
		return (int) $terms[0];
	}

	public function enqueue( string $hook ): void {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'idl' !== $post_type ) {
			return;
		}
		wp_enqueue_script(
			'idl-admin',
			IDL_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			IDL_VERSION,
			true
		);
		wp_localize_script(
			'idl-admin',
			'IDL',
			array(
				'nonce'   => wp_create_nonce( 'idl_admin' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Remove this file from the download?', 'i-downloads' ),
					'edit'          => __( 'Edit', 'i-downloads' ),
					'remove'        => __( 'Remove', 'i-downloads' ),
					'mirror'        => __( 'Mirror', 'i-downloads' ),
					'external'      => __( 'External', 'i-downloads' ),
					'noFiles'       => __( 'No files attached yet.', 'i-downloads' ),
					'title'         => __( 'Title', 'i-downloads' ),
					'description'   => __( 'Description', 'i-downloads' ),
					'save'          => __( 'Save', 'i-downloads' ),
					'cancel'        => __( 'Cancel', 'i-downloads' ),
					'saving'        => __( 'Saving…', 'i-downloads' ),
					'linking'       => __( 'Linking…', 'i-downloads' ),
					'retry'         => __( 'Retry', 'i-downloads' ),
					'error'         => __( 'Error', 'i-downloads' ),
					'networkError'  => __( 'Network error', 'i-downloads' ),
					'serverError'   => __( 'Server error', 'i-downloads' ),
					'linked'        => __( 'linked', 'i-downloads' ),
					'alreadyLinked' => __( 'already linked', 'i-downloads' ),
					'linkButton'    => __( 'Link to this download', 'i-downloads' ),
					'loading'       => __( 'Loading…', 'i-downloads' ),
					'noFolderFiles' => __( 'No files found in this category folder.', 'i-downloads' ),
				),
			)
		);
		wp_enqueue_style( 'idl-admin', IDL_PLUGIN_URL . 'admin/css/admin-style.css', array(), IDL_VERSION );
		wp_add_inline_style( 'idl-admin', '#visibility-action, .misc-pub-visibility { display: none !important; }' );
	}

	public function register(): void {
		// Files first — that is the primary purpose of this CPT.
		add_meta_box( 'idl-files', __( 'Files', 'i-downloads' ), array( $this, 'render_files' ), 'idl', 'normal', 'high' );
		// Description replaces the removed post editor.
		add_meta_box( 'idl-description', __( 'Description', 'i-downloads' ), array( $this, 'render_description' ), 'idl', 'normal', 'high' );
		add_meta_box( 'idl-version-info', __( 'Version & License', 'i-downloads' ), array( $this, 'render_version_info' ), 'idl', 'normal', 'default' );
		add_meta_box( 'idl-stats', __( 'Statistics', 'i-downloads' ), array( $this, 'render_stats' ), 'idl', 'side', 'default' );
	}

	// --- Render callbacks ---

	public function render_files( WP_Post $post ): void {
		wp_nonce_field( "idl_save_meta_{$post->ID}", 'idl_meta_nonce' );
		$files         = new IDL_File_Manager()->get_files( $post->ID );
		$is_new_post   = 'auto-draft' === $post->post_status || 0 === $post->ID;
		$category_id   = self::get_download_category( $post->ID );
		$category      = $category_id ? get_term( $category_id, 'idl_category' ) : null;
		$category_path = $category_id ? idl_category_folder_path( $category_id ) : '';
		require IDL_PLUGIN_DIR . 'admin/views/meta-box-files.php';
	}

	public function render_description( WP_Post $post ): void {
		$description = $post->post_content;
		?>
		<label for="idl-description" class="screen-reader-text"><?php esc_html_e( 'Description', 'i-downloads' ); ?></label>
		<textarea
			id="idl-description"
			name="content"
			rows="4"
			class="widefat"
			style="resize:vertical"
		><?php echo esc_textarea( $description ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Optional. Shown on the download page below the title.', 'i-downloads' ); ?></p>
		<?php
	}

	/**
	 * Render the Access Role dropdown inside the Publish meta box.
	 */
	public function render_access_role_in_publish_box( WP_Post $post ): void {
		if ( 'idl' !== $post->post_type ) {
			return;
		}
		$access_role = get_post_meta( $post->ID, '_idl_access_role', true )
			?: get_option( 'idl_default_access_role', 'public' );
		$roles       = array(
			'public'        => __( 'Public (everyone)', 'i-downloads' ),
			'subscriber'    => __( 'Subscriber+', 'i-downloads' ),
			'contributor'   => __( 'Contributor+', 'i-downloads' ),
			'author'        => __( 'Author+', 'i-downloads' ),
			'editor'        => __( 'Editor+', 'i-downloads' ),
			'administrator' => __( 'Administrator only', 'i-downloads' ),
		);
		?>
		<div class="misc-pub-section misc-pub-idl-access">
			<span class="dashicons dashicons-lock" style="color:#82878c;margin-right:2px;"></span>
			<label for="idl-access-role"><strong><?php esc_html_e( 'Access:', 'i-downloads' ); ?></strong></label>
			<select name="_idl_access_role" id="idl-access-role" style="margin-left:4px;">
				<?php foreach ( $roles as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $access_role, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Strip post_password for idl posts — our RBAC replaces WP password protection.
	 */
	public function strip_post_password( array $data, array $postarr ): array {
		unset( $postarr );
		if ( 'idl' === ( $data['post_type'] ?? '' ) ) {
			$data['post_password'] = '';
		}
		return $data;
	}

	public function render_version_info( WP_Post $post ): void {
		$version        = (string) get_post_meta( $post->ID, '_idl_version', true );
		$changelog      = (string) get_post_meta( $post->ID, '_idl_changelog', true );
		$license_id     = (int) get_post_meta( $post->ID, '_idl_license_id', true );
		$author_name    = (string) get_post_meta( $post->ID, '_idl_author_name', true );
		$author_url     = (string) get_post_meta( $post->ID, '_idl_author_url', true );
		$date_published = (string) get_post_meta( $post->ID, '_idl_date_published', true );
		$require_agree  = (bool) get_post_meta( $post->ID, '_idl_require_agree', true );
		$agree_text     = (string) get_post_meta( $post->ID, '_idl_agree_text', true );
		$licenses       = new IDL_License_Manager()->get_all();
		require IDL_PLUGIN_DIR . 'admin/views/meta-box-version-info.php';
	}

	public function render_stats( WP_Post $post ): void {
		$files           = new IDL_File_Manager()->get_files( $post->ID );
		$total_downloads = (int) get_post_meta( $post->ID, '_idl_download_count', true );
		require IDL_PLUGIN_DIR . 'admin/views/meta-box-stats.php';
	}

	// --- Save ---

	public function save( int $post_id, WP_Post $post ): void {
		unset( $post );
		if ( ! isset( $_POST['idl_meta_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['idl_meta_nonce'] ) ), "idl_save_meta_{$post_id}" )
		) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'idl_edit_own_downloads', $post_id ) ) {
			return;
		}

		$valid_roles = array( 'public', 'subscriber', 'contributor', 'author', 'editor', 'administrator' );

		// Access role — rendered in the Publish box via post_submitbox_misc_actions.
		$default_role = get_option( 'idl_default_access_role', 'public' );
		$role         = sanitize_text_field( wp_unslash( $_POST['_idl_access_role'] ?? $default_role ) );
		update_post_meta( $post_id, '_idl_access_role', in_array( $role, $valid_roles, true ) ? $role : $default_role );

		// Agreement — rendered in Version & License box.
		update_post_meta( $post_id, '_idl_require_agree', ! empty( $_POST['_idl_require_agree'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_idl_agree_text', wp_kses_post( wp_unslash( $_POST['_idl_agree_text'] ?? '' ) ) );

		// TODO v1.0: Featured flag — pin to top of category listing when sort=featured.
		// TODO v1.0: External Only — prefer external source when download has both local and remote files.

		update_post_meta( $post_id, '_idl_version', sanitize_text_field( wp_unslash( $_POST['_idl_version'] ?? '' ) ) );
		update_post_meta( $post_id, '_idl_changelog', wp_kses_post( wp_unslash( $_POST['_idl_changelog'] ?? '' ) ) );
		update_post_meta( $post_id, '_idl_license_id', absint( $_POST['_idl_license_id'] ?? 0 ) );
		update_post_meta( $post_id, '_idl_author_name', sanitize_text_field( wp_unslash( $_POST['_idl_author_name'] ?? '' ) ) );
		update_post_meta( $post_id, '_idl_author_url', esc_url_raw( wp_unslash( $_POST['_idl_author_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_idl_date_published', sanitize_text_field( wp_unslash( $_POST['_idl_date_published'] ?? '' ) ) );
	}

	// --- AJAX handlers ---

	public function ajax_delete_file(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );
		$file_id = absint( $_POST['file_id'] ?? 0 );
		if ( ! $file_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'i-downloads' ) ) );
		}
		$file = new IDL_File_Manager()->get_file( $file_id );
		if ( ! $file || ! current_user_can( 'edit_post', (int) $file->download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}
		if ( ! new IDL_File_Manager()->delete_file( $file_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete file.', 'i-downloads' ) ) );
		}
		wp_send_json_success();
	}

	public function ajax_save_order(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}
		$order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-element below.
		if ( ! is_array( $order ) ) {
			wp_send_json_error();
		}
		$sanitized = array();
		foreach ( $order as $fid => $pos ) {
			$sanitized[ absint( $fid ) ] = absint( $pos );
		}
		new IDL_File_Manager()->update_sort_order( $sanitized );
		wp_send_json_success();
	}

	public function ajax_add_external(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );
		$download_id = absint( $_POST['download_id'] ?? 0 );
		$url         = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
		if ( ! $download_id || ! $url ) {
			wp_send_json_error( array( 'message' => __( 'URL is required.', 'i-downloads' ) ) );
		}
		if ( ! current_user_can( 'edit_post', $download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}
		$file_id = new IDL_File_Manager()->add_external_link(
			$download_id,
			$url,
			array(
				'title'     => sanitize_text_field( wp_unslash( $_POST['title'] ?? $url ) ),
				'is_mirror' => (int) ! empty( $_POST['is_mirror'] ),
			)
		);
		if ( ! $file_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not add link.', 'i-downloads' ) ) );
		}
		wp_send_json_success( array( 'file_id' => $file_id ) );
	}

	/**
	 * Update a file record's editable metadata (title + description).
	 */
	public function ajax_update_file_meta(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );

		$file_id = absint( $_POST['file_id'] ?? 0 );
		if ( ! $file_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file id.', 'i-downloads' ) ) );
		}

		$manager = new IDL_File_Manager();
		$file    = $manager->get_file( $file_id );
		if ( ! $file || ! current_user_can( 'edit_post', (int) $file->download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}

		$title       = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

		if ( ! $manager->update_meta( $file_id, $title, $description ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save changes.', 'i-downloads' ) ) );
		}

		wp_send_json_success( array( 'file' => $manager->get_file( $file_id ) ) );
	}

	/**
	 * Receive a single uploaded file, place it in the download's category
	 * folder, and insert an idl_files row.
	 */
	public function ajax_upload_file(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );

		$download_id = absint( $_POST['download_id'] ?? 0 );
		if ( ! $download_id || ! current_user_can( 'edit_post', $download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}

		if ( empty( $_FILES['file'] ) || ! empty( $_FILES['file']['error'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded or upload error.', 'i-downloads' ) ) );
		}

		$category_id = self::get_download_category( $download_id );
		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Assign a category to this download and save before uploading files.', 'i-downloads' ) ) );
		}

		$upload        = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload handled by WP move_uploaded_file.
		$original_name = sanitize_file_name( wp_unslash( $upload['name'] ) );
		$sanitized     = idl_sanitize_filename( $original_name );

		if ( $sanitized['error'] ) {
			wp_send_json_error( array( 'message' => $sanitized['error'] ) );
		}

		$slug = $sanitized['slug'];
		if ( idl_filename_collision( $slug, $category_id ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
					/* translators: %s: filename */
						__( 'A file named "%s" already exists in this category. Rename the file and try again.', 'i-downloads' ),
						$slug
					),
				)
			);
		}

		IDL_Category_Folders::ensure( $category_id );
		$target_abs = idl_category_fs_path( $category_id ) . '/' . $slug;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$handled = wp_handle_upload(
			$_FILES['file'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_handle_upload validates.
			array(
				'test_form' => false,
				'action'    => 'idl_upload_file',
			)
		);
		if ( isset( $handled['error'] ) ) {
			wp_send_json_error( array( 'message' => $handled['error'] ) );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}
		if ( ! $wp_filesystem->move( $handled['file'], $target_abs, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save the uploaded file.', 'i-downloads' ) ) );
		}

		$rel_path = idl_category_folder_path( $category_id ) . '/' . $slug;
		$manager  = new IDL_File_Manager();
		$file_id  = $manager->add_local_file(
			$download_id,
			array(
				'title'     => idl_autofill_title( $sanitized['original_title'] ),
				'file_name' => $slug,
				'file_path' => $rel_path,
				'file_size' => (int) $upload['size'],
				'file_mime' => function_exists( 'mime_content_type' ) ? ( mime_content_type( $target_abs ) ?: $upload['type'] ) : $upload['type'],
				'file_hash' => hash_file( 'sha256', $target_abs ),
			)
		);

		if ( ! $file_id ) {
			wp_delete_file( $target_abs );
			wp_send_json_error( array( 'message' => __( 'Could not save file record.', 'i-downloads' ) ) );
		}

		wp_send_json_success( array( 'file' => $manager->get_file( $file_id ) ) );
	}

	/**
	 * List the physical contents of the download's current category folder,
	 * flagging which files are already tracked in idl_files.
	 */
	public function ajax_browse_category(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );

		$download_id = absint( $_POST['download_id'] ?? 0 );
		if ( ! $download_id || ! current_user_can( 'edit_post', $download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}

		$category_id = self::get_download_category( $download_id );
		if ( ! $category_id ) {
			wp_send_json_success(
				array(
					'files'    => array(),
					'category' => null,
				)
			);
		}

		$folder = idl_category_fs_path( $category_id );
		if ( ! is_dir( $folder ) ) {
			wp_send_json_success(
				array(
					'files'    => array(),
					'category' => idl_category_folder_path( $category_id ),
				)
			);
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin meta box: listing files under the download's category folder; single-request freshness required.
		$tracked = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT file_path FROM {$wpdb->prefix}idl_files WHERE download_id = %d",
				$download_id
			)
		);

		$rel_base = idl_category_folder_path( $category_id );
		$items    = array();
		foreach ( (array) glob( "{$folder}/*" ) as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}
			$name    = basename( $path );
			$rel     = "{$rel_base}/{$name}";
			$items[] = array(
				'name'    => $name,
				'rel'     => $rel,
				'size'    => filesize( $path ),
				'tracked' => in_array( $rel, $tracked, true ),
			);
		}

		wp_send_json_success(
			array(
				'files'    => $items,
				'category' => $rel_base,
			)
		);
	}

	/**
	 * Import an existing untracked file from disk into the idl_files table.
	 */
	public function ajax_import_file(): void {
		check_ajax_referer( 'idl_admin', 'nonce' );

		$download_id = absint( $_POST['download_id'] ?? 0 );
		$rel_path    = sanitize_text_field( wp_unslash( $_POST['rel_path'] ?? '' ) );

		if ( ! $download_id || ! $rel_path || ! current_user_can( 'edit_post', $download_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'i-downloads' ) ), 403 );
		}

		// Path traversal guard — resolved path must stay under idl_files_dir().
		$base = realpath( idl_files_dir() );
		$abs  = realpath( "{$base}/{$rel_path}" );
		if ( ! $abs || ! $base || ! str_starts_with( $abs, $base ) || ! is_file( $abs ) ) {
			wp_send_json_error( array( 'message' => __( 'File not found.', 'i-downloads' ) ) );
		}

		$name    = basename( $abs );
		$manager = new IDL_File_Manager();
		$file_id = $manager->add_local_file(
			$download_id,
			array(
				'title'     => idl_autofill_title( pathinfo( $name, PATHINFO_FILENAME ) ),
				'file_name' => $name,
				'file_path' => $rel_path,
				'file_size' => filesize( $abs ),
				'file_mime' => function_exists( 'mime_content_type' ) ? ( mime_content_type( $abs ) ?: 'application/octet-stream' ) : 'application/octet-stream',
				'file_hash' => hash_file( 'sha256', $abs ),
			)
		);

		if ( ! $file_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not save file record.', 'i-downloads' ) ) );
		}

		wp_send_json_success( array( 'file' => $manager->get_file( $file_id ) ) );
	}
}
