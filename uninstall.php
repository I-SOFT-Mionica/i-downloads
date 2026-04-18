<?php
/**
 * Uninstall handler for i-Downloads Core.
 *
 * Runs when the plugin is deleted via the WP admin.
 * Controlled by the 'idl_delete_data_on_uninstall' option (default: off).
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'idl_delete_data_on_uninstall' ) ) {
	return;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall runs once; table drop cannot go through higher-level APIs.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_files" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_download_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_licenses" );

// Delete all idl posts and their meta
$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'idl'" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
foreach ( $post_ids as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

// Delete all idl_category terms
$terms = get_terms( [ 'taxonomy' => 'idl_category', 'hide_empty' => false ] );
if ( is_array( $terms ) ) {
	foreach ( $terms as $term ) {
		wp_delete_term( $term->term_id, 'idl_category' );
	}
}

// Delete all plugin options
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup; no WP API for wildcard option delete.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'idl_' ) . '%'
	)
);

// Remove capabilities from all roles
$capabilities = [
	'idl_view_downloads',
	'idl_create_downloads',
	'idl_edit_own_downloads',
	'idl_edit_all_downloads',
	'idl_delete_downloads',
	'idl_manage_categories',
	'idl_view_logs',
	'idl_export_logs',
	'idl_manage_settings',
];

$role_names = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];
foreach ( $role_names as $role_name ) {
	$role = get_role( $role_name );
	if ( ! $role ) {
		continue;
	}
	foreach ( $capabilities as $cap ) {
		$role->remove_cap( $cap );
	}
}

// Delete custom upload folder
$upload_dir  = wp_upload_dir();
$custom_dir  = $upload_dir['basedir'] . '/idl-files';
if ( is_dir( $custom_dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	global $wp_filesystem;
	if ( ! $wp_filesystem ) {
		WP_Filesystem();
	}
	$wp_filesystem->delete( $custom_dir, true, 'd' );
}

flush_rewrite_rules();
