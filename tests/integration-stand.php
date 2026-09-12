<?php
/**
 * Login Watch integration — inside QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/lgw-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Login Watch integration ==\n";

check( 'plugin active', is_plugin_active( 'login-watch/login-watch.php' ) );
check( 'classes loaded', class_exists( 'LGW_Watcher' ) && class_exists( 'LGW_Store' ) );

global $wpdb;
$table     = LGW_Store::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( $has_table !== $table ) {
	LGW_Store::activate();
	$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
}
check( 'journal table present', $has_table === $table );

LGW_Store::erase_all();
delete_option( LGW_Watcher::BURST_STATE );

// Admin login event.
$admin = get_userdata( 1 );
check( 'admin user exists', (bool) $admin );
LGW_Watcher::on_login( $admin->user_login, $admin );
check( 'admin login journaled', 'login' === LGW_Store::recent( 1 )[0]['kind'] );

// Failed burst: 12 attempts from one source -> journaled 12, alert flag once.
for ( $i = 0; $i < 12; $i++ ) {
	do_action( 'wp_login_failed', 'bruteforce_bot' );
}
$rows      = LGW_Store::recent( 50 );
$bursts    = array_filter( $rows, static function ( $r ) { return 'failed_burst' === $r['kind']; } );
check( 'all failures journaled', 12 === count( $bursts ) );

// New admin event: user created directly with the administrator role.
LGW_Store::erase_all();
$new_admin_id = wp_insert_user(
	array(
		'user_login' => 'lgw_fake_admin_' . time(),
		'user_pass'  => 'Sup3rSecret!123',
		'user_email' => 'lgw-fake-' . time() . '@example.com',
		'role'       => 'administrator',
	)
);
$roles_ok = $new_admin_id && ! is_wp_error( $new_admin_id );
check( 'new-admin event journaled', $roles_ok && 'new_admin' === LGW_Store::recent( 1 )[0]['kind'] );

// Role change event: promote an editor -> admin.
$sub_id = wp_create_user( 'lgw_sub_' . time(), 'Sup3rSecret!123' );
if ( $sub_id && ! is_wp_error( $sub_id ) ) {
	$user = new WP_User( $sub_id );
	$user->set_role( 'editor' );
	LGW_Store::erase_all(); // Clear to isolate the next event.
	$user->set_role( 'administrator' );
}
check( 'role-change event journaled', 'role_change' === LGW_Store::recent( 1 )[0]['kind'] );

// Plugin activation journaled (no alert channel spam).
LGW_Watcher::on_plugin_activated( 'hello-dolly/hello.php' );
check( 'plugin activation journaled', 'plugin_activated' === LGW_Store::recent( 1 )[0]['kind'] );

// Cleanup.
if ( $new_admin_id && ! is_wp_error( $new_admin_id ) ) {
	wp_delete_user( (int) $new_admin_id );
}
if ( $sub_id && ! is_wp_error( $sub_id ) ) {
	wp_delete_user( (int) $sub_id );
}
LGW_Store::erase_all();
delete_option( LGW_Watcher::BURST_STATE );
check( 'erase clears journal', array() === LGW_Store::recent( 5 ) );

printf( "\n== Login Watch integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
