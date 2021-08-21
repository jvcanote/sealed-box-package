<?php
/**
 * SealedBox Routes class.
 *
 *
 *
 * @version  1.0.0
 * @package  SealedBox/Abstracts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Built in route type.
 *
 * @since   1.0.0
 * @var     string   SEALED_BOX_BUILT_IN_ROUTE_TYPE Default subclass.
 */
const SEALED_BOX_BUILT_IN_ROUTE_TYPE = SEALED_BOX_BASE['type'];

/**
 * Abstract Routes class.
 *
 * The SealedBox routes class handles individual route data.
 *
 * @version 1.0.0
 * @package SealedBox/Abstracts
 */
abstract class SBX_Service_Routes {

    /**
     * This is the name of this object type.
     *
     * @var string
     */
    protected static $object_type = 'service_route';

    /**
     * Post type.
     *
     * @since 1.0.0
     * @var string
     */
    protected static $post_type = 'sbx_service_route';

    /**
     * Cache group.
     *
     * @since 1.0.0
     * @var string
     */
    protected static $cache_group = 'sealed_box_service_routes';

    /**
     * Get a route.
     *
     * @param mixed $route SBX_Service_Route|WP_Post|int|bool $route Route instance, post instance, numeric or false to use global $post.
     * @return SBX_Service_Route|bool Route object or false if the route cannot be loaded.
     */
	public static function get_route( $route = false ) {
        global $wpdb;

		$route_id = self::get_service_route_id( $route );

		if ( false === $route_id ) {
            return false;
		}

		$_cached    = false;
		$route_type = self::get_service_route_type( $route_id );

        if ( is_object( $route ) ) {
			$_route = $route;
			if ( isset( $_route->type ) ) {
				$_route = self::get_service_route_object( $route, $_route->type );
			}
        } else {
			$_route  = wp_cache_get( $route_id, self::$cache_group );
			$_cached = ! empty( $_route );
        }

		if ( ! $_route ) {
            $_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $route_id ) );

			if ( ! $_post ) {
				return false;
			}

			try {
				$_route = self::get_service_route_object( $_post, $route_type );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! $_route ) {
			return false;
		}

		if ( empty( $_route->filter ) && ! $_cached ) {

			$_route = sanitize_post( $_route, 'raw' );
			wp_cache_add( $route_id, $_route, self::$cache_group );
        }

        return self::get_service_route_object( $_route, $route_type );
	}

	/**
	 * Gets a route classname and allows filtering. Returns SBX_Service_Route_Basic if the class does not exist.
	 *
	 * @since  1.0.0
	 * @param  int    $route_id   Route ID.
	 * @param  string $route_type Route type.
	 * @return string
	 */
	private static function get_service_route_object( $route_id, $route_type ) {
		$service = sbx_get_service( sbx_get_term_slug( $route_type, 'sbx_route_type' ) );
		$service = sbx_get_var( $service, sbx_get_service( SEALED_BOX_BUILT_IN_ROUTE_TYPE ) );
		if ( $service ) {
			return $service->construct_route( $route_id );
		}
		$classname = self::get_service_route_classname( $route_id, $route_type );

		if ( $service && $classname ) {
			return new $classname( $route_id, $service );
		}
	}

	/**
	 * Gets a route classname and allows filtering. Returns SBX_Service_Route_Basic if the class does not exist.
	 *
	 * @since  1.0.0
	 * @param  int    $route_id   Route ID.
	 * @param  string $route_type Route type.
	 * @return string
	 */
	private static function get_service_route_classname( $route_id, $route_type ) {
		$classname = apply_filters( 'sealed_box_service_route_class', self::get_classname_from_route_type( $route_type ), $route_type, 'sbx_service_route', $route_id );

		if ( ! $classname || ! class_exists( $classname ) ) {
			$classname = self::get_classname_from_route_type( SEALED_BOX_BUILT_IN_ROUTE_TYPE );
		}

		return $classname;
	}

	/**
	 * Get the route type for a route.
	 *
	 * @since  1.0.0
	 * @param  int $route_id Route ID.
	 * @return string|false
	 */
	private static function get_service_route_type( $route_id ) {
		// Allow the overriding of the lookup in this function. Return the route type here.
		$override = apply_filters( 'sealed_box_service_route_type_query', false, $route_id );
		if ( ! $override ) {
			return sbx_get_route_type_slug( $route_id );// ?? ( metadata_exists( 'post', $route_id, 'service' ) ? get_post_meta( $route_id, 'service', true ) : 'basic' );
		} else {
			return $override;
		}
	}

	/**
	 * Create a SBX coding standards compliant class name e.g. SBX_Service_Route_Type_Class instead of SBX_Service_Route_type-class.
	 *
	 * @param  string $route_type Route type.
	 * @return string|false
	 */
	public static function get_classname_from_route_type( $route_type ) {
		return $route_type ? 'SBX_Service_Route_' . implode( '_', array_map( 'ucfirst', explode( '-', $route_type ) ) ) : false;
	}

	/**
	 * Get the route ID depending on what was passed.
	 *
	 * @since  1.0.0
	 * @param  SBX_Service_Route|WP_Post|int|bool $route Route instance, post instance, numeric or false to use global $post.
	 * @return int|bool false on failure
	 */
	private static function get_service_route_id( $route ) {
		global $post;

		if ( false === $route && isset( $post, $post->ID ) && 'sbx_service_route' === get_post_type( $post->ID ) ) {
			return absint( $post->ID );
		} elseif ( is_numeric( $route ) ) {
			return absint( $route );
		} elseif ( is_object( $route ) && isset( $route->ID ) ) {
			return absint( $route->ID );
		} elseif ( is_array( $route ) && isset( $route['ID'] ) ) {
			return absint( $route['ID'] );
		} else {
			return false;
		}
	}
}
