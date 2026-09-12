<?php
/**
 * Journal storage.
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGW_Store {

	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'lgw_events';
	}

	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			kind varchar(20) NOT NULL DEFAULT '',
			user_login varchar(100) NOT NULL DEFAULT '',
			ip_masked varchar(45) NOT NULL DEFAULT '',
			detail varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY logged_at (logged_at),
			KEY kind (kind)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function add( $kind, $login, $ip, $detail = '' ) {
		global $wpdb;

		$wpdb->insert(
			self::table_name(),
			array(
				'logged_at'  => current_time( 'mysql' ),
				'kind'       => substr( (string) $kind, 0, 20 ),
				'user_login' => substr( (string) $login, 0, 100 ),
				'ip_masked'  => substr( (string) $ip, 0, 45 ),
				'detail'     => substr( (string) $detail, 0, 255 ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function recent( $limit = 100 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results( $wpdb->prepare( "SELECT id, logged_at, kind, user_login, ip_masked, detail FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
	}

	public static function prune( $days = 30 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE logged_at < DATE_SUB(%s, INTERVAL %d DAY)", current_time( 'mysql' ), (int) $days ) );
	}

	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.
	}

	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
