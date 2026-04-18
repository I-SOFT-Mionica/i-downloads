<?php
/**
 * TinyMCE / Classic Editor integration.
 *
 * Adds a "Insert Download [iD]" toolbar button that opens a search modal
 * and inserts [idl_download id=X] into the post content.
 */
defined( 'ABSPATH' ) || exit;

class IDL_Tinymce {

	public function register_hooks(): void {
		// Only load in the admin for users who can edit posts.
		if ( ! is_admin() ) {
			return;
		}
		add_filter( 'mce_external_plugins', array( $this, 'add_plugin' ) );
		add_filter( 'mce_buttons', array( $this, 'add_button' ) );
		add_action( 'admin_footer', array( $this, 'render_modal' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_idl_tmce_search', array( $this, 'ajax_search' ) );
	}

	public function add_plugin( array $plugins ): array {
		$plugins['idl_insert'] = IDL_PLUGIN_URL . 'admin/js/tinymce-plugin.js?v=' . IDL_VERSION;
		return $plugins;
	}

	public function add_button( array $buttons ): array {
		$buttons[] = 'idl_insert';
		return $buttons;
	}

	public function enqueue_assets(): void {
		$screen = get_current_screen();
		// Load only on post-editing screens.
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page' ), true ) ) {
			return;
		}
		wp_enqueue_style(
			'idl-tinymce-modal',
			IDL_PLUGIN_URL . 'admin/css/tinymce-modal.css',
			array(),
			IDL_VERSION
		);
	}

	/**
	 * Render the hidden search modal into the admin footer.
	 * JS shows/hides it; results are fetched via AJAX.
	 */
	public function render_modal(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page' ), true ) ) {
			return;
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'idl_category',
				'hide_empty' => false,
				'orderby'    => 'name',
				'fields'     => 'id=>name',
			)
		);
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}
		?>
		<div id="idl-tmce-modal" class="idl-tmce-modal" hidden>
			<div class="idl-tmce-modal__backdrop"></div>
			<div class="idl-tmce-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Insert Download', 'i-downloads' ); ?>">

				<div class="idl-tmce-modal__header">
					<h2 class="idl-tmce-modal__title">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Insert Download [iD]', 'i-downloads' ); ?>
					</h2>
					<button type="button" class="idl-tmce-modal__close" aria-label="<?php esc_attr_e( 'Close', 'i-downloads' ); ?>">&#x2715;</button>
				</div>

				<div class="idl-tmce-modal__filters">
					<input
						type="search"
						id="idl-tmce-search"
						class="idl-tmce-modal__search"
						placeholder="<?php esc_attr_e( 'Search downloads…', 'i-downloads' ); ?>"
						autocomplete="off"
					/>
					<select id="idl-tmce-category" class="idl-tmce-modal__category">
						<option value="0"><?php esc_html_e( 'All categories', 'i-downloads' ); ?></option>
						<?php foreach ( $categories as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div id="idl-tmce-results" class="idl-tmce-modal__results">
					<p class="idl-tmce-modal__hint"><?php esc_html_e( 'Loading…', 'i-downloads' ); ?></p>
				</div>

				<div class="idl-tmce-modal__footer">
					<span class="idl-tmce-modal__hint"><?php esc_html_e( 'Click a download to insert it as a card.', 'i-downloads' ); ?></span>
					<button type="button" class="button idl-tmce-modal__cancel">
						<?php esc_html_e( 'Cancel', 'i-downloads' ); ?>
					</button>
				</div>

			</div>
		</div>
		<script>
		var IDLTmce = {
			nonce: <?php echo wp_json_encode( wp_create_nonce( 'idl_tmce_search' ) ); ?>,
			ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
			i18n: {
				insertDownload: <?php echo wp_json_encode( __( 'Insert Download [iD]', 'i-downloads' ) ); ?>,
				loading:        <?php echo wp_json_encode( __( 'Loading…', 'i-downloads' ) ); ?>,
				loadError:      <?php echo wp_json_encode( __( 'Error loading results.', 'i-downloads' ) ); ?>
			}
		};
		</script>
		<?php
	}

	/**
	 * AJAX handler: search downloads and return HTML rows.
	 */
	public function ajax_search(): void {
		check_ajax_referer( 'idl_tmce_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( '', 403 );
		}

		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$category = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;

		$args = array(
			'post_type'      => 'idl',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'no_found_rows'  => true,
		);

		if ( $search ) {
			$args['s'] = $search;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		if ( $category > 0 ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering; term_relationships index covers this access pattern.
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'idl_category',
					'field'    => 'term_id',
					'terms'    => $category,
				),
			);
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			wp_send_json_success( array( 'html' => '<p class="idl-tmce-modal__empty">' . esc_html__( 'No downloads found.', 'i-downloads' ) . '</p>' ) );
		}

		ob_start();
		echo '<ul class="idl-tmce-modal__list">';
		foreach ( $posts as $post ) {
			$cats = wp_get_post_terms( $post->ID, 'idl_category', array( 'fields' => 'names' ) );
			$cat  = ! is_wp_error( $cats ) && ! empty( $cats ) ? $cats[0] : '';
			echo '<li>';
			echo '<button type="button" class="idl-tmce-modal__item" data-id="' . esc_attr( $post->ID ) . '" data-title="' . esc_attr( $post->post_title ) . '">';
			echo '<span class="dashicons dashicons-media-default"></span>';
			echo '<span class="idl-tmce-modal__item-title">' . esc_html( $post->post_title ) . '</span>';
			if ( $cat ) {
				echo '<span class="idl-tmce-modal__item-cat">' . esc_html( $cat ) . '</span>';
			}
			echo '</button>';
			echo '</li>';
		}
		echo '</ul>';

		$html = ob_get_clean();
		wp_send_json_success( array( 'html' => $html ) );
	}
}
