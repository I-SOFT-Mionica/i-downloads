<?php defined( 'ABSPATH' ) || exit; ?>
<table class="form-table">
	<tr>
		<th><label for="idl-access-role"><?php esc_html_e( 'Access Role', 'i-downloads' ); ?></label></th>
		<td>
			<select name="_idl_access_role" id="idl-access-role">
				<?php
				$roles = [
					'public'        => __( 'Public (everyone)', 'i-downloads' ),
					'subscriber'    => __( 'Subscriber+', 'i-downloads' ),
					'contributor'   => __( 'Contributor+', 'i-downloads' ),
					'author'        => __( 'Author+', 'i-downloads' ),
					'editor'        => __( 'Editor+', 'i-downloads' ),
					'administrator' => __( 'Administrator only', 'i-downloads' ),
				];
				foreach ( $roles as $value => $label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $value ),
						selected( $access_role, $value, false ),
						esc_html( $label )
					);
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Minimum role required to download files from this entry.', 'i-downloads' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Require Agreement', 'i-downloads' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="_idl_require_agree" value="1" <?php checked( $require_agree ); ?> />
				<?php esc_html_e( 'Require user to agree before downloading', 'i-downloads' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Uses the assigned license full text, or the custom text below.', 'i-downloads' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><label for="idl-agree-text"><?php esc_html_e( 'Agreement Text', 'i-downloads' ); ?></label></th>
		<td>
			<textarea name="_idl_agree_text" id="idl-agree-text" class="widefat" rows="4"><?php echo esc_textarea( $agree_text ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Shown in the agreement modal if no license is assigned.', 'i-downloads' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Featured', 'i-downloads' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="_idl_featured" value="1" <?php checked( $featured ); ?> />
				<?php esc_html_e( 'Mark as featured/pinned download', 'i-downloads' ); ?>
			</label>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'External Only', 'i-downloads' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="_idl_external_only" value="1" <?php checked( $external_only ); ?> />
				<?php esc_html_e( 'No local files — all links are external', 'i-downloads' ); ?>
			</label>
		</td>
	</tr>
</table>
