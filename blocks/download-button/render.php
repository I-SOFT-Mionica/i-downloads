<?php
/**
 * Server-side render for i-downloads/download-button block.
 *
 * Renders the full download card layout for the selected download,
 * identical to the download list view.
 */
defined( 'ABSPATH' ) || exit;

$download_id = absint( $attributes['downloadId'] ?? 0 );

if ( ! $download_id ) {
	echo '<p class="idl-block-placeholder">' . esc_html__( 'Select a download in the block settings.', 'i-downloads' ) . '</p>';
	return;
}

$post = get_post( $download_id );
if ( ! $post || $post->post_type !== 'idl' || $post->post_status !== 'publish' ) {
	echo '<p class="idl-block-placeholder">' . esc_html__( 'Download not found.', 'i-downloads' ) . '</p>';
	return;
}

$settings = idl_get_settings();

ob_start();
require IDL_PLUGIN_DIR . 'public/views/download-card.php';
echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
