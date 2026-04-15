<?php
/**
 * REST API endpoints — Phase 3 (Gutenberg block support).
 *
 * Namespace: i-downloads/v1
 */
defined( 'ABSPATH' ) || exit;

class IDL_Rest_Api {

	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$ns = 'i-downloads/v1';

		// GET /i-downloads/v1/downloads
		register_rest_route(
			$ns,
			'/downloads',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_downloads' ],
				'permission_callback' => [ $this, 'editor_permission' ],
				'args'                => [
					'per_page' => [
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
					'search'   => [
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'category' => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
					'tag'      => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
					'orderby'  => [
						'default'           => 'date',
						'sanitize_callback' => 'sanitize_key',
					],
					'order'    => [
						'default'           => 'DESC',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		// GET /i-downloads/v1/downloads/{id}/files
		register_rest_route(
			$ns,
			'/downloads/(?P<id>\d+)/files',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_download_files' ],
				'permission_callback' => [ $this, 'editor_permission' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// GET /i-downloads/v1/categories
		register_rest_route(
			$ns,
			'/categories',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_categories' ],
				'permission_callback' => [ $this, 'editor_permission' ],
				'args'                => [
					'parent' => [
						'default'           => null,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// GET /i-downloads/v1/stats/overview
		register_rest_route(
			$ns,
			'/stats/overview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_stats_overview' ],
				'permission_callback' => [ $this, 'editor_permission' ],
			]
		);

		// GET /i-downloads/v1/logs
		register_rest_route(
			$ns,
			'/logs',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_logs' ],
				'permission_callback' => [ $this, 'editor_permission' ],
				'args'                => [
					'per_page'    => [
						'default'           => 25,
						'sanitize_callback' => 'absint',
					],
					'page'        => [
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
					'download_id' => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * Require edit_posts capability (Editors, Admins, Contributors with custom cap).
	 */
	public function editor_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	// -------------------------------------------------------------------------
	// Endpoint handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /i-downloads/v1/downloads
	 *
	 * Returns published downloads for the block editor download picker.
	 * Supports: search, category (term_id), tag (term_id), orderby, order.
	 * Default: 20 most recent; search uses WP relevance ordering automatically.
	 */
	public function get_downloads( WP_REST_Request $request ): WP_REST_Response {
		$search   = (string) $request->get_param( 'search' );
		$category = (int) $request->get_param( 'category' );
		$tag      = (int) $request->get_param( 'tag' );

		$allowed_orderby = [ 'date', 'title', 'modified' ];
		$orderby         = (string) $request->get_param( 'orderby' );
		$order           = 'ASC' === strtoupper( (string) $request->get_param( 'order' ) ) ? 'ASC' : 'DESC';

		$args = [
			'post_type'      => 'idl',
			'post_status'    => 'publish',
			'posts_per_page' => min( (int) $request->get_param( 'per_page' ), 100 ),
			'no_found_rows'  => true,
		];

		if ( $search !== '' ) {
			// Let WP sort by relevance when a search term is provided.
			$args['s'] = $search;
		} else {
			$args['orderby'] = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'date';
			$args['order']   = $order;
		}

		if ( $category > 0 ) {
			$args['tax_query'][] = [
				'taxonomy' => 'idl_category',
				'field'    => 'term_id',
				'terms'    => $category,
			];
		}

		if ( $tag > 0 ) {
			$args['tax_query'][] = [
				'taxonomy' => 'idl_tag',
				'field'    => 'term_id',
				'terms'    => $tag,
			];
		}

		$posts = get_posts( $args );

		$data = array_map(
			function ( WP_Post $p ) {
				$cats = wp_get_post_terms( $p->ID, 'idl_category', [ 'fields' => 'names' ] );
				return [
					'id'         => $p->ID,
					'title'      => $p->post_title,
					'date'       => $p->post_date,
					'categories' => is_array( $cats ) ? $cats : [],
				];
			},
			$posts
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /i-downloads/v1/downloads/{id}/files
	 *
	 * Returns all files attached to a download — used by the download-button block.
	 */
	public function get_download_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$download_id = (int) $request->get_param( 'id' );

		$post = get_post( $download_id );
		if ( ! $post || $post->post_type !== 'idl' ) {
			return new WP_Error(
				'idl_not_found',
				__( 'Download not found.', 'i-downloads' ),
				[ 'status' => 404 ]
			);
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$files = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, file_name, file_type, file_size, file_mime, external_url, sort_order
				   FROM {$wpdb->prefix}idl_files
				  WHERE download_id = %d
				  ORDER BY sort_order ASC, id ASC",
				$download_id
			)
		);

		if ( $files === null ) {
			return new WP_Error(
				'idl_db_error',
				__( 'Database error.', 'i-downloads' ),
				[ 'status' => 500 ]
			);
		}

		$data = array_map(
			function ( object $f ): array {
				$label = $f->title ?: $f->file_name ?: $f->external_url ?: sprintf(
				/* translators: %d: file record id */
					__( 'File #%d', 'i-downloads' ),
					(int) $f->id
				);
				return [
					'id'           => (int) $f->id,
					'title'        => $label,
					'file_name'    => $f->file_name,
					'file_type'    => $f->file_type,
					'file_size'    => (int) $f->file_size,
					'file_mime'    => $f->file_mime,
					'external_url' => $f->external_url,
				];
			},
			$files
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /i-downloads/v1/categories
	 *
	 * Returns all idl_category terms, optionally filtered by parent.
	 */
	public function get_categories( WP_REST_Request $request ): WP_REST_Response {
		$args = [
			'taxonomy'   => 'idl_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$parent = $request->get_param( 'parent' );
		if ( $parent !== null ) {
			$args['parent'] = (int) $parent;
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return new WP_REST_Response( [], 200 );
		}

		$data = array_map(
			fn( WP_Term $t ) => [
				'id'     => $t->term_id,
				'name'   => $t->name,
				'slug'   => $t->slug,
				'parent' => $t->parent,
				'count'  => $t->count,
			],
			$terms
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /i-downloads/v1/stats/overview
	 *
	 * Returns aggregate statistics for the dashboard widget.
	 */
	public function get_stats_overview( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$total_downloads = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'idl' AND post_status = 'publish'"
		);

		$total_files = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}idl_files"
		);

		$total_log_entries = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}idl_download_log"
		);

		// Total file size in bytes
		$total_size = (int) $wpdb->get_var(
			"SELECT COALESCE(SUM(file_size),0) FROM {$wpdb->prefix}idl_files"
		);

		// Most downloaded in the last 30 days
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$top_downloads = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.download_id, p.post_title AS title, COUNT(*) AS count
				   FROM {$wpdb->prefix}idl_download_log l
				   JOIN {$wpdb->posts} p ON p.ID = l.download_id
				  WHERE l.downloaded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				  GROUP BY l.download_id, p.post_title
				  ORDER BY count DESC
				  LIMIT 5",
				30
			)
		);

		return new WP_REST_Response(
			[
				'total_downloads'   => $total_downloads,
				'total_files'       => $total_files,
				'total_log_entries' => $total_log_entries,
				'total_size_bytes'  => $total_size,
				'top_downloads_30d' => $top_downloads,
			],
			200
		);
	}

	/**
	 * GET /i-downloads/v1/logs
	 *
	 * Returns paginated download log entries for the Phase 4 log viewer.
	 */
	public function get_logs( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$per_page    = min( (int) $request->get_param( 'per_page' ), 200 );
		$page        = max( 1, (int) $request->get_param( 'page' ) );
		$download_id = (int) $request->get_param( 'download_id' );
		$offset      = ( $page - 1 ) * $per_page;

		// Build WHERE clause (already prepared if present).
		$where = $download_id > 0 ? $wpdb->prepare( 'WHERE l.download_id = %d', $download_id ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is already prepared above; $wpdb->prefix is safe.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.id, l.download_id, p.post_title AS download_title,
				        l.file_id, f.file_name, l.user_id, l.user_ip,
				        l.user_agent, l.downloaded_at
				   FROM {$wpdb->prefix}idl_download_log l
				   LEFT JOIN {$wpdb->posts} p ON p.ID = l.download_id
				   LEFT JOIN {$wpdb->prefix}idl_files f ON f.id = l.file_id
				   $where
				  ORDER BY l.downloaded_at DESC
				  LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}idl_download_log l $where"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$response = new WP_REST_Response( $rows ?? [], 200 );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

		return $response;
	}
}
