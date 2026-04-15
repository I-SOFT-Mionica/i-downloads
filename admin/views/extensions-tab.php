<?php defined( 'ABSPATH' ) || exit; ?>
<div class="idl-extensions-tab" style="margin-top:1.5em;">
	<h2><?php esc_html_e( 'Installed Extensions', 'i-downloads' ); ?></h2>

	<?php $extensions = IDL_Extension_Api::get_all(); ?>

	<?php if ( empty( $extensions ) ) : ?>
		<p><?php esc_html_e( 'No extensions are currently active.', 'i-downloads' ); ?></p>
	<?php else : ?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Extension', 'i-downloads' ); ?></th>
					<th><?php esc_html_e( 'Version', 'i-downloads' ); ?></th>
					<th><?php esc_html_e( 'Author', 'i-downloads' ); ?></th>
					<th><?php esc_html_e( 'Description', 'i-downloads' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $extensions as $ext ) : ?>
				<tr>
					<td>
						<?php if ( ! empty( $ext['url'] ) ) : ?>
							<a href="<?php echo esc_url( $ext['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ext['name'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $ext['name'] ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $ext['version'] ); ?></td>
					<td><?php echo esc_html( $ext['author'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $ext['description'] ?? '' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2 style="margin-top:2em;"><?php esc_html_e( 'Available Extensions', 'i-downloads' ); ?></h2>
	<div class="idl-extension-cards" style="display:flex;gap:1.5em;flex-wrap:wrap;margin-top:1em;">
		<div class="card" style="max-width:360px;">
			<h3>
				i-Downloads Sentinel
				<span class="idl-soon-badge"><?php esc_html_e( 'Coming soon', 'i-downloads' ); ?></span>
			</h3>
			<p><?php esc_html_e( 'Monitor local server folders for new files via rclone mirroring, SFTP drops, or scheduled folder scans. Automatically creates draft download entries when files appear in a category folder — no manual uploads needed.', 'i-downloads' ); ?></p>
			<p><a href="https://isoft.rs/sentinel" target="_blank" rel="noopener" class="button" aria-disabled="true"><?php esc_html_e( 'Learn More', 'i-downloads' ); ?></a></p>
		</div>
		<div class="card" style="max-width:360px;">
			<h3>
				i-Downloads Orbit
				<span class="idl-soon-badge"><?php esc_html_e( 'Coming soon', 'i-downloads' ); ?></span>
			</h3>
			<p><?php esc_html_e( 'Sync files from Google Shared Drives. Departments drop files into a shared folder — Orbit picks them up and creates draft downloads for review.', 'i-downloads' ); ?></p>
			<p><a href="https://isoft.rs/orbit" target="_blank" rel="noopener" class="button" aria-disabled="true"><?php esc_html_e( 'Learn More', 'i-downloads' ); ?></a></p>
		</div>
	</div>
	<style>
		.idl-soon-badge {
			display: inline-block;
			margin-left: .5em;
			padding: 2px 8px;
			font-size: 11px;
			font-weight: 600;
			letter-spacing: .03em;
			text-transform: uppercase;
			background: #dba617;
			color: #fff;
			border-radius: 3px;
			vertical-align: middle;
		}
	</style>
</div>
