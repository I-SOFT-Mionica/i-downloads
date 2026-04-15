<?php
defined( 'ABSPATH' ) || exit;

class IDL_Shortcodes {

	public function register_hooks(): void {
		add_shortcode( 'idl_list', [ $this, 'list_shortcode' ] );
		add_shortcode( 'idl_categories', [ $this, 'categories_shortcode' ] );
		add_shortcode( 'idl_download', [ $this, 'download_shortcode' ] );
		add_shortcode( 'idl_button', [ $this, 'button_shortcode' ] );
		add_shortcode( 'idl_count', [ $this, 'count_shortcode' ] );
		add_shortcode( 'idl_search', [ $this, 'search_shortcode' ] );
		add_shortcode( 'idl_recent', [ $this, 'recent_shortcode' ] );
		add_shortcode( 'idl_popular', [ $this, 'popular_shortcode' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'render_agree_modal' ] );
	}

	// -------------------------------------------------------------------------
	// Asset enqueue
	// -------------------------------------------------------------------------

	public function enqueue_assets(): void {
		wp_enqueue_style(
			'idl-public',
			IDL_PLUGIN_URL . 'public/css/public-style.css',
			[],
			IDL_VERSION
		);
		wp_enqueue_script(
			'idl-public',
			IDL_PLUGIN_URL . 'public/js/public-script.js',
			[],
			IDL_VERSION,
			true
		);
		wp_localize_script(
			'idl-public',
			'IDLPublic',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => [
					'agreeLabel'  => __( 'I have read and agree to the terms', 'i-downloads' ),
					'agreeButton' => __( 'Download', 'i-downloads' ),
					'cancel'      => __( 'Cancel', 'i-downloads' ),
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Agreement modal shell — output once in footer
	// -------------------------------------------------------------------------

	public function render_agree_modal(): void {
		?>
		<div id="idl-agree-overlay" class="idl-modal-overlay" hidden aria-modal="true" role="dialog">
			<div class="idl-modal">
				<h3 id="idl-agree-title" class="idl-modal__title"></h3>
				<div id="idl-agree-body" class="idl-modal__license-text"></div>
				<p>
					<label>
						<input type="checkbox" id="idl-agree-checkbox" />
						<?php esc_html_e( 'I have read and agree to the terms', 'i-downloads' ); ?>
					</label>
				</p>
				<p class="idl-modal__actions">
					<a id="idl-agree-proceed" href="#" class="wp-element-button idl-download-btn" aria-disabled="true">
						<?php esc_html_e( 'Download', 'i-downloads' ); ?>
					</a>
					<button type="button" id="idl-agree-cancel" class="button">
						<?php esc_html_e( 'Cancel', 'i-downloads' ); ?>
					</button>
				</p>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// [idl_list category="" tag="" limit="10" orderby="date" order="DESC" layout="" show_search="0"]
	// -------------------------------------------------------------------------

	public function list_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'category'              => '',
				'include_subcategories' => '1',
				'tag'                   => '',
				'limit'                 => 10,
				'orderby'               => 'date',
				'order'                 => 'DESC',
				'layout'                => '',
				'show_search'           => '0',
			],
			$atts,
			'idl_list'
		);

		$settings = idl_get_settings();
		$layout   = $atts['layout'] ?: $settings['listing_layout'];

		$query_args = [
			'post_type'      => 'idl',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
			'paged'          => max( 1, get_query_var( 'paged' ) ),
		];

		// Category filter
		if ( $atts['category'] ) {
			$query_args['tax_query'][] = [
				'taxonomy'         => 'idl_category',
				'field'            => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
				'terms'            => is_numeric( $atts['category'] ) ? absint( $atts['category'] ) : sanitize_text_field( $atts['category'] ),
				'include_children' => filter_var( $atts['include_subcategories'], FILTER_VALIDATE_BOOLEAN ),
			];
		}

		// Tag filter
		if ( $atts['tag'] ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'idl_tag',
				'field'    => is_numeric( $atts['tag'] ) ? 'term_id' : 'slug',
				'terms'    => is_numeric( $atts['tag'] ) ? absint( $atts['tag'] ) : sanitize_text_field( $atts['tag'] ),
			];
		}

		// Order by download count (stored in post meta)
		if ( 'download_count' === $atts['orderby'] ) {
			$query_args['meta_key'] = '_idl_download_count';
			$query_args['orderby']  = 'meta_value_num';
		}

		$query_args = apply_filters( 'idl_listing_query_args', $query_args, $atts );
		$query      = new WP_Query( $query_args );

		ob_start();

		if ( filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN ) ) {
			echo $this->search_shortcode( [ 'category' => $atts['category'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! $query->have_posts() ) {
			echo '<p class="idl-no-downloads">' . esc_html__( 'No downloads found.', 'i-downloads' ) . '</p>';
		} else {
			$grid_class = 'grid' === $layout ? 'idl-grid idl-grid--cols-3' : 'idl-download-list';
			echo '<div class="idl-list-wrap idl-layout--' . esc_attr( $layout ) . '">';

			if ( 'table' === $layout ) {
				$this->render_table_layout( $query, $settings );
			} else {
				echo '<div class="' . esc_attr( $grid_class ) . '">';
				while ( $query->have_posts() ) {
					$query->the_post();
					$post = get_post();
					$this->render_template( 'download-card', compact( 'post', 'settings' ) );
				}
				echo '</div>';
			}

			wp_reset_postdata();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe HTML.
			echo paginate_links(
				[
					'total'   => $query->max_num_pages,
					'current' => max( 1, get_query_var( 'paged' ) ),
				]
			);

			echo '</div>';
		}

		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// [idl_categories parent="0" columns="3" show_count="1" show_description="1"]
	// -------------------------------------------------------------------------

	public function categories_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'parent'           => 0,
				'columns'          => 3,
				'show_count'       => '1',
				'show_description' => '1',
			],
			$atts,
			'idl_categories'
		);

		$terms = get_terms(
			[
				'taxonomy'   => 'idl_category',
				'parent'     => absint( $atts['parent'] ),
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'meta_key'   => '_idl_cat_sort_order',
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<p class="idl-no-categories">' . esc_html__( 'No categories found.', 'i-downloads' ) . '</p>';
		}

		$columns    = min( 4, max( 1, absint( $atts['columns'] ) ) );
		$show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
		$show_desc  = filter_var( $atts['show_description'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		echo '<div class="idl-category-grid idl-grid idl-grid--cols-' . esc_attr( $columns ) . '">';
		foreach ( $terms as $term ) {
			$this->render_category_card( $term, $show_count, $show_desc );
		}
		echo '</div>';
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// [idl_download id="" show_description="1" show_files="1" style="card"]
	// -------------------------------------------------------------------------

	public function download_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'id'               => 0,
				'show_description' => '1',
				'show_files'       => '1',
				'style'            => 'card', // card | compact | button-only
			],
			$atts,
			'idl_download'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || 'idl' !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$access = new IDL_Access_Control();
		if ( ! $access->can_access_download( $post_id ) && ! is_user_logged_in() ) {
			return ''; // Future: show a teaser with login prompt.
		}

		$settings = idl_get_settings();
		$files    = ( new IDL_File_Manager() )->get_files( $post_id );

		if ( 'button-only' === $atts['style'] ) {
			$first_file = $files[0] ?? null;
			if ( ! $first_file ) {
				return '';
			}
			return $this->render_download_button( $first_file, $post_id );
		}

		ob_start();

		if ( 'compact' === $atts['style'] ) {
			echo '<div class="idl-download-embed idl-download-embed--compact">';
			echo '<strong>' . esc_html( get_the_title( $post_id ) ) . '</strong>';
			if ( ! empty( $files ) ) {
				echo ' <span class="idl-meta">' . esc_html(
					sprintf(
						/* translators: %d: number of files attached to the download */
						_n( '%d file', '%d files', count( $files ), 'i-downloads' ),
						count( $files )
					)
				) . '</span>';
			}
			$first_file = $files[0] ?? null;
			if ( $first_file && $access->can_access_download( $post_id ) ) {
				echo ' ' . $this->render_download_button( $first_file, $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		} else {
			// Full card
			$this->render_template( 'download-card', compact( 'post', 'settings' ) );
		}

		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// [idl_button file_id="" text="Download" class="" style=""]
	// -------------------------------------------------------------------------

	public function button_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'file_id' => 0,
				'text'    => __( 'Download', 'i-downloads' ),
				'class'   => '',
			],
			$atts,
			'idl_button'
		);

		$file_id = absint( $atts['file_id'] );
		if ( ! $file_id ) {
			return '';
		}

		$file = ( new IDL_File_Manager() )->get_file( $file_id );
		if ( ! $file ) {
			return '';
		}

		$access = new IDL_Access_Control();
		if ( ! $access->can_access_download( (int) $file->download_id ) ) {
			if ( ! is_user_logged_in() ) {
				return '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="wp-element-button idl-download-btn">'
					. esc_html__( 'Login to Download', 'i-downloads' ) . '</a>';
			}
			return '';
		}

		return $this->render_download_button( $file, (int) $file->download_id, sanitize_text_field( $atts['text'] ), sanitize_html_class( $atts['class'] ) );
	}

	// -------------------------------------------------------------------------
	// [idl_count id="" file_id="" format="%s downloads"]
	// -------------------------------------------------------------------------

	public function count_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'id'      => 0,   // download post ID
				'file_id' => 0,   // specific file ID
				'format'  => '%s',
			],
			$atts,
			'idl_count'
		);

