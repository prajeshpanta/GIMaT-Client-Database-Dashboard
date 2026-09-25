<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var string $gimat_page_title
 * @var string $gimat_page_sub
 * @var string $gimat_page_icon dashicon slug, e.g. 'dashicons-chart-bar'
 * @var bool   $gimat_brand_hero    Optional. Dashboard-only larger "GIMaT Nepal" branding.
 * @var string $gimat_brand_eyebrow Optional. Small label shown above the hero title.
 * @var string $gimat_notice_html   Optional. Pre-built compact notice pill HTML
 *             (already escaped by Gimat_Admin::notice_html()), shown inline
 *             next to the page title instead of as a separate large notice.
 */

$gimat_current_user  = wp_get_current_user();
$gimat_display_name  = $gimat_current_user->exists() ? $gimat_current_user->display_name : '';
$gimat_role_label    = Gimat_Admin::current_user_role_label();
$gimat_logout_url    = wp_logout_url( admin_url() );
$gimat_brand_hero    = ! empty( $gimat_brand_hero );
?>
<div class="gimat-brand-header<?php echo $gimat_brand_hero ? ' gimat-brand-header-hero' : ''; ?>">
	<div class="gimat-brand-left">
		<div class="gimat-brand-icon"><span class="dashicons <?php echo esc_attr( $gimat_page_icon ); ?>"></span></div>
		<div>
			<?php if ( $gimat_brand_hero && ! empty( $gimat_brand_eyebrow ) ) : ?>
				<p class="gimat-brand-eyebrow"><?php echo esc_html( $gimat_brand_eyebrow ); ?></p>
			<?php endif; ?>
			<div class="gimat-title-row">
				<h1<?php echo $gimat_brand_hero ? ' class="gimat-brand-hero-title"' : ''; ?>><?php echo esc_html( $gimat_page_title ); ?></h1>
				<?php if ( ! empty( $gimat_notice_html ) ) : ?>
					<?php echo $gimat_notice_html; // phpcs:ignore -- pre-escaped by Gimat_Admin::notice_html() ?>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $gimat_page_sub ) ) : ?>
				<p class="gimat-brand-sub"><?php echo esc_html( $gimat_page_sub ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="gimat-brand-right">
		<?php if ( ! $gimat_brand_hero ) : ?>
			<span class="gimat-brand-tag"><?php esc_html_e( 'GIMaT Nepal', 'gimat-dashboard' ); ?></span>
		<?php endif; ?>

		<button type="button" id="gimat-dark-toggle" class="gimat-dark-toggle" title="<?php esc_attr_e( 'Toggle Dark Mode', 'gimat-dashboard' ); ?>">
			<span class="gimat-dark-icon">🌙</span>
		</button>

		<?php if ( $gimat_current_user->exists() ) : ?>
			<div class="gimat-user-chip">
				<span class="dashicons dashicons-admin-users gimat-user-chip-icon"></span>
				<span class="gimat-user-chip-info">
					<span class="gimat-user-chip-name"><?php echo esc_html( $gimat_display_name ); ?></span>
					<span class="gimat-user-chip-role"><?php echo esc_html( $gimat_role_label ); ?></span>
				</span>
				<a href="<?php echo esc_url( $gimat_logout_url ); ?>" class="gimat-logout-btn" title="<?php esc_attr_e( 'Log out', 'gimat-dashboard' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Log out of the GIMaT Dashboard?', 'gimat-dashboard' ) ); ?>');">
					<span class="dashicons dashicons-exit"></span>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>