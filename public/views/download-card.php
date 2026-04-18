<?php
/**
 * Template: Download card — JDownloads-style compact layout.
 *
 * Each file gets its own row: icon · title · HOT badge · date · size · count · button.
 *
 * Expected variables:
 *   $post     WP_Post  The idl post.
 *   $settings array    Plugin settings from idl_get_settings().
 */
defined( 'ABSPATH' ) || exit;

$access        = new IDL_Access_Control();
$can_access    = $access->can_access_download( $post->ID );
$files         = new IDL_File_Manager()->get_files( $post->ID );
$require_agree = (bool) get_post_meta( $post->ID, '_idl_require_agree', true );
$access_role   = get_post_meta( $post->ID, '_idl_access_role', true ) ?: 'public';
// HOT = set by nightly cron at 01:00 (top 10 downloads last 7 days), stored in post meta.
$is_hot     = (bool) get_post_meta( $post->ID, '_idl_is_hot', true );
$license_id = (int) get_post_meta( $post->ID, '_idl_license_id', true );
$license    = ( $require_agree && $license_id ) ? new IDL_License_Manager()->get( $license_id ) : null;
$agree_text = $license ? wp_kses_post( $license->full_text ) : wp_kses_post( (string) get_post_meta( $post->ID, '_idl_agree_text', true ) );
$btn_text   = $settings['default_button_text'] ?: __( 'Download', 'i-downloads' );

?>
<article class="idl-download-card" id="idl-download-<?php echo esc_attr( $post->ID ); ?>">

	<?php if ( count( $files ) > 1 ) : ?>
	<h3 class="idl-download-card__title">
		<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post->ID ) ); ?></a>
		<?php if ( $is_hot ) : ?>
			<span class="idl-badge idl-badge--hot">HOT</span>
		<?php endif; ?>
	</h3>
	<?php endif; ?>

	<?php
	foreach ( $files as $i => $file ) :
		$ext        = strtolower( pathinfo( $file->file_name ?? '', PATHINFO_EXTENSION ) );
		$icon_cls   = idl_mime_icon_class( $ext );
		$title      = $file->title ?: $file->file_name ?: $file->external_url ?: get_the_title( $post->ID );
		$date       = $file->created_at ?? '';
		$hidden_id  = 'idl-agree-content-' . (int) $file->id;
		$is_missing = ! empty( $file->is_missing );
		$item_class = 'idl-file-item' . ( $is_missing ? ' idl-file-item--missing' : '' );
		?>
	<div class="<?php echo esc_attr( $item_class ); ?>">

		<div class="idl-file-item__icon idl-icon--<?php echo esc_attr( $icon_cls ); ?>" aria-hidden="true">
			<?php echo esc_html( strtoupper( $ext ) ?: '?' ); ?>
		</div>

		<div class="idl-file-item__info">
			<div class="idl-file-item__title">
				<?php if ( count( $files ) === 1 ) : ?>
					<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post->ID ) ); ?></a>
					<?php if ( $is_hot ) : ?>
						<span class="idl-badge idl-badge--hot">HOT</span>
					<?php endif; ?>
				<?php else : ?>
					<?php echo esc_html( $title ); ?>
				<?php endif; ?>
			</div>

			<div class="idl-file-item__meta">
				<span class="idl-meta idl-meta--type idl-type--<?php echo esc_attr( $icon_cls ); ?>">
					<?php echo esc_html( strtoupper( $ext ) ?: '?' ); ?>
				</span>

				<?php if ( $settings['show_date'] && $date ) : ?>
				<span class="idl-meta idl-meta--date">
					<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
					<?php echo esc_html( wp_date( $settings['date_format'] ?: get_option( 'date_format' ), strtotime( $date ) ) ); ?>
				</span>
				<?php endif; ?>

				<?php if ( $settings['show_file_size'] && $file->file_size ) : ?>
				<span class="idl-meta idl-meta--size">
					<span class="dashicons dashicons-media-archive" aria-hidden="true"></span>
					<?php echo esc_html( size_format( $file->file_size ) ); ?>
				</span>
				<?php endif; ?>

				<?php if ( $settings['show_download_count'] ) : ?>
				<span class="idl-meta idl-meta--count">
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
					<?php echo esc_html( number_format_i18n( (int) $file->download_count ) ); ?>
				</span>
				<?php endif; ?>

				<?php if ( 'public' !== $access_role ) : ?>
				<span class="idl-meta idl-meta--lock">
					<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				</span>
				<?php endif; ?>
			</div>
		</div>

		<div class="idl-file-item__action">
			<?php if ( $is_missing ) : ?>
				<span class="idl-file-missing-label">
					<?php esc_html_e( 'Temporarily unavailable', 'i-downloads' ); ?>
				</span>
			<?php elseif ( $can_access ) : ?>
				<?php if ( $require_agree ) : ?>
				<div id="<?php echo esc_attr( $hidden_id ); ?>" class="idl-agree-content" hidden>
					<?php echo $agree_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<a href="<?php echo esc_url( idl_get_download_url( (int) $file->id ) ); ?>"
					class="wp-element-button idl-download-btn idl-requires-agree"
					data-agree-content="#<?php echo esc_attr( $hidden_id ); ?>"
					data-agree-title="<?php echo $license ? esc_attr( $license->title ) : esc_attr( get_the_title( $post->ID ) ); ?>">
					<?php echo esc_html( $btn_text ); ?>
				</a>
				<?php else : ?>
				<a href="<?php echo esc_url( idl_get_download_url( (int) $file->id ) ); ?>"
					class="wp-element-button idl-download-btn">
					<?php echo esc_html( $btn_text ); ?>
				</a>
				<?php endif; ?>
			<?php elseif ( ! is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wp_login_url( get_permalink( $post->ID ) ) ); ?>"
					class="wp-element-button idl-download-btn idl-download-btn--login">
					<?php esc_html_e( 'Login', 'i-downloads' ); ?>
				</a>
			<?php else : ?>
				<span class="idl-download-btn idl-download-btn--restricted">
					<?php esc_html_e( 'Restricted', 'i-downloads' ); ?>
				</span>
			<?php endif; ?>
		</div>

	</div>
	<?php endforeach; ?>

	<?php if ( empty( $files ) ) : ?>
	<div class="idl-file-item idl-file-item--empty">
		<em><?php esc_html_e( 'No files available.', 'i-downloads' ); ?></em>
	</div>
	<?php endif; ?>

</article>
