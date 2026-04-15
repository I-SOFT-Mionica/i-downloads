<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Licenses', 'i-downloads' ); ?> <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'new' ], isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'i-downloads' ); ?></a></h1>

	<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display of query-string flags. ?>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License saved.', 'i-downloads' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License deleted.', 'i-downloads' ); ?></p></div>
	<?php endif; ?>

	<?php $action = sanitize_key( $_GET['action'] ?? 'list' ); ?>
	<?php $edit_id = absint( $_GET['edit_id'] ?? 0 ); ?>
	<?php // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>

	<?php
	if ( in_array( $action, [ 'new', 'edit' ], true ) ) :
		$license_manager = new IDL_License_Manager();
		$editing         = $edit_id ? $license_manager->get( $edit_id ) : null;
		?>
	<form method="post">
		<?php wp_nonce_field( 'idl_license_action' ); ?>
		<input type="hidden" name="idl_license_action" value="save" />
		<input type="hidden" name="license_id" value="<?php echo esc_attr( $edit_id ); ?>" />
		<table class="form-table">
			<tr>
				<th><label for="idl-lic-title"><?php esc_html_e( 'Title', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="title" id="idl-lic-title" value="<?php echo esc_attr( $editing->title ?? '' ); ?>" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="idl-lic-slug"><?php esc_html_e( 'Slug', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="slug" id="idl-lic-slug" value="<?php echo esc_attr( $editing->slug ?? '' ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="idl-lic-desc"><?php esc_html_e( 'Short Description', 'i-downloads' ); ?></label></th>
				<td><input type="text" name="description" id="idl-lic-desc" value="<?php echo esc_attr( $editing->description ?? '' ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="idl-lic-url"><?php esc_html_e( 'License URL', 'i-downloads' ); ?></label></th>
				<td><input type="url" name="url" id="idl-lic-url" value="<?php echo esc_attr( $editing->url ?? '' ); ?>" class="regular-text" placeholder="https://…" /></td>
			</tr>
			<tr>
				<th><label for="idl-lic-full-text"><?php esc_html_e( 'Full License Text', 'i-downloads' ); ?></label></th>
				<td>
					<textarea name="full_text" id="idl-lic-full-text" class="widefat" rows="10"><?php echo esc_textarea( $editing->full_text ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown in the agreement modal when a user downloads a file requiring consent.', 'i-downloads' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Default', 'i-downloads' ); ?></th>
				<td><label><input type="checkbox" name="is_default" value="1" <?php checked( $editing->is_default ?? 0 ); ?> /> <?php esc_html_e( 'Set as default license for new downloads', 'i-downloads' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="idl-lic-order"><?php esc_html_e( 'Sort Order', 'i-downloads' ); ?></label></th>
				<td><input type="number" name="sort_order" id="idl-lic-order" value="<?php echo esc_attr( $editing->sort_order ?? 0 ); ?>" class="small-text" min="0" /></td>
			</tr>
		</table>
		<?php submit_button( $edit_id ? __( 'Update License', 'i-downloads' ) : __( 'Add License', 'i-downloads' ) ); ?>
	</form>

	<?php else : ?>
		<?php $licenses = ( new IDL_License_Manager() )->get_all(); ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Title', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Description', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Default', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'i-downloads' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $licenses ) ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No licenses found.', 'i-downloads' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $licenses as $lic ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $lic->title ); ?></strong></td>
				<td><code><?php echo esc_html( $lic->slug ); ?></code></td>
				<td><?php echo esc_html( $lic->description ); ?></td>
				<td><?php echo $lic->is_default ? '✓' : ''; ?></td>
				<td>
					<a href="
					<?php
					echo esc_url(
						add_query_arg(
							[
								'action'  => 'edit',
								'edit_id' => $lic->id,
							]
						)
					);
					?>
								"><?php esc_html_e( 'Edit', 'i-downloads' ); ?></a>
					&nbsp;|&nbsp;
					<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this license?', 'i-downloads' ); ?>');">
						<?php wp_nonce_field( 'idl_license_action' ); ?>
						<input type="hidden" name="idl_license_action" value="delete" />
						<input type="hidden" name="license_id" value="<?php echo esc_attr( $lic->id ); ?>" />
						<button type="submit" class="button-link" style="color:#a00;"><?php esc_html_e( 'Delete', 'i-downloads' ); ?></button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
