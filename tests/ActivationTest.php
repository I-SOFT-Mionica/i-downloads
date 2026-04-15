<?php
/**
 * Smoke tests for plugin activation: custom tables, CPT, and taxonomy registration.
 */

class ActivationTest extends WP_UnitTestCase {

	public function test_custom_tables_exist(): void {
		global $wpdb;

		foreach ( [ 'idl_files', 'idl_download_log', 'idl_download_daily', 'idl_licenses' ] as $suffix ) {
			$table = $wpdb->prefix . $suffix;
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$this->assertSame( $table, $found, "Table {$table} should exist after activation." );
		}
	}

	public function test_cpt_registered(): void {
		$this->assertTrue( post_type_exists( 'idl' ), 'idl CPT should be registered.' );
	}

	public function test_taxonomies_registered(): void {
		$this->assertTrue( taxonomy_exists( 'idl_category' ) );
		$this->assertTrue( taxonomy_exists( 'idl_tag' ) );
	}

	public function test_files_dir_path_is_under_uploads(): void {
		$base = wp_upload_dir()['basedir'];
		$this->assertStringStartsWith( $base, idl_files_dir() );
		$this->assertStringEndsWith( 'idl-files', idl_files_dir() );
	}
}
