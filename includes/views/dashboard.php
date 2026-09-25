<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( GIMAT_DASH_CAP ) ) {
	wp_die( esc_html__( 'You do not have permission to view this page.', 'gimat-dashboard' ) );
}

$stats = Gimat_Records::stats();

$gimat_page_icon  = 'dashicons-dashboard';
$gimat_page_sub   = __( "Here's an overview of your training activities.", 'gimat-dashboard' );

// Dashboard-only "hero" branding: puts "GIMaT Nepal" front and center on the
// left, larger and more prominent. Other pages (Events, Records, Add/Edit
// Record) keep the standard header untouched — see partials/brand-header.php.
$gimat_brand_hero    = true;
$gimat_brand_eyebrow = __( 'Welcome To', 'gimat-dashboard' );
$gimat_page_title    = __( 'GIMaT Nepal', 'gimat-dashboard' );
?>
<div class="wrap gimat-wrap">
	<?php require __DIR__ . '/partials/brand-header.php'; ?>

	<div class="gimat-stat-grid">
		<div class="gimat-stat-card">
			<div>
				<p class="gimat-stat-label"><?php esc_html_e( 'Total Events', 'gimat-dashboard' ); ?></p>
				<p class="gimat-stat-value"><?php echo esc_html( $stats['events_count'] ); ?></p>
				<p class="gimat-stat-sub"><?php echo esc_html( $stats['records_count'] ); ?> <?php esc_html_e( 'training sessions', 'gimat-dashboard' ); ?></p>
			</div>
			<div class="gimat-stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
		</div>
		<div class="gimat-stat-card">
			<div>
				<p class="gimat-stat-label"><?php esc_html_e( 'Total Records', 'gimat-dashboard' ); ?></p>
				<p class="gimat-stat-value"><?php echo esc_html( $stats['records_count'] ); ?></p>
				<p class="gimat-stat-sub"><?php
					/* translators: %d: number of events */
					echo esc_html( sprintf( __( 'across %d events', 'gimat-dashboard' ), $stats['events_count'] ) );
				?></p>
			</div>
			<div class="gimat-stat-icon"><span class="dashicons dashicons-welcome-learn-more"></span></div>
		</div>
		<div class="gimat-stat-card">
			<div>
				<p class="gimat-stat-label"><?php esc_html_e( 'Total Participants', 'gimat-dashboard' ); ?></p>
				<p class="gimat-stat-value"><?php echo esc_html( number_format_i18n( $stats['total_participants'] ) ); ?></p>
				<p class="gimat-stat-sub"><?php esc_html_e( 'lifetime total', 'gimat-dashboard' ); ?></p>
			</div>
			<div class="gimat-stat-icon"><span class="dashicons dashicons-groups"></span></div>
		</div>
		<div class="gimat-stat-card">
			<div>
				<p class="gimat-stat-label"><?php esc_html_e( 'Active Instructors', 'gimat-dashboard' ); ?></p>
				<p class="gimat-stat-value"><?php echo esc_html( $stats['facilitators_count'] ); ?></p>
				<p class="gimat-stat-sub"><?php esc_html_e( 'unique facilitators', 'gimat-dashboard' ); ?></p>
			</div>
			<div class="gimat-stat-icon"><span class="dashicons dashicons-businessperson"></span></div>
		</div>
	</div>

	<div class="gimat-charts-section">
		<div class="gimat-charts-heading">
			<h1 style="font-weight: 700;"><?php esc_html_e( 'Dashboard Analytics', 'gimat-dashboard' ); ?></h1>
		</div>
		<div class="gimat-charts-grid">
			<div class="gimat-card gimat-chart-card gimat-chart-wide">
				<div class="gimat-card-header">
					<h2><?php esc_html_e( 'Participants by Month', 'gimat-dashboard' ); ?></h2>
				</div>
				<div class="gimat-chart-canvas-wrap">
					<canvas id="gimat-chart-monthly" aria-label="<?php esc_attr_e( 'Participants by month', 'gimat-dashboard' ); ?>" role="img"></canvas>
				</div>
			</div>

			<div class="gimat-card gimat-chart-card">
				<div class="gimat-card-header">
					<h2><?php esc_html_e( 'Participant Demographics', 'gimat-dashboard' ); ?></h2>
				</div>
				<p class="description gimat-chart-subtitle"><?php esc_html_e( 'Dalit / Janajati / Other', 'gimat-dashboard' ); ?></p>
				<div class="gimat-chart-canvas-wrap">
					<canvas id="gimat-chart-demographics" aria-label="<?php esc_attr_e( 'Participant demographics', 'gimat-dashboard' ); ?>" role="img"></canvas>
				</div>
			</div>

			<div class="gimat-card gimat-chart-card">
				<div class="gimat-card-header">
					<h2><?php esc_html_e( 'Gender Distribution', 'gimat-dashboard' ); ?></h2>
				</div>
				<p class="description gimat-chart-subtitle"><?php esc_html_e( 'Male / Female', 'gimat-dashboard' ); ?></p>
				<div class="gimat-chart-canvas-wrap">
					<canvas id="gimat-chart-gender" aria-label="<?php esc_attr_e( 'Gender distribution', 'gimat-dashboard' ); ?>" role="img"></canvas>
				</div>
			</div>

			<div class="gimat-card gimat-chart-card">
				<div class="gimat-card-header">
					<h2><?php esc_html_e( 'Top Locations', 'gimat-dashboard' ); ?></h2>
				</div>
				<p class="description gimat-chart-subtitle"><?php esc_html_e( 'Ranked by sessions held', 'gimat-dashboard' ); ?></p>
				<div class="gimat-chart-canvas-wrap">
					<canvas id="gimat-chart-locations" aria-label="<?php esc_attr_e( 'Top locations', 'gimat-dashboard' ); ?>" role="img"></canvas>
				</div>
			</div>

			<div class="gimat-card gimat-chart-card">
				<div class="gimat-card-header">
					<h2><?php esc_html_e( 'Top Instructors', 'gimat-dashboard' ); ?></h2>
				</div>
				<p class="description gimat-chart-subtitle"><?php esc_html_e( 'Ranked by sessions delivered', 'gimat-dashboard' ); ?></p>
				<div class="gimat-chart-canvas-wrap">
					<canvas id="gimat-chart-instructors" aria-label="<?php esc_attr_e( 'Top instructors', 'gimat-dashboard' ); ?>" role="img"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="gimat-bottom-grid">
		<div class="gimat-card gimat-recent">
			<div class="gimat-card-header">
				<h2><?php esc_html_e( 'Recent Training Records', 'gimat-dashboard' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-records' ) ); ?>"><?php esc_html_e( 'View all →', 'gimat-dashboard' ); ?></a>
			</div>
			<?php if ( empty( $stats['recent'] ) ) : ?>
				<div class="gimat-empty">
					<span class="dashicons dashicons-clipboard gimat-empty-icon"></span>
					<?php esc_html_e( 'No records yet.', 'gimat-dashboard' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-add-record' ) ); ?>"><?php esc_html_e( 'Add your first record →', 'gimat-dashboard' ); ?></a>
				</div>
			<?php else : ?>
				<ul class="gimat-recent-list">
					<?php foreach ( $stats['recent'] as $r ) :
						$total = Gimat_Records::row_total( $r );
						?>
						<li class="gimat-recent-item">
							<div class="gimat-recent-main">
								<div class="gimat-recent-titles">
									<strong class="gimat-recent-event"><?php echo esc_html( $r->event_name ?: __( 'Unknown Event', 'gimat-dashboard' ) ); ?></strong>
									<?php if ( ! empty( $r->sub_event_name ) ) : ?>
										<span class="gimat-recent-subevent"><?php echo esc_html( $r->sub_event_name ); ?></span>
									<?php endif; ?>
								</div>
								<div class="gimat-recent-meta">
									<?php if ( ! empty( $r->location ) ) : ?>
										<span class="gimat-recent-meta-item"><span class="dashicons dashicons-location"></span><?php echo esc_html( $r->location ); ?></span>
										<span class="gimat-recent-meta-dot">·</span>
									<?php endif; ?>
									<span class="gimat-recent-meta-item"><span class="dashicons dashicons-calendar-alt"></span><?php echo esc_html( $r->start_date ? mysql2date( get_option( 'date_format' ), $r->start_date ) : 'N/A' ); ?></span>
								</div>
							</div>
							<span class="gimat-pill"><?php echo esc_html( $total ); ?> <?php esc_html_e( 'people', 'gimat-dashboard' ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="gimat-card">
			<h2 style="margin-bottom:14px;"><?php esc_html_e( 'Quick Actions', 'gimat-dashboard' ); ?></h2>
			<a class="gimat-action gimat-action-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-add-record' ) ); ?>">
				<span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add New Record', 'gimat-dashboard' ); ?>
			</a>
			<a class="gimat-action" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-events' ) ); ?>">
				<span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e( 'Manage Events', 'gimat-dashboard' ); ?>
			</a>
			<a class="gimat-action" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-records' ) ); ?>">
				<span class="dashicons dashicons-list-view"></span><?php esc_html_e( 'View All Records', 'gimat-dashboard' ); ?>
			</a>
		</div>
	</div>
</div>
