<?php
/**
 * Fires only when the plugin is deleted from Plugins → Installed Plugins
 * (never on simple deactivation). Removes the custom capability, the
 * "Client" role, and plugin options.
 *
 * Data tables (events/records) are left in place by default so a client's
 * training history is never destroyed by an accidental uninstall. To also
 * remove them, define GIMAT_DASH_DELETE_DATA_ON_UNINSTALL as true in
 * wp-config.php before deleting the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$cap = 'manage_gimat_dashboard';

foreach ( wp_roles()->role_names as $role_slug => $role_name ) {
	$role = get_role( $role_slug );
	if ( $role && $role->has_cap( $cap ) ) {
		$role->remove_cap( $cap );
	}
}

remove_role( 'gimat_client' );

delete_option( 'gimat_dash_db_version' );

if ( defined( 'GIMAT_DASH_DELETE_DATA_ON_UNINSTALL' ) && true === GIMAT_DASH_DELETE_DATA_ON_UNINSTALL ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gimat_records" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gimat_events" );
}
