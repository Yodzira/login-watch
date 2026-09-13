<?php
/**
 * Watches login-related events and notifies.
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGW_Watcher {

	const BURST_STATE = 'lgw_burst_state';

	public static function boot() {
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'on_login_failed' ), 10, 2 );
		add_action( 'user_register', array( __CLASS__, 'on_user_register' ), 10, 3 );
		add_action( 'set_user_role', array( __CLASS__, 'on_role_change' ), 10, 3 );
		add_action( 'activated_plugin', array( __CLASS__, 'on_plugin_activated' ) );
		add_action( 'lgw_prune', array( __CLASS__, 'run_prune' ) );
	}

	/**
	 * Client IP (respects an optional trusted proxy header).
	 *
	 * @return string
	 */
	private static function client_ip() {
		$header = trim( (string) get_option( 'lgw_proxy_header', '' ) );
		if ( '' !== $header && isset( $_SERVER[ $header ] ) ) {
			return (string) $_SERVER[ $header ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Send through configured channels + journal.
	 *
	 * @param string $kind  Event kind.
	 * @param array  $data  Message data.
	 * @param bool   $alert Whether to push a notification (vs journal-only).
	 * @return void
	 */
	private static function record( $kind, array $data, $alert = true ) {
		$settings = LGW_Settings::get();
		$ip       = self::client_ip();
		$login    = isset( $data['login'] ) ? (string) $data['login'] : '';

		LGW_Store::add( $kind, $login, LGW_Ip::mask( $ip ), isset( $data['detail'] ) ? (string) $data['detail'] : '' );
		if ( ! $alert ) {
			return;
		}

		$text = LGW_Messages::build( $kind, array_merge( $data, array( 'ip' => LGW_Ip::mask( $ip ) ) ) );
		$chat = apply_filters( 'lgw_alert_chat', $settings['chat'], $kind, $data );
		if ( '' !== $settings['token'] && '' !== $chat ) {
			( new LGW_Telegram() )->send( $settings['token'], $chat, $text );
		}
		wp_mail( get_option( 'admin_email' ), 'Login Watch: ' . $kind, $text );
	}

	/**
	 * Successful login — alert only for administrators.
	 *
	 * @param string  $login Login.
	 * @param WP_User $user  User.
	 * @return void
	 */
	public static function on_login( $login, $user = null ) {
		$roles = ( $user instanceof WP_User ) ? (array) $user->roles : array();
		if ( ! in_array( 'administrator', $roles, true ) ) {
			return;
		}
		self::record( 'login', array( 'login' => $login ) );
	}

	/**
	 * Failed attempt — journal always, alert on burst threshold.
	 *
	 * @param string $login    Login tried.
	 * @param mixed  $error    Error.
	 * @return void
	 */
	public static function on_login_failed( $login, $error = null ) {
		$ip       = self::client_ip();
		$flood    = new LGW_Burst( (array) get_option( self::BURST_STATE, array() ) );
		$result   = $flood->register( LGW_Ip::signature( $login, $ip ), time(), 10 );
		update_option( self::BURST_STATE, $flood->export(), false );

		self::record(
			'failed_burst',
			array( 'login' => $login, 'count' => $result['count'] ),
			$result['alert']
		);
	}

	/**
	 * New user registered with an admin role.
	 *
	 * @param int   $user_id User id.
	 * @param array $userdata User data.
	 * @return void
	 */
	public static function on_user_register( $user_id, $userdata = array() ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( 'administrator', (array) $user->roles, true ) ) {
			return;
		}
		self::record( 'new_admin', array( 'login' => $user->user_login ) );
	}

	/**
	 * Role changed to administrator.
	 *
	 * @param int    $user_id User id.
	 * @param string $role    New role.
	 * @param array  $old_roles Previous roles.
	 * @return void
	 */
	public static function on_role_change( $user_id, $role, $old_roles = array() ) {
		if ( 'administrator' !== (string) $role ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		self::record( 'role_change', array( 'login' => $user->user_login, 'from' => $old_roles ? (string) reset( $old_roles ) : '(new)' ) );
	}

	/**
	 * Plugin activated (classic backdoor move).
	 *
	 * @param string $plugin Plugin file.
	 * @return void
	 */
	public static function on_plugin_activated( $plugin ) {
		self::record( 'plugin_activated', array( 'plugin' => (string) $plugin, 'login' => '' ), false );
	}

	public static function run_prune() {
		LGW_Store::prune( 30 );
	}
}
