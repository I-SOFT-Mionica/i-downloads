<?php
/**
 * Tag archive template for 'idl_tag'.
 * Theme-overridable: place a copy at {theme}/i-downloads/taxonomy-idl_tag.php
 */
defined( 'ABSPATH' ) || exit;

get_header();

$settings = idl_get_settings();
$tag      = get_queried_object();
?>
<div class="idl-tag-archive">
	<header class="idl-tag-archive__header">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: tag name */
				esc_html__( 'Downloads tagged: %s', 'i-downloads' ),
				'<span>' . esc_html( single_term_title( '', false ) ) . '</span>'
			);
			?>
		</h1>
		<?php if ( $tag->description ) : ?>
		<div class="taxonomy-description"><?php echo wp_kses_post( term_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
	<div class="idl-tag-archive__downloads idl-grid idl-grid--cols-3">
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
	<p><?php esc_html_e( 'No downloads found with this tag.', 'i-downloads' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
