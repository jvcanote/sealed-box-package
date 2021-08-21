<?php
/**
 * Route Data
 *
 * Displays the route data box, tabbed, with several panels covering price, stock etc.
 *
 * @package  SealedBox/Admin/MetaBoxes
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SBX_Meta_Box_Service_Route_Data Class.
 */
class SBX_Meta_Box_Service_Route_Data {

	/**
	 * Get class instance
	 */
	public static function get_instance() {
		static $meta_box_route_data = null;
		if ( null === $meta_box_route_data ) {
			$meta_box_route_data = new SBX_Meta_Box_Service_Route_Data();
		}
		return $meta_box_route_data;
	}

	private function __construct() {
		add_action( 'edit_form_after_title', array( $this, 'meta_box_after_title' ), 10, 1 );

		add_meta_box( 'sealed-box-service-route-data', __( 'REST Details', 'sealedbox' ), array( $this, 'route_details_metabox' ), 'sbx_service_route', 'postbox', 'core' );
	}

	public function meta_box_after_title( $post ) {
        if ( current_user_can('manage_options') ) {

            if ( 'sbx_service_route' === sbx_get_var( $post->post_type ) ) {
                do_meta_boxes( 'sbx_service_route', 'postbox', $post );
            }
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function route_details_metabox( $post ) {
		global $route_post_id, $route_object, $route_type_object, $route_namespace_object, $route_version_object;

		$route_post_id          = $post->ID;
		$route_object           = sbx_get_route( $route_post_id );
		$route_type_object      = get_taxonomy( 'sbx_route_type' );
		$route_namespace_object = get_taxonomy( 'sbx_route_namespace' );
		$route_version_object   = get_taxonomy( 'sbx_route_version' );

		wp_nonce_field( 'sealed_box_save_data', 'sealed_box_meta_nonce' );

		include 'views/html-route-data-panel.php';
	}

	/**
	 * Show tab content/settings.
	 */
	private static function output_tabs() {
		global $route_post_id, $route_object, $route_type_object, $route_namespace_object, $route_version_object;

		foreach ( self::get_data_meta_box_tab_settings() as $tab ) {
			include $tab['view'];
		}
	}

	/**
	 * Return array of tabs to show.
	 *
	 * @return array
	 */
	private static function get_data_meta_box_tab_settings() {
		global $route_post_id, $route_object, $route_type_object, $route_namespace_object, $route_version_object;

		$tabs = apply_filters( 'sealed_box_service_route_data_meta_box_tab_settings', array() );

		// Sort tabs based on priority.
		uasort( $tabs, array( __CLASS__, 'service_route_data_tabs_sort' ) );

		return $tabs;
	}

	/**
	 * Callback to sort route data tabs on priority.
	 *
	 * @since 3.1.0
	 * @param int $a First item.
	 * @param int $b Second item.
	 *
	 * @return bool
	 */
	private static function service_route_data_tabs_sort( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return -1;
		}

		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? -1 : 1;
	}
}
