<?php
/**
 * WooCommerce Services class
 *
 * Loads Services into WooCommerce.
 *
 * @version 3.9.0
 * @package WooCommerce/Classes/Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * Services class.
 */
class SBX_Services extends ArrayObject {

    /**
     * Initialize services.
     */
    public function __construct() {

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( function_exists( 'did_action' ) && did_action( 'sealed_box_after_register_services' ) ) {
            _doing_it_wrong( __FUNCTION__, __( 'Sealed Box has already completed registration for REST route services.', 'sealedbox' ), SEALED_BOX_VERSION );
			return;
		}

        do_action( 'sealed_box_register_services' );

        /**
         * Sealed Box service classes.
         *
         * Built in service class names:
         *  • SBX_Service_Basic
         *  • SBX_Service_Redirection
         *
         * @since 1.0.0
         * @var string[] $built_in_services
         */
        $built_in_services = array_map( array( $this, 'get_classname_from_service' ), array_keys( SEALED_BOX_BUILT_IN_SERVICES ) );
        $load_services     = apply_filters( 'sealed_box_services', $built_in_services );
        $loaded_services   = array();

        // Load service classes.
        foreach ( $load_services as $service ) {
            if ( is_string( $service ) && class_exists( $service ) ) {
				$load_service = $service::get_instance();
				$loaded_services[ $load_service->get_name() ] = $load_service;
			}
		}

        parent::__construct( $loaded_services );

		do_action( 'sealed_box_after_register_services' );


        // error_log( print_r($this, true));
    }

    /**
     * Return a desired service.
     *
     * @since 1.0.0
     * @param string $name The name of the service to get.
     * @return mixed|null The service if one is found, otherwise null.
     */
    public function get_service( $name ) {
        return ArrayObject::offsetGet( $name );
    }

    /**
     * Disabled set and unset.
     *
     * @since 1.0.0
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetUnset( $offset ) {}
    public function offsetSet( $offset, $value ) {}

    /**
     * Whether or not an offset exists.
     *
     * This method is executed when using isset() or empty().
     *
     * @since 1.0.0
     * @param string $offset An offset to check for.
     * @return boolean
     */
    public function offsetExists( $offset ) {
        return parent::offsetExists( $offset );
    }

    /**
     * Offset to retrieve. Returns the value at specified offset.
     *
     * This method is executed when checking if offset is empty().
     *
     * @since 1.0.0
     * @param string $offset
     * @param mixed $value
     */
    public function offsetGet( $offset ) {
        return parent::offsetExists( $offset ) ? parent::offsetGet( $offset ) : null;
    }

    /**
     * Based on wp_list_pluck, this calls a method instead of returning a property.
     *
     * @since 1.0.0
     * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
     * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
     *                                      Default null.
     * @return array Array of values.
     */
    public function list_pluck_services( $callback_or_field, $index_key = null ) {
        return sbx_list_pluck( $this, $callback_or_field, $index_key  );
    }

	/**
	 * Create a SBX coding standards compliant class name e.g. SBX_Service_Route_Type_Class instead of SBX_Service_Route_type-class.
	 *
	 * @param  string $service Route type.
	 * @return string|false
	 */
	public function get_classname_from_service( $service ) {
		return $service ? 'SBX_Service_' . implode( '_', array_map( 'ucfirst', explode( '-', $service ) ) ) : false;
	}
}
