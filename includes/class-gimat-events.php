<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gimat_Events {

	public static function get_all() {
		global $wpdb;
		$table = Gimat_DB::events_table();
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY event_name ASC" );
	}

	public static function get( $id ) {
		global $wpdb;
		$table = Gimat_DB::events_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	public static function name_exists( $name, $exclude_id = 0 ) {
		global $wpdb;
		$table = Gimat_DB::events_table();
		if ( $exclude_id ) {
			$found = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_name = %s AND id != %d",
				$name, $exclude_id
			) );
		} else {
			$found = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_name = %s",
				$name
			) );
		}
		return ! empty( $found );
	}

	/**
	 * @return int|WP_Error new event id, or WP_Error on failure/duplicate
	 */
	public static function insert( $event_name ) {
		global $wpdb;
		$event_name = trim( sanitize_text_field( $event_name ) );

		if ( '' === $event_name ) {
			return new WP_Error( 'gimat_invalid', __( 'Event name is required.', 'gimat-dashboard' ) );
		}
		if ( strlen( $event_name ) > 200 ) {
			return new WP_Error( 'gimat_invalid', __( 'Event name is too long.', 'gimat-dashboard' ) );
		}
		if ( self::name_exists( $event_name ) ) {
			return new WP_Error( 'gimat_duplicate', __( 'An event with this name already exists.', 'gimat-dashboard' ) );
		}

		$table  = Gimat_DB::events_table();
		$result = $wpdb->insert(
			$table,
			array(
				'event_name' => $event_name,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not save event.', 'gimat-dashboard' ) );
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * @return true|WP_Error true on success, or WP_Error on failure/duplicate
	 */
	public static function update( $id, $event_name ) {
		global $wpdb;
		$id         = (int) $id;
		$event_name = trim( sanitize_text_field( $event_name ) );

		if ( ! $id || ! self::get( $id ) ) {
			return new WP_Error( 'gimat_invalid', __( 'Event not found.', 'gimat-dashboard' ) );
		}
		if ( '' === $event_name ) {
			return new WP_Error( 'gimat_invalid', __( 'Event name is required.', 'gimat-dashboard' ) );
		}
		if ( strlen( $event_name ) > 200 ) {
			return new WP_Error( 'gimat_invalid', __( 'Event name is too long.', 'gimat-dashboard' ) );
		}
		if ( self::name_exists( $event_name, $id ) ) {
			return new WP_Error( 'gimat_duplicate', __( 'An event with this name already exists.', 'gimat-dashboard' ) );
		}

		$table  = Gimat_DB::events_table();
		$result = $wpdb->update(
			$table,
			array( 'event_name' => $event_name ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not update event.', 'gimat-dashboard' ) );
		}
		return true;
	}

	/**
	 * Deletes the event AND all training records linked to it (cascade),
	 * mirroring the original dashboard's behaviour.
	 */
	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( ! $id ) {
			return new WP_Error( 'gimat_invalid', __( 'Event ID is required.', 'gimat-dashboard' ) );
		}

		$records_table = Gimat_DB::records_table();
		$events_table  = Gimat_DB::events_table();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$records_table} WHERE event_id = %d", $id ) );
		$deleted = $wpdb->delete( $events_table, array( 'id' => $id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'gimat_db_error', __( 'Could not delete event.', 'gimat-dashboard' ) );
		}
		return true;
	}
}
