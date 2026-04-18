<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'i-Downloads Settings', 'i-downloads' ); ?></h1>

	<?php settings_errors( 'idl_settings' ); ?>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selector; nonce belongs on form submit, not nav.
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	$tabs       = array(
		'general'     => __( 'General', 'i-downloads' ),
		'display'     => __( 'Display', 'i-downloads' ),
		'security'    => __( 'Security', 'i-downloads' ),
		'advanced'    => __( 'Advanced', 'i-downloads' ),
		'maintenance' => __( 'Maintenance', 'i-downloads' ),
		'extensions'  => __( 'Extensions', 'i-downloads' ),
	);
	?>
	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab => $label ) : ?>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page'      => 'idl-settings',
						'post_type' => 'idl',
						'tab'       => $tab,
					),
					admin_url( 'edit.php' )
				)
			);
			?>
						"
				class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'extensions' === $active_tab ) : ?>
		<?php require __DIR__ . '/extensions-tab.php'; ?>
	<?php elseif ( 'maintenance' === $active_tab ) : ?>
		<?php require __DIR__ . '/maintenance-tab.php'; ?>
	<?php else : ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'idl_settings' ); ?>

		<?php if ( 'general' === $active_tab ) : ?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Storage Location', 'i-downloads' ); ?></th>
				<td>
					<code><?php echo esc_html( idl_files_dir() ); ?></code>
					<p class="description"><?php esc_html_e( 'All files are stored here, organised by category. The folder is protected by .htaccess and served through the plugin\'s secure download handler.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Default Access Role', 'i-downloads' ); ?></th>
				<td>
					<select name="idl_default_access_role">
						<?php
						foreach ( array(
							'public'        => __( 'Public', 'i-downloads' ),
							'subscriber'    => __( 'Subscriber+', 'i-downloads' ),
							'editor'        => __( 'Editor+', 'i-downloads' ),
							'administrator' => __( 'Administrator only', 'i-downloads' ),
						) as $v => $l ) :
							?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( get_option( 'idl_default_access_role', 'public' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Download Counting', 'i-downloads' ); ?></th>
				<td><label><input type="checkbox" name="idl_enable_counting" value="1" <?php checked( get_option( 'idl_enable_counting', 1 ) ); ?> /> <?php esc_html_e( 'Count downloads', 'i-downloads' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Basic Logging', 'i-downloads' ); ?></th>
				<td><label><input type="checkbox" name="idl_enable_logging" value="1" <?php checked( get_option( 'idl_enable_logging', 1 ) ); ?> /> <?php esc_html_e( 'Log downloads (timestamp, file, user)', 'i-downloads' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Detailed Logging', 'i-downloads' ); ?></th>
				<td>
					<label><input type="checkbox" name="idl_enable_detailed_logging" value="1" <?php checked( get_option( 'idl_enable_detailed_logging', 0 ) ); ?> /> <?php esc_html_e( 'Also log IP address, user agent, and referer', 'i-downloads' ); ?></label>
					<p class="description"><?php esc_html_e( 'Collects personally identifiable information (PII). Enable only when needed for security investigation.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="idl-log-retention"><?php esc_html_e( 'Log Retention (days)', 'i-downloads' ); ?></label></th>
				<td>
					<input type="number" name="idl_log_retention_days" id="idl-log-retention" value="<?php echo esc_attr( get_option( 'idl_log_retention_days', 365 ) ); ?>" min="0" class="small-text" />
					<p class="description"><?php esc_html_e( '0 = keep forever.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'PDF Thumbnails', 'i-downloads' ); ?></th>
				<td><label><input type="checkbox" name="idl_enable_pdf_thumbnails" value="1" <?php checked( get_option( 'idl_enable_pdf_thumbnails', 1 ) ); ?> /> <?php esc_html_e( 'Auto-generate thumbnail from PDF first page', 'i-downloads' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="idl-allowed-extensions"><?php esc_html_e( 'Allowed File Extensions', 'i-downloads' ); ?></label></th>
				<td>
					<textarea name="idl_allowed_extensions" id="idl-allowed-extensions" class="regular-text" rows="3"><?php echo esc_textarea( get_option( 'idl_allowed_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,csv,zip,rar,7z,jpg,jpeg,png,gif,webp,mp4,mp3,wav' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Comma-separated list of permitted extensions. Uploads with unlisted extensions are blocked.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Cyrillic Titles', 'i-downloads' ); ?></th>
				<td>
					<label><input type="checkbox" name="idl_cyrillic_titles" value="1" <?php checked( get_option( 'idl_cyrillic_titles', 0 ) ); ?> /> <?php esc_html_e( 'Auto-convert upload title to Serbian Cyrillic', 'i-downloads' ); ?></label>
					<p class="description"><?php esc_html_e( 'When enabled, the title field is pre-filled with a Cyrillic transliteration of the filename.', 'i-downloads' ); ?></p>
				</td>
			</tr>
		</table>

		<?php elseif ( 'display' === $active_tab ) : ?>
		<table class="form-table">
			<tr>
				<th><label for="idl-default-button-text"><?php esc_html_e( 'Default Button Text', 'i-downloads' ); ?></label></th>
				<td>
					<input type="text" name="idl_default_button_text" id="idl-default-button-text"
						value="<?php echo esc_attr( get_option( 'idl_default_button_text', '' ) ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Download', 'i-downloads' ); ?>" />
					<p class="description"><?php esc_html_e( 'Text shown on download buttons site-wide. Leave empty to use "Download".', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Default Layout', 'i-downloads' ); ?></th>
				<td>
					<select name="idl_listing_layout">
						<?php
						foreach ( array(
							'list'  => __( 'List', 'i-downloads' ),
							'grid'  => __( 'Grid', 'i-downloads' ),
							'table' => __( 'Table', 'i-downloads' ),
						) as $v => $l ) :
							?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( get_option( 'idl_listing_layout', 'list' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="idl-items-per-page"><?php esc_html_e( 'Items Per Page', 'i-downloads' ); ?></label></th>
				<td><input type="number" name="idl_items_per_page" id="idl-items-per-page" value="<?php echo esc_attr( get_option( 'idl_items_per_page', 10 ) ); ?>" min="1" max="100" class="small-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Show in Listings', 'i-downloads' ); ?></th>
				<td>
					<label><input type="checkbox" name="idl_show_file_size" value="1" <?php checked( get_option( 'idl_show_file_size', 1 ) ); ?> /> <?php esc_html_e( 'File size', 'i-downloads' ); ?></label><br>
					<label><input type="checkbox" name="idl_show_download_count" value="1" <?php checked( get_option( 'idl_show_download_count', 1 ) ); ?> /> <?php esc_html_e( 'Download count', 'i-downloads' ); ?></label><br>
					<label><input type="checkbox" name="idl_show_date" value="1" <?php checked( get_option( 'idl_show_date', 1 ) ); ?> /> <?php esc_html_e( 'Date', 'i-downloads' ); ?></label>
				</td>
			</tr>
		</table>

		<?php elseif ( 'security' === $active_tab ) : ?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Detected Server', 'i-downloads' ); ?></th>
				<td><code><?php echo esc_html( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'Unknown', 'i-downloads' ) ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'File Serving Method', 'i-downloads' ); ?></th>
				<td>
					<select name="idl_serve_method">
						<?php
						foreach ( array(
							'auto'      => __( 'Auto-detect', 'i-downloads' ),
							'xsendfile' => 'X-Sendfile (Apache)',
							'xaccel'    => 'X-Accel-Redirect (Nginx)',
							'php'       => __( 'PHP streaming', 'i-downloads' ),
						) as $v => $l ) :
							?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( get_option( 'idl_serve_method', 'auto' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="idl-rate-limit"><?php esc_html_e( 'Rate Limit (per IP/hour)', 'i-downloads' ); ?></label></th>
				<td>
					<input type="number" name="idl_rate_limit_per_hour" id="idl-rate-limit" value="<?php echo esc_attr( get_option( 'idl_rate_limit_per_hour', 0 ) ); ?>" min="0" class="small-text" />
					<p class="description"><?php esc_html_e( '0 = no limit.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Hotlink Protection', 'i-downloads' ); ?></th>
				<td><label><input type="checkbox" name="idl_hotlink_protection" value="1" <?php checked( get_option( 'idl_hotlink_protection', 0 ) ); ?> /> <?php esc_html_e( 'Block downloads from external referers', 'i-downloads' ); ?></label></td>
			</tr>
		</table>

		<?php elseif ( 'advanced' === $active_tab ) : ?>
		<table class="form-table">
			<tr>
				<th><label for="idl-archive-slug"><?php esc_html_e( 'Download Archive Slug', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="idl_archive_slug" id="idl-archive-slug" value="<?php echo esc_attr( get_option( 'idl_archive_slug', 'downloads' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="idl-category-slug"><?php esc_html_e( 'Category Archive Slug', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="idl_category_slug" id="idl-category-slug" value="<?php echo esc_attr( get_option( 'idl_category_slug', 'download-category' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="idl-tag-slug"><?php esc_html_e( 'Tag Archive Slug', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="idl_tag_slug" id="idl-tag-slug" value="<?php echo esc_attr( get_option( 'idl_tag_slug', 'download-tag' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="idl-custom-css"><?php esc_html_e( 'Custom CSS', 'i-downloads' ); ?></label></th>
				<td><textarea name="idl_custom_css" id="idl-custom-css" class="widefat code" rows="8"><?php echo esc_textarea( get_option( 'idl_custom_css', '' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Flush Rewrite Rules', 'i-downloads' ); ?></th>
				<td>
					<button type="submit" name="idl_flush_rewrite" value="1" class="button"><?php esc_html_e( 'Flush Now', 'i-downloads' ); ?></button>
					<p class="description"><?php esc_html_e( 'Run this after changing slug options.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Uninstall Behavior', 'i-downloads' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="idl_delete_data_on_uninstall" value="1" <?php checked( get_option( 'idl_delete_data_on_uninstall', 0 ) ); ?> />
						<?php esc_html_e( 'Delete all plugin data when the plugin is uninstalled', 'i-downloads' ); ?>
					</label>
					<p class="description" style="color:#c00;"><?php esc_html_e( 'Warning: this will permanently delete all downloads, files, logs, and settings.', 'i-downloads' ); ?></p>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<?php submit_button(); ?>
	</form>
	<?php endif; ?>
</div>
