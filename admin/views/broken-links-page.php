<?php
/**
 * Admin view: Downloads → Broken Links.
 *
 * $table is an IDL_Broken_Links_Table prepared by IDL_Settings::render_broken_links().
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap idl-broken-links-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Broken Links', 'i-downloads' ); ?></h1>

	<p class="description" style="max-width:780px;">
		<?php esc_html_e( 'Files listed here are missing from their expected folder on disk. Use the Recover action on each row to relink, move the file back, reassign the download, split it into a new download, reupload, or detach the file from this download.', 'i-downloads' ); ?>
	</p>

	<?php if ( empty( $table->items ) ) : ?>
		<div class="notice notice-success inline" style="margin-top:1em;">
			<p><?php esc_html_e( 'All files are present. Nothing to recover.', 'i-downloads' ); ?></p>
		</div>
	<?php else : ?>
		<form method="get">
			<input type="hidden" name="post_type" value="idl" />
			<input type="hidden" name="page" value="idl-broken-links" />
			<?php $table->display(); ?>
		</form>
	<?php endif; ?>

	<!-- Recovery dialog template (hidden, cloned by JS per row click). -->
	<div id="idl-recover-dialog" style="display:none;" aria-hidden="true">
		<div class="idl-recover-dialog__backdrop"></div>
		<div class="idl-recover-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="idl-recover-title">
			<button type="button" class="idl-recover-close" aria-label="<?php esc_attr_e( 'Close', 'i-downloads' ); ?>">&times;</button>
			<h2 id="idl-recover-title"><?php esc_html_e( 'Recover File', 'i-downloads' ); ?></h2>
			<div class="idl-recover-status" aria-live="polite"></div>
			<div class="idl-recover-summary"></div>
			<div class="idl-recover-actions">
				<p class="idl-recover-cross-cat" hidden>
					<strong><?php esc_html_e( 'File found in a different category folder.', 'i-downloads' ); ?></strong><br>
					<span class="description"><?php esc_html_e( 'Pick how to resolve the mismatch:', 'i-downloads' ); ?></span>
				</p>
				<p>
					<button type="button" class="button" data-action="move_back" hidden>
						<?php esc_html_e( '1. Move file back', 'i-downloads' ); ?>
					</button>
					<button type="button" class="button" data-action="reassign" hidden>
						<?php esc_html_e( '2. Reassign download', 'i-downloads' ); ?>
					</button>
					<button type="button" class="button" data-action="split" hidden>
						<?php esc_html_e( '3. Split into new download', 'i-downloads' ); ?>
					</button>
				</p>
				<p class="idl-recover-fallback">
					<label class="button">
						<?php esc_html_e( 'Reupload…', 'i-downloads' ); ?>
						<input type="file" class="idl-recover-file" hidden>
					</label>
					<button type="button" class="button button-link-delete" data-action="detach">
						<?php esc_html_e( 'Detach file', 'i-downloads' ); ?>
					</button>
				</p>
			</div>
		</div>
	</div>
</div>
