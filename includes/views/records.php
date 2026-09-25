<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( GIMAT_DASH_CAP ) ) {
	wp_die( esc_html__( 'You do not have permission to view this page.', 'gimat-dashboard' ) );
}

$events = Gimat_Events::get_all();

$filters = array(
	'event_id'    => isset( $_GET['event_id'] ) ? (int) $_GET['event_id'] : 0,
	'from_date'   => isset( $_GET['from_date'] ) ? sanitize_text_field( $_GET['from_date'] ) : '',
	'to_date'     => isset( $_GET['to_date'] ) ? sanitize_text_field( $_GET['to_date'] ) : '',
	'facilitator' => isset( $_GET['facilitator'] ) ? sanitize_text_field( $_GET['facilitator'] ) : '',
);
$has_filters = array_filter( $filters );

$per_page = 25;
$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;

$total_count = Gimat_Records::count( $filters );
$total_pages = (int) ceil( $total_count / $per_page );
$records     = Gimat_Records::query( array_merge( $filters, array( 'per_page' => $per_page, 'paged' => $paged ) ) );

$base_url   = admin_url( 'admin.php?page=gimat-records' );
	$export_nonce = wp_create_nonce( 'gimat_export_csv' );

$gimat_page_title  = __( 'All Records', 'gimat-dashboard' );
$gimat_page_sub    = __( 'Browse, filter, and manage all training sessions.', 'gimat-dashboard' );
$gimat_page_icon   = 'dashicons-clipboard';
$gimat_notice_html = $this->notice_html();
?>
<div class="wrap gimat-wrap">
	<?php require __DIR__ . '/partials/brand-header.php'; ?>

	<div class="gimat-page-header">
		<div></div>
		<div class="gimat-header-actions">
			<button type="button" class="button" id="gimat-open-import"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Import CSV', 'gimat-dashboard' ); ?></button>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-export-form" style="display:inline;">
				<input type="hidden" name="action" value="gimat_export_csv" />
				<?php wp_nonce_field( 'gimat_export_csv' ); ?>
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $filters['event_id'] ); ?>" />
				<input type="hidden" name="from_date" value="<?php echo esc_attr( $filters['from_date'] ); ?>" />
				<input type="hidden" name="to_date" value="<?php echo esc_attr( $filters['to_date'] ); ?>" />
				<input type="hidden" name="facilitator" value="<?php echo esc_attr( $filters['facilitator'] ); ?>" />
				<button type="submit" class="button"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Export CSV', 'gimat-dashboard' ); ?></button>
			</form>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-add-record' ) ); ?>"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add New Record', 'gimat-dashboard' ); ?></a>
		</div>
	</div>

	<!-- Import panel (toggled by JS) -->
	<div class="gimat-card gimat-import-panel" id="gimat-import-panel" style="display:none;">
		<h2><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Import Training Records from CSV', 'gimat-dashboard' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Extra columns are automatically saved as Custom Fields. If your data is in Excel, save/export it as CSV first.', 'gimat-dashboard' ); ?></p>
		<div class="gimat-format-tags">
			<?php foreach ( array( 'Event Name', 'Sub Event Name', 'Organization', 'Start Date', 'Duration', 'Location', 'Facilitator' ) as $h ) : ?>
				<span class="gimat-tag gimat-tag-required"><?php echo esc_html( $h ); ?> *</span>
			<?php endforeach; ?>
			<?php foreach ( array( 'Male Participants', 'Female Participants', 'Dalit', 'Janajati', 'Other' ) as $h ) : ?>
				<span class="gimat-tag gimat-tag-optional"><?php echo esc_html( $h ); ?></span>
			<?php endforeach; ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin-top:14px;">
			<input type="hidden" name="action" value="gimat_import_csv" />
			<?php wp_nonce_field( 'gimat_import_csv' ); ?>
			<div class="gimat-import-dropzone">
				<input type="file" name="import_file" accept=".csv" required />
				<button type="submit" class="button button-primary"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Upload & Import', 'gimat-dashboard' ); ?></button>
			</div>
		</form>
	</div>

	<!-- Filters -->
	<?php if ( $total_count > 0 || $has_filters ) : ?>
	<div class="gimat-card gimat-filter-card">
		<p class="gimat-filter-title"><span class="dashicons dashicons-filter"></span><?php esc_html_e( 'Filters', 'gimat-dashboard' ); ?></p>
		<form method="get" class="gimat-filter-form">
			<input type="hidden" name="page" value="gimat-records" />
			<div class="gimat-filter-grid">
				<div>
					<label><?php esc_html_e( 'Event', 'gimat-dashboard' ); ?></label>
					<select name="event_id">
						<option value=""><?php esc_html_e( 'All events', 'gimat-dashboard' ); ?></option>
						<?php foreach ( $events as $evt ) : ?>
							<option value="<?php echo esc_attr( $evt->id ); ?>" <?php selected( $filters['event_id'], $evt->id ); ?>><?php echo esc_html( $evt->event_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label><?php esc_html_e( 'From Date', 'gimat-dashboard' ); ?></label>
					<input type="date" name="from_date" value="<?php echo esc_attr( $filters['from_date'] ); ?>" />
				</div>
				<div>
					<label><?php esc_html_e( 'To Date', 'gimat-dashboard' ); ?></label>
					<input type="date" name="to_date" value="<?php echo esc_attr( $filters['to_date'] ); ?>" />
				</div>
				<div>
					<label><?php esc_html_e( 'Facilitator', 'gimat-dashboard' ); ?></label>
					<input type="text" name="facilitator" value="<?php echo esc_attr( $filters['facilitator'] ); ?>" placeholder="<?php esc_attr_e( 'Filter by facilitator…', 'gimat-dashboard' ); ?>" />
				</div>
				<div class="gimat-filter-buttons">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'gimat-dashboard' ); ?></button>
					<?php if ( $has_filters ) : ?>
						<a class="button" href="<?php echo esc_url( $base_url ); ?>"><span class="dashicons dashicons-no-alt"></span><?php esc_html_e( 'Clear', 'gimat-dashboard' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $has_filters ) : ?>
				<p class="gimat-filter-result-count"><strong><?php echo esc_html( $total_count ); ?></strong> <?php echo esc_html( _n( 'result', 'results', $total_count, 'gimat-dashboard' ) ); ?></p>
			<?php endif; ?>
		</form>
	</div>
	<?php endif; ?>

	<div class="gimat-table-card">
		<table class="widefat gimat-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Event', 'gimat-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Organization', 'gimat-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Date & Location', 'gimat-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'gimat-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Facilitator', 'gimat-dashboard' ); ?></th>
					<th class="gimat-center" title="Male / Female"><?php esc_html_e( 'M / F', 'gimat-dashboard' ); ?></th>
					<th class="gimat-center" title="Dalit / Janajati / Other"><?php esc_html_e( 'D / J / O', 'gimat-dashboard' ); ?></th>
					<th class="gimat-center"><?php esc_html_e( 'Total', 'gimat-dashboard' ); ?></th>
					<th class="gimat-col-actions"><?php esc_html_e( 'Actions', 'gimat-dashboard' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $records ) ) : ?>
					<tr>
						<td colspan="9" class="gimat-empty-cell">
							<span class="dashicons dashicons-clipboard gimat-empty-icon"></span><br />
							<?php esc_html_e( 'No records found.', 'gimat-dashboard' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-add-record' ) ); ?>"><?php esc_html_e( 'Add your first record →', 'gimat-dashboard' ); ?></a>
						</td>
					</tr>
				<?php else : foreach ( $records as $r ) :
					$total = Gimat_Records::row_total( $r );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $r->sub_event_name ?: '—' ); ?></strong><br />
							<span class="gimat-muted"><?php echo esc_html( $r->event_name ?: __( 'Unknown Event', 'gimat-dashboard' ) ); ?></span>
						</td>
						<td><?php echo esc_html( $r->organization ); ?></td>
						<td>
							<?php echo esc_html( $r->start_date ? mysql2date( get_option( 'date_format' ), $r->start_date ) : 'N/A' ); ?><br />
							<span class="gimat-muted"><?php echo esc_html( $r->location ); ?></span>
						</td>
						<td><span class="gimat-badge"><?php echo esc_html( $r->duration ); ?></span></td>
						<td><?php echo esc_html( $r->facilitator ); ?></td>
						<td class="gimat-center"><?php echo esc_html( $r->participant_male ); ?> / <?php echo esc_html( $r->participant_female ); ?></td>
						<td class="gimat-center"><?php echo esc_html( $r->demographic_dalit ); ?> / <?php echo esc_html( $r->demographic_janajati ); ?> / <?php echo esc_html( $r->demographic_other ); ?></td>
						<td class="gimat-center"><span class="gimat-total-badge"><span class="dashicons dashicons-groups"></span><?php echo esc_html( $total ); ?></span></td>
						<td class="gimat-col-actions">
							<div class="gimat-row-actions">
								<a class="gimat-icon-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-edit-record&id=' . $r->id ) ); ?>" title="<?php esc_attr_e( 'Edit', 'gimat-dashboard' ); ?>"><span class="dashicons dashicons-edit"></span></a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-delete-form" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this training record? This cannot be undone.', 'gimat-dashboard' ) ); ?>');">
									<input type="hidden" name="action" value="gimat_delete_record" />
									<input type="hidden" name="record_id" value="<?php echo esc_attr( $r->id ); ?>" />
									<?php wp_nonce_field( 'gimat_delete_record' ); ?>
									<button type="submit" class="gimat-icon-btn gimat-icon-btn-danger" title="<?php esc_attr_e( 'Delete', 'gimat-dashboard' ); ?>"><span class="dashicons dashicons-trash"></span></button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $total_pages > 1 ) :
		$page_base = add_query_arg( array_merge( $filters, array( 'paged' => '%#%' ) ), $base_url );
		echo '<div class="gimat-pagination">';
		echo wp_kses_post( paginate_links( array(
			'base'      => $page_base,
			'format'    => '',
			'current'   => $paged,
			'total'     => $total_pages,
			'prev_text' => __( '‹', 'gimat-dashboard' ),
			'next_text' => __( '›', 'gimat-dashboard' ),
		) ) );
		echo '</div>';
	endif; ?>
</div>
