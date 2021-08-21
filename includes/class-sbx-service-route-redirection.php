<?php
/**
 * Redirect Route Class.
 *
 * The redirection service type route type.
 *
 * @version  1.0.0
 * @package  SealedBox/Classes/Routes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect route class.
 */
class SBX_Service_Route_Redirection extends SBX_Service_Route {

	/**
	 * Initialize redirect route.
	 *
	 * @param SBX_Service_Route|int $route Route instance or ID.
	 */
	protected function __construct( $route = 0, $service ) {
		$this->supports[] = 'feature';
		$this->service = $service;
		parent::__construct( $route, $service );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return 'redirection';
	}
}