		$count = 0;

		if ( absint( $atts['file_id'] ) ) {
			$file  = ( new IDL_File_Manager() )->get_file( absint( $atts['file_id'] ) );
			$count = $file ? (int) $file->download_count : 0;
		} elseif ( absint( $atts['id'] ) ) {
			$count = (int) get_post_meta( absint( $atts['id'] ), '_idl_download_count', true );
		}

		return esc_html( sprintf( $atts['format'], number_format_i18n( $count ) ) );
	}

	// -------------------------------------------------------------------------
	// [idl_search category="" placeholder="Search downloads…"]
	// -------------------------------------------------------------------------

	public function search_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'category'    => '',
				'placeholder' => __( 'Search downloads…', 'i-downloads' ),
			],
			$atts,
			'idl_search'
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public search form, no state change.
		$search_term = isset( $_GET['idl_s'] ) ? sanitize_text_field( wp_unslash( $_GET['idl_s'] ) ) : '';

		ob_start();
		?>
		<div class="idl-search-wrap">
			<form class="idl-search-form" method="get" action="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
				<?php if ( $atts['category'] ) : ?>
					<input type="hidden" name="idl_cat" value="<?php echo esc_attr( $atts['category'] ); ?>" />
				<?php endif; ?>
				<label class="screen-reader-text" for="idl-search-input"><?php esc_html_e( 'Search downloads', 'i-downloads' ); ?></label>
				<input
					type="search"
					id="idl-search-input"
					name="idl_s"
					class="idl-search-input"
					value="<?php echo esc_attr( $search_term ); ?>"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				/>
				<button type="submit" class="button idl-search-btn">
					<span class="dashicons dashicons-search"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Search', 'i-downloads' ); ?></span>
				</button>
			</form>

			<?php if ( $search_term ) : ?>
				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public search form, no state change.
				$cat_filter = isset( $_GET['idl_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['idl_cat'] ) ) : $atts['category'];
				$query_args = [
					'post_type'      => 'idl',
					'post_status'    => 'publish',
					'posts_per_page' => (int) idl_get_settings()['items_per_page'],
					's'              => $search_term,
				];
				if ( $cat_filter ) {
					$query_args['tax_query'] = [
						[
							'taxonomy' => 'idl_category',
							'field'    => is_numeric( $cat_filter ) ? 'term_id' : 'slug',
							'terms'    => is_numeric( $cat_filter ) ? absint( $cat_filter ) : $cat_filter,
						],
					];
				}
				$query    = new WP_Query( $query_args );
				$settings = idl_get_settings();
				?>
				<div class="idl-search-results">
					<p class="idl-search-count">
						<?php
						printf(
							/* translators: %1$s: search term, %2$d: result count */
							esc_html( _n( '%2$d result for "%1$s"', '%2$d results for "%1$s"', $query->found_posts, 'i-downloads' ) ),
							esc_html( $search_term ),
							(int) $query->found_posts
						);
						?>
					</p>
					<?php if ( $query->have_posts() ) : ?>
					<div class="idl-grid idl-grid--cols-1">
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							$post = get_post();
							$this->render_template( 'download-card', compact( 'post', 'settings' ) );
						endwhile;
						?>
					</div>
						<?php wp_reset_postdata(); ?>
					<?php else : ?>
					<p><?php esc_html_e( 'No downloads found.', 'i-downloads' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// [idl_recent limit="5" days="30" category=""]
	// -------------------------------------------------------------------------

	public function recent_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'limit'    => 5,
				'days'     => 0,
				'category' => '',
			],
			$atts,
			'idl_recent'
		);

		$query_args = [
			'post_type'      => 'idl',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( absint( $atts['days'] ) ) {
			$query_args['date_query'] = [
				[
					'after'     => absint( $atts['days'] ) . ' days ago',
					'inclusive' => true,
				],
			];
		}

		if ( $atts['category'] ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => 'idl_category',
					'field'    => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
					'terms'    => is_numeric( $atts['category'] ) ? absint( $atts['category'] ) : sanitize_text_field( $atts['category'] ),
				],
			];
		}

		return $this->render_query_as_cards( $query_args );
	}

	// -------------------------------------------------------------------------
	// [idl_popular limit="5" period="all" category=""]
	// -------------------------------------------------------------------------

	public function popular_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'limit'    => 5,
				'period'   => 'all', // all | 30d | 7d
				'category' => '',
			],
			$atts,
			'idl_popular'
		);

		$query_args = [
			'post_type'      => 'idl',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'meta_key'       => '_idl_download_count',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		];

		// For period filtering we'd need to query the log table directly —
		// for now 'all' is supported; 30d/7d falls back to all-time.
		// TODO: period-based ranking via sub-query in Phase 4.

		if ( $atts['category'] ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => 'idl_category',
					'field'    => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
					'terms'    => is_numeric( $atts['category'] ) ? absint( $atts['category'] ) : sanitize_text_field( $atts['category'] ),
				],
			];
		}

		return $this->render_query_as_cards( $query_args );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private function render_query_as_cards( array $query_args ): string {
		$query    = new WP_Query( $query_args );
		$settings = idl_get_settings();

		if ( ! $query->have_posts() ) {
			return '<p class="idl-no-downloads">' . esc_html__( 'No downloads found.', 'i-downloads' ) . '</p>';
		}

		ob_start();
		echo '<div class="idl-grid idl-grid--cols-1">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post = get_post();
			$this->render_template( 'download-card', compact( 'post', 'settings' ) );
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render a plugin template, injecting variables into scope.
	 */
	private function render_template( string $name, array $vars = [] ): void {
		$path = IDL_PLUGIN_DIR . 'public/views/' . $name . '.php';
		if ( ! file_exists( $path ) ) {
			return;
		}
		// Inject variables without extract() — assign each explicitly
		foreach ( $vars as $key => $value ) {
			$$key = $value;
		}
		require $path;
	}

	/**
	 * Render a single download button, with agree-modal support.
	 */
	private function render_download_button( object $file, int $download_id, string $text = '', string $extra_class = '' ): string {
		$default_text  = idl_get_settings()['default_button_text'] ?: __( 'Download', 'i-downloads' );
		$text          = $text ?: $default_text;
		$require_agree = (bool) get_post_meta( $download_id, '_idl_require_agree', true );
		$url           = idl_get_download_url( (int) $file->id );
		$class         = trim( 'wp-element-button idl-download-btn ' . $extra_class );

		if ( 'external' === $file->file_type ) {
			$url = esc_url( $file->external_url );
		}

		if ( $require_agree ) {
			$license_id  = (int) get_post_meta( $download_id, '_idl_license_id', true );
			$license     = $license_id ? ( new IDL_License_Manager() )->get( $license_id ) : null;
			$agree_text  = $license ? wp_kses_post( $license->full_text ) : wp_kses_post( (string) get_post_meta( $download_id, '_idl_agree_text', true ) );
			$agree_title = $license ? esc_html( $license->title ) : esc_html( get_the_title( $download_id ) );

			// Hidden div holds the agreement content for the modal JS to pick up
			$hidden_id = 'idl-agree-content-' . (int) $file->id;

			return '<div class="idl-agree-wrap">'
				. '<div id="' . esc_attr( $hidden_id ) . '" class="idl-agree-content" hidden>'
				. $agree_text
				. '</div>'
				. '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . ' idl-requires-agree"'
				. ' data-agree-content="' . esc_attr( '#' . $hidden_id ) . '"'
				. ' data-agree-title="' . $agree_title . '">'
				. '<span class="dashicons dashicons-download"></span>'
				. esc_html( $text )
				. '</a></div>';
		}

		return '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">'
			. '<span class="dashicons dashicons-download"></span>'
			. esc_html( $text )
			. '</a>';
	}

	private function render_category_card( WP_Term $term, bool $show_count, bool $show_desc ): void {
		$icon = get_term_meta( $term->term_id, '_idl_cat_icon', true );
		echo '<div class="idl-category-card">';

		if ( $icon ) {
			echo '<div class="idl-category-card__icon">';
			if ( filter_var( $icon, FILTER_VALIDATE_URL ) ) {
				echo '<img src="' . esc_url( $icon ) . '" alt="" />';
			} else {
				echo '<span class="dashicons ' . esc_attr( $icon ) . '"></span>';
			}
			echo '</div>';
		}

		echo '<h3 class="idl-category-card__title"><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></h3>';

		if ( $show_desc && $term->description ) {
			echo '<div class="idl-category-card__desc">' . esc_html( wp_trim_words( $term->description, 20 ) ) . '</div>';
		}

		if ( $show_count ) {
			echo '<div class="idl-category-card__meta"><span class="idl-meta">';
			printf(
				/* translators: %d: number of downloads in the category */
				esc_html( _n( '%d download', '%d downloads', $term->count, 'i-downloads' ) ),
				(int) $term->count
			);
			echo '</span></div>';
		}

		echo '</div>';
	}

	private function render_table_layout( WP_Query $query, array $settings ): void {
		echo '<table class="idl-file-list"><thead><tr>';
		echo '<th>' . esc_html__( 'Title', 'i-downloads' ) . '</th>';
		if ( $settings['show_file_size'] ) {
			echo '<th>' . esc_html__( 'Size', 'i-downloads' ) . '</th>';
		}
		if ( $settings['show_download_count'] ) {
			echo '<th>' . esc_html__( 'Downloads', 'i-downloads' ) . '</th>';
		}
		if ( $settings['show_date'] ) {
			echo '<th>' . esc_html__( 'Date', 'i-downloads' ) . '</th>';
		}
		echo '<th></th></tr></thead><tbody>';

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$files   = ( new IDL_File_Manager() )->get_files( $post_id );
			$access  = new IDL_Access_Control();

			echo '<tr>';
			echo '<td><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></td>';

			if ( $settings['show_file_size'] ) {
				$size = array_sum( array_column( (array) $files, 'file_size' ) );
				echo '<td>' . ( $size ? esc_html( size_format( $size ) ) : '—' ) . '</td>';
			}
			if ( $settings['show_download_count'] ) {
				echo '<td>' . esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_idl_download_count', true ) ) ) . '</td>';
			}
			if ( $settings['show_date'] ) {
				echo '<td>' . esc_html( get_the_date( $settings['date_format'] ) ) . '</td>';
			}

			echo '<td>';
			$first = $files[0] ?? null;
			if ( $first && $access->can_access_download( $post_id ) ) {
				echo $this->render_download_button( $first, $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( ! is_user_logged_in() ) {
				echo '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="wp-element-button idl-download-btn">' . esc_html__( 'Login', 'i-downloads' ) . '</a>';
			}
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}
}
