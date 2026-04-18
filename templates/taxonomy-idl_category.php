<?php
/**
 * Taxonomy archive template for 'idl_category'.
 * Theme-overridable: place a copy at {theme}/i-downloads/taxonomy-idl_category.php
 */
defined( 'ABSPATH' ) || exit;

get_header();

$settings    = idl_get_settings();
$queried     = get_queried_object();
$child_terms = get_terms(
	array(
		'taxonomy'   => 'idl_category',
		'parent'     => $queried->term_id,
		'hide_empty' => false,
	)
);
?>
<div class="idl-category-archive">
	<header class="idl-category-archive__header">
		<h1 class="page-title"><?php single_term_title(); ?></h1>
		<?php if ( $queried->description ) : ?>
		<div class="taxonomy-description"><?php echo wp_kses_post( term_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( $child_terms && ! is_wp_error( $child_terms ) ) : ?>
	<div class="idl-category-archive__subcategories">
		<h2><?php esc_html_e( 'Subcategories', 'i-downloads' ); ?></h2>
		<?php
		$terms = $child_terms;
		require IDL_PLUGIN_DIR . 'public/views/category-listing.php';
		?>
	</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
	<div class="idl-category-archive__downloads">
		<?php if ( $child_terms ) : ?>
		<h2><?php esc_html_e( 'Downloads in this Category', 'i-downloads' ); ?></h2>
		<?php endif; ?>

		<div class="idl-grid idl-grid--cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<?php
				$post = get_post();
				require IDL_PLUGIN_DIR . 'public/views/download-card.php';
				?>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
