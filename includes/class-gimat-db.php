<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gimat_DB {

	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'gimat_events';
	}

	public static function records_table() {
		global $wpdb;
		return $wpdb->prefix . 'gimat_records';
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$events_table     = self::events_table();
		$records_table    = self::records_table();

		$sql_events = "CREATE TABLE {$events_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_name VARCHAR(200) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_name (event_name)
		) {$charset_collate};";

		$sql_records = "CREATE TABLE {$records_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT UNSIGNED NOT NULL,
			sub_event_name VARCHAR(200) NOT NULL DEFAULT '',
			organization VARCHAR(200) NOT NULL DEFAULT '',
			duration VARCHAR(100) NOT NULL DEFAULT '',
			start_date DATE NULL,
			location VARCHAR(200) NOT NULL DEFAULT '',
			facilitator VARCHAR(300) NOT NULL DEFAULT '',
			participant_male INT UNSIGNED NOT NULL DEFAULT 0,
			participant_female INT UNSIGNED NOT NULL DEFAULT 0,
			demographic_dalit INT UNSIGNED NOT NULL DEFAULT 0,
			demographic_janajati INT UNSIGNED NOT NULL DEFAULT 0,
			demographic_other INT UNSIGNED NOT NULL DEFAULT 0,
			custom_fields LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY event_id (event_id),
			KEY start_date (start_date),
			KEY facilitator (facilitator(50))
		) {$charset_collate};";

		dbDelta( $sql_events );
		dbDelta( $sql_records );
	}
}
