<?php defined( 'ABSPATH' ) || exit; ?>
<table class="form-table">
	<tr>
		<th><label for="idl-version"><?php esc_html_e( 'Version', 'i-downloads' ); ?></label></th>
		<td><input type="text" name="_idl_version" id="idl-version" value="<?php echo esc_attr( $version ); ?>" class="regular-text" placeholder="1.0.0" /></td>
	</tr>
	<tr>
		<th><label for="idl-changelog"><?php esc_html_e( 'Changelog', 'i-downloads' ); ?></label></th>
		<td>
			<textarea name="_idl_changelog" id="idl-changelog" class="widefat" rows="5"><?php echo esc_textarea( $changelog ); ?></textarea>
			<p class="description"><?php esc_html_e( "What's new in this version.", 'i-downloads' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><label for="idl-license"><?php esc_html_e( 'License', 'i-downloads' ); ?></label></th>
		<td>
			<select name="_idl_license_id" id="idl-license">
				<option value="0"><?php esc_html_e( '— None —', 'i-downloads' ); ?></option>
				<?php foreach ( $licenses as $lic ) : ?>
					<option value="<?php echo esc_attr( $lic->id ); ?>" <?php selected( $license_id, $lic->id ); ?>>
						<?php echo esc_html( $lic->title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><label for="idl-author-name"><?php esc_html_e( 'Author Name', 'i-downloads' ); ?></label></th>
		<td><input type="text" name="_idl_author_name" id="idl-author-name" value="<?php echo esc_attr( $author_name ); ?>" class="regular-text" /></td>
	</tr>
	<tr>
		<th><label for="idl-author-url"><?php esc_html_e( 'Author URL', 'i-downloads' ); ?></label></th>
		<td><input type="url" name="_idl_author_url" id="idl-author-url" value="<?php echo esc_attr( $author_url ); ?>" class="regular-text" placeholder="https://…" /></td>
	</tr>
	<tr>
		<th><label for="idl-date-published"><?php esc_html_e( 'Date Published', 'i-downloads' ); ?></label></th>
		<td>
			<input type="date" name="_idl_date_published" id="idl-date-published" value="<?php echo esc_attr( $date_published ); ?>" />
			<p class="description"><?php esc_html_e( 'Original publication date of the document (may differ from the WordPress post date).', 'i-downloads' ); ?></p>
		</td>
	</tr>
</table>
