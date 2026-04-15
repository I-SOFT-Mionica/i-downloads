<?php
/**
 * Archive template for the 'idl' post type.
 * Theme-overridable: place a copy at {theme}/i-downloads/archive-idl.php
 */
defined( 'ABSPATH' ) || exit;

get_header();

$settings = idl_get_settings();
?>
<div class="idl-archive">
	<?php if ( have_posts() ) : ?>
		<header class="idl-archive__header">
			<h1 class="page-title"><?php esc_html_e( 'Downloads', 'i-downloads' ); ?></h1>
		</header>

		<div class="idl-archive__content idl-grid idl-grid--cols-3">
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
	<?php else : ?>
		<p><?php esc_html_e( 'No downloads available.', 'i-downloads' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
