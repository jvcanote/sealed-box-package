<?php
/**
 * Basic Route Class.
 *
 * The default route type kinda route.
 *
 * @version  1.0.0
 * @package  SealedBox/Classes/Routes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Basic route class.
 */
class SBX_Service_Route_Basic extends SBX_Service_Route {

	/**
	 * Initialize basic route.
	 *
	 * @param SBX_Service_Route|int $route Route instance or ID.
	 */
	protected function __construct( $route = 0, $service ) {
		$this->supports[] = 'feature';
		$this->service = $service;
		parent::__construct( $route );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return 'basic';
	}
}
