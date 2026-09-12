<?php
/**
 * Split classes.
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGW_Telegram {

	/** @var callable|null */
	private $transport;

	public function __construct( $transport = null ) {
		$this->transport = $transport;
	}

	/**
	 * Send a message; true when Telegram confirmed.
	 *
	 * @param string $token Token.
	 * @param string $chat  Chat.
	 * @param string $text  Text.
	 * @return bool
	 */
	public function send( $token, $chat, $text ) {
		if ( '' === trim( (string) $token ) || '' === trim( (string) $chat ) || '' === trim( (string) $text ) ) {
			return false;
		}
		$url = 'https://api.telegram.org/bot' . rawurlencode( (string) $token ) . '/sendMessage';
		if ( $this->transport ) {
			$result = call_user_func( $this->transport, $url, array( 'body' => array( 'chat_id' => $chat, 'text' => $text ) ) );
		} else {
			$response = wp_remote_post( $url, array( 'timeout' => 3, 'body' => array( 'chat_id' => (string) $chat, 'text' => (string) $text ) ) );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$result = array( 'code' => (int) wp_remote_retrieve_response_code( $response ), 'body' => (string) wp_remote_retrieve_body( $response ) );
		}
		$payload = json_decode( isset( $result['body'] ) ? (string) $result['body'] : '', true );

		return is_array( $payload ) && ! empty( $payload['ok'] );
	}
}
