<?php
defined( 'ABSPATH' ) || exit;

class IDL_I18n {

	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'i-downloads',
			false,
			dirname( IDL_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
