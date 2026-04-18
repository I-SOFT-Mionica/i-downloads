<?php
/**
 * Plugin Name: i-Downloads
 * Plugin URI:  https://isoft.rs/i-downloads
 * Description: Hierarchical file download manager — categories, multi-file entries, secure download handler, audit logging, and role-based access control.
 * Version:     0.5.3
 * Author:      I-SOFT Mionica
 * Author URI:  https://isoft.rs
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: i-downloads
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP:      8.4
 */

defined( 'ABSPATH' ) || exit;

const IDL_VERSION = '0.5.3';
define( 'IDL_PLUGIN_FILE', __FILE__ );
define( 'IDL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IDL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IDL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Global helper functions (always available, no autoload magic needed)
require_once IDL_PLUGIN_DIR . 'includes/functions.php';

// Class autoloader: IDL_Post_Type → includes/class-post-type.php
spl_autoload_register(
	function ( string $class ): void {
		if ( ! str_starts_with( $class, 'IDL_' ) ) {
				return;
		}
		$name = strtolower( str_replace( [ 'IDL_', '_' ], [ '', '-' ], $class ) );
		$path = IDL_PLUGIN_DIR . 'includes/class-' . $name . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

// Activation / deactivation
register_activation_hook( __FILE__, [ 'IDL_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'IDL_Deactivator', 'deactivate' ] );

// Ensure custom capabilities are always registered (guards against activation timing issues).
add_action( 'init', [ 'IDL_Activator', 'maybe_register_capabilities' ] );

// If the stored version differs from the current version, queue a rewrite flush and
// run dbDelta so new table columns appear without manual deactivate/reactivate.
add_action(
	'plugins_loaded',
	function (): void {
		if ( get_option( 'idl_db_version' ) !== IDL_VERSION ) {
			IDL_Activator::activate();
		}
	},
	1
);

/**
 * Bootstrap after all plugins are loaded so extensions can hook in.
 */
add_action(
	'plugins_loaded',
	function (): void {
		// Translations for wp.org-hosted plugins are auto-loaded by WordPress since 4.6.

		// Core registrations
		new IDL_Post_Type()->register_hooks();
		new IDL_Taxonomy()->register_hooks();
		new IDL_Meta_Fields()->register_hooks();

		// Extension API — fires idl_extensions_init so Sentinel/Orbit can register
		new IDL_Extension_Api()->register_hooks();

		// Download routing (frontend)
		new IDL_Download_Handler()->register_hooks();

		// Access control — query-level RBAC filtering on frontend.
		new IDL_Access_Control()->register_hooks();

		// File integrity — serve-time detection + daily cron.
		new IDL_File_Integrity()->register_hooks();

		// Template hierarchy — load plugin templates for CPT/taxonomies (classic themes only).
		// Single downloads are handled via the_content filter in IDL_Post_Type for all themes.
		// Archive/taxonomy pages still need PHP templates for classic themes.
		add_filter(
			'template_include',
			function ( string $template ): string {
				// FSE block themes handle everything via block templates + the_content filter.
				if ( wp_is_block_theme() ) {
					return $template;
				}

				// Classic theme fallbacks — single download uses the_content filter, no custom template needed.
				$candidates = [];
				if ( is_post_type_archive( 'idl' ) ) {
					$candidates[] = 'archive-idl.php';
				}
				if ( is_tax( 'idl_category' ) ) {
					$candidates[] = 'taxonomy-idl_category.php';
				}
				if ( is_tax( 'idl_tag' ) ) {
					$candidates[] = 'taxonomy-idl_tag.php';
				}

				foreach ( $candidates as $file ) {
					$theme_override = locate_template( "i-downloads/{$file}" );
					if ( $theme_override ) {
						return $theme_override;
					}
					$plugin_template = IDL_PLUGIN_DIR . 'templates/' . $file;
					if ( file_exists( $plugin_template ) ) {
						return $plugin_template;
					}
				}

				return $template;
			}
		);

		// Category folder lifecycle (create / rename / warn on delete)
		new IDL_Category_Folders()->register_hooks();

		// Per-user write-side category ACL.
		new IDL_Category_ACL()->register_hooks();

		// Shortcodes (registered on all requests for REST/preview compatibility)
		new IDL_Shortcodes()->register_hooks();

		// REST API (needed outside admin too)
		new IDL_Rest_Api()->register_hooks();

		// Gutenberg blocks
		new IDL_Blocks()->register_hooks();

		// CSV / JSON export + log purge (admin-post.php actions)
		new IDL_Export()->register_hooks();

		// Scheduled tasks (HOT recalculation, log purge)
		new IDL_Cron()->register_hooks();

		if ( is_admin() ) {
			new IDL_Admin_Meta_Boxes()->register_hooks();
			new IDL_Admin_Columns()->register_hooks();
			new IDL_Settings()->register_hooks();
			new IDL_Broken_Links_Ajax()->register_hooks();
			new IDL_License_Manager()->register_hooks();
			new IDL_Pdf_Thumbnail()->register_hooks();
			new IDL_Tinymce()->register_hooks();
			new IDL_Demo_Content()->register_hooks();
		}
	}
);
