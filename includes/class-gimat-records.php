<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gimat_Records {

	/**
	 * Shared WHERE-clause builder for filters used by both list + export + count.
	 *
	 * @param array $args {
	 *   @type int    $event_id
	 *   @type string $from_date  Y-m-d
	 *   @type string $to_date    Y-m-d
	 *   @type string $facilitator
	 * }
	 */
	private static function build_where( $args ) {
		global $wpdb;
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['event_id'] ) ) {
			$where[]  = 'r.event_id = %d';
			$params[] = (int) $args['event_id'];
		}
		if ( ! empty( $args['from_date'] ) ) {
			$where[]  = 'r.start_date >= %s';
			$params[] = $args['from_date'];
		}
		if ( ! empty( $args['to_date'] ) ) {
			$where[]  = 'r.start_date <= %s';
			$params[] = $args['to_date'];
		}
		if ( ! empty( $args['facilitator'] ) ) {
			$where[]  = 'r.facilitator LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['facilitator'] ) . '%';
		}

		return array( implode( ' AND ', $where ), $params );
	}

	public static function query( $args = array() ) {
		global $wpdb;
		$records_table = Gimat_DB::records_table();
		$events_table  = Gimat_DB::events_table();

		list( $where_sql, $params ) = self::build_where( $args );

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 25;
		$paged    = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$sql = "SELECT r.*, e.event_name
				FROM {$records_table} r
				LEFT JOIN {$events_table} e ON e.id = r.event_id
				WHERE {$where_sql}
				ORDER BY r.created_at DESC
				LIMIT %d OFFSET %d";

		$params[] = $per_page;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/** Same filters, no pagination — used for CSV export. */
	public static function query_all( $args = array() ) {
		global $wpdb;
		$records_table = Gimat_DB::records_table();
		$events_table  = Gimat_DB::events_table();

		list( $where_sql, $params ) = self::build_where( $args );

		$sql = "SELECT r.*, e.event_name
				FROM {$records_table} r
				LEFT JOIN {$events_table} e ON e.id = r.event_id
				WHERE {$where_sql}
				ORDER BY r.created_at DESC";

		if ( $params ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}
		return $wpdb->get_results( $sql );
	}

	public static function count( $args = array() ) {
		global $wpdb;
		$records_table = Gimat_DB::records_table();
		list( $where_sql, $params ) = self::build_where( $args );

		$sql = "SELECT COUNT(*) FROM {$records_table} r WHERE {$where_sql}";
		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}
		return (int) $wpdb->get_var( $sql );
	}

	public static function get( $id ) {
		global $wpdb;
		$table = Gimat_DB::records_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	public static function distinct_values( $column ) {
		global $wpdb;
		$allowed = array( 'sub_event_name', 'organization' );
		if ( ! in_array( $column, $allowed, true ) ) {
			return array();
		}
		$table = Gimat_DB::records_table();
		if ( 'sub_event_name' === $column ) {
			$rows = $wpdb->get_col( "SELECT DISTINCT sub_event_name FROM {$table} WHERE sub_event_name != '' ORDER BY sub_event_name ASC" );
		} else {
			$rows = $wpdb->get_col( "SELECT DISTINCT organization FROM {$table} WHERE organization != '' ORDER BY organization ASC" );
		}
		return $rows ?: array();
	}

	/**
	 * Validates + sanitizes raw POST-style input into a DB-ready row.
	 * Returns WP_Error with all validation failures.
	 */
	public static function sanitize_input( $raw, $require_all = true ) {
		$errors = array();

		$event_id = isset( $raw['event_id'] ) ? (int) $raw['event_id'] : 0;
		if ( ! $event_id || ! Gimat_Events::get( $event_id ) ) {
			$errors[] = __( 'Please select a valid event.', 'gimat-dashboard' );
		}

		$start_date = isset( $raw['start_date'] ) ? trim( $raw['start_date'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			$errors[] = __( 'Please enter a valid start date.', 'gimat-dashboard' );
		}

		$text_fields = array(
			'sub_event_name' => 200,
			'organization'   => 200,
			'duration'       => 100,
			'location'       => 200,
			'facilitator'    => 300,
		);
		$clean = array();
		foreach ( $text_fields as $field => $maxlen ) {
			$val = isset( $raw[ $field ] ) ? trim( sanitize_text_field( $raw[ $field ] ) ) : '';
			if ( $require_all && '' === $val ) {
				/* translators: %s: field label */
				$errors[] = sprintf( __( '%s is required.', 'gimat-dashboard' ), ucwords( str_replace( '_', ' ', $field ) ) );
			}
			if ( strlen( $val ) > $maxlen ) {
				$val = substr( $val, 0, $maxlen );
			}
			$clean[ $field ] = $val;
		}

		$number_fields = array( 'participant_male', 'participant_female', 'demographic_dalit', 'demographic_janajati', 'demographic_other' );
		foreach ( $number_fields as $field ) {
			$val = isset( $raw[ $field ] ) ? (int) $raw[ $field ] : 0;
			if ( $val < 0 ) {
				$errors[] = __( 'Numeric fields cannot be negative.', 'gimat-dashboard' );
				$val = 0;
			}
			$clean[ $field ] = $val;
		}

		// custom_fields: expects assoc array [label => value]
		$custom_fields = array();
		if ( ! empty( $raw['custom_field_label'] ) && is_array( $raw['custom_field_label'] ) ) {
			foreach ( $raw['custom_field_label'] as $i => $label ) {
				$label = trim( sanitize_text_field( $label ) );
				$value = isset( $raw['custom_field_value'][ $i ] ) ? sanitize_text_field( $raw['custom_field_value'][ $i ] ) : '';
				if ( '' !== $label ) {
					$custom_fields[ $label ] = $value;
				}
			}
		} elseif ( ! empty( $raw['custom_fields'] ) && is_array( $raw['custom_fields'] ) ) {
			foreach ( $raw['custom_fields'] as $label => $value ) {
				$label = trim( sanitize_text_field( $label ) );
				if ( '' !== $label ) {
					$custom_fields[ $label ] = sanitize_text_field( $value );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$error = new WP_Error( 'gimat_invalid' );
			foreach ( $errors as $err ) {
				$error->add( 'gimat_invalid', $err );
			}
			return $error;
		}

		$clean['event_id']      = $event_id;
		$clean['start_date']    = $start_date;
		$clean['custom_fields'] = ! empty( $custom_fields ) ? wp_json_encode( $custom_fields ) : null;

		return $clean;
	}

	public static function insert( $raw ) {
		global $wpdb;
		$data = self::sanitize_input( $raw, true );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$signature = self::duplicate_signature( $data );
		$lock      = self::acquire_dup_lock( $signature );
		if ( ! $lock ) {
			// Another request for this exact same record is being saved
			// right now (e.g. a fast double-click on "Add Record").
			return new WP_Error(
				'gimat_duplicate',
				__( 'This record is already being saved. Please check All Records before submitting again.', 'gimat-dashboard' )
			);
		}

		if ( self::find_duplicate( $data ) ) {
			self::release_dup_lock( $lock );
			return new WP_Error(
				'gimat_duplicate',
				__( 'This data is already begin recorded.', 'gimat-dashboard' )
			);
		}

		$now              = current_time( 'mysql' );
		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		$table  = Gimat_DB::records_table();
		$result = $wpdb->insert( $table, $data );

		self::release_dup_lock( $lock );

		if ( false === $result ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not save record.', 'gimat-dashboard' ) );
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * Acquires a short-lived, atomic lock for a given duplicate signature so
	 * two nearly-simultaneous requests for the exact same record (e.g. a
	 * fast double-click on "Add Record", or a resubmitted form) can't both
	 * pass the find_duplicate() check before either one has actually saved.
	 *
	 * Uses add_option(), which is atomic at the database level (wp_options
	 * has a UNIQUE index on option_name) — so this is safe even under real
	 * concurrent requests, unlike a plain get/set pair.
	 *
	 * @return string|false The lock name to pass to release_dup_lock(), or
	 *                       false if another request currently holds it.
	 */
	private static function acquire_dup_lock( $signature ) {
		$lock_name = 'gimat_dup_lock_' . md5( $signature );
		$now       = time();

		if ( add_option( $lock_name, $now, '', 'no' ) ) {
			return $lock_name;
		}

		// A lock already exists. If it's older than 15 seconds, the request
		// that created it must have died before releasing it (e.g. a PHP
		// fatal error) — steal it rather than block new submissions forever.
		$existing = get_option( $lock_name );
		if ( false !== $existing && ( $now - (int) $existing ) > 15 ) {
			update_option( $lock_name, $now, 'no' );
			return $lock_name;
		}

		return false;
	}

	/** Releases a lock acquired by acquire_dup_lock(). Safe to call with false. */
	private static function release_dup_lock( $lock_name ) {
		if ( $lock_name ) {
			delete_option( $lock_name );
		}
	}

	/**
	 * Normalizes a text field for duplicate comparison: trims leading/
	 * trailing whitespace, collapses internal runs of whitespace to a
	 * single space, and lowercases (multibyte-safe) so "Shiva", "shiva"
	 * and "SHIVA" — or "Kathmandu " and "  KATHMANDU" — all compare equal.
	 */
	public static function normalize_dup_text( $value ) {
		$value = trim( (string) $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
	}

	/**
	 * Builds a stable "fingerprint" string for a record from the fields that
	 * define a duplicate: Event, Sub Event Name, Organization, Start Date,
	 * Duration, Location, Facilitator, Male/Female participants, and the
	 * Dalit/Janajati/Other demographic counts. Text fields are compared
	 * case-insensitively with extra whitespace trimmed; numeric/date fields
	 * are compared as-is. Two rows with the same fingerprint are duplicates.
	 *
	 * Accepts either an associative array or an object (e.g. a $wpdb row)
	 * with these keys/properties.
	 */
	public static function duplicate_signature( $fields ) {
		$fields = (array) $fields;
		$get    = function ( $key, $default = '' ) use ( $fields ) {
			return isset( $fields[ $key ] ) ? $fields[ $key ] : $default;
		};

		$parts = array(
			(int) $get( 'event_id', 0 ),
			self::normalize_dup_text( $get( 'sub_event_name' ) ),
			self::normalize_dup_text( $get( 'organization' ) ),
			(string) $get( 'start_date' ),
			self::normalize_dup_text( $get( 'duration' ) ),
			self::normalize_dup_text( $get( 'location' ) ),
			self::normalize_dup_text( $get( 'facilitator' ) ),
			(int) $get( 'participant_male', 0 ),
			(int) $get( 'participant_female', 0 ),
			(int) $get( 'demographic_dalit', 0 ),
			(int) $get( 'demographic_janajati', 0 ),
			(int) $get( 'demographic_other', 0 ),
		);

		// \x1F (unit separator) is a control character that will never appear
		// in real CSV/text values, so it can't accidentally merge two
		// different fields into a false match.
		return implode( "\x1F", $parts );
	}

	/**
	 * Preloads every existing record's duplicate fingerprint into a hash set
	 * (fingerprint => true) in a single query, so CSV import can check each
	 * incoming row for O(1) in-memory lookups instead of one SQL query per
	 * row. Used by Gimat_Import_Export::import_csv().
	 */
	public static function existing_duplicate_signatures() {
		global $wpdb;
		$table = Gimat_DB::records_table();

		$rows = $wpdb->get_results(
			"SELECT event_id, sub_event_name, organization, start_date, duration, location, facilitator,
					participant_male, participant_female, demographic_dalit, demographic_janajati, demographic_other
			 FROM {$table}",
			ARRAY_A
		);

		$signatures = array();
		foreach ( (array) $rows as $row ) {
			$signatures[ self::duplicate_signature( $row ) ] = true;
		}
		return $signatures;
	}

	/**
	 * Looks for a single existing record that matches the same duplicate
	 * fingerprint as $data (see duplicate_signature()) — used by the Add
	 * Record / Edit Record form (manual entry), as opposed to CSV import
	 * which checks a preloaded signature set for speed on bulk rows.
	 *
	 * Narrows the SQL query to same event_id + start_date first (cheap,
	 * indexed), then confirms the full fingerprint match in PHP so the
	 * whitespace/case-insensitive comparison rules stay in one place
	 * (duplicate_signature()).
	 *
	 * @param array $data       Sanitized record fields (as returned by sanitize_input()).
	 * @param int   $exclude_id Optional. A record id to ignore — used when
	 *                          editing a record, so it isn't compared against itself.
	 * @return object|null The matching row, or null if no duplicate exists.
	 */
	public static function find_duplicate( $data, $exclude_id = 0 ) {
		global $wpdb;
		$table = Gimat_DB::records_table();

		$sql    = "SELECT * FROM {$table} WHERE event_id = %d AND start_date = %s";
		$params = array( (int) $data['event_id'], (string) $data['start_date'] );

		$exclude_id = (int) $exclude_id;
		if ( $exclude_id ) {
			$sql     .= ' AND id != %d';
			$params[] = $exclude_id;
		}

		$candidates = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		if ( ! $candidates ) {
			return null;
		}

		$target_signature = self::duplicate_signature( $data );
		foreach ( $candidates as $row ) {
			if ( self::duplicate_signature( $row ) === $target_signature ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Used by CSV import: only event_id + start_date are hard-required;
	 * other text fields are allowed to be blank (saved as empty strings),
	 * matching the original dashboard's "warnings, not errors" import behaviour.
	 */
	public static function insert_relaxed( $raw ) {
		global $wpdb;
		$data = self::sanitize_input( $raw, false );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$now                = current_time( 'mysql' );
		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		$table  = Gimat_DB::records_table();
		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not save record.', 'gimat-dashboard' ) );
		}
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $raw ) {
		global $wpdb;
		$id = (int) $id;
		if ( ! $id ) {
			return new WP_Error( 'gimat_invalid', __( 'Record ID is required.', 'gimat-dashboard' ) );
		}

		$data = self::sanitize_input( $raw, true );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$signature = self::duplicate_signature( $data );
		$lock      = self::acquire_dup_lock( $signature );
		if ( ! $lock ) {
			return new WP_Error(
				'gimat_duplicate',
				__( 'This record is already being saved. Please check All Records before submitting again.', 'gimat-dashboard' )
			);
		}

		if ( self::find_duplicate( $data, $id ) ) {
			self::release_dup_lock( $lock );
			return new WP_Error(
				'gimat_duplicate',
				__( 'Another record already exists with the same Event, Sub Event Name, Organization, Start Date, Duration, Location, Facilitator, and participant counts.', 'gimat-dashboard' )
			);
		}

		$data['updated_at'] = current_time( 'mysql' );

		$table  = Gimat_DB::records_table();
		$result = $wpdb->update( $table, $data, array( 'id' => $id ) );

		self::release_dup_lock( $lock );

		if ( false === $result ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not update record.', 'gimat-dashboard' ) );
		}
		return true;
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( ! $id ) {
			return new WP_Error( 'gimat_invalid', __( 'Record ID is required.', 'gimat-dashboard' ) );
		}
		$table   = Gimat_DB::records_table();
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not delete record.', 'gimat-dashboard' ) );
		}
		return true;
	}

	/**
	 * Total headcount for one training record. Adds the gender breakdown
	 * (Male/Female) and the ethnicity breakdown (Dalit/Janajati/Other)
	 * together, matching how the dashboard's summary cards total up.
	 */
	public static function row_total( $record ) {
		return (int) $record->participant_male
			+ (int) $record->participant_female
			+ (int) $record->demographic_dalit
			+ (int) $record->demographic_janajati
			+ (int) $record->demographic_other;
	}

	/** Dashboard summary numbers. */
	public static function stats() {
		global $wpdb;
		$records_table = Gimat_DB::records_table();
		$events_table   = Gimat_DB::events_table();

		$events_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$events_table}" );
		$records_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$records_table}" );

		$totals = $wpdb->get_row(
			"SELECT
				COALESCE(SUM(participant_male),0)     AS male,
				COALESCE(SUM(participant_female),0)   AS female,
				COALESCE(SUM(demographic_dalit),0)    AS dalit,
				COALESCE(SUM(demographic_janajati),0) AS janajati,
				COALESCE(SUM(demographic_other),0)    AS other
			 FROM {$records_table}"
		);
		$total_participants = $totals
			? ( (int) $totals->male + (int) $totals->female + (int) $totals->dalit + (int) $totals->janajati + (int) $totals->other )
			: 0;

		$facilitators = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT LOWER(TRIM(facilitator))) FROM {$records_table} WHERE facilitator != ''"
		);

		$recent = $wpdb->get_results(
			"SELECT r.id, r.location, r.start_date, r.sub_event_name, r.participant_male, r.participant_female,
				r.demographic_dalit, r.demographic_janajati, r.demographic_other, e.event_name
			 FROM {$records_table} r
			 LEFT JOIN {$events_table} e ON e.id = r.event_id
			 ORDER BY r.created_at DESC
			 LIMIT 5"
		);

		return array(
			'events_count'        => $events_count,
			'records_count'       => $records_count,
			'total_participants'  => $total_participants,
			'facilitators_count'  => $facilitators,
			'recent'              => $recent ?: array(),
		);
	}

	/**
	 * Data for the Dashboard Analytics charts (Chart.js). Always queried live
	 * from {$wpdb->prefix}gimat_records / gimat_events, so the numbers are
	 * current as of the moment the Dashboard page is rendered — no cached or
	 * hardcoded values. Whenever a record/event is added, edited or deleted,
	 * the next Dashboard page load reflects it automatically.
	 */
	public static function chart_data() {
		global $wpdb;
		$records_table = self::table_for_charts();

		// ---- Participants by Month (last 12 months that have data) ----
		$monthly_rows = $wpdb->get_results(
			"SELECT DATE_FORMAT(start_date, '%Y-%m') AS ym,
					SUM(participant_male + participant_female + demographic_dalit + demographic_janajati + demographic_other) AS total
			 FROM {$records_table}
			 WHERE start_date IS NOT NULL
			 GROUP BY ym
			 ORDER BY ym ASC"
		);
		if ( $monthly_rows && count( $monthly_rows ) > 12 ) {
			$monthly_rows = array_slice( $monthly_rows, -12 );
		}
		$monthly = array();
		foreach ( (array) $monthly_rows as $row ) {
			$monthly[] = array(
				'label' => date_i18n( 'M Y', strtotime( $row->ym . '-01' ) ),
				'total' => (int) $row->total,
			);
		}

		// ---- Participant Demographics (Dalit / Janajati / Other) ----
		$demo_row = $wpdb->get_row(
			"SELECT COALESCE(SUM(demographic_dalit),0)    AS dalit,
					COALESCE(SUM(demographic_janajati),0) AS janajati,
					COALESCE(SUM(demographic_other),0)    AS other
			 FROM {$records_table}"
		);
		$demographics = array(
			'dalit'    => $demo_row ? (int) $demo_row->dalit : 0,
			'janajati' => $demo_row ? (int) $demo_row->janajati : 0,
			'other'    => $demo_row ? (int) $demo_row->other : 0,
		);

		// ---- Gender Distribution (Male / Female) — kept separate from the
		// Dalit/Janajati/Other demographics chart, per its own chart. ----
		$gender_row = $wpdb->get_row(
			"SELECT COALESCE(SUM(participant_male),0)   AS male,
					COALESCE(SUM(participant_female),0) AS female
			 FROM {$records_table}"
		);
		$gender = array(
			'male'   => $gender_row ? (int) $gender_row->male : 0,
			'female' => $gender_row ? (int) $gender_row->female : 0,
		);

		// ---- Top Locations (ranked by sessions held) ----
		$location_rows = $wpdb->get_results(
			"SELECT location, COUNT(*) AS sessions
			 FROM {$records_table}
			 WHERE location != ''
			 GROUP BY location
			 ORDER BY sessions DESC, location ASC
			 LIMIT 6"
		);
		$top_locations = array();
		foreach ( (array) $location_rows as $row ) {
			$top_locations[] = array( 'label' => $row->location, 'sessions' => (int) $row->sessions );
		}

		// ---- Top Instructors (ranked by sessions delivered) ----
		$instructor_rows = $wpdb->get_results(
			"SELECT facilitator, COUNT(*) AS sessions
			 FROM {$records_table}
			 WHERE facilitator != ''
			 GROUP BY facilitator
			 ORDER BY sessions DESC, facilitator ASC
			 LIMIT 6"
		);
		$top_instructors = array();
		foreach ( (array) $instructor_rows as $row ) {
			$top_instructors[] = array( 'label' => $row->facilitator, 'sessions' => (int) $row->sessions );
		}

		return array(
			'monthly'         => $monthly,
			'demographics'    => $demographics,
			'gender'          => $gender,
			'top_locations'   => $top_locations,
			'top_instructors' => $top_instructors,
			'i18n'            => array(
				'noData' => __( 'No data yet.', 'gimat-dashboard' ),
			),
		);
	}

	/** Small helper so chart_data() reads clearly; same table as everywhere else. */
	private static function table_for_charts() {
		return Gimat_DB::records_table();
	}
}
