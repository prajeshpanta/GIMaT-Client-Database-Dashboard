<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gimat_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_menu', array( $this, 'restrict_client_role_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'restrict_client_role_access' ) );

		// Form handlers (admin-post.php)
		add_action( 'admin_post_gimat_add_event', array( $this, 'handle_add_event' ) );
		add_action( 'admin_post_gimat_update_event', array( $this, 'handle_update_event' ) );
		add_action( 'admin_post_gimat_delete_event', array( $this, 'handle_delete_event' ) );
		add_action( 'admin_post_gimat_add_record', array( $this, 'handle_add_record' ) );
		add_action( 'admin_post_gimat_update_record', array( $this, 'handle_update_record' ) );
		add_action( 'admin_post_gimat_delete_record', array( $this, 'handle_delete_record' ) );
		add_action( 'admin_post_gimat_export_csv', array( $this, 'handle_export_csv' ) );
		add_action( 'admin_post_gimat_import_csv', array( $this, 'handle_import_csv' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Client Database Dashboard', 'gimat-dashboard' ),
			__( 'Client Database Dashboard', 'gimat-dashboard' ),
			GIMAT_DASH_CAP,
			'gimat-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-clipboard',
			26
		);
		add_submenu_page( 'gimat-dashboard', __( 'Dashboard', 'gimat-dashboard' ), __( 'Dashboard', 'gimat-dashboard' ), GIMAT_DASH_CAP, 'gimat-dashboard', array( $this, 'render_dashboard_page' ) );
		add_submenu_page( 'gimat-dashboard', __( 'Events', 'gimat-dashboard' ), __( 'Events', 'gimat-dashboard' ), GIMAT_DASH_CAP, 'gimat-events', array( $this, 'render_events_page' ) );
		add_submenu_page( 'gimat-dashboard', __( 'Add Record', 'gimat-dashboard' ), __( 'Add Record', 'gimat-dashboard' ), GIMAT_DASH_CAP, 'gimat-add-record', array( $this, 'render_add_record_page' ) );
		add_submenu_page( 'gimat-dashboard', __( 'All Records', 'gimat-dashboard' ), __( 'All Records', 'gimat-dashboard' ), GIMAT_DASH_CAP, 'gimat-records', array( $this, 'render_records_page' ) );
		// Hidden edit page (not added to menu, linked to directly)
		add_submenu_page( null, __( 'Edit Record', 'gimat-dashboard' ), '', GIMAT_DASH_CAP, 'gimat-edit-record', array( $this, 'render_edit_record_page' ) );
	}

	/**
	 * Human-friendly role label shown next to the user's name in the header
	 * (e.g. "Admin" for administrators, "Client" for the restricted role).
	 */
	public static function current_user_role_label() {
		$user = wp_get_current_user();
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return __( 'Admin', 'gimat-dashboard' );
		}
		if ( in_array( GIMAT_DASH_CLIENT_ROLE, (array) $user->roles, true ) ) {
			return __( 'Client', 'gimat-dashboard' );
		}
		if ( in_array( 'editor', (array) $user->roles, true ) ) {
			return __( 'Editor', 'gimat-dashboard' );
		}
		$roles = (array) $user->roles;
		return $roles ? ucfirst( $roles[0] ) : __( 'User', 'gimat-dashboard' );
	}

	/**
	 * Keeps the restricted "Client" role scoped to only this dashboard:
	 * hides every other wp-admin menu item so a Client account can't wander
	 * into Posts, Plugins, Users, Settings, etc. Administrators and Editors
	 * are untouched.
	 */
	public function restrict_client_role_menu() {
		$user = wp_get_current_user();
		if ( ! in_array( GIMAT_DASH_CLIENT_ROLE, (array) $user->roles, true ) ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return; // never restrict a user who is also an administrator.
		}

		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( '' === $slug || 'gimat-dashboard' === $slug || 0 === strpos( $slug, 'gimat-' ) ) {
				continue;
			}
			remove_menu_page( $slug );
		}
	}

	/**
	 * Redirect Client role users away from non-GIMaT admin pages.
	 * This prevents direct URL access to other wp-admin screens.
	 */
	public function restrict_client_role_access() {
		$user = wp_get_current_user();
		if ( ! in_array( GIMAT_DASH_CLIENT_ROLE, (array) $user->roles, true ) ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return; // never restrict a user who is also an administrator.
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Allow access to GIMaT Dashboard pages, profile page, and admin-post.php (for form submissions)
		$allowed_screens = array(
			'toplevel_page_gimat-dashboard',
			'gimat-dashboard_page_gimat-events',
			'gimat-dashboard_page_gimat-add-record',
			'gimat-dashboard_page_gimat-records',
			'gimat-dashboard_page_gimat-edit-record',
			'profile',
		);

		$screen_id = $screen->id;
		if ( in_array( $screen_id, $allowed_screens, true ) ) {
			return;
		}

		// Redirect to the GIMaT Dashboard
		wp_safe_redirect( admin_url( 'admin.php?page=gimat-dashboard' ) );
		exit;
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'gimat-' ) === false && strpos( $hook, 'page_gimat' ) === false ) {
			return;
		}
		wp_enqueue_style( 'gimat-dash-admin', GIMAT_DASH_URL . 'assets/css/admin.css', array(), GIMAT_DASH_VERSION );
		wp_enqueue_script( 'gimat-dash-admin', GIMAT_DASH_URL . 'assets/js/admin.js', array(), GIMAT_DASH_VERSION, true );

		// Dashboard Analytics charts: only needed on the main Dashboard page
		// (top-level "gimat-dashboard" screen), not Events/Records/etc.
		if ( 'toplevel_page_gimat-dashboard' === $hook && current_user_can( GIMAT_DASH_CAP ) ) {
			wp_enqueue_script(
				'gimat-chartjs',
				GIMAT_DASH_URL . 'assets/js/chart.umd.min.js',
				array(),
				'4.4.4',
				true
			);
			wp_enqueue_script(
				'gimat-dash-charts',
				GIMAT_DASH_URL . 'assets/js/dashboard-charts.js',
				array( 'gimat-chartjs' ),
				GIMAT_DASH_VERSION,
				true
			);
			// Fresh from the DB on every page load — never hardcoded.
			wp_localize_script( 'gimat-dash-charts', 'gimatChartData', Gimat_Records::chart_data() );
		}
	}

	/* ---------------------------------------------------------------- */
	/* Form handlers                                                    */
	/* ---------------------------------------------------------------- */

	private function guard( $nonce_action, $nonce_name = '_wpnonce' ) {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Invalid request method.', 'gimat-dashboard' ), '', array( 'response' => 405 ) );
		}
		if ( ! current_user_can( GIMAT_DASH_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'gimat-dashboard' ) );
		}
		check_admin_referer( $nonce_action, $nonce_name );
	}

	private function redirect_with_notice( $url, $type, $message ) {
		$url = add_query_arg( array(
			'gimat_notice'      => $type,
			'gimat_notice_text' => rawurlencode( $message ),
		), $url );
		wp_safe_redirect( $url );
		exit;
	}

	public function handle_add_event() {
		$this->guard( 'gimat_add_event' );
		$result = Gimat_Events::insert( isset( $_POST['event_name'] ) ? wp_unslash( $_POST['event_name'] ) : '' );
		$back   = admin_url( 'admin.php?page=gimat-events' );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', $result->get_error_message() );
		}
		$this->redirect_with_notice( $back, 'success', __( 'Event added.', 'gimat-dashboard' ) );
	}

	public function handle_update_event() {
		$this->guard( 'gimat_update_event' );
		$id     = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
		$name   = isset( $_POST['event_name'] ) ? wp_unslash( $_POST['event_name'] ) : '';
		$result = Gimat_Events::update( $id, $name );
		$back   = admin_url( 'admin.php?page=gimat-events' );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', $result->get_error_message() );
		}
		$this->redirect_with_notice( $back, 'success', __( 'Event updated.', 'gimat-dashboard' ) );
	}

	public function handle_delete_event() {
		$this->guard( 'gimat_delete_event' );
		$id     = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
		$result = Gimat_Events::delete( $id );
		$back   = admin_url( 'admin.php?page=gimat-events' );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', $result->get_error_message() );
		}
		$this->redirect_with_notice( $back, 'success', __( 'Event and its records were deleted.', 'gimat-dashboard' ) );
	}

	public function handle_add_record() {
		$this->guard( 'gimat_add_record' );
		$raw    = wp_unslash( $_POST );
		$result = Gimat_Records::insert( $raw );
		$back   = admin_url( 'admin.php?page=gimat-add-record' );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', implode( ' ', $result->get_error_messages() ) );
		}
		$this->redirect_with_notice( admin_url( 'admin.php?page=gimat-records' ), 'success', __( 'Record added.', 'gimat-dashboard' ) );
	}

	public function handle_update_record() {
		$this->guard( 'gimat_update_record' );
		$id     = isset( $_POST['record_id'] ) ? (int) $_POST['record_id'] : 0;
		$raw    = wp_unslash( $_POST );
		$result = Gimat_Records::update( $id, $raw );
		$back   = admin_url( 'admin.php?page=gimat-edit-record&id=' . $id );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', implode( ' ', $result->get_error_messages() ) );
		}
		$this->redirect_with_notice( admin_url( 'admin.php?page=gimat-records' ), 'success', __( 'Record updated.', 'gimat-dashboard' ) );
	}

	public function handle_delete_record() {
		$this->guard( 'gimat_delete_record' );
		$id     = isset( $_POST['record_id'] ) ? (int) $_POST['record_id'] : 0;
		$result = Gimat_Records::delete( $id );
		$back   = admin_url( 'admin.php?page=gimat-records' );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', $result->get_error_message() );
		}
		$this->redirect_with_notice( $back, 'success', __( 'Record deleted.', 'gimat-dashboard' ) );
	}

	public function handle_export_csv() {
		$this->guard( 'gimat_export_csv' );
		$args = array(
			'event_id'    => isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0,
			'from_date'   => isset( $_POST['from_date'] ) ? sanitize_text_field( $_POST['from_date'] ) : '',
			'to_date'     => isset( $_POST['to_date'] ) ? sanitize_text_field( $_POST['to_date'] ) : '',
			'facilitator' => isset( $_POST['facilitator'] ) ? sanitize_text_field( $_POST['facilitator'] ) : '',
		);
		$records = Gimat_Records::query_all( $args );
		Gimat_Import_Export::export_csv( $records ); // exits
	}

	public function handle_import_csv() {
		$this->guard( 'gimat_import_csv' );
		$back = admin_url( 'admin.php?page=gimat-records' );

		if ( empty( $_FILES['import_file'] ) || UPLOAD_ERR_NO_FILE === $_FILES['import_file']['error'] ) {
			$this->redirect_with_notice( $back, 'error', __( 'Please choose a CSV file to import.', 'gimat-dashboard' ) );
		}
		if ( UPLOAD_ERR_OK !== $_FILES['import_file']['error'] ) {
			$this->redirect_with_notice( $back, 'error', __( 'The file failed to upload. Please try again.', 'gimat-dashboard' ) );
		}

		// Rate limiting: max 5 imports per hour per user
		$rate_limit_key = 'gimat_import_rate_limit_' . get_current_user_id();
		$import_count   = get_transient( $rate_limit_key );
		if ( false !== $import_count && $import_count >= 5 ) {
			$this->redirect_with_notice( $back, 'error', __( 'Too many import attempts. Please wait before trying again.', 'gimat-dashboard' ) );
		}

		// Cap size (5 MB) and require a .csv extension / plausible mime type
		// before we ever touch the file contents.
		$max_bytes = 5 * MB_IN_BYTES;
		if ( isset( $_FILES['import_file']['size'] ) && $_FILES['import_file']['size'] > $max_bytes ) {
			$this->redirect_with_notice( $back, 'error', __( 'That file is too large (5 MB max).', 'gimat-dashboard' ) );
		}

		$original_name = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
		$filetype      = wp_check_filetype( $original_name, array( 'csv' => 'text/csv' ) );
		if ( empty( $filetype['ext'] ) || 'csv' !== $filetype['ext'] ) {
			$this->redirect_with_notice( $back, 'error', __( 'Please upload a .csv file.', 'gimat-dashboard' ) );
		}

		// Validate MIME type using finfo (more reliable than extension)
		$tmp_name = $_FILES['import_file']['tmp_name'];
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = finfo_file( $finfo, $tmp_name );
			finfo_close( $finfo );
			$allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				$this->redirect_with_notice( $back, 'error', __( 'Invalid file type. Please upload a valid CSV file.', 'gimat-dashboard' ) );
			}
		}

		// Increment rate limit counter
		if ( false === $import_count ) {
			set_transient( $rate_limit_key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $rate_limit_key, $import_count + 1, HOUR_IN_SECONDS );
		}

		$result = Gimat_Import_Export::import_csv( $_FILES['import_file'] );
		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $back, 'error', $result->get_error_message() );
		}

		$duplicates = isset( $result['duplicates'] ) ? (int) $result['duplicates'] : 0;

		$message_parts   = array();
		$message_parts[] = sprintf(
			/* translators: %d: number of records imported */
			_n( 'Imported %d record.', 'Imported %d records.', $result['imported'], 'gimat-dashboard' ),
			$result['imported']
		);
		if ( $duplicates > 0 ) {
			$message_parts[] = sprintf(
				/* translators: %d: number of duplicate rows skipped */
				_n( '%d duplicate skipped.', '%d duplicates skipped.', $duplicates, 'gimat-dashboard' ),
				$duplicates
			);
		}
		if ( $result['skipped'] > 0 ) {
			$message_parts[] = sprintf(
				/* translators: %d: number of invalid rows skipped */
				_n( '%d row skipped (invalid data).', '%d rows skipped (invalid data).', $result['skipped'], 'gimat-dashboard' ),
				$result['skipped']
			);
		}
		$message = implode( ' ', $message_parts );

		if ( ! empty( $result['errors'] ) ) {
			set_transient( 'gimat_import_errors_' . get_current_user_id(), $result['errors'], MINUTE_IN_SECONDS * 5 );
		}
		$has_skips = ( $duplicates > 0 || $result['skipped'] > 0 );
		$this->redirect_with_notice( $back, $has_skips ? 'warning' : 'success', $message );
	}

	/* ---------------------------------------------------------------- */
	/* Page renders                                                     */
	/* ---------------------------------------------------------------- */

	/**
	 * Builds a compact "pill" notice (small icon + short text) meant to sit
	 * inline next to the page title, instead of a large full-width WP-style
	 * notice block. Has no close button — it auto-dismisses via JS (see
	 * admin.js), except when the person has expanded its details, in which
	 * case the auto-dismiss timer waits until they close it again.
	 *
	 * For CSV import warnings, the (potentially long) list of skipped rows
	 * is kept out of the page by default and tucked into a native
	 * <details>/<summary> toggle inside the pill, so the notice itself stays
	 * small while the information is still one click away.
	 *
	 * @return string Empty string if there's nothing to show.
	 */
	private function notice_html() {
		if ( empty( $_GET['gimat_notice'] ) ) {
			return '';
		}
		$type = sanitize_text_field( $_GET['gimat_notice'] );
		$text = isset( $_GET['gimat_notice_text'] ) ? sanitize_text_field( rawurldecode( $_GET['gimat_notice_text'] ) ) : '';

		$styles = array(
			'success' => array( 'class' => 'gimat-notice-pill-success', 'icon' => 'dashicons-yes-alt', 'ms' => 2500 ),
			'warning' => array( 'class' => 'gimat-notice-pill-warning', 'icon' => 'dashicons-warning', 'ms' => 6000 ),
			'error'   => array( 'class' => 'gimat-notice-pill-error', 'icon' => 'dashicons-dismiss', 'ms' => 8000 ),
		);
		$type  = isset( $styles[ $type ] ) ? $type : 'error';
		$style = $styles[ $type ];

		$details_html = '';
		if ( 'warning' === $type ) {
			$errors = get_transient( 'gimat_import_errors_' . get_current_user_id() );
			if ( $errors ) {
				$count = count( $errors );
				$details_html .= '<details class="gimat-notice-details">';
				$details_html .= '<summary>' . esc_html( sprintf(
					/* translators: %d: number of skipped rows */
					_n( 'View %d skipped row', 'View %d skipped rows', $count, 'gimat-dashboard' ),
					$count
				) ) . '</summary>';
				$details_html .= '<ul>';
				foreach ( $errors as $e ) {
					$details_html .= '<li>' . esc_html( $e ) . '</li>';
				}
				$details_html .= '</ul></details>';
				delete_transient( 'gimat_import_errors_' . get_current_user_id() );
			}
		}

		$html  = '<span class="gimat-notice-pill ' . esc_attr( $style['class'] ) . '" data-gimat-autodismiss="' . esc_attr( $style['ms'] ) . '">';
		$html .= '<span class="dashicons ' . esc_attr( $style['icon'] ) . '" aria-hidden="true"></span>';
		$html .= '<span class="gimat-notice-pill-text">' . esc_html( $text ) . '</span>';
		$html .= $details_html;
		$html .= '</span>';

		return $html;
	}

	public function render_dashboard_page() {
		require GIMAT_DASH_DIR . 'includes/views/dashboard.php';
	}

	public function render_events_page() {
		require GIMAT_DASH_DIR . 'includes/views/events.php';
	}

	public function render_records_page() {
		require GIMAT_DASH_DIR . 'includes/views/records.php';
	}

	public function render_add_record_page() {
		require GIMAT_DASH_DIR . 'includes/views/record-form.php';
	}

	public function render_edit_record_page() {
		require GIMAT_DASH_DIR . 'includes/views/record-form.php';
	}
}
