<?php
/**
 * Burst limiter, IP masking, message templates (pure).
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Failed-attempt bursts: one alert per signature per window.
 */
class LGW_Burst {

	const WINDOW_SECONDS = 600;

	/** @var array signature => ['count' => int, 'since' => int, 'alerted' => bool] */
	private $state;

	public function __construct( array $state = array() ) {
		$this->state = $state;
	}

	/**
	 * Register one failed attempt.
	 *
	 * @param string $signature Signature (login + IP subnet).
	 * @param int    $now       Timestamp.
	 * @param int    $threshold Alert threshold.
	 * @return array {alert: bool, count: int} — alert fires once per window.
	 */
	public function register( $signature, $now, $threshold = 10 ) {
		$now = (int) $now;
		if ( ! isset( $this->state[ $signature ] ) || $now - (int) $this->state[ $signature ]['since'] > self::WINDOW_SECONDS ) {
			$this->state[ $signature ] = array( 'count' => 0, 'since' => $now, 'alerted' => false );
		}
		$this->state[ $signature ]['count']++;

		$alert = false;
		if ( $this->state[ $signature ]['count'] >= $threshold && ! $this->state[ $signature ]['alerted'] ) {
			$alert                          = true;
			$this->state[ $signature ]['alerted'] = true;
		}

		return array( 'alert' => $alert, 'count' => $this->state[ $signature ]['count'] );
	}

	/**
	 * Persisted state.
	 *
	 * @return array
	 */
	public function export() {
		return $this->state;
	}
}

/**
 * IP masking and signatures.
 */
class LGW_Ip {

	/**
	 * Mask an IP for privacy: last v4 octet / last 4 hextets -> zero.
	 *
	 * @param string $ip IP.
	 * @return string
	 */
	public static function mask( $ip ) {
		$ip = trim( (string) $ip );
		if ( false !== strpos( $ip, ':' ) ) { // IPv6.
			$parts = explode( ':', $ip );
			if ( count( $parts ) > 4 ) {
				return implode( ':', array_slice( $parts, 0, 4 ) ) . '::';
			}

			return $ip;
		}
		$parts = explode( '.', $ip );
		if ( 4 === count( $parts ) ) {
			return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
		}

		return $ip;
	}

	/**
	 * Burst signature: login + masked IP subnet.
	 *
	 * @param string $login Login.
	 * @param string $ip    IP.
	 * @return string
	 */
	public static function signature( $login, $ip ) {
		return strtolower( (string) $login ) . '|' . self::mask( $ip );
	}
}

/**
 * Message templates (EN, wp.org audience).
 */
class LGW_Messages {

	/**
	 * Build a message for an event kind.
	 *
	 * @param string $kind    login|failed_burst|new_admin|role_change|plugin_activated.
	 * @param array  $data {login, ip, count, plugin, from, to}.
	 * @return string
	 */
	public static function build( $kind, array $data ) {
		$login = isset( $data['login'] ) ? (string) $data['login'] : '?';
		$ip    = isset( $data['ip'] ) ? (string) $data['ip'] : '';

		switch ( $kind ) {
			case 'login':
				return '🔑 Admin login: "' . $login . '"' . ( $ip ? ' from ' . $ip : '' );
			case 'failed_burst':
				return '⚠️ Failed login burst: "' . $login . '" — ' . ( isset( $data['count'] ) ? (int) $data['count'] : 0 ) . ' attempts' . ( $ip ? ' from ' . $ip : '' ) . '. Watchdog only — nothing was blocked.';
			case 'new_admin':
				return '🚨 New administrator created: "' . $login . '". If this was not you, act immediately.';
			case 'role_change':
				return '🚨 Role changed: "' . $login . '" ' . ( isset( $data['from'] ) ? $data['from'] : '?' ) . ' → ' . ( isset( $data['to'] ) ? $data['to'] : '?' ) . '. If this was not you, act immediately.';
			case 'plugin_activated':
				return '🔌 Plugin activated: ' . ( isset( $data['plugin'] ) ? $data['plugin'] : '?' );
		}

		return 'Login Watch: ' . $kind;
	}
}
