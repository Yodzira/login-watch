<?php
/**
 * Standalone bootstrap: burst, ip, messages are pure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'LGW_DIR' ) ) {
	define( 'LGW_DIR', dirname( __DIR__ ) );
}
if ( ! defined( 'LGW_VERSION' ) ) {
	define( 'LGW_VERSION', '0.1.0-test' );
}

require_once LGW_DIR . '/includes/class-lgw-core.php';

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'LGW_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 4 ) ) );
		$snake = str_replace( '_', '-', $snake );
		$file  = LGW_DIR . '/includes/class-lgw-' . $snake . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

$GLOBALS['__lgw_options'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['__lgw_options'] ) ? $GLOBALS['__lgw_options'][ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['__lgw_options'][ $key ] = $value;

	return true;
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
