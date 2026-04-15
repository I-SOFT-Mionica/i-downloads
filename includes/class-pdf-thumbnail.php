<?php
defined( 'ABSPATH' ) || exit;

class IDL_Pdf_Thumbnail {

	public function register_hooks(): void {
		add_action( 'idl_file_uploaded', [ $this, 'maybe_generate' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'maybe_show_notice' ] );
	}

	public function maybe_generate( int $file_id, int $download_id ): void {
		$settings = idl_get_settings();
		if ( ! $settings['enable_pdf_thumbnails'] ) {
			return;
		}

		$file = new IDL_File_Manager()->get_file( $file_id );
		if ( ! $file || 'application/pdf' !== $file->file_mime || empty( $file->file_path ) ) {
			return;
		}
		if ( has_post_thumbnail( $download_id ) && ! $settings['overwrite_pdf_thumbnail'] ) {
			return;
		}

		$pdf_path = idl_files_dir() . '/' . $file->file_path;
		if ( ! file_exists( $pdf_path ) ) {
			return;
		}

		$backend = apply_filters( 'idl_pdf_thumbnail_backend', $this->detect_backend() );
		if ( ! $backend ) {
			return;
		}

		$args = apply_filters(
			'idl_pdf_thumbnail_args',
			[
				'width'   => $settings['pdf_thumb_width'],
				'height'  => $settings['pdf_thumb_height'],
				'quality' => $settings['pdf_thumb_quality'],
			]
		);

		$thumb_path = $this->render( $pdf_path, $backend, $args );
		if ( ! $thumb_path ) {
			return;
		}

		$attachment_id = $this->save_as_attachment( $thumb_path, $download_id );
		if ( $attachment_id ) {
			set_post_thumbnail( $download_id, $attachment_id );
			do_action( 'idl_pdf_thumbnail_generated', $download_id, $file_id, $attachment_id );
		}
	}

	public function detect_backend(): ?string {
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			return 'imagick';
		}
		$gs = trim( (string) @shell_exec( 'which gs 2>/dev/null' ) );
		if ( $gs ) {
			return 'ghostscript';
		}
		return null;
	}

	private function render( string $pdf_path, string $backend, array $args ): ?string {
		$out = sys_get_temp_dir() . '/idl_thumb_' . md5( $pdf_path . microtime() ) . '.jpg';

		if ( 'imagick' === $backend ) {
			try {
				/** @var \Imagick $im */
				$im = new \Imagick();
				$im->setResolution( 150, 150 );
				$im->readImage( $pdf_path . '[0]' );
				$im->setImageFormat( 'jpeg' );
				$im->setImageCompressionQuality( $args['quality'] );
				$im->thumbnailImage( $args['width'], $args['height'], true );
				$im->writeImage( $out );
				$im->clear();
			} catch ( Exception $e ) {
				return null;
			}
		} elseif ( 'ghostscript' === $backend ) {
			$cmd = sprintf(
				'gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile=%s %s 2>/dev/null',
				escapeshellarg( $out ),
				escapeshellarg( $pdf_path )
			);
			@exec( $cmd ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		return file_exists( $out ) ? $out : null;
	}

	private function save_as_attachment( string $image_path, int $parent_id ): int|false {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$contents = file_get_contents( $image_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $contents ) {
			return false;
		}

		$upload = wp_upload_bits( basename( $image_path ), null, $contents );
		wp_delete_file( $image_path );

		if ( $upload['error'] ) {
			return false;
		}

		$att_id = wp_insert_attachment(
			[
				'post_mime_type' => 'image/jpeg',
				'post_title'     => get_the_title( $parent_id ) . ' — PDF Thumbnail',
				'post_status'    => 'inherit',
			],
			$upload['file'],
			$parent_id
		);

		if ( is_wp_error( $att_id ) ) {
			return false;
		}

		wp_generate_attachment_metadata( $att_id, $upload['file'] );
		return $att_id;
	}

	public function maybe_show_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( 'idl_pdf_notice_dismissed' ) ) {
			return;
		}
		if ( ! $this->detect_backend() ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			esc_html_e( 'i-Downloads: PDF thumbnail generation is disabled — neither Imagick nor Ghostscript is available on this server.', 'i-downloads' );
			echo '</p></div>';
		}
	}
}
