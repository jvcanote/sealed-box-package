<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @version  1.0.0
 * @package  SealedBox/Classes/Routes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types class.
 */
class SBX_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// actions
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 7 );
		add_action( 'init', array( __CLASS__, 'support_jetpack_omnisearch' ), 9 );
		add_action( 'sealed_box_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		add_action( 'sealed_box_after_register_post_type', array( __CLASS__, 'add_rest_routes' ) );
		add_action( 'sealed_box_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );

		// filters
		add_filter( 'rest_api_allowed_post_types', array( __CLASS__, 'rest_api_allowed_post_types' ) );
		add_filter( 'gutenberg_can_edit_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomy() {

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( taxonomy_exists( 'sbx_route_type' ) ) {
			return;
		}

		do_action( 'sealed_box_register_taxonomy' );

		register_taxonomy(
			'sbx_route_type',
			apply_filters( 'sealed_box_taxonomy_objects_route_type', array( 'sbx_service_route' ) ),
			apply_filters(
				'sealed_box_taxonomy_args_route_type',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => __( 'Services', 'sealedbox' ),
					'labels'                => array(
						'name'              => __( 'Service Type', 'sealedbox' ), // REST Route Service Types
						'singular_name'     => __( 'Service', 'sealedbox' ),
						'menu_name'         => _x( 'Services', 'Admin menu name', 'sealedbox' ),
					),
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => false,
					'show_in_quick_edit'    => true,
					'show_in_rest'          => true,
					'rest_base'             => 'services',
					'rest_controller_class' => 'SBX_REST_Service_Types_Terms_Controller',
					'meta_box_cb'           => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'read_only_taxonomy',
						'delete_terms' => 'read_only_taxonomy',
						'assign_terms' => 'edit_posts',
					),
					'query_var'             => is_admin(),
					'publicly_queryable'    => false,
					'rewrite'               => false,
					'sort'                  => false,
				)
			)
		);

		register_taxonomy(
			'sbx_route_namespace',
			apply_filters( 'sealed_box_taxonomy_objects_route_namespace', array( 'sbx_service_route' ) ),
			apply_filters(
				'sealed_box_taxonomy_args_route_namespace',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => __( 'Namespaces', 'sealedbox' ),
					'labels'                => array(
						'name'              => __( 'Route Namespace', 'sealedbox' ),
						'singular_name'     => __( 'Namespace', 'sealedbox' ),
						'menu_name'         => _x( 'Namespaces', 'Admin menu name', 'sealedbox' ),
						'search_items'      => __( 'Search Namespaces', 'sealedbox' ),
						'all_items'         => __( 'All Namespaces', 'sealedbox' ),
						'edit_item'         => __( 'Edit Namespace', 'sealedbox' ),
						'update_item'       => __( 'Update Namespace', 'sealedbox' ),
						'add_new_item'      => __( 'Add Namespace', 'sealedbox' ),
						'new_item_name'     => __( 'New Namespace', 'sealedbox' ),
					),
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => false,
					'show_in_quick_edit'    => true,
					'show_in_rest'          => false,
					'rest_base'             => false,
					'meta_box_sanitize_cb'  => 'sanitize_title_with_dashes',
					// 'meta_box_cb'           => array( 'SealedBox\\Metabox', 'radio_input_metabox' ),
					'query_var'             => is_admin(),
					'capabilities'          => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'rewrite'               => false,
					'sort'                  => false,
					'public'                => false,
				)
			)
		);

		register_taxonomy(
			'sbx_route_version',
			apply_filters( 'sealed_box_taxonomy_objects_route_version', array( 'sbx_service_route' ) ),
			apply_filters(
				'sealed_box_taxonomy_args_route_version',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => __( 'Versions', 'sealedbox' ),
					'labels'                => array(
						'name'              => __( 'Route Versions', 'sealedbox' ), // REST Route Service Types
						'singular_name'     => __( 'Version', 'sealedbox' ),
						'menu_name'         => _x( 'Versions', 'Admin menu name', 'sealedbox' ),
					),
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => true,
					'show_in_quick_edit'    => true,
					'show_in_rest'          => true,
					'rest_base'             => false,
					'rest_controller_class' => 'SBX_REST_Service_Types_Terms_Controller',
					'meta_box_cb'           => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'read_only_taxonomy',
						'delete_terms' => 'read_only_taxonomy',
						'assign_terms' => 'edit_posts',
					),
					'query_var'             => is_admin(),
					'publicly_queryable'    => false,
					'rewrite'               => false,
					'sort'                  => false,
				)
			)
		);

		do_action( 'sealed_box_after_register_taxonomy' );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_type() {

		if ( ! is_blog_installed() || post_type_exists( 'sbx_service_route' ) ) {
			return;
		}

		do_action( 'sealed_box_register_post_type' );

		register_post_type(
			'sbx_service_route',
			apply_filters(
				'sealed_box_register_post_type_service_route',
				array(
					'labels'              => array(
						'name'                  => __( 'Sealed Box Anonymous Service Routes', 'sealedbox' ),
						'singular_name'         => __( 'Route', 'sealedbox' ),
						'all_items'             => __( 'All Routes', 'sealedbox' ),
						'menu_name'             => _x( 'Sealed Box', 'Admin menu name', 'sealedbox' ),
						'add_new'               => __( 'Add New', 'sealedbox' ),
						'add_new_item'          => __( 'Add new route', 'sealedbox' ),
						'edit'                  => __( 'Edit', 'sealedbox' ),
						'edit_item'             => __( 'Edit route', 'sealedbox' ),
						'new_item'              => __( 'New route', 'sealedbox' ),
						'view_item'             => __( 'View route', 'sealedbox' ),
						'view_items'            => __( 'View routes', 'sealedbox' ),
						'search_items'          => __( 'Search Routes', 'sealedbox' ),
						'not_found'             => __( 'No routes found', 'sealedbox' ),
						'not_found_in_trash'    => __( 'No routes found in trash', 'sealedbox' ),
						'parent'                => __( 'Parent route', 'sealedbox' ),
						'featured_image'        => __( 'Route image', 'sealedbox' ),
						'set_featured_image'    => __( 'Set route image', 'sealedbox' ),
						'remove_featured_image' => __( 'Remove route image', 'sealedbox' ),
						'use_featured_image'    => __( 'Use as route image', 'sealedbox' ),
						'insert_into_item'      => __( 'Insert into route', 'sealedbox' ),
						'uploaded_to_this_item' => __( 'Uploaded to this route', 'sealedbox' ),
						'filter_items_list'     => __( 'Filter routes', 'sealedbox' ),
						'items_list_navigation' => __( 'Routes navigation', 'sealedbox' ),
						'items_list'            => __( 'Routes list', 'sealedbox' ),
					),
					'description'           => __( 'This is where you can add new routes to your Sealed Box anonymous services.', 'sealedbox' ),
					'public'                => false,
					'show_ui'               => true,
					'map_meta_cap'          => true,
					'capabilities'          => array(
						// Meta capabilities.
						// 'edit_post'          => 'edit_' . $singular_base,
						// 'read_post'          => 'read_' . $singular_base,
						// 'delete_post'        => 'delete_' . $singular_base,
						// Primitive capabilities used outside of map_meta_cap():
						// 'edit_posts'         => 'edit_' . $plural_base,
						// 'edit_others_posts'  => 'edit_others_' . $plural_base,
						// 'delete_posts'       => 'delete_' . $plural_base,
						// 'publish_posts'      => 'publish_route',
						// 'read_private_posts' => 'read_private_' . $plural_base,
					),
					'publicly_queryable'    => false,
					'exclude_from_search'   => true,
					'hierarchical'          => false,
					'rewrite'               => false,
					'query_var'             => true,
					'has_archive'           => false,
					'show_in_nav_menus'     => true,
					// 'rest_controller_class' => 'WP_REST_Route_Posts_Controller',
					'show_in_rest'          => true,
					'rest_base'             => 'service',
                    'menu_icon'             => 'dashicons-tide',
					'supports'              => array( 'none' ),
					'register_meta_box_cb'  => array( 'SBX_Meta_Box_Service_Route_Data', 'get_instance' ), // 'capability_type'     => 'edit_posts',
				)
			)
		);

		do_action( 'sealed_box_after_register_post_type' );
	}

	/**
	 * Add REST routes.
	 *
	 * @since 1.0.0
	 */
	public static function add_rest_routes() {
		if ( class_exists( 'SBX_Route_Namespace_Controller' ) ) {
			foreach ( sbx_get_namespace_term_slugs( 'term_id', 'hide_empty=1' ) as $namespace_id => $namespace ) {
				foreach ( sbx_get_version_term_slugs( 'term_id', 'hide_empty=1' ) as $version_id => $version ) {
					if ( sbx_has_term_routes( $namespace_id, $version_id ) ) {
						new SBX_Route_Namespace_Controller( 'sbx_route_namespace', $namespace, $version );
					}
				}
			}
		}
    }

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'sealed_box_queue_flush_rewrite_rules' ) ) {
			update_option( 'sealed_box_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Disable Gutenberg for routes.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
		return 'sbx_service_route' === $post_type ? false : $can_edit;
	}

	/**
	 * Add Route Support to Jetpack Omnisearch.
	 */
	public static function support_jetpack_omnisearch() {
		if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
			// new Jetpack_Omnisearch_Posts( 'sbx_service_route' );
		}
	}

	/**
	 * Added route for Jetpack related posts.
	 *
	 * @param  array $post_types Post types.
	 * @return array
	 */
	public static function rest_api_allowed_post_types( $post_types ) {
		$post_types[] = 'sbx_service_route';
		return $post_types;
	}
}
