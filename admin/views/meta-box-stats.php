<?php defined( 'ABSPATH' ) || exit; ?>
<p>
	<strong><?php esc_html_e( 'Total Downloads:', 'i-downloads' ); ?></strong>
	<?php echo esc_html( number_format_i18n( $total_downloads ) ); ?>
</p>

<?php if ( $files ) : ?>
	<table class="widefat striped" style="margin-top:.5em;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'File', 'i-downloads' ); ?></th>
				<th><?php esc_html_e( 'Count', 'i-downloads' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $files as $file ) : ?>
			<tr>
				<td><?php echo esc_html( $file->title ?: $file->file_name ?: $file->external_url ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $file->download_count ) ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p style="color:#999;"><?php esc_html_e( 'No files attached.', 'i-downloads' ); ?></p>
<?php endif; ?>

<?php if ( $total_downloads > 0 ) : ?>
	<p style="margin-top:.75em;">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				[
					'page'        => 'idl-log',
					'post_type'   => 'idl',
					'download_id' => get_the_ID(),
				],
				admin_url( 'edit.php' )
			)
		);
		?>
					">
			<?php esc_html_e( 'View full download log →', 'i-downloads' ); ?>
		</a>
	</p>
<?php endif; ?>
