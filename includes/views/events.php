<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( GIMAT_DASH_CAP ) ) {
	wp_die( esc_html__( 'You do not have permission to view this page.', 'gimat-dashboard' ) );
}

$events = Gimat_Events::get_all();

$gimat_page_title  = __( 'Events', 'gimat-dashboard' );
$gimat_page_sub    = __( 'Events group related training records together (e.g. "Digital Literacy Program").', 'gimat-dashboard' );
$gimat_page_icon   = 'dashicons-calendar-alt';
$gimat_notice_html = $this->notice_html();
?>
<div class="wrap gimat-wrap">
	<?php require __DIR__ . '/partials/brand-header.php'; ?>

	<div class="gimat-card gimat-add-event-card">
		<h2><span class="dashicons dashicons-plus-alt2" style="color:var(--gimat-primary);vertical-align:middle;margin-right:4px;"></span><?php esc_html_e( 'Add New Event', 'gimat-dashboard' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Give it a short, recognizable name — you\'ll pick it from a dropdown when adding records.', 'gimat-dashboard' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-inline-form">
			<input type="hidden" name="action" value="gimat_add_event" />
			<?php wp_nonce_field( 'gimat_add_event' ); ?>
			<input type="text" name="event_name" maxlength="200" required placeholder="<?php esc_attr_e( 'e.g. Digital Literacy Program 2026', 'gimat-dashboard' ); ?>" />
			<button type="submit" class="button button-primary"><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Add Event', 'gimat-dashboard' ); ?></button>
		</form>
	</div>

	<div class="gimat-table-card">
		<table class="widefat gimat-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Event Name', 'gimat-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Created', 'gimat-dashboard' ); ?></th>
					<th class="gimat-col-actions"><?php esc_html_e( 'Actions', 'gimat-dashboard' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr><td colspan="3" class="gimat-empty-cell">
						<span class="dashicons dashicons-calendar-alt gimat-empty-icon"></span><br />
						<?php esc_html_e( 'No events yet. Add your first one above.', 'gimat-dashboard' ); ?>
					</td></tr>
				<?php else : foreach ( $events as $event ) : ?>
					<tr class="gimat-event-row" data-event-row="<?php echo esc_attr( $event->id ); ?>">
						<td class="gimat-event-name-cell">
							<strong class="gimat-event-name-display"><?php echo esc_html( $event->event_name ); ?></strong>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-inline-form gimat-event-edit-form" style="display:none;margin-top:0;">
								<input type="hidden" name="action" value="gimat_update_event" />
								<input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
								<?php wp_nonce_field( 'gimat_update_event' ); ?>
								<input type="text" name="event_name" maxlength="200" required value="<?php echo esc_attr( $event->event_name ); ?>" />
								<button type="submit" class="button button-primary button-small"><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Save', 'gimat-dashboard' ); ?></button>
								<button type="button" class="button button-small gimat-cancel-edit-event"><?php esc_html_e( 'Cancel', 'gimat-dashboard' ); ?></button>
							</form>
						</td>
						<td class="gimat-muted"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $event->created_at ) ); ?></td>
						<td class="gimat-col-actions">
							<div class="gimat-row-actions">
								<button type="button" class="gimat-icon-btn gimat-edit-event-btn" title="<?php esc_attr_e( 'Edit event', 'gimat-dashboard' ); ?>"><span class="dashicons dashicons-edit"></span></button>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-delete-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this event and ALL its training records? This cannot be undone.', 'gimat-dashboard' ) ); ?>');">
									<input type="hidden" name="action" value="gimat_delete_event" />
									<input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
									<?php wp_nonce_field( 'gimat_delete_event' ); ?>
									<button type="submit" class="gimat-icon-btn gimat-icon-btn-danger" title="<?php esc_attr_e( 'Delete event', 'gimat-dashboard' ); ?>"><span class="dashicons dashicons-trash"></span></button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
