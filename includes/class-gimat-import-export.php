<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gimat_Import_Export {

	private static $standard_headers = array(
		'event type'           => 'event_name',
		'event name'           => 'event_name',
		'sub event name'       => 'sub_event_name',
		'organization'         => 'organization',
		'start date'           => 'start_date',
		'duration'             => 'duration',
		'location'             => 'location',
		'facilitator'          => 'facilitator',
		'male participants'    => 'participant_male',
		'female participants'  => 'participant_female',
		'dalit'                => 'demographic_dalit',
		'janajati'             => 'demographic_janajati',
		'other'                => 'demographic_other',
	);

	// Maximum lengths for custom field keys and values
	private static $custom_field_max_key_len   = 100;
	private static $custom_field_max_value_len = 500;

	/**
	 * Neutralizes CSV/formula injection: if a cell starts with =, +, -, @, a
	 * tab, or a carriage return, spreadsheet apps (Excel, Sheets) can treat
	 * it as a formula when the file is opened. Prefixing with a single quote
	 * forces it to be read as plain text while keeping the visible value
	 * unchanged for a human reader.
	 */
	private static function csv_safe( $value ) {
		$value = (string) $value;
		if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Streams a CSV of the given records directly to the browser and exits.
	 * $records is the array of row objects returned by Gimat_Records::query_all().
	 */
	public static function export_csv( $records ) {
		nocache_headers();

		$custom_keys = array();
		foreach ( $records as $r ) {
			if ( ! empty( $r->custom_fields ) ) {
				$decoded = json_decode( $r->custom_fields, true );
				if ( is_array( $decoded ) ) {
					foreach ( array_keys( $decoded ) as $k ) {
						$custom_keys[ $k ] = true;
					}
				}
			}
		}
		$custom_keys = array_keys( $custom_keys );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=training_records_' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );

		$headers = array_merge( array(
			'Event Type', 'Sub Event Name', 'Organization', 'Start Date', 'Duration',
			'Location', 'Facilitator', 'Male Participants', 'Female Participants',
			'Dalit', 'Janajati', 'Other',
		), $custom_keys );
		fputcsv( $out, $headers );

		foreach ( $records as $r ) {
			$custom = array();
			if ( ! empty( $r->custom_fields ) ) {
				$decoded = json_decode( $r->custom_fields, true );
				if ( is_array( $decoded ) ) {
					$custom = $decoded;
				}
			}

			$row = array(
				self::csv_safe( $r->event_name ),
				self::csv_safe( $r->sub_event_name ),
				self::csv_safe( $r->organization ),
				self::csv_safe( $r->start_date ),
				self::csv_safe( $r->duration ),
				self::csv_safe( $r->location ),
				self::csv_safe( $r->facilitator ),
				$r->participant_male,
				$r->participant_female,
				$r->demographic_dalit,
				$r->demographic_janajati,
				$r->demographic_other,
			);
			foreach ( $custom_keys as $k ) {
				$row[] = isset( $custom[ $k ] ) ? self::csv_safe( $custom[ $k ] ) : '';
			}
			fputcsv( $out, $row );
		}

		fclose( $out );
		exit;
	}

	/**
	 * Parses an uploaded CSV ($_FILES style array) and inserts valid rows.
	 * Rows missing a matching Event or a valid Start Date are skipped, and
	 * rows that duplicate an existing record (see
	 * Gimat_Records::duplicate_signature()) are skipped and counted
	 * separately so a re-imported export doesn't create duplicate rows.
	 *
	 * @return array|WP_Error { imported: int, duplicates: int, skipped: int, errors: string[] }
	 */
	public static function import_csv( $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'gimat_upload', __( 'No file was uploaded.', 'gimat-dashboard' ) );
		}

		$handle = fopen( $file['tmp_name'], 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'gimat_upload', __( 'Could not read the uploaded file.', 'gimat-dashboard' ) );
		}

		$header_row = fgetcsv( $handle );
		if ( ! $header_row ) {
			fclose( $handle );
			return new WP_Error( 'gimat_upload', __( 'File is empty or missing a header row.', 'gimat-dashboard' ) );
		}

		$headers = array_map( function ( $h ) {
			return strtolower( trim( $h ) );
		}, $header_row );

		$events        = Gimat_Events::get_all();
		$events_by_name = array();
		foreach ( $events as $e ) {
			$events_by_name[ strtolower( trim( $e->event_name ) ) ] = (int) $e->id;
		}

		// Loaded once, up front: every existing record's duplicate
		// "fingerprint" (see Gimat_Records::duplicate_signature()), so each
		// CSV row can be checked with an O(1) in-memory lookup rather than a
		// query per row. New/kept rows are added to this set as we go, so
		// two identical rows within the *same* CSV also get caught.
		$existing_signatures = Gimat_Records::existing_duplicate_signatures();

		$imported   = 0;
		$skipped    = 0;
		$duplicates = 0;
		$errors     = array();
		$row_num    = 1;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;
			if ( count( array_filter( $row, function ( $v ) { return trim( (string) $v ) !== ''; } ) ) === 0 ) {
				continue; // skip fully blank lines
			}

			$assoc = array();
			foreach ( $headers as $i => $h ) {
				$assoc[ $h ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
			}

			$mapped         = array();
			$custom_fields  = array();
			foreach ( $assoc as $h => $val ) {
				if ( isset( self::$standard_headers[ $h ] ) ) {
					$mapped[ self::$standard_headers[ $h ] ] = trim( $val );
				} elseif ( '' !== trim( (string) $val ) ) {
					$original_key = $header_row[ array_search( $h, $headers, true ) ];
					$custom_key   = trim( sanitize_text_field( $original_key ) );
					$custom_value = trim( sanitize_text_field( $val ) );
					// Enforce length limits on custom field keys and values
					if ( strlen( $custom_key ) > self::$custom_field_max_key_len ) {
						$custom_key = substr( $custom_key, 0, self::$custom_field_max_key_len );
					}
					if ( strlen( $custom_value ) > self::$custom_field_max_value_len ) {
						$custom_value = substr( $custom_value, 0, self::$custom_field_max_value_len );
					}
					if ( '' !== $custom_key ) {
						$custom_fields[ $custom_key ] = $custom_value;
					}
				}
			}

			$event_name = isset( $mapped['event_name'] ) ? $mapped['event_name'] : '';
			$event_id   = isset( $events_by_name[ strtolower( $event_name ) ] ) ? $events_by_name[ strtolower( $event_name ) ] : 0;

			if ( ! $event_id ) {
				$skipped++;
				$errors[] = sprintf(
					/* translators: 1: row number, 2: event name */
					__( 'Row %1$d: unknown event "%2$s" — skipped.', 'gimat-dashboard' ),
					$row_num,
					$event_name ?: '(blank)'
				);
				continue;
			}

			$start_date = self::normalize_date( isset( $mapped['start_date'] ) ? $mapped['start_date'] : '' );
			if ( ! $start_date ) {
				$skipped++;
				$errors[] = sprintf(
					/* translators: %d: row number */
					__( 'Row %d: missing/invalid Start Date — skipped.', 'gimat-dashboard' ),
					$row_num
				);
				continue;
			}

			$raw = array(
				'event_id'             => $event_id,
				'sub_event_name'       => isset( $mapped['sub_event_name'] ) ? $mapped['sub_event_name'] : '',
				'organization'         => isset( $mapped['organization'] ) ? $mapped['organization'] : '',
				'duration'             => isset( $mapped['duration'] ) ? $mapped['duration'] : '',
				'start_date'           => $start_date,
				'location'             => isset( $mapped['location'] ) ? $mapped['location'] : '',
				'facilitator'          => isset( $mapped['facilitator'] ) ? $mapped['facilitator'] : '',
				'participant_male'     => isset( $mapped['participant_male'] ) ? (int) $mapped['participant_male'] : 0,
				'participant_female'   => isset( $mapped['participant_female'] ) ? (int) $mapped['participant_female'] : 0,
				'demographic_dalit'    => isset( $mapped['demographic_dalit'] ) ? (int) $mapped['demographic_dalit'] : 0,
				'demographic_janajati' => isset( $mapped['demographic_janajati'] ) ? (int) $mapped['demographic_janajati'] : 0,
				'demographic_other'    => isset( $mapped['demographic_other'] ) ? (int) $mapped['demographic_other'] : 0,
				'custom_fields'        => $custom_fields,
			);

			// Duplicate check: Event, Sub Event Name, Organization, Start Date,
			// Duration, Location, Facilitator, Male/Female participants, and
			// the Dalit/Janajati/Other counts must all match an existing
			// record (text fields case-insensitive, extra spaces trimmed).
			$signature = Gimat_Records::duplicate_signature( $raw );
			if ( isset( $existing_signatures[ $signature ] ) ) {
				$duplicates++;
				$errors[] = sprintf(
					/* translators: %d: row number */
					__( 'duplicate of an existing record — skipped.', 'gimat-dashboard' ),
					$row_num
				);
				continue;
			}

			// Import rows are allowed to have blank optional text fields (warnings, not errors).
			$result = Gimat_Records::insert_relaxed( $raw );
			if ( is_wp_error( $result ) ) {
				$skipped++;
				$errors[] = sprintf( '%s: %s', 'Row ' . $row_num, $result->get_error_message() );
				continue;
			}
			$imported++;
			// Remember this row too, so a second identical row later in the
			// same CSV file is also caught as a duplicate.
			$existing_signatures[ $signature ] = true;
		}

		fclose( $handle );

		return array(
			'imported'   => $imported,
			'duplicates' => $duplicates,
			'skipped'    => $skipped,
			'errors'     => array_slice( $errors, 0, 20 ), // cap for display
		);
	}

	private static function normalize_date( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return $value;
		}
		$ts = strtotime( $value );
		if ( false === $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d', $ts );
	}
}
