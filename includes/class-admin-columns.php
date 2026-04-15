<?php
defined( 'ABSPATH' ) || exit;

class IDL_Admin_Columns {

	public function register_hooks(): void {
		add_filter( 'manage_idl_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_idl_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'manage_edit-idl_sortable_columns', [ $this, 'sortable_columns' ] );
	}

	public function add_columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['idl_thumbnail']      = __( 'Thumb', 'i-downloads' );
				$new['idl_category']       = __( 'Category', 'i-downloads' );
				$new['idl_files_count']    = __( 'Files', 'i-downloads' );
				$new['idl_download_count'] = __( 'Downloads', 'i-downloads' );
				$new['idl_access_role']    = __( 'Access', 'i-downloads' );
				$new['idl_featured']       = '★';
			}
		}
		return $new;
	}

	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'idl_thumbnail':
				echo has_post_thumbnail( $post_id )
					? get_the_post_thumbnail( $post_id, [ 40, 40 ] )
					: '<span class="dashicons dashicons-media-default" style="font-size:32px;color:#ccc;line-height:40px;"></span>';
				break;

			case 'idl_category':
				$terms = get_the_terms( $post_id, 'idl_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$links = array_map(
						fn( $t ) => sprintf(
							'<a href="%s">%s</a>',
							esc_url(
								add_query_arg(
									[
										'post_type'    => 'idl',
										'idl_category' => $t->slug,
									],
									admin_url( 'edit.php' )
								)
							),
							esc_html( $t->name )
						),
						$terms
					);
					echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '—';
				}
				break;

			case 'idl_files_count':
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list column COUNT(*) per row on custom idl_files table; rare admin load, caching a single integer is not worth the bust complexity.
				echo esc_html(
					(int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->prefix}idl_files WHERE download_id = %d",
							$post_id
						)
					)
				);
				break;

			case 'idl_download_count':
				echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_idl_download_count', true ) ) );
				break;

			case 'idl_access_role':
				$labels = [
					'public'        => __( 'Public', 'i-downloads' ),
					'subscriber'    => __( 'Subscriber+', 'i-downloads' ),
					'contributor'   => __( 'Contributor+', 'i-downloads' ),
					'author'        => __( 'Author+', 'i-downloads' ),
					'editor'        => __( 'Editor+', 'i-downloads' ),
					'administrator' => __( 'Admin only', 'i-downloads' ),
				];
				$role   = get_post_meta( $post_id, '_idl_access_role', true ) ?: 'public';
				echo esc_html( $labels[ $role ] ?? $role );
				break;

			case 'idl_featured':
				if ( get_post_meta( $post_id, '_idl_featured', true ) ) {
					echo '<span class="dashicons dashicons-star-filled" style="color:#f0b849;" title="' . esc_attr__( 'Featured', 'i-downloads' ) . '"></span>';
				}
				break;
		}
	}

	public function sortable_columns( array $columns ): array {
		$columns['idl_download_count'] = 'idl_download_count';
		return $columns;
	}
}
