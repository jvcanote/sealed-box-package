<?php
/**
 * Sealed Box Client
 *
 * @package  SealedBox/Client
 * @version  3.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Client Procedural Encrypted Value class.
 *
 * Derive API parameter values using the whitelist functions and procedural opperations
 * established within the response data of the external endopint OPTIONS request.
 */
class SBX_Client_Procedural_Encrypted_Value extends SBX_Client_Procedural_Value {

	/**
	 * Whitelist functions.
	 *
	 * @var string[]
	 */
	const WHITELIST = array(
		'md5',
		'sprintf',
		'vsprintf',
		'serialize',
		'unserialize',
		'base64_decode',
		'base64_encode',
		'json_decode',
		'json_encode',
		'rawurldecode',
		'rawurlencode',
		'sodium_crypto_box_seal',
	);

	/**
	 * Alternate functions.
	 *
	 * @var mixed[]
	 */
	const ALTERNATE = array(
		'json_encode'  => 'wp_json_encode',
		'serialize'    => 'maybe_serialize',
		'unserialize'  => 'maybe_unserialize',
	);

	public function __construct( array $data ) {
		$this->data['publickey'] = '';
		parent::__construct( $data, $data['issued'] );
	}

	/**
	 * Get processed value.
	 *
	 * Create and update response values. The values
	 * are produced by filters defined in the response.
	 *
	 * @param  mixed  $input  Value used to process result.
	 * @return mixed  Proccess result.
	 */
	public function get_value( $input = null ) {
		$this->processing = array(
			'input'  => array(
				'message'   => $input,
				'publickey' => $this->data['publickey'],
			),
			'output' => array(),
		);
		$this->process();
		return $this->processing['output'];
	}
}
