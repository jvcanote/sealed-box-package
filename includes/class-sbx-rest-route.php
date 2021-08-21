<?php
/**
 * REST API: WP_REST_Route_Posts_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Core class to access posts via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Route_Posts_Controller extends WP_REST_Controller {
	/**
	 * Post type.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $post_type;

	/**
	 * Instance of a post meta fields object.
	 *
	 * @since 4.7.0
	 * @var WP_REST_Post_Meta_Fields
	 */
	protected $meta;

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type, $namespace = null ) {

		if ( null === $namespace ) {
			$namespaces = sbx_get_namespace_term_slugs( null, 'hide_empty=1' );
			$namespace = array_shift( $namespaces );

			foreach ( $namespaces as $ns ) {
				$controller = new self( $post_type, $ns );
				$controller->register_routes();
			}
		}

		$this->post_type = $post_type;
		$this->namespace = $namespace;
		$this->rest_base = 'service';

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace . '/v1',
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace . '/v1',
			'/' . $this->rest_base . '/(?P<services>([\w-])+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);

		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => __( 'The password for the post if it is password protected.' ),
				'type'        => 'string',
			);
		}

		register_rest_route(
			$this->namespace . '/v1',
			'/' . $this->rest_base . '/(?P<services>([\w-])+).(?P<slug>([\w-])+)',
			array(
				'args'   => array(
					'services' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'string',
					),
					'slug' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass Trash and force deletion.' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		$post_type = get_post_type_object( $this->post_type );

		// if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
		// 	return new WP_Error(
		// 		'rest_forbidden_context',
		// 		__( 'Sorry, you are not allowed to edit posts in this post type.' ),
		// 		array( 'status' => rest_authorization_required_code() )
		// 	);
		// }

		return true;
	}

	/**
	 * Retrieves a collection of posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// Ensure a search string is set in case the orderby is set to 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {

			return new WP_Error(
				'rest_no_search_term_defined',
				__( 'You need to define a search term to order by relevance.' ),
				array( 'status' => 400 )
			);
		}

		// Ensure an include parameter is set in case the orderby is set to 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {

			return new WP_Error(
				'rest_orderby_include_missing_include',
				__( 'You need to define an include parameter to order by include.' ),
				array( 'status' => 400 )
			);
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = array();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {

			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Check for & assign any parameters which require special handling or setting.
		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		if ( isset( $registered['sticky'], $request['sticky'] ) ) {
			$sticky_posts = get_option( 'sticky_posts', array() );

			if ( ! is_array( $sticky_posts ) ) {
				$sticky_posts = array();
			}

			if ( $request['sticky'] ) {
				/*
				 * As post__in will be used to only get sticky posts,
				 * we have to support the case where post__in was already
				 * specified.
				 */
				$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

				/*
				 * If we intersected, but there are no post ids in common,
				 * WP_Query won't return "no posts" for post__in = array()
				 * so we have to fake it a bit.
				 */
				if ( ! $args['post__in'] ) {
					$args['post__in'] = array( 0 );
				}

			} elseif ( $sticky_posts ) {
				/*
				 * As post___not_in will be used to only get posts that
				 * are not sticky, we have to support the case where post__not_in
				 * was already specified.
				 */
				$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
			}
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @since 4.7.0
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args       = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! empty( $request[ 'slug' ] ) ) {
			$query_args['post_name'] = $request['slug'];
		}

		if ( ! empty( $request[ 'services' ] ) ) {
			$query_args['tax_query'] = array( 'relation' => 'AND' );
			$query_args['tax_query'][] = array(
				'taxonomy'         => 'sbx_route_type',
				'field'            => 'slug',
				'terms'            => array( 'sbx_route_type-' . $request[ 'services' ] ),
			);
		} else {
			$query_args['tax_query'] = array();
		}

		$query_args['tax_query'][] = array(
			'taxonomy'         => 'sbx_route_namespace',
			'field'            => 'slug',
			'terms'            => array( 'sbx_route_namespace-' . $this->namespace ),
		);

		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );

		// Allow access to all password protected
		// posts if the context is edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', '__return_false' );
		}

		$posts = array();

		foreach ( $query_result as $post ) {

			if ( ! $this->check_read_permission( $post ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

		// Reset filter.
		if ( 'edit' === $request['context'] ) {
			remove_filter( 'post_password_required', '__return_false' );
		}

		$page        = (int) $query_args['paged'];
		$total_posts = $posts_query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = ceil( $total_posts / (int) $posts_query->query_vars['posts_per_page'] );

		if ( $page > $max_pages && $total_posts > 0 ) {

			return new WP_Error(
				'rest_post_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.' ),
				array( 'status' => 400 )
			);
		}

		$response = rest_ensure_response( $posts );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$base           = add_query_arg( urlencode_deep( $request_params ), rest_url( sprintf( '%s/%s', $this->namespace . '/v1', $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Get the post, if the slug is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $slug Supplied slug.
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $request ) {

		$error = new WP_Error(
			'rest_post_invalid_slug',
			__( 'Invalid post slug.' ),
			array( 'status' => 404 )
		);

		if ( empty( $request['slug'] ) ) {

			return $error;
		}

		// Ensure a search string is set in case the orderby is set to 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {

			return new WP_Error(
				'rest_no_search_term_defined',
				__( 'You need to define a search term to order by relevance.' ),
				array( 'status' => 400 )
			);
		}

		// Ensure an include parameter is set in case the orderby is set to 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {

			return new WP_Error(
				'rest_orderby_include_missing_include',
				__( 'You need to define an include parameter to order by include.' ),
				array( 'status' => 400 )
			);
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = array();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {

			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Check for & assign any parameters which require special handling or setting.
		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		if ( isset( $registered['sticky'], $request['sticky'] ) ) {
			$sticky_posts = get_option( 'sticky_posts', array() );

			if ( ! is_array( $sticky_posts ) ) {
				$sticky_posts = array();
			}

			if ( $request['sticky'] ) {
				/*
				 * As post__in will be used to only get sticky posts,
				 * we have to support the case where post__in was already
				 * specified.
				 */
				$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

				/*
				 * If we intersected, but there are no post ids in common,
				 * WP_Query won't return "no posts" for post__in = array()
				 * so we have to fake it a bit.
				 */
				if ( ! $args['post__in'] ) {
					$args['post__in'] = array( 0 );
				}
			} elseif ( $sticky_posts ) {
				/*
				 * As post___not_in will be used to only get posts that
				 * are not sticky, we have to support the case where post__not_in
				 * was already specified.
				 */
				$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
			}
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @since 4.7.0
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args       = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! empty( $request[ 'slug' ] ) ) {
			$query_args['post_name'] = $request['slug'];
		}

		if ( ! empty( $request[ 'services' ] ) ) {
			$query_args['tax_query'] = array( 'relation' => 'AND' );
			$query_args['tax_query'][] = array(
				'taxonomy'         => 'sbx_route_type',
				'field'            => 'slug',
				'terms'            => array( 'sbx_route_type-' . $request[ 'services' ] ),
			);

		} else {
			$query_args['tax_query'] = array();
		}

		$query_args['tax_query'][] = array(
			'taxonomy'         => 'sbx_route_namespace',
			'field'            => 'slug',
			'terms'            => array( 'sbx_route_namespace-' . $this->namespace ),
		);

		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );

		foreach ( $query_result as $post_result ) {

			if ( $request[ 'slug' ] === $post_result->post_name && $this->post_type === $post_result->post_type ) {
				$post = $post_result;

				break;
			}
		}

		if ( ! isset( $post ) ) {

			return $error;
		}

		return $post;
	}

	/**
	 * Checks if a given request has access to read a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		// $post = $this->get_post( $request );
		// if ( is_wp_error( $post ) ) {
		// 	return $post;
		// }

		// if ( 'edit' === $request['context'] && $post && ! $this->check_update_permission( $post ) ) {
		// 	return new WP_Error(
		// 		'rest_forbidden_context',
		// 		__( 'Sorry, you are not allowed to edit this post.' ),
		// 		array( 'status' => rest_authorization_required_code() )
		// 	);
		// }

		// if ( $post && ! empty( $request['password'] ) ) {
		// 	// Check post password, and return error if invalid.
		// 	if ( ! hash_equals( $post->post_password, $request['password'] ) ) {
		// 		return new WP_Error(
		// 			'rest_post_incorrect_password',
		// 			__( 'Incorrect post password.' ),
		// 			array( 'status' => 403 )
		// 		);
		// 	}
		// }

		// // Allow access to all password protected posts if the context is edit.
		// if ( 'edit' === $request['context'] ) {
		// 	add_filter( 'post_password_required', '__return_false' );
		// }

		// if ( $post ) {
		// 	return $this->check_read_permission( $post );
		// }

		return true;
	}

	/**
	 * Checks if the user can access password-protected content.
	 *
	 * This method determines whether we need to override the regular password
	 * check in core with a filter.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post         $post    Post to check against.
	 * @param WP_REST_Request $request Request data to check.
	 * @return bool True if the user can access password-protected content, otherwise false.
	 */
	public function can_access_password_content( $post, $request ) {
		if ( empty( $post->post_password ) ) {
			// No filter required.
			return false;
		}

		// Edit context always gets access to password-protected posts.
		if ( 'edit' === $request['context'] ) {
			return true;
		}

		// No password, no auth.
		if ( empty( $request['password'] ) ) {
			return false;
		}

		// Double-check the request password.
		return hash_equals( $post->post_password, $request['password'] );
	}

	/**
	 * Retrieves a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		if ( is_post_type_viewable( get_post_type_object( $post->post_type ) ) ) {
			$response->link_header( 'alternate', get_permalink( $post->ID ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Checks if a given request has access to create a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! empty( $request['slug'] ) ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'Cannot create existing post.' ),
				array( 'status' => 400 )
			);
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['sticky'] ) && ! current_user_can( $post_type->cap->edit_others_posts ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
			return new WP_Error(
				'rest_cannot_assign_sticky',
				__( 'Sorry, you are not allowed to make posts sticky.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $this->check_assign_terms_permission( $request ) ) {
			return new WP_Error(
				'rest_cannot_assign_term',
				__( 'Sorry, you are not allowed to assign the provided terms.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Creates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['slug'] ) ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'Cannot create existing post.' ),
				array( 'status' => 400 )
			);
		}

		$prepared_post = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$prepared_post->post_type = $this->post_type;

		$post_id = wp_insert_post( wp_slash( (array) $prepared_post ), true );

		if ( is_wp_error( $post_id ) ) {

			if ( 'db_insert_error' === $post_id->get_error_code() ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}

			return $post_id;
		}

		$post = get_post( $post_id );

		/**
		 * Fires after a single post is created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post         $post     Inserted or updated post object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		do_action( "rest_insert_{$this->post_type}", $post, $request, true );

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post_id );
		}

		if ( ! empty( $schema['properties']['format'] ) && ! empty( $request['format'] ) ) {
			set_post_format( $post, $request['format'] );
		}

		if ( ! empty( $schema['properties']['template'] ) && isset( $request['template'] ) ) {
			$this->handle_template( $request['template'], $post_id, true );
		}

		$terms_update = $this->handle_terms( $post_id, $request );

		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $post_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$post          = get_post( $post_id );
		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Fires after a single post is completely created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Post         $post     Inserted or updated post object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		do_action( "rest_after_insert_{$this->post_type}", $post, $request, true );

		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%s.%s', $this->namespace . '/v1', $this->rest_base, sbx_get_route_term_slug( $post_id, 'sbx_route_type' ), $post->post_name ) ) );

		return $response;
	}

	/**
	 * Checks if a given request has access to update a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$post = $this->get_post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to update posts as this user.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! empty( $request['sticky'] ) && ! current_user_can( $post_type->cap->edit_others_posts ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
			return new WP_Error(
				'rest_cannot_assign_sticky',
				__( 'Sorry, you are not allowed to make posts sticky.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $this->check_assign_terms_permission( $request ) ) {
			return new WP_Error(
				'rest_cannot_assign_term',
				__( 'Sorry, you are not allowed to assign the provided terms.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Updates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$valid_check = $this->get_post( $request );
		if ( is_wp_error( $valid_check ) ) {
			return $valid_check;
		}

		$post = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Convert the post object to an array, otherwise wp_update_post() will expect non-escaped input.
		$post_id = wp_update_post( wp_slash( (array) $post ), true );

		if ( is_wp_error( $post_id ) ) {
			if ( 'db_update_error' === $post_id->get_error_code() ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}

		$post = get_post( $post_id );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
		do_action( "rest_insert_{$this->post_type}", $post, $request, false );

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['format'] ) && ! empty( $request['format'] ) ) {
			set_post_format( $post, $request['format'] );
		}

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post_id );
		}

		if ( ! empty( $schema['properties']['sticky'] ) && isset( $request['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		if ( ! empty( $schema['properties']['template'] ) && isset( $request['template'] ) ) {
			$this->handle_template( $request['template'], $post->ID );
		}

		$terms_update = $this->handle_terms( $post->ID, $request );

		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $post->ID );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$post          = get_post( $post_id );
		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		// Filter is fired in WP_REST_Attachments_Controller subclass.
		if ( 'attachment' === $this->post_type ) {
			$response = $this->prepare_item_for_response( $post, $request );
			return rest_ensure_response( $response );
		}

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
		do_action( "rest_after_insert_{$this->post_type}", $post, $request, false );

		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to delete a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		$post = $this->get_post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $post && ! $this->check_delete_permission( $post ) ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Deletes a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$id    = $post->ID;
		$force = (bool) $request['force'];

		$supports_trash = ( EMPTY_TRASH_DAYS > 0 );

		if ( 'attachment' === $post->post_type ) {
			$supports_trash = $supports_trash && MEDIA_TRASH;
		}

		/**
		 * Filters whether a post is trashable.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * Pass false to disable Trash support for the post.
		 *
		 * @since 4.7.0
		 *
		 * @param bool    $supports_trash Whether the post type support trashing.
		 * @param WP_Post $post           The Post object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "rest_{$this->post_type}_trashable", $supports_trash, $post );

		if ( ! $this->check_delete_permission( $post ) ) {
			return new WP_Error(
				'rest_user_cannot_delete_post',
				__( 'Sorry, you are not allowed to delete this post.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$request->set_param( 'context', 'edit' );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$previous = $this->prepare_item_for_response( $post, $request );
			$result   = wp_delete_post( $id, true );
			$response = new WP_REST_Response();
			$response->set_data(
				array(
					'deleted'  => true,
					'previous' => $previous->get_data(),
				)
			);
		} else {
			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				return new WP_Error(
					'rest_trash_not_supported',
					/* translators: %s: force=true */
					sprintf( __( "The post does not support trashing. Set '%s' to delete." ), 'force=true' ),
					array( 'status' => 501 )
				);
			}

			// Otherwise, only trash if we haven't already.
			if ( 'trash' === $post->post_status ) {
				return new WP_Error(
					'rest_already_trashed',
					__( 'The post has already been deleted.' ),
					array( 'status' => 410 )
				);
			}

			// (Note that internally this falls through to `wp_delete_post()`
			// if the Trash is disabled.)
			$result   = wp_trash_post( $id );
			$post     = get_post( $id );
			$response = $this->prepare_item_for_response( $post, $request );
		}

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The post cannot be deleted.' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires immediately after a single post is deleted or trashed via the REST API.
		 *
		 * They dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post          $post     The deleted or trashed post.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "rest_delete_{$this->post_type}", $post, $response, $request );

		return $response;
	}

	/**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since 4.7.0
	 *
	 * @param array           $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
	 * @param WP_REST_Request $request       Optional. Full details about the request.
	 * @return array Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			/**
			 * Filters the query_vars used in get_items() for the constructed query.
			 *
			 * The dynamic portion of the hook name, `$key`, refers to the query_var key.
			 *
			 * @since 4.7.0
			 *
			 * @param string $value The query_var value.
			 */
			$query_args[ $key ] = apply_filters( "rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( 'post' !== $this->post_type || ! isset( $query_args['ignore_sticky_posts'] ) ) {
			$query_args['ignore_sticky_posts'] = true;
		}

		// Map to proper WP_Query orderby param.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'            => 'ID',
				'include'       => 'post__in',
				'slug'          => 'post_name',
				'include_slugs' => 'post_name__in',
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		return $query_args;
	}

	/**
	 * Checks the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @since 4.7.0
	 *
	 * @param string      $date_gmt GMT publication time.
	 * @param string|null $date     Optional. Local publication time. Default null.
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt );
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return stdClass|WP_Error Post object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_post = new stdClass();

		// Post ID.
		if ( isset( $request['slug'] ) ) {
			$existing_post = $this->get_post( $request );
			if ( is_wp_error( $existing_post ) ) {
				return $existing_post;
			}

			$prepared_post->ID = $existing_post->ID;
		}

		$schema = $this->get_item_schema();

		// Post title.
		if ( ! empty( $schema['properties']['title'] ) && isset( $request['title'] ) ) {
			if ( is_string( $request['title'] ) ) {
				$prepared_post->post_title = $request['title'];
			} elseif ( ! empty( $request['title']['raw'] ) ) {
				$prepared_post->post_title = $request['title']['raw'];
			}
		}

		// Post content.
		if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_post->post_content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_post->post_content = $request['content']['raw'];
			}
		}

		// Post excerpt.
		if ( ! empty( $schema['properties']['excerpt'] ) && isset( $request['excerpt'] ) ) {
			if ( is_string( $request['excerpt'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt'];
			} elseif ( isset( $request['excerpt']['raw'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt']['raw'];
			}
		}

		// Post type.
		if ( empty( $request['slug'] ) ) {
			// Creating new post, use default type for the controller.
			$prepared_post->post_type = $this->post_type;
		} else {
			// Updating a post, use previous type.
			$prepared_post->post_type = get_post_type( $request['slug'] );
		}

		$post_type = get_post_type_object( $prepared_post->post_type );

		// Post status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
			$status = $this->handle_status_param( $request['status'], $post_type );

			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$prepared_post->post_status = $status;
		}

		// Post date.
		if ( ! empty( $schema['properties']['date'] ) && ! empty( $request['date'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date : false;
			$date_data    = rest_get_date_with_gmt( $request['date'] );

			if ( ! empty( $date_data ) && $current_date !== $date_data[0] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		} elseif ( ! empty( $schema['properties']['date_gmt'] ) && ! empty( $request['date_gmt'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date_gmt : false;
			$date_data    = rest_get_date_with_gmt( $request['date_gmt'], true );

			if ( ! empty( $date_data ) && $current_date !== $date_data[1] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		}

		// Sending a null date or date_gmt value resets date and date_gmt to their
		// default values (`0000-00-00 00:00:00`).
		if (
			( ! empty( $schema['properties']['date_gmt'] ) && $request->has_param( 'date_gmt' ) && null === $request['date_gmt'] ) ||
			( ! empty( $schema['properties']['date'] ) && $request->has_param( 'date' ) && null === $request['date'] )
		) {
			$prepared_post->post_date_gmt = null;
			$prepared_post->post_date     = null;
		}

		// Post slug.
		if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
			$prepared_post->post_name = $request['slug'];
		}

		// Author.
		if ( ! empty( $schema['properties']['author'] ) && ! empty( $request['author'] ) ) {
			$post_author = (int) $request['author'];

			if ( get_current_user_id() !== $post_author ) {
				$user_obj = get_userdata( $post_author );

				if ( ! $user_obj ) {
					return new WP_Error(
						'rest_invalid_author',
						__( 'Invalid author ID.' ),
						array( 'status' => 400 )
					);
				}
			}

			$prepared_post->post_author = $post_author;
		}

		// Post password.
		if ( ! empty( $schema['properties']['password'] ) && isset( $request['password'] ) ) {
			$prepared_post->post_password = $request['password'];

			if ( '' !== $request['password'] ) {
				if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A post can not be sticky and have a password.' ),
						array( 'status' => 400 )
					);
				}

				if ( ! empty( $prepared_post->ID ) && is_sticky( $prepared_post->ID ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A sticky post can not be password protected.' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
			if ( ! empty( $prepared_post->ID ) && post_password_required( $prepared_post->ID ) ) {
				return new WP_Error(
					'rest_invalid_field',
					__( 'A password protected post can not be set to sticky.' ),
					array( 'status' => 400 )
				);
			}
		}

		// Parent.
		if ( ! empty( $schema['properties']['parent'] ) && isset( $request['parent'] ) ) {
			if ( 0 === (int) $request['parent'] ) {
				$prepared_post->post_parent = 0;
			} else {
				$parent = get_post( (int) $request['parent'] );

				if ( empty( $parent ) ) {
					return new WP_Error(
						'rest_post_invalid_id',
						__( 'Invalid post parent ID.' ),
						array( 'status' => 400 )
					);
				}

				$prepared_post->post_parent = (int) $parent->ID;
			}
		}

		// Menu order.
		if ( ! empty( $schema['properties']['menu_order'] ) && isset( $request['menu_order'] ) ) {
			$prepared_post->menu_order = (int) $request['menu_order'];
		}

		// Comment status.
		if ( ! empty( $schema['properties']['comment_status'] ) && ! empty( $request['comment_status'] ) ) {
			$prepared_post->comment_status = $request['comment_status'];
		}

		// Ping status.
		if ( ! empty( $schema['properties']['ping_status'] ) && ! empty( $request['ping_status'] ) ) {
			$prepared_post->ping_status = $request['ping_status'];
		}

		if ( ! empty( $schema['properties']['template'] ) ) {
			// Force template to null so that it can be handled exclusively by the REST controller.
			$prepared_post->page_template = null;
		}

		/**
		 * Filters a post before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param stdClass        $prepared_post An object representing a single post prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "rest_pre_insert_{$this->post_type}", $prepared_post, $request );

	}

	/**
	 * Determines validity and normalizes the given status parameter.
	 *
	 * @since 4.7.0
	 *
	 * @param string       $post_status Post status.
	 * @param WP_Post_Type $post_type   Post type.
	 * @return string|WP_Error Post status or WP_Error if lacking the proper permission.
	 */
	protected function handle_status_param( $post_status, $post_type ) {

		switch ( $post_status ) {
			case 'draft':
			case 'pending':
				break;
			case 'private':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to create private posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			case 'publish':
			case 'future':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to publish posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			default:
				if ( ! get_post_status_object( $post_status ) ) {
					$post_status = 'draft';
				}
				break;
		}

		return $post_status;
	}

	/**
	 * Determines the featured media based on a request param.
	 *
	 * @since 4.7.0
	 *
	 * @param int $featured_media Featured Media ID.
	 * @param int $post_id        Post ID.
	 * @return bool|WP_Error Whether the post thumbnail was successfully deleted, otherwise WP_Error.
	 */
	protected function handle_featured_media( $featured_media, $post_id ) {

		$featured_media = (int) $featured_media;
		if ( $featured_media ) {
			$result = set_post_thumbnail( $post_id, $featured_media );
			if ( $result ) {
				return true;
			} else {
				return new WP_Error(
					'rest_invalid_featured_media',
					__( 'Invalid featured media ID.' ),
					array( 'status' => 400 )
				);
			}
		} else {
			return delete_post_thumbnail( $post_id );
		}

	}

	/**
	 * Check whether the template is valid for the given post.
	 *
	 * @since 4.9.0
	 *
	 * @param string          $template Page template filename.
	 * @param WP_REST_Request $request  Request.
	 * @return bool|WP_Error True if template is still valid or if the same as existing value, or false if template not supported.
	 */
	public function check_template( $template, $request ) {

		if ( ! $template ) {
			return true;
		}
		$post = $this->get_post( $request );
		$post_id = $post ? $post->ID : 0;

		if ( $post_id ) {
			$current_template = get_page_template_slug( $post_id );
		} else {
			$current_template = '';
		}

		// Always allow for updating a post to the same template, even if that template is no longer supported.
		if ( $template === $current_template ) {
			return true;
		}

		// If this is a create request, get_post() will return null and wp theme will fallback to the passed post type.
		$allowed_templates = wp_get_theme()->get_page_templates( get_post( $post_id ), $this->post_type );

		if ( isset( $allowed_templates[ $template ] ) ) {
			return true;
		}

		return new WP_Error(
			'rest_invalid_param',
			/* translators: 1: Parameter, 2: List of valid values. */
			sprintf( __( '%1$s is not one of %2$s.' ), 'template', implode( ', ', array_keys( $allowed_templates ) ) )
		);
	}

	/**
	 * Sets the template for a post.
	 *
	 * @since 4.7.0
	 * @since 4.9.0 Added the `$validate` parameter.
	 *
	 * @param string  $template Page template filename.
	 * @param integer $post_id  Post ID.
	 * @param bool    $validate Whether to validate that the template selected is valid.
	 */
	public function handle_template( $template, $post_id, $validate = false ) {

		if ( $validate && ! array_key_exists( $template, wp_get_theme()->get_page_templates( get_post( $post_id ) ) ) ) {
			$template = '';
		}

		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	/**
	 * Updates the post's terms from a REST request.
	 *
	 * @since 4.7.0
	 *
	 * @param int             $post_id The post ID to update the terms form.
	 * @param WP_REST_Request $request The request object with post and terms data.
	 * @return null|WP_Error WP_Error on an error assigning any of the terms, otherwise null.
	 */
	protected function handle_terms( $post_id, $request ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			$result = wp_set_object_terms( $post_id, $request[ $base ], $taxonomy->name );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
	}

	/**
	 * Checks whether current user can assign all terms sent with the current request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request The request object with post and terms data.
	 * @return bool Whether the current user can assign the provided terms.
	 */
	protected function check_assign_terms_permission( $request ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			foreach ( $request[ $base ] as $slug ) {
				// Invalid terms will be rejected later.
				$term = get_term_by( 'slug', $slug, $taxonomy->name );
				if ( ! $term ) {
					continue;
				}

				if ( ! current_user_can( 'assign_term', $term->term_id ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Checks if a given post type can be viewed or managed.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post_Type|string $post_type Post type name or object.
	 * @return bool Whether the post type is allowed in REST.
	 */
	protected function check_is_post_type_allowed( $post_type ) {
		if ( ! is_object( $post_type ) ) {
			$post_type = get_post_type_object( $post_type );
		}

		if ( ! empty( $post_type ) && ! empty( $post_type->show_in_rest ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a post can be read.
	 *
	 * Correctly handles posts with the inherit status.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );
			if ( $parent ) {
				return $this->check_read_permission( $parent );
			}
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a post can be edited.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be edited.
	 */
	protected function check_update_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->edit_post, $post->ID );
	}

	/**
	 * Checks if a post can be created.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be created.
	 */
	protected function check_create_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->create_posts );
	}

	/**
	 * Checks if a post can be deleted.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the post can be deleted.
	 */
	protected function check_delete_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->delete_post, $post->ID );
	}

	/**
	 * Prepares a single post output for response.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$GLOBALS['post'] = $post;

		setup_postdata( $post );

		$fields = $this->get_fields_for_response( $request );

		// Base fields for every post.
		$data = array();

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $post->ID;
		}

		if ( rest_is_field_included( 'date', $fields ) ) {
			$data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );
		}

		if ( rest_is_field_included( 'date_gmt', $fields ) ) {
			/*
			 * For drafts, `post_date_gmt` may not be set, indicating that the date
			 * of the draft should be updated each time it is saved (see #38883).
			 * In this case, shim the value based on the `post_date` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				$post_date_gmt = get_gmt_from_date( $post->post_date );
			} else {
				$post_date_gmt = $post->post_date_gmt;
			}
			$data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
		}

		if ( rest_is_field_included( 'guid', $fields ) ) {
			$data['guid'] = array(
				/** This filter is documented in wp-includes/post-template.php */
				'rendered' => apply_filters( 'get_the_guid', $post->guid, $post->ID ),
				'raw'      => $post->guid,
			);
		}

		if ( rest_is_field_included( 'modified', $fields ) ) {
			$data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );
		}

		if ( rest_is_field_included( 'modified_gmt', $fields ) ) {
			/*
			 * For drafts, `post_modified_gmt` may not be set (see `post_date_gmt` comments
			 * above). In this case, shim the value based on the `post_modified` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
				$post_modified_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) - ( get_option( 'gmt_offset' ) * 3600 ) );
			} else {
				$post_modified_gmt = $post->post_modified_gmt;
			}
			$data['modified_gmt'] = $this->prepare_date_response( $post_modified_gmt );
		}

		if ( rest_is_field_included( 'password', $fields ) ) {
			$data['password'] = $post->post_password;
		}

		if ( rest_is_field_included( 'slug', $fields ) ) {
			$data['slug'] = $post->post_name;
		}

		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = $post->post_status;
		}

		if ( rest_is_field_included( 'type', $fields ) ) {
			$data['type'] = $post->post_type;
		}

		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = get_permalink( $post->ID );
		}

		if ( rest_is_field_included( 'title', $fields ) ) {
			$data['title'] = array();
		}
		if ( rest_is_field_included( 'title.raw', $fields ) ) {
			$data['title']['raw'] = $post->post_title;
		}
		if ( rest_is_field_included( 'title.rendered', $fields ) ) {
			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );

			$data['title']['rendered'] = get_the_title( $post->ID );

			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
		}

		$has_password_filter = false;

		if ( $this->can_access_password_content( $post, $request ) ) {
			// Allow access to the post, permissions already checked before.
			add_filter( 'post_password_required', '__return_false' );

			$has_password_filter = true;
		}

		if ( rest_is_field_included( 'content', $fields ) ) {
			$data['content'] = array();
		}
		if ( rest_is_field_included( 'content.raw', $fields ) ) {
			$data['content']['raw'] = $post->post_content;
		}
		if ( rest_is_field_included( 'content.rendered', $fields ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			$data['content']['rendered'] = post_password_required( $post ) ? '' : apply_filters( 'the_content', $post->post_content );
		}
		if ( rest_is_field_included( 'content.protected', $fields ) ) {
			$data['content']['protected'] = (bool) $post->post_password;
		}
		if ( rest_is_field_included( 'content.block_version', $fields ) ) {
			$data['content']['block_version'] = block_version( $post->post_content );
		}

		if ( rest_is_field_included( 'excerpt', $fields ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );

			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'the_excerpt', $excerpt );

			$data['excerpt'] = array(
				'raw'       => $post->post_excerpt,
				'rendered'  => post_password_required( $post ) ? '' : $excerpt,
				'protected' => (bool) $post->post_password,
			);
		}

		if ( $has_password_filter ) {
			// Reset filter.
			remove_filter( 'post_password_required', '__return_false' );
		}

		if ( rest_is_field_included( 'author', $fields ) ) {
			$data['author'] = (int) $post->post_author;
		}

		if ( rest_is_field_included( 'featured_media', $fields ) ) {
			$data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
		}

		if ( rest_is_field_included( 'parent', $fields ) ) {
			$data['parent'] = (int) $post->post_parent;
		}

		if ( rest_is_field_included( 'menu_order', $fields ) ) {
			$data['menu_order'] = (int) $post->menu_order;
		}

		if ( rest_is_field_included( 'comment_status', $fields ) ) {
			$data['comment_status'] = $post->comment_status;
		}

		if ( rest_is_field_included( 'ping_status', $fields ) ) {
			$data['ping_status'] = $post->ping_status;
		}

		if ( rest_is_field_included( 'sticky', $fields ) ) {
			$data['sticky'] = is_sticky( $post->ID );
		}

		if ( rest_is_field_included( 'template', $fields ) ) {
			$template = get_page_template_slug( $post->ID );
			if ( $template ) {
				$data['template'] = $template;
			} else {
				$data['template'] = '';
			}
		}

		if ( rest_is_field_included( 'format', $fields ) ) {
			$data['format'] = get_post_format( $post->ID );

			// Fill in blank post format.
			if ( empty( $data['format'] ) ) {
				$data['format'] = 'standard';
			}
		}

		if ( rest_is_field_included( 'meta', $fields ) ) {
			$data['meta'] = $this->meta->get_value( $post->ID, $request );
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( rest_is_field_included( $base, $fields ) ) {
				$terms         = get_the_terms( $post, $taxonomy->name );
				$data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'slug' ) ) : array();
			}
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$permalink_template_requested = rest_is_field_included( 'permalink_template', $fields );
			$generated_slug_requested     = rest_is_field_included( 'generated_slug', $fields );

			if ( $permalink_template_requested || $generated_slug_requested ) {
				if ( ! function_exists( 'get_sample_permalink' ) ) {
					require_once ABSPATH . 'wp-admin/includes/post.php';
				}

				$sample_permalink = get_sample_permalink( $post->ID, $post->post_title, '' );

				if ( $permalink_template_requested ) {
					$data['permalink_template'] = $sample_permalink[0];
				}

				if ( $generated_slug_requested ) {
					$data['generated_slug'] = $sample_permalink[1];
				}
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $post );
		$response->add_links( $links );

		if ( ! empty( $links['self']['href'] ) ) {
			$actions = $this->get_available_actions( $post, $request );

			$self = $links['self']['href'];

			foreach ( $actions as $rel ) {
				$response->add_link( $rel, $self );
			}
		}

		/**
		 * Filters the post data for a response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     Post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Overwrites the default protected title format.
	 *
	 * By default, WordPress will show password protected posts with a title of
	 * "Protected: %s", as the REST API communicates the protected status of a post
	 * in a machine readable format, we remove the "Protected: " prefix.
	 *
	 * @since 4.7.0
	 *
	 * @return string Protected title format.
	 */
	public function protected_title_format() {
		return '%s';
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$base = sprintf( '%s/%s/%s', $this->namespace . '/v1', $this->rest_base, sbx_get_route_term_slug( $post->ID, 'sbx_route_type' ) );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . '.' . $post->post_name ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'about'      => array(
				'href' => rest_url(  'wp/v2' . '/types/' . $this->post_type ),
			),
		);

		$post_type_obj = get_post_type_object( $post->post_type );

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = rest_url( $this->namespace  . '/v1'. '/' . $tax_base . '/' . sbx_get_route_term_slug( $post->ID, $tax ) /* . '.' . $post->post_name */ );

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return $links;
	}

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 4.9.8
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $post, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		$post_type = get_post_type_object( $post->post_type );

		if ( 'attachment' !== $this->post_type && current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		if ( current_user_can( 'unfiltered_html' ) ) {
			$rels[] = 'https://api.w.org/action-unfiltered-html';
		}

		if ( 'post' === $post_type->name ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) && current_user_can( $post_type->cap->publish_posts ) ) {
				$rels[] = 'https://api.w.org/action-sticky';
			}
		}

		if ( post_type_supports( $post_type->name, 'author' ) ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) ) {
				$rels[] = 'https://api.w.org/action-assign-author';
			}
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base   = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;
			$create_cap = is_taxonomy_hierarchical( $tax->name ) ? $tax->cap->edit_terms : $tax->cap->assign_terms;

			if ( current_user_can( $create_cap ) ) {
				$rels[] = 'https://api.w.org/action-create-' . $tax_base;
			}

			if ( current_user_can( $tax->cap->assign_terms ) ) {
				$rels[] = 'https://api.w.org/action-assign-' . $tax_base;
			}
		}

		return $rels;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			// Base properties for every Post.
			'properties' => array(
				'date'         => array(
					'description' => __( "The date the object was published, in the site's timezone." ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'date_gmt'     => array(
					'description' => __( 'The date the object was published, as GMT.' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'guid'         => array(
					'description' => __( 'The globally unique identifier for the object.' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the object, as it exists in the database.' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
							'readonly'    => true,
						),
						'rendered' => array(
							'description' => __( 'GUID for the object, transformed for display.' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'id'           => array(
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'link'         => array(
					'description' => __( 'URL to the object.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'modified'     => array(
					'description' => __( "The date the object was last modified, in the site's timezone." ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt' => array(
					'description' => __( 'The date the object was last modified, as GMT.' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'         => array(
					'description' => __( 'An alphanumeric identifier for the object unique to its type.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'status'       => array(
					'description' => __( 'A named status for the object.' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'type'         => array(
					'description' => __( 'Type of Post for the object.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'password'     => array(
					'description' => __( 'A password to protect access to the content and excerpt.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
			),
		);

		$post_type_obj = get_post_type_object( $this->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$schema['properties']['permalink_template'] = array(
				'description' => __( 'Permalink template for the object.' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['generated_slug'] = array(
				'description' => __( 'Slug automatically generated from the object title.' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);
		}

		if ( $post_type_obj->hierarchical ) {
			$schema['properties']['parent'] = array(
				'description' => __( 'The ID for the parent of the object.' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$post_type_attributes = array(
			'title',
			'editor',
			'author',
			'excerpt',
			'thumbnail',
			'comments',
			'revisions',
			'page-attributes',
			'post-formats',
			'custom-fields',
		);
		$fixed_schemas        = array(
			'post'       => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'comments',
				'revisions',
				'post-formats',
				'custom-fields',
			),
			'page'       => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'comments',
				'revisions',
				'page-attributes',
				'custom-fields',
			),
			'attachment' => array(
				'title',
				'author',
				'comments',
				'revisions',
				'custom-fields',
			),
		);
		foreach ( $post_type_attributes as $attribute ) {
			if ( isset( $fixed_schemas[ $this->post_type ] ) && ! in_array( $attribute, $fixed_schemas[ $this->post_type ], true ) ) {
				continue;
			} elseif ( ! isset( $fixed_schemas[ $this->post_type ] ) && ! post_type_supports( $this->post_type, $attribute ) ) {
				continue;
			}

			switch ( $attribute ) {

				case 'title':
					$schema['properties']['title'] = array(
						'description' => __( 'The title for the object.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title for the object, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML title for the object, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'editor':
					$schema['properties']['content'] = array(
						'description' => __( 'The content for the object.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'           => array(
								'description' => __( 'Content for the object, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'      => array(
								'description' => __( 'HTML content for the object, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'block_version' => array(
								'description' => __( 'Version of the content block format used by the object.' ),
								'type'        => 'integer',
								'context'     => array( 'edit' ),
								'readonly'    => true,
							),
							'protected'     => array(
								'description' => __( 'Whether the content is protected with a password.' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'author':
					$schema['properties']['author'] = array(
						'description' => __( 'The ID for the author of the object.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'excerpt':
					$schema['properties']['excerpt'] = array(
						'description' => __( 'The excerpt for the object.' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'       => array(
								'description' => __( 'Excerpt for the object, as it exists in the database.' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'  => array(
								'description' => __( 'HTML excerpt for the object, transformed for display.' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
							'protected' => array(
								'description' => __( 'Whether the excerpt is protected with a password.' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'thumbnail':
					$schema['properties']['featured_media'] = array(
						'description' => __( 'The ID of the featured media for the object.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'comments':
					$schema['properties']['comment_status'] = array(
						'description' => __( 'Whether or not comments are open on the object.' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					$schema['properties']['ping_status']    = array(
						'description' => __( 'Whether or not the object can be pinged.' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'page-attributes':
					$schema['properties']['menu_order'] = array(
						'description' => __( 'The order of the object in relation to other object of its type.' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'post-formats':
					// Get the native post formats and remove the array keys.
					$formats = array_values( get_post_format_slugs() );

					$schema['properties']['format'] = array(
						'description' => __( 'The format for the object.' ),
						'type'        => 'string',
						'enum'        => $formats,
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'custom-fields':
					$schema['properties']['meta'] = $this->meta->get_field_schema();
					break;

			}
		}

		if ( 'post' === $this->post_type ) {
			$schema['properties']['sticky'] = array(
				'description' => __( 'Whether or not the object should be treated as sticky.' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema['properties']['template'] = array(
			'description' => __( 'The theme file to use to display the object.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'validate_callback' => array( $this, 'check_template' ),
			),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( array_key_exists( $base, $schema['properties'] ) ) {
				$taxonomy_field_name_with_conflict = ! empty( $taxonomy->rest_base ) ? 'rest_base' : 'name';
				_doing_it_wrong(
					'register_taxonomy',
					sprintf(
						/* translators: 1. The taxonomy name, 2. The property name, either 'rest_base' or 'name', 3. The conflicting value. */
						__( 'The "%1$s" taxonomy "%2$s" property (%3$s) conflicts with an existing property on the REST API Posts Controller. Specify a custom "rest_base" when registering the taxonomy to avoid this error.' ),
						$taxonomy->name,
						$taxonomy_field_name_with_conflict,
						$base
					),
					'5.4.0'
				);
			}

			$schema['properties'][ $base ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( __( 'The terms assigned to the object in the %s taxonomy.' ), $taxonomy->name ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema_links = $this->get_schema_links();

		if ( $schema_links ) {
			$schema['links'] = $schema_links;
		}

		// Take a snapshot of which fields are in the schema pre-filtering.
		$schema_fields = array_keys( $schema['properties'] );

		/**
		 * Filter the post's schema.
		 *
		 * The dynamic portion of the filter, `$this->post_type`, refers to the
		 * post type slug for the controller.
		 *
		 * @since 5.4.0
		 *
		 * @param array $schema Item schema data.
		 */
		$schema = apply_filters( "rest_{$this->post_type}_item_schema", $schema );

		// Emit a _doing_it_wrong warning if user tries to add new properties using this filter.
		$new_fields = array_diff( array_keys( $schema['properties'] ), $schema_fields );
		if ( count( $new_fields ) > 0 ) {
			_doing_it_wrong( __METHOD__, __( 'Please use register_rest_field to add new schema properties.' ), '5.4.0' );
		}

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieve Link Description Objects that should be added to the Schema for the posts collection.
	 *
	 * @since 4.9.8
	 *
	 * @return array
	 */
	protected function get_schema_links() {

		$href = rest_url( "{$this->namespace}/v1/{$this->rest_base}/{services}.{slug}" );

		$links = array();

		if ( 'attachment' !== $this->post_type ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-publish',
				'title'        => __( 'The current user can publish this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type' => 'string',
							'enum' => array( 'publish', 'future' ),
						),
					),
				),
			);
		}

		$links[] = array(
			'rel'          => 'https://api.w.org/action-unfiltered-html',
			'title'        => __( 'The current user can post unfiltered HTML markup and JavaScript.' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'raw' => array(
							'type' => 'string',
						),
					),
				),
			),
		);

		if ( 'post' === $this->post_type ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-sticky',
				'title'        => __( 'The current user can sticky this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'sticky' => array(
							'type' => 'boolean',
						),
					),
				),
			);
		}

		if ( post_type_supports( $this->post_type, 'author' ) ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-author',
				'title'        => __( 'The current user can change the author on this post.' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'author' => array(
							'type' => 'integer',
						),
					),
				),
			);
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;

			/* translators: %s: Taxonomy name. */
			$assign_title = sprintf( __( 'The current user can assign terms in the %s taxonomy.' ), $tax->name );
			/* translators: %s: Taxonomy name. */
			$create_title = sprintf( __( 'The current user can create terms in the %s taxonomy.' ), $tax->name );

			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-' . $tax_base,
				'title'        => $assign_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
				),
			);

			$links[] = array(
				'rel'          => 'https://api.w.org/action-create-' . $tax_base,
				'title'        => $create_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
				),
			);
		}

		return $links;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['after'] = array(
			'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		if ( post_type_supports( $this->post_type, 'author' ) ) {
			$query_params['author']         = array(
				'description' => __( 'Limit result set to posts assigned to specific authors.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
			$query_params['author_exclude'] = array(
				'description' => __( 'Ensure result set excludes posts assigned to specific authors.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		$query_params['before'] = array(
			'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['exclude'] = array(
			'description' => __( 'Ensure result set excludes specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		if ( 'page' === $this->post_type || post_type_supports( $this->post_type, 'page-attributes' ) ) {
			$query_params['menu_order'] = array(
				'description' => __( 'Limit result set to posts with a specific menu_order value.' ),
				'type'        => 'integer',
			);
		}

		$query_params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.' ),
			'type'        => 'integer',
		);

		$query_params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.' ),
			'type'        => 'string',
			'default'     => 'date',
			'enum'        => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
			),
		);

		if ( 'page' === $this->post_type || post_type_supports( $this->post_type, 'page-attributes' ) ) {
			$query_params['orderby']['enum'][] = 'menu_order';
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( $post_type->hierarchical || 'attachment' === $this->post_type ) {
			$query_params['parent']         = array(
				'description' => __( 'Limit result set to items with particular parent IDs.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
			$query_params['parent_exclude'] = array(
				'description' => __( 'Limit result set to all items except those of a particular parent ID.' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		$query_params['slug'] = array(
			'description'       => __( 'Limit result set to posts with one or more specific slugs.' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
			),
			'sanitize_callback' => 'wp_parse_slug_list',
		);

		$query_params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to posts assigned one or more statuses.' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array_merge( array_keys( get_post_stati() ), array( 'any' ) ),
				'type' => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! empty( $taxonomies ) ) {
			$query_params['tax_relation'] = array(
				'description' => __( 'Limit result set based on relationship between multiple taxonomies.' ),
				'type'        => 'string',
				'enum'        => array( 'AND', 'OR' ),
				'default'     => 'AND',
			);
		}

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$query_params[ $base ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( __( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'default'     => array(),
			);

			$query_params[ $base . '_exclude' ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( __( 'Limit result set to all items except those that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'default'     => array(),
			);
		}

		$query_params[ 'sbx_route_namespace' ] = array(
			/* translators: %s: Taxonomy name. */
			'description' => sprintf( __( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.' ), $base ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'default'     => array( 'sbx_route_namespace-' . $this->namespace ),
		);

		if ( 'post' === $this->post_type ) {
			$query_params['sticky'] = array(
				'description' => __( 'Limit result set to items that are sticky.' ),
				'type'        => 'boolean',
			);
		}

		$query_params[ 'services' ] = array(
			'description' => __( 'Unique identifier for the object.' ),
			'type'        => 'string',
		);
		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * The dynamic part of the filter `$this->post_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->post_type}_query` filter to set WP_Query parameters.
		 *
		 * @since 4.7.0
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 * @param WP_Post_Type $post_type    Post type object.
		 */
		return apply_filters( "rest_{$this->post_type}_collection_params", $query_params, $post_type );
	}

	/**
	 * Sanitizes and validates the list of post statuses, including whether the
	 * user can query private statuses.
	 *
	 * @since 4.7.0
	 *
	 * @param string|array    $statuses  One or more post statuses.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Additional parameter to pass to validation.
	 * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
	 */
	public function sanitize_post_statuses( $statuses, $request, $parameter ) {
		$statuses = wp_parse_slug_list( $statuses );

		// The default status is different in WP_REST_Attachments_Controller.
		$attributes     = $request->get_attributes();
		$default_status = $attributes['args']['status']['default'];

		foreach ( $statuses as $status ) {
			if ( $status === $default_status ) {
				continue;
			}

			$post_type_obj = get_post_type_object( $this->post_type );

			if ( current_user_can( $post_type_obj->cap->edit_posts ) || 'private' === $status && current_user_can( $post_type_obj->cap->read_private_posts ) ) {
				$result = rest_validate_request_arg( $status, $request, $parameter );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			} else {
				return new WP_Error(
					'rest_forbidden_status',
					__( 'Status is forbidden.' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
		}

		return $statuses;
	}
}

?><?php
/**
 * REST API: SBX_REST_Service_Types_Terms_Controller class
 *
 * @package SealedBox
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Core class used to managed terms associated with a taxonomy via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class SBX_REST_Service_Types_Terms_Controller extends WP_REST_Controller {

	/**
	 * Taxonomy key.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * The service types of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $service_types;

	/**
	 * The namespaces of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $namespaces;

	/**
	 * The version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $version;

	/**
	 * The versions of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $versions;

	/**
	 * Instance of a term meta fields object.
	 *
	 * @since 4.7.0
	 * @var WP_REST_Term_Meta_Fields
	 */
	protected $meta;

	/**
	 * Column to have the terms be sorted by.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $sort_column;

	/**
	 * Number of terms that were found.
	 *
	 * @since 4.7.0
	 * @var int
	 */
	protected $total_terms;

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 *
	 * @param string $taxonomy Taxonomy key.
	 */
	public function __construct( $taxonomy, $namespace = null, $version = null ) {
		if ( is_string( $version ) ) {
			$this->version = $version;
		}
		if ( null === $namespace ) {
			$namespaces      = sbx_get_namespace_term_slugs( 'term_id', 'hide_empty=1' );
			$versions        = sbx_get_version_term_slugs( 'term_id', 'hide_empty=1' );
			$this->namespace = $namespace = array_shift( $namespaces );
			if ( null === $this->version ) {
				$this->version = array_shift( $versions );
			}
			foreach ( $namespaces as $namespace_id => $namespace ) {
				if ( ! empty( $versions ) ) {
					foreach ( $versions as $version_id => $version ) {
						if ( sbx_has_term_routes( $namespace_id, $version_id ) ) {
							$controller = new self( $taxonomy, $namespace, $version );
							$controller->register_routes();
						}
					}
				} else {
					$controller = new self( $taxonomy, $namespace, $version );
					$controller->register_routes();
				}
			}
		} else {
			$this->namespace = $namespace;
		}
		$this->taxonomy  = $taxonomy;
		$this->rest_base = 'services';

		$this->meta = new WP_REST_Term_Meta_Fields( $taxonomy );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		$rest_base = ltrim( $this->rest_base, '/' );
		$namespace = rtrim( $this->namespace . '/' . $this->version, '/' );

		register_rest_route(
			$namespace,
			'/' . $rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $rest_base . '/(?P<slug>([\w-])+)',
			array(
				'args'   => array(
					'slug' => array(
						'type'              => 'string',
						'description'       => __( 'Unique identifier for the term.' ),
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Required to be true, as terms do not support trashing.' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a request has access to read terms in the specified taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access, otherwise false or WP_Error object.
	 */
	public function get_items_permissions_check( $request ) {
		$tax_obj = get_taxonomy( $this->taxonomy );

		if ( ! $tax_obj || ! $this->check_is_taxonomy_allowed( $this->taxonomy ) ) {
			return false;
		}

		if ( 'edit' === $request['context'] && ! current_user_can( $tax_obj->cap->edit_terms ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit terms in this taxonomy.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Retrieves terms associated with a taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'exclude'    => 'exclude',
			'include'    => 'include',
			'order'      => 'order',
			'orderby'    => 'orderby',
			'post'       => 'post',
			'hide_empty' => 'hide_empty',
			'per_page'   => 'number',
			'search'     => 'search',
			'slug'       => 'slug',
		);

		$prepared_args = array( 'taxonomy' => $this->taxonomy );

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $prepared_args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( isset( $prepared_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'include_slugs' => 'slug__in',
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$prepared_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
		}

		$taxonomy_obj = get_taxonomy( $this->taxonomy );

		if ( $taxonomy_obj->hierarchical && isset( $registered['parent'], $request['parent'] ) ) {
			if ( 0 === $request['parent'] ) {
				// Only query top-level terms.
				$prepared_args['parent'] = 0;
			} else {
				if ( $request['parent'] ) {
					$prepared_args['parent'] = $request['parent'];
				}
			}
		}

		/**
		 * Filters the query arguments before passing them to get_terms().
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * Enables adding extra arguments or setting defaults for a terms
		 * collection request.
		 *
		 * @since 4.7.0
		 *
		 * @link https://developer.wordpress.org/reference/functions/get_terms/
		 *
		 * @param array           $prepared_args Array of arguments to be
		 *                                       passed to get_terms().
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( "rest_{$this->taxonomy}_query", $prepared_args, $request );

		if ( ! empty( $prepared_args['post'] ) ) {
			$query_result = wp_get_object_terms( $prepared_args['post'], $this->taxonomy, $prepared_args );

			// Used when calling wp_count_terms() below.
			$prepared_args['object_ids'] = $prepared_args['post'];
		} else {
			$query_result = get_terms( $prepared_args );
		}

		$count_args = $prepared_args;

		unset( $count_args['number'], $count_args['offset'] );

		$total_terms = wp_count_terms( $this->taxonomy, $count_args );

		// wp_count_terms() can return a falsy value when the term has no children.
		if ( ! $total_terms ) {
			$total_terms = 0;
		}

		$response = array();

		foreach ( $query_result as $term ) {
			$data       = $this->prepare_item_for_response( $term, $request );
			$response[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response );

		// Store pagination values for headers.
		$per_page = (int) $prepared_args['number'];
		$page     = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

		$response->header( 'X-WP-Total', (int) $total_terms );

		$max_pages = ceil( $total_terms / $per_page );

		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( urlencode_deep( $request->get_query_params() ), rest_url( $this->namespace . '/v1' . '/' . $this->rest_base ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Get the term, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param string $slug Supplied slug.
	 * @return WP_Term|WP_Error Term object if slug is valid, WP_Error otherwise.
	 */
	protected function get_term( $slug ) {
		$error = new WP_Error(
			'rest_term_invalid',
			__( 'Term does not exist.' ),
			array( 'status' => 404 )
		);

		if ( ! $this->check_is_taxonomy_allowed( $this->taxonomy ) ) {
			return $error;
		}

		if ( empty( $slug ) ) {
			return $error;
		}

		$term = get_term_by( 'slug', $slug, $this->taxonomy );
		if ( empty( $term ) || $term->taxonomy !== $this->taxonomy ) {
			return $error;
		}

		return $term;
	}

	/**
	 * Checks if a request has access to read or edit the specified term.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, otherwise false or WP_Error object.
	 */
	public function get_item_permissions_check( $request ) {
		$term = $this->get_term( $request['slug'] );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		if ( 'edit' === $request['context'] && ! current_user_can( 'edit_term', $term->term_id ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit this term.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Gets a single term from a taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$term = $this->get_term( $request['slug'] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$response = $this->prepare_item_for_response( $term, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Checks if a request has access to create a term.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to create items, false or WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! $this->check_is_taxonomy_allowed( $this->taxonomy ) ) {
			return false;
		}

		$taxonomy_obj = get_taxonomy( $this->taxonomy );

		if ( ( is_taxonomy_hierarchical( $this->taxonomy )
				&& ! current_user_can( $taxonomy_obj->cap->edit_terms ) )
			|| ( ! is_taxonomy_hierarchical( $this->taxonomy )
				&& ! current_user_can( $taxonomy_obj->cap->assign_terms ) ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create terms in this taxonomy.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Creates a single term in a taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error(
					'rest_taxonomy_not_hierarchical',
					__( 'Cannot set parent term, taxonomy is not hierarchical.' ),
					array( 'status' => 400 )
				);
			}

			$parent = get_term( (int) $request['parent'], $this->taxonomy );

			if ( ! $parent ) {
				return new WP_Error(
					'rest_term_invalid',
					__( 'Parent term does not exist.' ),
					array( 'status' => 400 )
				);
			}
		}

		$prepared_term = $this->prepare_item_for_database( $request );

		$term = wp_insert_term( wp_slash( $prepared_term->name ), $this->taxonomy, wp_slash( (array) $prepared_term ) );
		if ( is_wp_error( $term ) ) {
			/*
			 * If we're going to inform the client that the term already exists,
			 * give them the identifier for future use.
			 */
			$term_id = $term->get_error_data( 'term_exists' );
			if ( $term_id ) {
				$existing_term = get_term( $term_id, $this->taxonomy );
				$term->add_data( $existing_term->term_id, 'term_exists' );
				$term->add_data(
					array(
						'status'  => 400,
						'term_id' => $term_id,
					)
				);
			}

			return $term;
		}

		$term = get_term( $term['term_id'], $this->taxonomy );

		/**
		 * Fires after a single term is created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Term         $term     Inserted or updated term object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a term, false when updating.
		 */
		do_action( "rest_insert_{$this->taxonomy}", $term, $request, true );

		$schema = $this->get_item_schema();
		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $term->term_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$fields_update = $this->update_additional_fields_for_object( $term, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Fires after a single term is completely created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Term         $term     Inserted or updated term object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a term, false when updating.
		 */
		do_action( "rest_after_insert_{$this->taxonomy}", $term, $request, true );

		$response = $this->prepare_item_for_response( $term, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );

		$rest_base = ltrim( $this->rest_base, '/' );
		$namespace = rtrim( $this->namespace . '/' . $this->version, '/' );
		$response->header( 'Location', rest_url( $namespace  . '/' . $rest_base . '/' . str_replace( $term->taxonomy . '-', '', $term->slug ) ) );

		return $response;
	}

	/**
	 * Checks if a request has access to update the specified term.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update the item, false or WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$term = $this->get_term( $request['slug'] );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		if ( ! current_user_can( 'edit_term', $term->term_id ) ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'Sorry, you are not allowed to edit this term.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Updates a single term from a taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$term = $this->get_term( $request['slug'] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error(
					'rest_taxonomy_not_hierarchical',
					__( 'Cannot set parent term, taxonomy is not hierarchical.' ),
					array( 'status' => 400 )
				);
			}

			$parent = get_term( (int) $request['parent'], $this->taxonomy );

			if ( ! $parent ) {
				return new WP_Error(
					'rest_term_invalid',
					__( 'Parent term does not exist.' ),
					array( 'status' => 400 )
				);
			}
		}

		$prepared_term = $this->prepare_item_for_database( $request );

		// Only update the term if we have something to update.
		if ( ! empty( $prepared_term ) ) {
			$update = wp_update_term( $term->term_id, $term->taxonomy, wp_slash( (array) $prepared_term ) );

			if ( is_wp_error( $update ) ) {
				return $update;
			}
		}

		$term = get_term( $term->term_id, $this->taxonomy );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-terms-controller.php */
		do_action( "rest_insert_{$this->taxonomy}", $term, $request, false );

		$schema = $this->get_item_schema();
		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $term->term_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$fields_update = $this->update_additional_fields_for_object( $term, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-terms-controller.php */
		do_action( "rest_after_insert_{$this->taxonomy}", $term, $request, false );

		$response = $this->prepare_item_for_response( $term, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Checks if a request has access to delete the specified term.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to delete the item, otherwise false or WP_Error object.
	 */
	public function delete_item_permissions_check( $request ) {
		$term = $this->get_term( $request['slug'] );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		if ( ! current_user_can( 'delete_term', $term->term_id ) ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this term.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Deletes a single term from a taxonomy.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$term = $this->get_term( $request['slug'] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for terms.
		if ( ! $force ) {
			return new WP_Error(
				'rest_trash_not_supported',
				/* translators: %s: force=true */
				sprintf( __( "Terms do not support trashing. Set '%s' to delete." ), 'force=true' ),
				array( 'status' => 501 )
			);
		}

		$request->set_param( 'context', 'view' );

		$previous = $this->prepare_item_for_response( $term, $request );

		$retval = wp_delete_term( $term->term_id, $term->taxonomy );

		if ( ! $retval ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The term cannot be deleted.' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a single term is deleted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Term          $term     The deleted term.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "rest_delete_{$this->taxonomy}", $term, $response, $request );

		return $response;
	}

	/**
	 * Prepares a single term for create or update.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return object $prepared_term Term object.
	 */
	public function prepare_item_for_database( $request ) {
		$prepared_term = new stdClass;

		$schema = $this->get_item_schema();
		if ( isset( $request['name'] ) && ! empty( $schema['properties']['name'] ) ) {
			$prepared_term->name = $request['name'];
		}

		if ( isset( $request['slug'] ) && ! empty( $schema['properties']['slug'] ) ) {
			$prepared_term->slug = $request['slug'];
		}

		if ( isset( $request['taxonomy'] ) && ! empty( $schema['properties']['taxonomy'] ) ) {
			$prepared_term->taxonomy = $request['taxonomy'];
		}

		if ( isset( $request['description'] ) && ! empty( $schema['properties']['description'] ) ) {
			$prepared_term->description = $request['description'];
		}

		if ( isset( $request['parent'] ) && ! empty( $schema['properties']['parent'] ) ) {
			$parent_term_id   = 0;
			$requested_parent = (int) $request['parent'];

			if ( $requested_parent ) {
				$parent_term = get_term( $requested_parent, $this->taxonomy );

				if ( $parent_term instanceof WP_Term ) {
					$parent_term_id = $parent_term->term_id;
				}
			}

			$prepared_term->parent = $parent_term_id;
		}

		/**
		 * Filters term data before inserting term via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * @since 4.7.0
		 *
		 * @param object          $prepared_term Term object.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "rest_pre_insert_{$this->taxonomy}", $prepared_term, $request );
	}

	/**
	 * Prepares a single term output for response.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Term         $item    Term object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {

		$fields = $this->get_fields_for_response( $request );
		$data   = array();

		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = (int) $item->term_id;
		}

		if ( in_array( 'count', $fields, true ) ) {
			$data['count'] = (int) $item->count;
		}

		if ( in_array( 'description', $fields, true ) ) {
			$data['description'] = $item->description;
		}

		if ( in_array( 'link', $fields, true ) ) {
			$data['link'] = get_term_link( $item );
		}

		if ( in_array( 'name', $fields, true ) ) {
			$data['name'] = $item->name;
		}

		if ( in_array( 'slug', $fields, true ) ) {
			$data['slug'] = str_replace( $item->taxonomy . '-', '', $item->slug );
		}

		if ( in_array( 'taxonomy', $fields, true ) ) {
			$data['taxonomy'] = str_replace( 'sbx_route_', '', $item->taxonomy );
		}

		if ( in_array( 'parent', $fields, true ) ) {
			$data['parent'] = (int) $item->parent;
		}

		if ( in_array( 'meta', $fields, true ) ) {
			$data['meta'] = $this->meta->get_value( $item->term_id, $request );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filters a term item returned from the API.
		 *
		 * The dynamic portion of the hook name, `$this->taxonomy`, refers to the taxonomy slug.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param WP_Term           $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( "rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Term $term Term object.
	 * @return array Links for the given term.
	 */
	protected function prepare_links( $term ) {

		$rest_base = ltrim( $this->rest_base, '/' );
		$namespace = rtrim( $this->namespace . '/' . $this->version, '/' );
		$base  = $namespace . '/' . $rest_base;
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . str_replace( $term->taxonomy . '-', '', $term->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'about'      => array(
				'href' => rest_url( sprintf( '%s/taxonomies/%s', $namespace, $rest_base ) ),
			),
		);

		if ( $term->parent ) {
			$parent_term = get_term( (int) $term->parent, $term->taxonomy );

			if ( $parent_term ) {
				$links['up'] = array(
					'href'       => rest_url( trailingslashit( $base ) . str_replace( $parent_term->taxonomy . '-', '', $parent_term->slug ) ),
					'embeddable' => true,
				);
			}
		}

		$taxonomy_obj = get_taxonomy( $term->taxonomy );

		if ( empty( $taxonomy_obj->object_type ) ) {
			return $links;
		}

		$post_type_links = array();

		foreach ( $taxonomy_obj->object_type as $type ) {
			$post_type_object = get_post_type_object( $type );

			if ( empty( $post_type_object->show_in_rest ) ) {
				continue;
			}

			$rest_base         = ! empty( $post_type_object->rest_base ) ? $post_type_object->rest_base : $post_type_object->name;

			$post_type_links[] = array(
				// 'href' => add_query_arg( $this->rest_base, str_replace( $term->taxonomy . '-', '', $term->slug ), rest_url( sprintf( '%s/%s/%s', $this->namespace . '/v1', $rest_base ) ) ),
				'href' => rest_url( sprintf( '%s/%s/%s', $namespace, $rest_base,  str_replace( $term->taxonomy . '-', '', $term->slug ) ) ),
			);
		}

		if ( ! empty( $post_type_links ) ) {
			$links['https://api.w.org/post_type'] = $post_type_links;
		}

		return $links;
	}

	/**
	 * Retrieves the term's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'services',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the term.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed', 'edit' ),
					'readonly'    => true,
				),
				'count'       => array(
					'description' => __( 'Number of published posts for the term.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'HTML description of the term.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'link'        => array(
					'description' => __( 'URL of the term.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'embed', 'edit' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'HTML title for the term.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'required'    => true,
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the term unique to its type.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'taxonomy'    => array(
					'description' => __( 'Type attribution for the term.' ),
					'type'        => 'string',
					'enum'        => array_keys( get_taxonomies() ),
					'context'     => array( 'view', 'embed', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_taxonomy_name' ),
					),
					'readonly'    => true,
				),
			),
		);

		$taxonomy = get_taxonomy( $this->taxonomy );

		if ( $taxonomy->hierarchical ) {
			$schema['properties']['parent'] = array(
				'description' => __( 'The parent term ID.' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema['properties']['meta'] = $this->meta->get_field_schema();

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 4.7.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$taxonomy     = get_taxonomy( $this->taxonomy );

		$query_params['context']['default'] = 'view';

		$query_params['exclude'] = array(
			'description' => __( 'Ensure result set excludes specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		if ( ! $taxonomy->hierarchical ) {
			$query_params['offset'] = array(
				'description' => __( 'Offset the result set by a specific number of items.' ),
				'type'        => 'integer',
			);
		}

		$query_params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.' ),
			'type'        => 'string',
			'default'     => 'asc',
			'enum'        => array(
				'asc',
				'desc',
			),
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by term attribute.' ),
			'type'        => 'string',
			'default'     => 'name',
			'enum'        => array(
				'id',
				'include',
				'name',
				'slug',
				'include_slugs',
				'term_group',
				'description',
				'count',
			),
		);

		$query_params['hide_empty'] = array(
			'description' => __( 'Whether to hide terms not assigned to any posts.' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		if ( $taxonomy->hierarchical ) {
			$query_params['parent'] = array(
				'description' => __( 'Limit result set to terms assigned to a specific parent.' ),
				'type'        => 'integer',
			);
		}

		$query_params['post'] = array(
			'description' => __( 'Limit result set to terms assigned to a specific post.' ),
			'type'        => 'integer',
			'default'     => null,
		);

		$query_params['slug'] = array(
			'description' => __( 'Limit result set to terms with one or more specific slugs.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		/**
		 * Filter collection parameters for the terms controller.
		 *
		 * The dynamic part of the filter `$this->taxonomy` refers to the taxonomy
		 * slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Term_Query parameter.  Use the
		 * `rest_{$this->taxonomy}_query` filter to set WP_Term_Query parameters.
		 *
		 * @since 4.7.0
		 *
		 * @param array       $query_params JSON Schema-formatted collection parameters.
		 * @param WP_Taxonomy $taxonomy     Taxonomy object.
		 */
		return apply_filters( "rest_{$this->taxonomy}_collection_params", $query_params, $taxonomy );
	}

	/**
	 * Checks that the taxonomy is valid.
	 *
	 * @since 4.7.0
	 *
	 * @param string $taxonomy Taxonomy to check.
	 * @return bool Whether the taxonomy is allowed for REST management.
	 */
	protected function check_is_taxonomy_allowed( $taxonomy ) {
		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( $taxonomy_obj && ! empty( $taxonomy_obj->show_in_rest ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sanitizes the slug value.
	 *
	 * @since 4.7.0
	 *
	 * @internal We can't use sanitize_title() directly, as the second
	 * parameter is the fallback title, which would end up being set to the
	 * request object.
	 *
	 * @see https://github.com/WP-API/WP-API/issues/1585
	 *
	 * @todo Remove this in favour of https://core.trac.wordpress.org/ticket/34659
	 *
	 * @param string $slug Slug value passed in request.
	 * @return string Sanitized value for the slug.
	 */
	public function sanitize_slug( $slug ) {
		$slug = parent::sanitize_slug( $slug );
		return 0 === strpos( $slug, $this->taxonomy ) ? $slug : $this->taxonomy . '-' . $slug;
	}

	/**
	 * Sanitizes the taxonomy name value.
	 *
	 * @since 4.7.0
	 *
	 * @internal We can't use sanitize_title() directly, as the second
	 * parameter is the fallback title, which would end up being set to the
	 * request object.
	 *
	 * @see https://github.com/WP-API/WP-API/issues/1585
	 *
	 * @todo Remove this in favour of https://core.trac.wordpress.org/ticket/34659
	 *
	 * @param string $slug Slug value passed in request.
	 * @return string Sanitized value for the slug.
	 */
	public function sanitize_taxonomy_name( $taxonomy ) {
		return 0 === strpos( $taxonomy, 'sbx_route_' ) ? $taxonomy : $this->taxonomy;
	}
}

?><?php
/**
 * REST API: SBX_Route_Namespace_Controller class
 *
 * @package SealedBox
 * @subpackage REST_API
 * @since 1.0.0
 */

/**
 * Class used to managed Sealed Box REST Route Namespaces.
 *
 * @since 1.0.0
 *
 * @see WP_REST_Terms_Controller
 */
class SBX_Route_Namespace_Controller {

	/**
	 * The validated route object.
	 *
	 * @since 1.0.0
	 * @var SBX_Service_Route
	 */
	protected $route;

	/**
	 * The service type validated service type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $service_type;

	/**
	 * The service types of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $service_types;

	/**
	 * The namespaces of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $namespaces;

	/**
	 * The version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $version;

	/**
	 * The versions of this controller's routes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $versions;

	/**
	 * The service type validated service type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * The REST request object.
	 *
	 * @since 1.0.0
	 * @var WP_REST_Request
	 */
	public $request;

	/**
	 * The REST response object.
	 *
	 * @since 1.0.0
	 * @var WP_REST_Response
	 */
	public $response;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy key.
	 */
	public function __construct( $taxonomy, $namespace, $version = null ) {
		$this->taxonomy      = $taxonomy;
		$this->namespace     = $namespace;
		$this->version       = $version;
		$this->service_types = sbx_get_route_type_term_ids( 'slug', 'hide_empty=1' );
		$this->namespaces    = sbx_get_namespace_term_ids( 'slug', 'hide_empty=1' );
		$this->versions      = sbx_get_version_term_ids( 'slug', 'hide_empty=1' );
		$this->rest_base     = 'service';

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		// add_filter( 'rest_index', array( $this, 'rest_index' ), 10, 1 );
		add_filter( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 12, 3 );
		// add_filter( 'rest_namespace_index', array( $this, 'rest_namespace_index' ), 10, 2 );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		$rest_base = ltrim( $this->rest_base, '/' );
		$namespace = rtrim( $this->namespace . '/' . $this->version, '/' );
		// register_rest_route( $namespace, '/' . $rest_base, array(
		// 	array(
		// 		'methods'             => 'OPTIONS',
		// 		'show_in_index'       => true,
		// 	),
		// ) );
		register_rest_route( untrailingslashit( $namespace . '/' . $rest_base ), '/(?P<service>([\w-])+).(?P<route>([\w-])+)', array(
			array(
				'methods'             => 'GET, POST, OPTIONS',
				'callback'            => array( $this, 'perform_service' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'service' => array(
						'validate_callback' => array( $this, 'check_service_type' ),
						'type'              => 'string',
						'required'          => true,
					),
					'route' => array(
						'validate_callback' => array( $this, 'check_route' ),
						'type'              => 'string',
						'required'          => true,
					),
				),
				'show_in_index'       => true,
			),
		) );
	}

	/**
	* Check for a valid service type from the parameter
	*
	* @param string $service_type
	* @return bool
	*/
	public function check_service_type( $service_type, $request ) {
		if ( array_key_exists( $service_type, $this->service_types )/*  && sbx_query_the_route( $this->namespace, $service_type ) */ ) {
			$this->service_type = $service_type;
			return true;
		}
		return false;
	}

	/**
	* Check for a valid service type from the parameter
	*
	* @param string $route
	* @return bool
	*/
	public function check_route( $route, $request ) {
		if ( sbx_query_the_route( $this->namespace, null, $this->service_type, $route ) ) {
			return true;
		}
		return false;
	}

	/**
	* Check to see if the user has permission to do this
	*
	* @param WP_REST_Request $request
	* @throws \Exception
	*/
	public function permissions_check( WP_REST_Request $request ) {

		$route = $this->get_route( $request->get_param('route') );

		if ( is_wp_error( $route ) ) {
			return $route;
		}

		$restricted_route = $route->restricted_route;
		$request_method   = is_array( $route->request_method ) ? $route->request_method : explode( ',', $route->request_method );
		// $message_format   = $route->message_format;
		$message_param    = $route->message_param;
		$request_hosts    = $route->request_hosts;

		if ( 'yes' === $restricted_route ) {

			if ( ! in_array( $request->get_method(), $request_method ) ) {
				return new WP_Error( 'rest_forbidden', esc_html__( 'Incorrect request method.', 'sealedbox' ), array( 'status' => 401 ) );
			}

			$referring_host = parse_url( wp_get_referer(), PHP_URL_HOST );

			if ( ! empty( $referring_host ) && ! empty( $request_hosts ) && ! sbx_validate_host( $referring_host, $request_hosts ) ) {
				return new WP_Error( 'rest_forbidden', esc_html__( 'Referring host not permitted. ' .  wp_get_referer(), 'sealedbox' ), array( 'status' => 401 ) );
			}
		}

		switch ( $request->get_method() ) {

			case WP_REST_Server::READABLE:
				if ( ! array_key_exists( $message_param, $_GET ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'Message parameter missing.', 'sealedbox' ), array( 'status' => 401 ) );
				}
				break;
			case WP_REST_Server::CREATABLE:
				if ( ! array_key_exists( $message_param, $_POST ) ) {
					return new WP_Error( 'rest_forbidden', esc_html__( 'Message parameter missing.', 'sealedbox' ), array( 'status' => 401 ) );
				}
				break;
			case 'OPTIONS':
				return true;
				$response = array(
					'success'    => true,
					'statusCode' => 200,
					'code'       => 'jwt_auth_valid_token',
					'message'    => __( 'Token is valid', 'jwt-auth' ),
					'data'       => array(),
				);

				$response = apply_filters( 'sbx_valid_public_key_response', $response ); // , $user, $token, $payload );

				print_r($response);

				// Otherwise, return success response.
				return new WP_REST_Response( $response );
		}

		$cipher_text = $request->get_param( $message_param );

		if ( empty( $cipher_text ) || strlen( $cipher_text ) < 64 ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Message parameter is missing data.', 'sealedbox' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	* Process the REST API request
	*
	* @param WP_REST_Request $request
	* @return array $result
	*/
	public function process( WP_REST_Request $request ) {
		// see methods: https://developer.wordpress.org/reference/classes/wp_rest_request/
		//error_log( 'request is ' . print_r( $request, true ) );
		$http_method = $request->get_method();
		$route       = $request->get_route();
		$url_params  = $request->get_url_params();
		$body_params = $request->get_body_params();
		$class       = $request->get_url_params()['class'];
		$api_call    = str_replace( '/' . $this->namespace . $this->version . '/', '', $route );
		//error_log( 'api call is ' . $api_call . ' and params are ' . print_r( $params, true ) );
		$result = '';
		switch ( $class ) {
			case 'salesforce':
				break;
			case 'mappings':
				break;
			case 'pull':
				if ( 'GET' === $http_method ) {
					$result = $this->pull->salesforce_pull_webhook( $request );
				}
				if ( 'POST' === $http_method && isset( $body_params['salesforce_object_type'] ) && isset( $body_params['salesforce_id'] ) ) {
					$result = $this->pull->manual_pull( $body_params['salesforce_object_type'], $body_params['salesforce_id'] );
				}
				break;
			case 'push':
				if ( ( 'POST' === $http_method || 'PUT' === $http_method || 'DELETE' === $http_method ) && isset( $body_params['wordpress_object_type'] ) && isset( $body_params['wordpress_id'] ) ) {
					$result = $this->push->manual_push( $body_params['wordpress_object_type'], $body_params['wordpress_id'], $http_method );
				}
				break;
		}

		return $result;
	}

	/**
	 * Get the route, if the name is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route Route slug name.
	 * @return SBX_Service_Route|WP_Error Route object if name is valid, WP_Error otherwise.
	 */
	protected function get_route( $route ) {

		$error = new WP_Error(
			'rest_forbidden',
			__( 'Route does not exist.' ),
			array( 'status' => 404 )
		);

		if ( empty( $route ) ) {
			return $error;
		}

		if ( empty( $this->route ) ) {

			$the_route = sbx_query_the_route( $this->namespace, null, $this->service_type, $route );

			if ( ! $the_route ) {
				return $error;
			}

			$this->route = $the_route;
		}

		return $this->route;

	}

	/**
	 * Gets a single term from a taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function perform_service( $request ) {

		$route = $this->get_route( $request->get_param('route') );

		if ( is_wp_error( $route ) ) {
			return $route;
		}

		$cipher_text    = $request->get_param( $route->message_param );
		$payload        = $route->decrypt_payload( $cipher_text );

        if ( ! empty( $route->message_format ) && isset( $payload ) ) {

            $type = gettype( $payload );
			$match = false;

            switch ( $route->message_format ) {

                case 'array':
                    $match = 'array' === $type;
					break;

                case 'boolean':
                    $match = 'boolean' === $type || in_array( $payload, array( '1', '0', 'true', 'false' ) );
					break;

                case 'integer':
					$match = 'integer' === $type;
					break;

                case 'number':
                    $match = 'number' === $type || in_array( $type, array( 'float', 'double', 'integer', INF )  );
					break;

                case 'object':
                    $match = 'object' === $type;
					break;

                case 'string':
                    $match = 'string' === $type;
					break;

                case 'csv':
                    $match = 'string' === $type && ! empty( explode( ',', $payload ) );
					break;

                case 'json':
                    $match = 'string' === $type && json_decode( $payload );
					break;

                case 'url':
                    $match = 'string' === $type && sbx_is_valid_url( $payload );
					break;

                case 'xml':
                    $match = 'string' === $type && simplexml_load_string( $payload );
					break;

                default:
                case 'raw':
                    $match = true;
                    break;
			}

			if ( ! $match ) {
				return new WP_Error( 'rest_forbidden', esc_html__( 'The decryptic value is not typed correctly.', 'sealedbox' ), array( 'status' => 401 ) );
			}
        }

		$this->request  = $request;
		$this->response = rest_ensure_response( $payload );

		do_action_ref_array( 'perform_sealed_box_' . $this->service_type . '_service', array( $payload, $cipher_text, $route->ID, &$this ) );

		return $this->response;

	}

	/**
	 * Handles OPTIONS requests for the server.
	 *
	 * This is handled outside of the server code, as it doesn't obey normal route
	 * mapping.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed           $response Current response, either response or `null` to indicate pass-through.
	 * @param WP_REST_Server  $handler  ResponseHandler instance (usually WP_REST_Server).
	 * @param WP_REST_Request $request  The request that was used to make current response.
	 * @return WP_REST_Response Modified response, either response or `null` to indicate pass-through.
	 */
	public function rest_pre_dispatch( $response, $handler, $request ) {
		if ( 'OPTIONS' !== $request->get_method() ) {
			return $response;
		}

		$data = array();
		$namespace = implode( '/', array( '', $this->namespace, $this->version, $this->rest_base ) );

		if ( false !== strstr( $request->get_route(), $namespace ) ) {

			$this->service_type = $request->get_param('service');
			$route_object          = $this->get_route( $request->get_param('route') );

			if ( is_wp_error( $route_object ) ) {
				return rest_ensure_response( $route_object );
			}

			$method = $route_object->request_method;
			$token  = strtotime( $route_object->modified );
			$hook   = sbx_prefix( 'client_procedural_encrypted_value_' . $token );
			$type   = 'string';
			$args   = array();
			$filter = array();

			switch ( $route_object->message_format ) {
                case 'array':
                case 'boolean':
                case 'integer':
                case 'number':
                case 'object':
                    $type = $route_object->message_format;
					break;
                default:
                    $type = 'string';
                    break;
			}

			$args[ $route_object->message_param ] = array(
				'type'     => $type,
				'format'   => $route_object->message_format,
				'required' => true ,
			);

			foreach ( $route_object->argument_schema as $argument ) {
				if ( isset( $argument['name'] ) && ! empty( $argument['name'] ) ) {
					$args[ $argument['name'] ] = array_intersect_key( $argument, array_flip( array( 'type' ) ) );
					$required = false;
					if ( array_key_exists( 'required', $argument ) ) {
						$required = sbx_string_to_bool( $argument['required'] );
					}
					$args[ $argument['name'] ]['required'] = $required;
				}
			}

			if ( in_array( $route_object->message_format, array( 'array', 'object' ) ) ) {
				$filter[] = $hook . '[input:sodium_crypto_box_seal.0],serialize,10,1';
			}
			if ( 'json' === $route_object->message_format ) {
				$filter[] = $hook . '[input:sodium_crypto_box_seal.0],json_encode,10,1';
			}

			$filter[] = $hook . '[input:sodium_crypto_box_seal.1],rawurldecode,10,1';
			$filter[] = $hook . '[input:sodium_crypto_box_seal.1],base64_decode,11,1';
			$filter[] = $hook . '[output:' . $route_object->message_param . '],sodium_crypto_box_seal,-1,2';
			$filter[] = $hook . '[output:' . $route_object->message_param . '],base64_encode,10,1';
			$filter[] = $hook . '[output:' . $route_object->message_param . '],rawurlencode,11,1';

			$data = array(
				'route'     => $route_object->get_rest_api_service_route(),
				'publickey' => $route_object->get_public_key(),
				'for'       => 'encryption',
				'type'      => SEALED_BOX_CRYPT['type'],
				'format'    => 'base64',
				'issued'    => $token,
				'expires'   => strtotime( $route_object->modified ) + MONTH_IN_SECONDS,
				'method'    => array_shift( $method ),
				'args'      => $args,
				'data'  => array(
					'for'    => 'procedural-value',
					'input'  => 'sodium_crypto_box_seal.0,sodium_crypto_box_seal.1',
					'output' => $route_object->message_param,
					'value'  => array(
						'input:sodium_crypto_box_seal.0' => 'message',
						'input:sodium_crypto_box_seal.1' => 'publickey',
						'output:' . $route_object->message_param => 'sodium_crypto_box_seal.0,sodium_crypto_box_seal.1',
					),
					'filter' => $filter
				),
			);

			$response->set_data( $data );
		}

		return $response;
    }

	/**
	 * Filter REST namespace index.
	 *
	 * @param WP_REST_Response $response Description
	 * @param WP_REST_Request  $request  Description
	 * @return WP_REST_Response|WP_Error
	 **/
	public function rest_namespace_index( $response, $request ) {
		$namespace = $this->namespace . '/' . $this->version;
		$rest_base = $namespace . '/' . $this->rest_base;

		if ( $response->data['namespace'] === $namespace || $response->data['namespace'] === $rest_base ) {
			return new WP_Error(
				'rest_no_route',
				__( 'No route was found matching the URL and request method' ),
				array( 'status' => 404 )
			);
		}

		if ( $response->data['namespace'] === $rest_base ) {
			unset( $response->data['routes'][ '/' . $rest_base ] );
			$route  = key( $response->data['routes'] );
			$response->data['routes'][ $route ]['methods'] =
			$response->data['routes'][ $route ]['endpoints'][0]['methods'] = array( 'OPTIONS' );
		}

		return $response;
	}

	/**
	 * Filter REST index.
	 *
	 * @param WP_REST_Response $response Description
	 * @return WP_REST_Response
	 **/
	public function rest_index( $response ) {
		$namespace = $this->namespace . '/' . $this->version;
		$index = array_search( $namespace, $response->data['namespaces'] );
		if ( isset( $index ) ) {
			unset( $response->data['namespaces'][ $index ], $response->data['namespaces'][ ++$index ] );
		}
		$namespace = '/' . $namespace;
		$rest_base = $namespace . '/' . $this->rest_base;
		if ( isset( $response->data['routes'][ $namespace ] ) ) {
			unset( $response->data['routes'][ $namespace ], $response->data['routes'][ $rest_base ] );
		}
		$rest_base .= '/';
		foreach ( array_keys( $response->data['routes'] ) as $route ) {
            if ( 0 === strpos( $route, $rest_base ) ) {
                $response->data['routes'][ $route ]['methods'] = array( 'OPTIONS' );
                $response->data['routes'][ $route ]['endpoints'][0]['methods'] = array( 'OPTIONS' );
            }
		}
		return $response;
    }
}


// goto myrest;

return;

?><?php



// Register our routes.
function prefix_register_my_comment_route() {
    register_rest_route( 'my-namespace/v1', '/comments', array(
        // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
        array(
            'methods'  => 'GET',
            'callback' => 'prefix_get_comment_sample',
        ),
        // Register our schema callback.
        'schema' => 'prefix_get_comment_schema',
    ) );
}

add_action( 'rest_api_init', 'prefix_register_my_comment_route' );

/**
 * Grabs the five most recent comments and outputs them as a rest response.
 *
 * @param WP_REST_Request $request Current request.
 */
function prefix_get_comment_sample( $request ) {
    $args = array(
        'number' => 5,
    );
    $comments = get_comments( $args );

    $data = array();

    if ( empty( $comments ) ) {
        return rest_ensure_response( $data );
    }

    foreach ( $comments as $comment ) {
        $response = prefix_rest_prepare_comment( $comment, $request );
        $data[] = prefix_prepare_for_collection( $response );
    }

    // Return all of our comment response data.
    return rest_ensure_response( $data );
}

/**
 * Matches the comment data to the schema we want.
 *
 * @param WP_Comment $comment The comment object whose response is being prepared.
 */
function prefix_rest_prepare_comment( $comment, $request ) {
    $comment_data = array();

    $schema = prefix_get_comment_schema();

    // We are also renaming the fields to more understandable names.
    if ( isset( $schema['properties']['id'] ) ) {
        $comment_data['id'] = (int) $comment->comment_ID;
    }

    if ( isset( $schema['properties']['author'] ) ) {
        $comment_data['author'] = (int) $comment->user_id;
    }

    if ( isset( $schema['properties']['content'] ) ) {
        $comment_data['content'] = apply_filters( 'comment_text', $comment->comment_content, $comment );
    }

    return rest_ensure_response( $comment_data );
}

/**
 * Prepare a response for inserting into a collection of responses.
 *
 * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
 *
 * @param WP_REST_Response $response Response object.
 * @return array Response data, ready for insertion into collection data.
 */
function prefix_prepare_for_collection( $response ) {
    if ( ! ( $response instanceof WP_REST_Response ) ) {
        return $response;
    }

    $data = (array) $response->get_data();
    $server = rest_get_server();

    if ( method_exists( $server, 'get_compact_response_links' ) ) {
        $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
    } else {
        $links = call_user_func( array( $server, 'get_response_links' ), $response );
    }

    if ( ! empty( $links ) ) {
        $data['_links'] = $links;
    }

    return $data;
}

/**
 * Get our sample schema for comments.
 */
function prefix_get_comment_schema() {
    $schema = array(
        // This tells the spec of JSON Schema we are using which is draft 4.
        '$schema'              => 'http://json-schema.org/draft-04/schema#',
        // The title property marks the identity of the resource.
        'title'                => 'comment',
        'type'                 => 'object',
        // In JSON Schema you can specify object properties in the properties attribute.
        'properties'           => array(
            'id' => array(
                'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                'type'         => 'integer',
                'context'      => array( 'view', 'edit', 'embed' ),
                'readonly'     => true,
            ),
            'author' => array(
                'description'  => esc_html__( 'The id of the user object, if author was a user.', 'my-textdomain' ),
                'type'         => 'integer',
            ),
            'content' => array(
                'description'  => esc_html__( 'The content for the object.', 'my-textdomain' ),
                'type'         => 'string',
            ),
        ),
    );

    return $schema;
}







// Register our routes.
function prefix_register_my_arg_route() {
    register_rest_route( 'my-namespace/v1', '/schema-arg', array(
        // Here we register our endpoint.
        array(
            'methods'  => 'GET',
            'callback' => 'prefix_get_item',
            'args' => prefix_get_endpoint_args(),
        ),
    ) );
}

// Hook registration into 'rest_api_init' hook.
// add_action( 'rest_api_init', 'prefix_register_my_arg_route' );

/**
 * Returns the request argument `my-arg` as a rest response.
 *
 * @param WP_REST_Request $request Current request.
 */
function prefix_get_item( $request ) {
    // If we didn't use required in the schema this would throw an error when my arg is not set.
    return rest_ensure_response( $request['my-arg'] );
}

/**
 * Get the argument schema for this example endpoint.
 */
function prefix_get_endpoint_args() {
    $args = array();

    // Here we add our PHP representation of JSON Schema.
    $args['my-arg'] = array(
        'description'       => esc_html__( 'This is the argument our endpoint returns.', 'my-textdomain' ),
        'type'              => 'string',
        'validate_callback' => 'prefix_validate_my_arg',
        'sanitize_callback' => 'prefix_sanitize_my_arg',
        'required'          => true,
    );

    return $args;
}

/**
 * Our validation callback for `my-arg` parameter.
 *
 * @param mixed           $value   Value of the my-arg parameter.
 * @param WP_REST_Request $request Current request object.
 * @param string          $param   The name of the parameter in this case, 'my-arg'.
 */
function prefix_validate_my_arg( $value, $request, $param ) {
    $attributes = $request->get_attributes();

    if ( isset( $attributes['args'][ $param ] ) ) {
        $argument = $attributes['args'][ $param ];
        // Check to make sure our argument is a string.
        if ( 'string' === $argument['type'] && ! is_string( $value ) ) {
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'my-textdomain' ), $param, 'string' ), array( 'status' => 400 ) );
        }
    } else {
        // This code won't execute because we have specified this argument as required.
        // If we reused this validation callback and did not have required args then this would fire.
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
    }

    // If we got this far then the data is valid.
    return true;
}

/**
 * Our santization callback for `my-arg` parameter.
 *
 * @param mixed           $value   Value of the my-arg parameter.
 * @param WP_REST_Request $request Current request object.
 * @param string          $param   The name of the parameter in this case, 'my-arg'.
 */
function prefix_sanitize_my_arg( $value, $request, $param ) {
    $attributes = $request->get_attributes();

    if ( isset( $attributes['args'][ $param ] ) ) {
        $argument = $attributes['args'][ $param ];
        // Check to make sure our argument is a string.
        if ( 'string' === $argument['type'] ) {
            return sanitize_text_field( $value );
        }
    } else {
        // This code won't execute because we have specified this argument as required.
        // If we reused this validation callback and did not have required args then this would fire.
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
    }

    // If we got this far then something went wrong don't use user input.
    return new WP_Error( 'rest_api_sad', esc_html__( 'Something went terribly wrong.', 'my-textdomain' ), array( 'status' => 500 ) );
}






$data = array( 'some', 'response', 'data' );

// Create the response object
$response = new WP_REST_Response( $data );

// Add a custom status code
$response->set_status( 201 );

// Add a custom header
$response->header( 'Location', 'http://example.com/' );



register_rest_route( untrailingslashit( $namespace, '/' . $rest_base ), array(
	array(
		'methods'             => 'OPTIONS',
		'show_in_index'       => false,
	),
) );
register_rest_route( untrailingslashit( $namespace . '/' . $rest_base ), '(?P<service>([\w-])+).(?P<route>([\w-])+)', array(
	array(
		'methods'             => 'GET, POST, OPTIONS',
		'callback'            => array( $this, 'perform_service' ),
		'permission_callback' => array( $this, 'permissions_check' ),
		'args'                => array(
			'service' => array(
				'validate_callback' => array( $this, 'check_service_type' ),
				'type'              => 'string',
				'required'          => true,
			),
			'route' => array(
				'validate_callback' => array( $this, 'check_route' ),
				'type'              => 'string',
				'required'          => true,
			),
		),
		'show_in_index'       => true,
	),
) );


class Slug_Custom_Route extends WP_REST_Controller {

  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {
    $version = '1';
    $namespace = 'vendor/v' . $version;
    $base = 'route';
    register_rest_route( untrailingslashit( $namespace, '/' . $base ), array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' ),
        'args'                => array(

        ),
      ),
      array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'create_item' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
        'args'                => $this->get_endpoint_args_for_item_schema( true ),
      ),
    ) );
    register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_item' ),
        'permission_callback' => array( $this, 'get_item_permissions_check' ),
        'args'                => array(
          'context' => array(
            'default' => 'view',
          ),
        ),
      ),
      array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => array( $this, 'update_item' ),
        'permission_callback' => array( $this, 'update_item_permissions_check' ),
        'args'                => $this->get_endpoint_args_for_item_schema( false ),
      ),
      array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => array( $this, 'delete_item' ),
        'permission_callback' => array( $this, 'delete_item_permissions_check' ),
        'args'                => array(
          'force' => array(
            'default' => false,
          ),
        ),
      ),
    ) );
    register_rest_route( $namespace, '/' . $base . '/schema', array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => array( $this, 'get_public_item_schema' ),
    ) );
  }

  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_items( $request ) {
    $items = array(); //do a query, call another class, etc
    $data = array();
    foreach( $items as $item ) {
      $itemdata = $this->prepare_item_for_response( $item, $request );
      $data[] = $this->prepare_response_for_collection( $itemdata );
    }

    return new WP_REST_Response( $data, 200 );
  }

  /**
   * Get one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_item( $request ) {
    //get parameters from request
    $params = $request->get_params();
    $item = array();//do a query, call another class, etc
    $data = $this->prepare_item_for_response( $item, $request );

    //return a response or error based on some conditional
    if ( 1 == 1 ) {
      return new WP_REST_Response( $data, 200 );
    } else {
      return new WP_Error( 'code', __( 'message', 'text-domain' ) );
    }
  }

  /**
   * Create one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function create_item( $request ) {
    $item = $this->prepare_item_for_database( $request );

    if ( function_exists( 'slug_some_function_to_create_item' ) ) {
      $data = slug_some_function_to_create_item( $item );
      if ( is_array( $data ) ) {
        return new WP_REST_Response( $data, 200 );
      }
    }

    return new WP_Error( 'cant-create', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }

  /**
   * Update one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function update_item( $request ) {
    $item = $this->prepare_item_for_database( $request );

    if ( function_exists( 'slug_some_function_to_update_item' ) ) {
      $data = slug_some_function_to_update_item( $item );
      if ( is_array( $data ) ) {
        return new WP_REST_Response( $data, 200 );
      }
    }

    return new WP_Error( 'cant-update', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }

  /**
   * Delete one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function delete_item( $request ) {
    $item = $this->prepare_item_for_database( $request );

    if ( function_exists( 'slug_some_function_to_delete_item' ) ) {
      $deleted = slug_some_function_to_delete_item( $item );
      if ( $deleted ) {
        return new WP_REST_Response( true, 200 );
      }
    }

    return new WP_Error( 'cant-delete', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
  }

  /**
   * Check if a given request has access to get items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_items_permissions_check( $request ) {
    //return true; <--use to make readable by all
    return current_user_can( 'edit_something' );
  }

  /**
   * Check if a given request has access to get a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_item_permissions_check( $request ) {
    return $this->get_items_permissions_check( $request );
  }

  /**
   * Check if a given request has access to create items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function create_item_permissions_check( $request ) {
    return current_user_can( 'edit_something' );
  }

  /**
   * Check if a given request has access to update a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function update_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }

  /**
   * Check if a given request has access to delete a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function delete_item_permissions_check( $request ) {
    return $this->create_item_permissions_check( $request );
  }

  /**
   * Prepare the item for create or update operation
   *
   * @param WP_REST_Request $request Request object
   * @return WP_Error|object $prepared_item
   */
  protected function prepare_item_for_database( $request ) {
    return array();
  }

  /**
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response( $item, $request ) {
    return array();
  }

  /**
   * Get the query params for collections
   *
   * @return array
   */
  public function get_collection_params() {
    return array(
      'page'     => array(
        'description'       => 'Current page of the collection.',
        'type'              => 'integer',
        'default'           => 1,
        'sanitize_callback' => 'absint',
      ),
      'per_page' => array(
        'description'       => 'Maximum number of items to be returned in result set.',
        'type'              => 'integer',
        'default'           => 10,
        'sanitize_callback' => 'absint',
      ),
      'search'   => array(
        'description'       => 'Limit results to those matching a string.',
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
      ),
    );
  }
}

myrest:

class My_REST_Posts_Controller {

    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace     = '/my-namespace/v1';
        $this->resource_name = 'posts';
    }

    // Register our routes.
    public function register_routes() {
        register_rest_route( untrailingslashit( $this->namespace, '/' . $this->resource_name ), array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<services>([\w-])+).(?P<slug>([\w-])+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check( $request ) {
        // if ( ! current_user_can( 'read' ) ) {
        //     return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
        // }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items( $request ) {
        $posts = get_posts( array(
			'post_name'           => $request['slug'],
			'sbx_route_type'   => 'sbx_route_type-' . $request['services'],
			'sbx_route_namespace' => 'sbx_route_namespace-' . $this->namespace,
			'post_per_page'       => 5,
			'post_type'           => 'sbx_service_route'
		) );

        $data = array();

        if ( empty( $posts ) ) {
            return rest_ensure_response( $data );
        }

        foreach ( $posts as $post ) {
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }

        // Return all of our comment response data.
        return rest_ensure_response( $data );
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_permissions_check( $request ) {
        // if ( ! current_user_can( 'read' ) ) {
        //     return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
        // }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item( $request ) {
        $post = sbx_flatten_meta_callback( get_posts( array(
			'post_name'           => $request['slug'],
			'sbx_route_type'      => 'sbx_route_type-' . $request['services'],
			'sbx_route_namespace' => 'sbx_route_namespace-' . $this->namespace,
			'post_per_page'       => 5,
			'post_type'           => 'sbx_service_route'
		) ) );

        if ( empty( $post ) ) {
            return rest_ensure_response( array() );
        }

        $response = $this->prepare_item_for_response( $post, $request );

        // Return all of our post response data.
        return $response;
    }

    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response( $post, $request ) {
        $post_data = array();

        $schema = $this->get_item_schema( $request );

        // We are also renaming the fields to more understandable names.
        if ( isset( $schema['properties']['id'] ) ) {
            $post_data['id'] = (int) $post->ID;
        }

        if ( isset( $schema['properties']['content'] ) ) {
            $post_data['content'] = apply_filters( 'the_content', $post->post_content, $post );
        }

        return rest_ensure_response( $post_data );
    }

    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection( $response ) {
        if ( ! ( $response instanceof WP_REST_Response ) ) {
            return $response;
        }

        $data = (array) $response->get_data();
        $server = rest_get_server();

        if ( method_exists( $server, 'get_compact_response_links' ) ) {
            $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
        } else {
            $links = call_user_func( array( $server, 'get_response_links' ), $response );
        }

        if ( ! empty( $links ) ) {
            $data['_links'] = $links;
        }

        return $data;
    }

    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema( $request = null ) {
        if ( $this->schema ) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }

        $this->schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'post',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true,
                ),
                'content' => array(
                    'description'  => esc_html__( 'The content for the object.', 'my-textdomain' ),
                    'type'         => 'string',
                ),
            ),
        );

        return $this->schema;
    }

    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {

        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    }
}

// Function to register our new routes from the controller.
function prefix_register_my_rest_routes() {
    $controller = new My_REST_Posts_Controller();
    $controller->register_routes();
}

// add_action( 'rest_api_init', 'prefix_register_my_rest_routes' );