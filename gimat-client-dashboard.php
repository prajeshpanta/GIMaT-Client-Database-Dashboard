<?php
/**
 * Plugin Name: GIMaT Client Database Dashboard
 * Description: Custom training-records / events dashboard (Events, Records, CSV import/export, stats) built as a free, dependency-free replacement for paid data-table plugins.
 * Version: 1.5.0
 * Author: GIMaT Nepal
 * Text Domain: gimat-dashboard
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'GIMAT_DASH_VERSION', '1.5.0' );
define( 'GIMAT_DASH_DIR', plugin_dir_path( __FILE__ ) );
define( 'GIMAT_DASH_URL', plugin_dir_url( __FILE__ ) );
define( 'GIMAT_DASH_CAP', 'manage_gimat_dashboard' ); // custom capability
define( 'GIMAT_DASH_CLIENT_ROLE', 'gimat_client' ); // restricted "Client" role

require_once GIMAT_DASH_DIR . 'includes/class-gimat-db.php';
require_once GIMAT_DASH_DIR . 'includes/class-gimat-events.php';
require_once GIMAT_DASH_DIR . 'includes/class-gimat-records.php';
require_once GIMAT_DASH_DIR . 'includes/class-gimat-import-export.php';
require_once GIMAT_DASH_DIR . 'includes/class-gimat-admin.php';

/**
 * Activation: create tables, grant the custom capability to Administrators
 * and Editors, and register a dedicated, low-privilege "Client" role.
 *
 * The Client role can ONLY see this dashboard (read + manage_gimat_dashboard).
 * It cannot edit posts, manage plugins, see other users, or reach any other
 * part of wp-admin — this is what keeps the dashboard restricted to the
 * people you explicitly grant it to.
 */
function gimat_dash_activate() {
	Gimat_DB::create_tables();

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( GIMAT_DASH_CAP );
	}
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( GIMAT_DASH_CAP );
	}

	if ( ! get_role( GIMAT_DASH_CLIENT_ROLE ) ) {
		add_role(
			GIMAT_DASH_CLIENT_ROLE,
			__( 'Client', 'gimat-dashboard' ),
			array(
				'read'          => true,
				GIMAT_DASH_CAP  => true,
			)
		);
	}
}
register_activation_hook( __FILE__, 'gimat_dash_activate' );

/**
 * Keep the DB schema current on plugin upgrades (dbDelta is safe to re-run).
 */
function gimat_dash_maybe_upgrade() {
	$installed = get_option( 'gimat_dash_db_version' );
	if ( $installed !== GIMAT_DASH_VERSION ) {
		Gimat_DB::create_tables();
		update_option( 'gimat_dash_db_version', GIMAT_DASH_VERSION );
	}
}
add_action( 'plugins_loaded', 'gimat_dash_maybe_upgrade' );

/**
 * Boot the admin UI.
 */
function gimat_dash_init() {
	new Gimat_Admin();
}
add_action( 'plugins_loaded', 'gimat_dash_init' );

/**
 * Deactivation: intentionally does NOT drop tables, remove the capability,
 * or delete the Client role — deactivating a plugin should never destroy a
 * client's data or lock out accounts. See uninstall.php for the deliberate,
 * explicit cleanup path (only runs when the plugin is actually deleted).
 */
