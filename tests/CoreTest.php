<?php

use PHPUnit\Framework\TestCase;

/**
 * Burst window, IP masking, messages.
 */
class CoreTest extends TestCase {

	public function test_burst_alerts_once_per_window() {
		$flood = new LGW_Burst();
		$alerts = 0;
		$last = array();
		for ( $i = 0; $i < 15; $i++ ) {
			$last = $flood->register( 'admin|1.2.3.0', 1000 + $i, 10 );
			if ( $last['alert'] ) {
				$alerts++;
			}
		}
		$this->assertSame( 1, $alerts ); // One alert, not fifteen.
		$this->assertSame( 15, $last['count'] );

		// A new window resets the alert flag.
		$again = $flood->register( 'admin|1.2.3.0', 1000 + LGW_Burst::WINDOW_SECONDS + 1, 10 );
		$this->assertFalse( $again['alert'] ); // count == 1.
	}

	public function test_burst_signatures_independent() {
		$flood = new LGW_Burst();
		for ( $i = 0; $i < 12; $i++ ) {
			$flood->register( 'admin|1.2.3.0', 1000, 10 );
		}
		$other = $flood->register( 'root|5.6.7.0', 1001, 10 );
		$this->assertFalse( $other['alert'] ); // Different signature: fresh window.
		$this->assertSame( 1, $other['count'] );
	}

	public function test_ip_masking() {
		$this->assertSame( '1.2.3.0', LGW_Ip::mask( '1.2.3.4' ) );
		$this->assertSame( '2001:db8:85a3:0::', LGW_Ip::mask( '2001:db8:85a3:0:0:8a2e:370:7334' ) );
		$this->assertSame( 'weird', LGW_Ip::mask( 'weird' ) );
	}

	public function test_message_templates() {
		$this->assertSame( '🔑 Admin login: "admin" from 1.2.3.0', LGW_Messages::build( 'login', array( 'login' => 'admin', 'ip' => '1.2.3.0' ) ) );
		$this->assertStringContainsString( '47 attempts', LGW_Messages::build( 'failed_burst', array( 'login' => 'root', 'count' => 47, 'ip' => '9.9.9.0' ) ) );
		$this->assertStringContainsString( 'If this was not you', LGW_Messages::build( 'new_admin', array( 'login' => 'bot_master' ) ) );
		$this->assertSame( 'Login Watch: unknown_kind', LGW_Messages::build( 'unknown_kind', array() ) );
	}

	public function test_settings_sanitize() {
		$GLOBALS['__lgw_options'] = array();
		$clean = LGW_Settings::save( array( 'token' => "12:ab!cd", 'chat' => '@x y', 'junk' => 1 ) );
		$this->assertSame( '12:abcd', $clean['token'] );
		$this->assertSame( '@xy', $clean['chat'] );
		$this->assertSame( LGW_Settings::get(), $clean );
	}
}
