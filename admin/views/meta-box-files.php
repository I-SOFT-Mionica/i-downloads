<?php
/**
 * Files meta box — upload / browse / external URL tabs + current file list.
 *
 * Expected variables:
 *   $post            WP_Post
 *   $files           array of idl_files rows
 *   $is_new_post     bool
 *   $category_id     int|null
 *   $category        WP_Term|null
 *   $category_path   string   relative category folder path
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="idl-files-wrap">

	<div class="idl-target-bar">
		<?php if ( $category ) : ?>
			<span class="dashicons dashicons-category" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'Target category:', 'i-downloads' ); ?></strong>
			<span class="idl-target-name"><?php echo esc_html( $category->name ); ?></span>
			<code class="idl-target-path"><?php echo esc_html( $category_path ); ?></code>
		<?php else : ?>
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<em><?php esc_html_e( 'Assign a category (in the sidebar) and save the download before uploading files.', 'i-downloads' ); ?></em>
		<?php endif; ?>
	</div>

	<nav class="idl-tab-nav" role="tablist">
		<button type="button" class="idl-tab-btn is-active" data-tab="upload" role="tab" aria-selected="true">
			<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload', 'i-downloads' ); ?>
		</button>
		<button type="button" class="idl-tab-btn" data-tab="browse" role="tab" aria-selected="false">
			<span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'From Folder', 'i-downloads' ); ?>
		</button>
		<button type="button" class="idl-tab-btn" data-tab="external" role="tab" aria-selected="false">
			<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'External URL', 'i-downloads' ); ?>
		</button>
	</nav>

	<div class="idl-tab-panels">

		<!-- Upload tab -->
		<div class="idl-tab-panel is-active" data-tab="upload" role="tabpanel">
			<?php if ( $category_id && ! $is_new_post ) : ?>
				<div class="idl-dropzone" id="idl-dropzone">
					<input type="file" id="idl-file-input" multiple hidden />
					<div class="idl-dropzone__inner">
						<span class="dashicons dashicons-cloud-upload" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Drag & drop files here, or click to select.', 'i-downloads' ); ?></p>
						<p class="description"><?php esc_html_e( 'Multiple files supported. Each file is saved directly to the category folder.', 'i-downloads' ); ?></p>
					</div>
				</div>
				<ul class="idl-upload-queue" id="idl-upload-queue"></ul>
			<?php else : ?>
				<p class="idl-notice-warning">
					<?php esc_html_e( 'Uploads are disabled until the download has a category and has been saved.', 'i-downloads' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<!-- Browse tab -->
		<div class="idl-tab-panel" data-tab="browse" role="tabpanel" hidden>
			<?php if ( $category_id && ! $is_new_post ) : ?>
				<p class="description">
					<?php esc_html_e( 'Files already present in the category folder that are not yet linked to this download:', 'i-downloads' ); ?>
				</p>
				<ul class="idl-browse-list" id="idl-browse-list">
					<li class="idl-browse-empty"><?php esc_html_e( 'Loading…', 'i-downloads' ); ?></li>
				</ul>
			<?php else : ?>
				<p class="idl-notice-warning">
					<?php esc_html_e( 'Assign a category and save the download before browsing its folder.', 'i-downloads' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<!-- External URL tab -->
		<div class="idl-tab-panel" data-tab="external" role="tabpanel" hidden>
			<table class="form-table">
				<tr>
					<th><label for="idl-ext-url"><?php esc_html_e( 'URL', 'i-downloads' ); ?></label></th>
					<td><input type="url" id="idl-ext-url" class="widefat" placeholder="https://…" /></td>
				</tr>
				<tr>
					<th><label for="idl-ext-title"><?php esc_html_e( 'Title', 'i-downloads' ); ?></label></th>
					<td><input type="text" id="idl-ext-title" class="widefat" /></td>
				</tr>
				<tr>
					<th></th>
					<td>
						<label>
							<input type="checkbox" id="idl-ext-mirror" />
							<?php esc_html_e( 'This is a mirror (alternate source for the same file)', 'i-downloads' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<p>
				<button type="button" class="button button-primary idl-btn-ext-save">
					<?php esc_html_e( 'Add Link', 'i-downloads' ); ?>
				</button>
			</p>
		</div>

	</div>

	<h4 class="idl-section-heading"><?php esc_html_e( 'Files attached to this download', 'i-downloads' ); ?></h4>

	<table class="widefat idl-file-list" id="idl-file-list">
		<thead>
			<tr>
				<th class="idl-col-sort"></th>
				<th><?php esc_html_e( 'Title', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Filename / URL', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Type', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Size', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Downloads', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'i-downloads' ); ?></th>
			</tr>
		</thead>
		<tbody id="idl-file-list-body">
		<?php if ( empty( $files ) ) : ?>
			<tr class="idl-no-files" id="idl-no-files-row">
				<td colspan="7"><?php esc_html_e( 'No files attached yet.', 'i-downloads' ); ?></td>
			</tr>
		<?php else : ?>
			<?php foreach ( $files as $file ) : ?>
			<tr class="idl-file-row" data-file-id="<?php echo esc_attr( $file->id ); ?>">
				<td class="idl-col-sort"><span class="dashicons dashicons-move idl-sort-handle"></span></td>
				<td class="idl-file-title"
					data-title="<?php echo esc_attr( $file->title ); ?>"
					data-description="<?php echo esc_attr( (string) $file->description ); ?>">
					<?php echo esc_html( $file->title ?: $file->file_name ?: $file->external_url ); ?>
				</td>
				<td class="idl-file-source">
					<?php if ( 'local' === $file->file_type ) : ?>
						<?php echo esc_html( $file->file_name ); ?>
					<?php else : ?>
						<a href="<?php echo esc_url( $file->external_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $file->external_url ); ?>
						</a>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( 'local' === $file->file_type ) : ?>
						<span class="idl-badge idl-badge--local"><?php echo esc_html( strtoupper( pathinfo( $file->file_name ?? '', PATHINFO_EXTENSION ) ) ); ?></span>
					<?php elseif ( $file->is_mirror ) : ?>
						<span class="idl-badge idl-badge--mirror"><?php esc_html_e( 'Mirror', 'i-downloads' ); ?></span>
					<?php else : ?>
						<span class="idl-badge idl-badge--external"><?php esc_html_e( 'External', 'i-downloads' ); ?></span>
					<?php endif; ?>
				</td>
				<td><?php echo $file->file_size ? esc_html( size_format( $file->file_size ) ) : '—'; ?></td>
				<td><?php echo esc_html( number_format_i18n( $file->download_count ) ); ?></td>
				<td>
					<button type="button" class="button button-small idl-btn-edit-file"
						data-file-id="<?php echo esc_attr( $file->id ); ?>">
						<?php esc_html_e( 'Edit', 'i-downloads' ); ?>
					</button>
					<button type="button" class="button button-small idl-btn-delete-file"
						data-file-id="<?php echo esc_attr( $file->id ); ?>">
						<?php esc_html_e( 'Remove', 'i-downloads' ); ?>
					</button>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

</div>
