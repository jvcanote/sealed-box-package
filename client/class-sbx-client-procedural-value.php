<?php
/**
 * Sealed Box Client
 *
 * @package  SealedBox/Client
 * @version  3.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Client Procedural Value class.
 *
 * Derive API parameter values using the whitelist functions and procedural opperations
 * established within the response data of the external endopint OPTIONS request.
 */
class SBX_Client_Procedural_Value extends SBX_Abstract_Client_Procedural_Value {

	/**
	 * Get processed value.
	 *
	 * Create and update response values. The values
	 * are produced by filters defined in the response.
	 *
	 * @param  mixed  $input  Value used to process result.
	 * @return mixed  Proccessed result.
	 */
	public function get_value( $input = null ) {
		$this->processing = array(
			'input'  =>  $input,
			'output' => array(),
		);
		$this->process();
		return $this->processing['output'];
	}
}
