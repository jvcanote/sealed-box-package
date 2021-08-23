<?php
/**
 * Sealed Box Services
 *
 * Functions for handling services.
 *
 * @package SealedBox/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main function for returning rest service, uses the SBX_Service_Routes class.
 *
 * This function should only be called after 'init' action is finished, as there might be services that are getting
 * registered during the init action.
 *
 * @since 1.0.0
 *
 * @param string $service Name of the route service.
 * @return SBX_Abstract_Service|false
 */
function &sbx_get_service( $service ) {
	if ( ! did_action( 'sealed_box_init' ) || ! did_action( 'sealed_box_after_register_services' ) || ! did_action( 'sealed_box_after_register_post_type' ) ) {
		/* translators: 1: sbx_get_route 2: sealed_box_init 3: sealed_box_after_register_services 4: sealed_box_after_register_post_type */
		_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s, %3$s and %4$s actions have finished.', 'sealedbox' ), 'sbx_get_service', 'sealed_box_init', 'sealed_box_after_register_services', 'sealed_box_after_register_post_type' ), SEALED_BOX_VERSION );
		return false;
	}
	$service = SealedBox()->services[ $service ] ?? false;
	return $service;
}