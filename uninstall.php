<?php
/**
 * Uninstall cleanup.
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

lgw_uninstall_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function lgw_uninstall_cleanup() {
	global $wpdb;

	delete_option( 'lgw_settings' );
	delete_option( 'lgw_burst_state' );
	delete_option( 'lgw_proxy_header' );
	wp_clear_scheduled_hook( 'lgw_prune' );

	$table = $wpdb->prefix . 'lgw_events';
	// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix only.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
