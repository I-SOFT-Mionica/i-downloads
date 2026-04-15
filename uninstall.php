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

// Drop custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_files" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_download_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}idl_licenses" );

// Delete all idl posts and their meta
$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'idl'" );
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
	// Recursive delete
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $custom_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $file ) {
		$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
	}
	rmdir( $custom_dir );
}

flush_rewrite_rules();
