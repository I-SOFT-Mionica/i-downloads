<?php
defined( 'ABSPATH' ) || exit;

class IDL_Meta_Fields {

	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register' ] );
	}

	public function register(): void {
		$fields = [
			'_idl_version'        => 'string',
			'_idl_changelog'      => 'string',
			'_idl_license_id'     => 'integer',
			'_idl_author_name'    => 'string',
			'_idl_author_url'     => 'string',
			'_idl_date_published' => 'string',
			'_idl_download_count' => 'integer',
			'_idl_access_role'    => 'string',
			'_idl_require_agree'  => 'boolean',
			'_idl_agree_text'     => 'string',
			'_idl_is_hot'         => 'boolean',
			'_idl_featured'       => 'boolean',
			'_idl_external_only'  => 'boolean',
		];

		foreach ( $fields as $key => $type ) {
			register_post_meta(
				'idl',
				$key,
				[
					'type'          => $type,
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => fn() => current_user_can( 'idl_edit_own_downloads' ),
				]
			);
		}
	}
}
