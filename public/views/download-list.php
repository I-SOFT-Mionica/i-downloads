<?php
/**
 * Template: Download list within a category — Phase 2.
 *
 * Expected variables: $query WP_Query, $settings array
 */
defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! $query->have_posts() ) : ?>
	<p class="idl-no-downloads"><?php esc_html_e( 'No downloads found.', 'i-downloads' ); ?></p>
<?php else : ?>
	<div class="idl-download-list idl-grid idl-grid--cols-1">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			?>
			<?php
			$post = get_post();
			require __DIR__ . '/download-card.php';
			?>
		<?php endwhile; ?>
	</div>
	<?php wp_reset_postdata(); ?>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe HTML.
	echo paginate_links(
		[
			'total'   => $query->max_num_pages,
			'current' => max( 1, get_query_var( 'paged' ) ),
		]
	);
	?>
<?php endif; ?>
