<?php
/**
 * Download Log viewer — Phase 4.
 *
 * @var IDL_Log_Table $table  Prepared list table instance.
 */
defined( 'ABSPATH' ) || exit;

// Process bulk actions before output.
$table->process_bulk_action();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display filters from query string.
$filter_download = isset( $_REQUEST['filter_download'] ) ? absint( $_REQUEST['filter_download'] ) : 0;
$search          = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// All downloads for filter dropdown.
$all_downloads = get_posts(
	[
		'post_type'      => 'idl',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	]
);

$base_url = admin_url( 'edit.php?post_type=idl&page=idl-log' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Download Log', 'i-downloads' ); ?></h1>

	<?php if ( current_user_can( 'idl_export_logs' ) ) : ?>
		<a href="<?php echo esc_url( $base_url . '&idl_action=export_csv' . ( $filter_download ? '&filter_download=' . $filter_download : '' ) . ( $search ? '&s=' . rawurlencode( $search ) : '' ) ); ?>"
			class="page-title-action">
			<?php esc_html_e( 'Export CSV', 'i-downloads' ); ?>
		</a>
		<a href="<?php echo esc_url( $base_url . '&idl_action=export_json' . ( $filter_download ? '&filter_download=' . $filter_download : '' ) . ( $search ? '&s=' . rawurlencode( $search ) : '' ) ); ?>"
			class="page-title-action">
			<?php esc_html_e( 'Export JSON', 'i-downloads' ); ?>
		</a>
	<?php endif; ?>

	<hr class="wp-header-end">

	<?php if ( ! get_option( 'idl_enable_logging', true ) ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'Download logging is currently disabled. Enable it in Settings → General.', 'i-downloads' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	// Show purge notice.
	if ( isset( $_GET['purged'] ) ) {
		$purged = absint( $_GET['purged'] );
		echo '<div class="notice notice-success is-dismissible"><p>';
		/* translators: %d: number of entries deleted */
		printf( esc_html__( '%d log entries deleted.', 'i-downloads' ), (int) $purged );
		echo '</p></div>';
	}
	?>

	<form method="get" class="idl-log-filters">
		<input type="hidden" name="post_type" value="idl" />
		<input type="hidden" name="page" value="idl-log" />

		<div class="alignleft actions">
			<select name="filter_download">
				<option value="0"><?php esc_html_e( '— All downloads —', 'i-downloads' ); ?></option>
				<?php foreach ( $all_downloads as $dl ) : ?>
					<option value="<?php echo absint( $dl->ID ); ?>" <?php selected( $filter_download, $dl->ID ); ?>>
						<?php echo esc_html( $dl->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'i-downloads' ), 'action', 'filter_action', false ); ?>
		</div>

		<?php $table->search_box( __( 'Search log', 'i-downloads' ), 'idl_log_search' ); ?>
	</form>

	<form method="post">
		<input type="hidden" name="post_type" value="idl" />
		<input type="hidden" name="page" value="idl-log" />
		<?php if ( $filter_download ) : ?>
			<input type="hidden" name="filter_download" value="<?php echo absint( $filter_download ); ?>" />
		<?php endif; ?>
		<?php if ( $search ) : ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
		<?php endif; ?>

		<?php $table->display(); ?>
	</form>

	<?php if ( current_user_can( 'idl_manage_settings' ) ) : ?>
		<hr>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				onsubmit="return confirm('<?php echo esc_js( __( 'This will permanently delete log entries older than the configured retention period. Continue?', 'i-downloads' ) ); ?>')">
			<?php wp_nonce_field( 'idl_purge_logs' ); ?>
			<input type="hidden" name="action" value="idl_purge_logs" />
			<?php submit_button( __( 'Purge old log entries', 'i-downloads' ), 'delete', 'submit', false ); ?>
			<span class="description">
				<?php
				printf(
					/* translators: %d: retention days setting */
					esc_html__( 'Deletes entries older than %d days (configured in Settings).', 'i-downloads' ),
					(int) get_option( 'idl_log_retention_days', 365 )
				);
				?>
			</span>
		</form>
	<?php endif; ?>
</div>
