<?php
defined( 'ABSPATH' ) || exit;

class IDL_Post_Type {

	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
		add_filter( 'the_content', array( $this, 'append_download_content' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'latinize_slug' ), 10, 2 );
	}

	/**
	 * Transliterate Cyrillic in idl post slugs to Latin.
	 * sanitize_title() URL-percent-encodes non-ASCII, so by the time we see
	 * post_name it may be either raw Cyrillic or %XX sequences — urldecode
	 * first before inspecting.
	 */
	public function latinize_slug( array $data, array $postarr ): array {
		unset( $postarr );
		if ( 'idl' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		$source = $data['post_name'] !== '' ? $data['post_name'] : $data['post_title'];
		if ( '' === $source ) {
			return $data;
		}

		$decoded = urldecode( $source );
		if ( preg_match( '/\p{Cyrillic}/u', $decoded ) ) {
			$data['post_name'] = sanitize_title( idl_cyrillic_to_latin( $decoded ) );
		}
		return $data;
	}

	public function register(): void {
		$labels = array(
			'name'               => _x( 'Downloads', 'post type general name', 'i-downloads' ),
			'singular_name'      => _x( 'Download', 'post type singular name', 'i-downloads' ),
			'menu_name'          => _x( 'i-Downloads', 'admin menu', 'i-downloads' ),
			'name_admin_bar'     => _x( 'Download', 'add new on admin bar', 'i-downloads' ),
			'add_new'            => __( 'Add New', 'i-downloads' ),
			'add_new_item'       => __( 'Add New Download', 'i-downloads' ),
			'new_item'           => __( 'New Download', 'i-downloads' ),
			'edit_item'          => __( 'Edit Download', 'i-downloads' ),
			'view_item'          => __( 'View Download', 'i-downloads' ),
			'all_items'          => __( 'All Downloads', 'i-downloads' ),
			'search_items'       => __( 'Search Downloads', 'i-downloads' ),
			'not_found'          => __( 'No downloads found.', 'i-downloads' ),
			'not_found_in_trash' => __( 'No downloads found in Trash.', 'i-downloads' ),
		);

		register_post_type(
			'idl',
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => get_option( 'idl_archive_slug', 'downloads' ) ),
				'capability_type'    => 'post',  // Standard WP caps — no custom mapping.
				'map_meta_cap'       => true,    // Custom caps used only for settings/logs/export.
				'has_archive'        => get_option( 'idl_archive_slug', 'downloads' ),
				'hierarchical'       => false,
				'menu_position'      => 26,
				'menu_icon'          => 'dashicons-download',
				'supports'           => array( 'title', 'thumbnail', 'excerpt', 'revisions', 'author' ),
				'show_in_rest'       => true,
				'rest_base'          => 'idl-downloads',
			)
		);

		// Disable block editor for downloads — files are the primary content, not prose.
		add_filter(
			'use_block_editor_for_post_type',
			function ( bool $use, string $post_type ): bool {
				return $post_type === 'idl' ? false : $use;
			},
			10,
			2
		);
	}

	/**
	 * Flush rewrite rules once after activation (or whenever the flag is set).
	 * Runs at init priority 999 — after the CPT is already registered — so the
	 * 'idl' rewrite rules are included in the flushed set.
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'idl_flush_rewrite_rules' ) ) {
			delete_option( 'idl_flush_rewrite_rules' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Append file listing and metadata to the post content on single download pages.
	 * Works with both FSE block templates (via <!-- wp:post-content /-->) and
	 * classic PHP templates.
	 */
	public function append_download_content( string $content ): string {
		if ( ! is_singular( 'idl' ) ) {
			return $content;
		}

		// In FSE themes the loop context differs — get_post() is reliable here.
		$post = get_post();
		if ( ! $post || $post->post_type !== 'idl' ) {
			return $content;
		}

		// Prevent double-injection if the filter runs more than once.
		static $appended = array();
		if ( isset( $appended[ $post->ID ] ) ) {
			return $content;
		}
		$appended[ $post->ID ] = true;

		$files    = new IDL_File_Manager()->get_files( $post->ID );
		$settings = idl_get_settings();

		ob_start();
		require IDL_PLUGIN_DIR . 'public/views/download-single.php';
		return $content . ob_get_clean();
	}
}
