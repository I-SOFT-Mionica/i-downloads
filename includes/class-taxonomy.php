<?php
defined( 'ABSPATH' ) || exit;

class IDL_Taxonomy {

	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'idl_category_add_form_fields', [ $this, 'add_term_fields' ] );
		add_action( 'idl_category_edit_form_fields', [ $this, 'edit_term_fields' ] );
		add_action( 'created_idl_category', [ $this, 'save_term_fields' ] );
		add_action( 'edited_idl_category', [ $this, 'save_term_fields' ] );
	}

	public function register(): void {
		register_taxonomy(
			'idl_category',
			'idl',
			[
				'labels'            => [
					'name'              => _x( 'Download Categories', 'taxonomy general name', 'i-downloads' ),
					'singular_name'     => _x( 'Download Category', 'taxonomy singular name', 'i-downloads' ),
					'search_items'      => __( 'Search Categories', 'i-downloads' ),
					'all_items'         => __( 'All Categories', 'i-downloads' ),
					'parent_item'       => __( 'Parent Category', 'i-downloads' ),
					'parent_item_colon' => __( 'Parent Category:', 'i-downloads' ),
					'edit_item'         => __( 'Edit Category', 'i-downloads' ),
					'update_item'       => __( 'Update Category', 'i-downloads' ),
					'add_new_item'      => __( 'Add New Category', 'i-downloads' ),
					'new_item_name'     => __( 'New Category Name', 'i-downloads' ),
					'menu_name'         => __( 'Categories', 'i-downloads' ),
				],
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => get_option( 'idl_category_slug', 'download-category' ) ],
				'capabilities'      => [
					'manage_terms' => 'idl_manage_categories',
					'edit_terms'   => 'idl_manage_categories',
					'delete_terms' => 'idl_manage_categories',
					'assign_terms' => 'idl_create_downloads',
				],
			]
		);

		// Tags — non-hierarchical, multiple per download
		register_taxonomy(
			'idl_tag',
			'idl',
			[
				'labels'            => [
					'name'                       => _x( 'Download Tags', 'taxonomy general name', 'i-downloads' ),
					'singular_name'              => _x( 'Download Tag', 'taxonomy singular name', 'i-downloads' ),
					'search_items'               => __( 'Search Tags', 'i-downloads' ),
					'all_items'                  => __( 'All Tags', 'i-downloads' ),
					'edit_item'                  => __( 'Edit Tag', 'i-downloads' ),
					'update_item'                => __( 'Update Tag', 'i-downloads' ),
					'add_new_item'               => __( 'Add New Tag', 'i-downloads' ),
					'new_item_name'              => __( 'New Tag Name', 'i-downloads' ),
					'popular_items'              => __( 'Popular Tags', 'i-downloads' ),
					'separate_items_with_commas' => __( 'Separate tags with commas', 'i-downloads' ),
					'add_or_remove_items'        => __( 'Add or remove tags', 'i-downloads' ),
					'choose_from_most_used'      => __( 'Choose from the most used tags', 'i-downloads' ),
					'not_found'                  => __( 'No tags found.', 'i-downloads' ),
					'menu_name'                  => __( 'Tags', 'i-downloads' ),
				],
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => get_option( 'idl_tag_slug', 'download-tag' ) ],
				'capabilities'      => [
					'manage_terms' => 'idl_manage_categories',
					'edit_terms'   => 'idl_manage_categories',
					'delete_terms' => 'idl_manage_categories',
					'assign_terms' => 'idl_create_downloads',
				],
			]
		);

		register_term_meta(
			'idl_category',
			'_idl_cat_icon',
			[
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			]
		);
		register_term_meta(
			'idl_category',
			'_idl_cat_access_role',
			[
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
		register_term_meta(
			'idl_category',
			'_idl_cat_sort_order',
			[
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
			]
		);
	}

	public function add_term_fields(): void {
		?>
		<div class="form-field">
			<label for="idl-cat-icon"><?php esc_html_e( 'Icon', 'i-downloads' ); ?></label>
			<input type="text" name="idl_cat_icon" id="idl-cat-icon" value="" />
			<p class="description"><?php esc_html_e( 'Dashicon name (e.g. dashicons-folder) or image URL.', 'i-downloads' ); ?></p>
		</div>
		<div class="form-field">
			<label for="idl-cat-access-role"><?php esc_html_e( 'Access Role', 'i-downloads' ); ?></label>
			<?php $this->render_access_role_select( '', 'idl_cat_access_role', 'idl-cat-access-role' ); ?>
		</div>
		<div class="form-field">
			<label for="idl-cat-sort-order"><?php esc_html_e( 'Sort Order', 'i-downloads' ); ?></label>
			<input type="number" name="idl_cat_sort_order" id="idl-cat-sort-order" value="0" min="0" />
		</div>
		<?php
	}

	public function edit_term_fields( WP_Term $term ): void {
		$icon       = get_term_meta( $term->term_id, '_idl_cat_icon', true );
		$role       = get_term_meta( $term->term_id, '_idl_cat_access_role', true );
		$sort_order = (int) get_term_meta( $term->term_id, '_idl_cat_sort_order', true );
		?>
		<tr class="form-field">
			<th><label for="idl-cat-icon"><?php esc_html_e( 'Icon', 'i-downloads' ); ?></label></th>
			<td>
				<input type="text" name="idl_cat_icon" id="idl-cat-icon" value="<?php echo esc_attr( $icon ); ?>" />
				<p class="description"><?php esc_html_e( 'Dashicon name or image URL.', 'i-downloads' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="idl-cat-access-role"><?php esc_html_e( 'Access Role', 'i-downloads' ); ?></label></th>
			<td><?php $this->render_access_role_select( $role, 'idl_cat_access_role', 'idl-cat-access-role' ); ?></td>
		</tr>
		<tr class="form-field">
			<th><label for="idl-cat-sort-order"><?php esc_html_e( 'Sort Order', 'i-downloads' ); ?></label></th>
			<td><input type="number" name="idl_cat_sort_order" id="idl-cat-sort-order" value="<?php echo esc_attr( $sort_order ); ?>" min="0" /></td>
		</tr>
		<?php
	}

	public function save_term_fields( int $term_id ): void {
		// Nonce verified by WP core term edit form.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['idl_cat_icon'] ) ) {
			update_term_meta( $term_id, '_idl_cat_icon', sanitize_text_field( wp_unslash( $_POST['idl_cat_icon'] ) ) );
		}
		if ( isset( $_POST['idl_cat_access_role'] ) ) {
			update_term_meta( $term_id, '_idl_cat_access_role', sanitize_text_field( wp_unslash( $_POST['idl_cat_access_role'] ) ) );
		}
		if ( isset( $_POST['idl_cat_sort_order'] ) ) {
			update_term_meta( $term_id, '_idl_cat_sort_order', absint( $_POST['idl_cat_sort_order'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	private function render_access_role_select( string $selected, string $name, string $id ): void {
		$roles = [
			'public'        => __( 'Public (everyone)', 'i-downloads' ),
			'subscriber'    => __( 'Subscriber+', 'i-downloads' ),
			'contributor'   => __( 'Contributor+', 'i-downloads' ),
			'author'        => __( 'Author+', 'i-downloads' ),
			'editor'        => __( 'Editor+', 'i-downloads' ),
			'administrator' => __( 'Administrator only', 'i-downloads' ),
		];
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';
		foreach ( $roles as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}
}
